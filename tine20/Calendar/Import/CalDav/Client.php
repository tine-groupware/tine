<?php
//./tine20.php --username unittest --method Calendar.importCalDav url="https://osx-testfarm-mavericks-server.hh.metaways.de:8443" caldavuserfile=caldavuserfile.csv

/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Import_CalDAV
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_CalDav_Client extends \Sabre\DAV\Client
{
    /**
     * used to overwrite default retry behavior (if != null)
     *
     * @var integer
     */
    protected $_requestTries = null;

    protected $currentUserPrincipal = null;
    protected $calendarHomeSet = null;
    protected $principals = [];
    protected $principalGroups = array();

    protected $requestLogFH;

    protected $calendars = array();
    protected $calendarICSs = array();
    protected $existingRecordIds = array();
    protected $maxBulkRequest = 20;
    protected $mapToDefaultContainer = 'calendar';
    /** @var Calendar_Import_CalDav_Decorator_Abstract|null  */
    protected $decorator = null;
    
    protected $component = 'VEVENT';
    protected $skipComonent = 'VTODO';

    protected $webdavFrontend = Calendar_Frontend_WebDAV_EventImport::class;
    protected $_uuidPrefix = '';

    protected $_allowDuplicateEvents = false;

    protected $userName;
    
    /**
     * record backend
     * 
     * @var Tinebase_Backend_Sql_Abstract
     */
    protected $_recordBackend = null;
    
    public function __construct(array $settings, $flavor)
    {
        parent::__construct($settings);

        $this->userName = $settings['userName'] ?? null;
        
        if (isset($settings['allowDuplicateEvents'])) {
            $this->_allowDuplicateEvents = $settings['allowDuplicateEvents'];
        }

        $flavor = 'Calendar_Import_CalDav_Decorator_' . $flavor;
        $this->decorator = new $flavor($this);
        $this->_recordBackend = Calendar_Controller_Event::getInstance()->getBackend();
    }

    public function setVerifyPeer(bool $verify): void
    {
        $this->addCurlSetting(CURLOPT_PROXY_SSL_VERIFYPEER, $verify);
        $this->addCurlSetting(CURLOPT_PROXY_SSL_VERIFYHOST, $verify ? 2 : 0);
    }

    /**
     * perform calDavRequest
     *
     * @throws Tinebase_Exception
     */
    public function calDavRequest(string $method, string $uri, string $body, int $depth = 0, int $tries = 10, int $sleep = 30): array
    {
        $response = null;
        if ($this->_requestTries !== null) {
            // overwrite default retry behavior
            $tries = $this->_requestTries;
        }
        while ($tries > 0)
        {
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Sending ' . $method . ' request for uri ' . $uri . ' ...');
                $response = $this->request($method, $uri, $body, array(
                    'Depth' => $depth,
                    'Content-Type' => 'text/xml',
                ));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Caldav request failed: '
                        . '(' . $this->userName . ')' . $method . ' ' . $uri . "\n" . $body
                        . "\n" . $e->getMessage());
                if (--$tries > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Sleeping ' . $sleep . ' seconds and retrying ... ');
                    sleep($sleep);
                }
                continue;
            }
            break;
        }

        if (! $response) {
            throw new Tinebase_Exception("no response");
        }

        $result = $this->parseMultiStatus($response['body']);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Uri: ' . $uri . ' | request: ' . $body . ' | response: ' . print_r($response, true));

        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            $result = isset($result[200]) && is_array($result[200]) ? $result[200] : [];

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Result (depth 0): ' . var_export($result, true));

            return $result;
        }

        $newResult = array();
        foreach($result as $href => $statusList)
        {
            $newResult[$href] = isset($statusList[200])?$statusList[200]:array();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Result: ' . var_export($newResult, true));

        return $newResult;
    }

    /**
     * Parses a WebDAV multistatus response body
     *
     * @param string $body xml body
     * @return array
     */
    public function parseMultiStatus($body)
    {
        $oldSetting = libxml_use_internal_errors(true);

        try {
            $result = parent::parseMultiStatus($body);

            if (count($xmlErrors = libxml_get_errors()) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' XML errors occured: ' . print_r($xmlErrors, true));
            }
            libxml_clear_errors();
            libxml_use_internal_errors($oldSetting);

        } catch(InvalidArgumentException $e) {
            libxml_clear_errors();
            libxml_use_internal_errors($oldSetting);

            // remove possible broken chars here to avoid simplexml_load_string errors
            // this line may throw an Exception again! thats why the libxml_* functions are called in try and catch!
            $result = parent::parseMultiStatus(Tinebase_Helper::removeIllegalXMLChars($body));
        }

        return $result;
    }

    /**
     * findUserPrincipal
     * - result ($this->currentUserPrincipal) is cached for 1 week
     *
     * @param int $tries
     * @return boolean
     */
    public function findCurrentUserPrincipal(int $tries = 1): bool
    {
        $cacheId = Tinebase_Helper::convertCacheId(__METHOD__ . $this->userName);
        if (Tinebase_Core::getCache()->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Loading user principal from cache');

            if (($this->currentUserPrincipal = Tinebase_Core::getCache()->load($cacheId) ?: null)) {
                return true;
            }
        }

        $result = $this->calDavRequest('PROPFIND', '/principals/', self::findCurrentUserPrincipalRequest, 0, $tries);
        if (isset($result['{DAV:}current-user-principal']))
        {
            $this->currentUserPrincipal = $result['{DAV:}current-user-principal'];

            Tinebase_Core::getCache()->save($this->currentUserPrincipal, $cacheId, array(), /* 1 week */ 24*3600*7);
            return true;
        }

        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find current users principal');
        return false;
    }

    /**
     * findCalendarHomeSet
     * - result ($this->calendarHomeSet) is cached for 1 week
     *
     * @return boolean
     */
    public function findCalendarHomeSet(): bool
    {
        if (null === $this->currentUserPrincipal && ! $this->findCurrentUserPrincipal(/* tries = */ 3)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' No principal found for user ' . $this->userName);
            return false;
        }
        $cacheId = Tinebase_Helper::convertCacheId(__METHOD__ . $this->userName);
        if (Tinebase_Core::getCache()->test($cacheId) && ($this->calendarHomeSet = Tinebase_Core::getCache()->load($cacheId) ?: null)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Loading user home set from cache');
            return true;
        }

        $result = $this->calDavRequest('PROPFIND', $this->currentUserPrincipal, self::findCalendarHomeSetRequest);

        if (isset($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'])) {
            $this->calendarHomeSet = rtrim($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'], '/') . '/';
            Tinebase_Core::getCache()->save($this->calendarHomeSet, $cacheId, array(), /* 1 week */ 24*3600*7);
            return true;
        }

        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find calendar homeset');
        return false;
    }
    
    public function findAllCalendarICSs(string $uri): bool
    {
        $result = $this->calDavRequest('PROPFIND', $uri, self::findAllCalendarICSsRequest, 1);

        $this->calendarICSs[$uri] = [];
        foreach ($result as $ics => $value) {
            if (strpos($ics, '.ics') !== FALSE)
                $this->calendarICSs[$uri][] = $ics;
        }
        
        return true;
    }

    public function syncCalendarEvents(string $uri, Tinebase_Model_Container $targetContainer, array $todo = [
            Tinebase_Controller_Record_Abstract::ACTION_CREATE => true,
            Tinebase_Controller_Record_Abstract::ACTION_UPDATE => true,
            Tinebase_Controller_Record_Abstract::ACTION_DELETE => true,
        ]): bool
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' Importing calendar ' . $uri . ' with user ' . $this->userName);

        if (!isset($this->calendarICSs[$uri]) && !$this->findAllCalendarICSs($uri)) {
            return false;
        }

        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';

        // sets $_SERVER['HTTP_USER_AGENT'] for ics import / conversion handling
        $this->decorator->initCalendarImport();


        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Looking for updates in ' . $uri . ' calendar ...');
        $updateResult = $this->calculateCalendarUpdates($uri, $this->calendarICSs[$uri], $targetContainer);

        if (! empty($updateResult['todelete']) && isset($todo[Tinebase_Controller_Record_Abstract::ACTION_DELETE]) &&
                $todo[Tinebase_Controller_Record_Abstract::ACTION_DELETE]) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' '
                . ' Deleting ' . count($updateResult['todelete']) . ' in calendar '  . $uri);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' IDs to delete: ' . print_r($updateResult['todelete'], true));
            Calendar_Controller_Event::getInstance()->delete($updateResult['todelete']);
        }

        if (count($updateResult['ics']) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . 'Events changed: ' . print_r($updateResult['ics'], true));

            $this->_importIcs($uri, $updateResult['ics'], $targetContainer, $todo);
        }

        return true;
    }

    protected function calculateCalendarUpdates(string $calUri, array $calICSs, Tinebase_Model_Container $container): array
    {
        $updateResult = [
            'ics'       => [],
            'toupdate'  => 0,
            'toadd'     => 0,
            'todelete'  => [], // of record ids
        ];
        
        $serverEtags = $this->_fetchServerEtags($calUri, $calICSs);
        $containerEtags = $this->_recordBackend->getEtagsForContainerId($container->getId());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Got ' . count($serverEtags) . ' server etags for container ' . $container->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' server etags: ' . print_r($serverEtags, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' tine20 etags: ' . print_r($containerEtags, true));
        
        // handle add/updates
        $existingIds = [];
        $this->existingRecordIds[$calUri] = [];

        foreach ($serverEtags as $ics => $data) {

            if (isset($containerEtags[$data['id']])) {
                $tine20Etag = $containerEtags[$data['id']]['etag'];

                $existingIds[$data['id']] = $containerEtags[$data['id']]['id'];
                // remove from $containerEtags list to be able to tell deletes
                unset($containerEtags[$data['id']]);

                if ($tine20Etag === $data['etag']) {
                    continue; // same
                } else if (empty($tine20Etag)) {
                    // event has been added in tine -> don't overwrite/delete
                    continue;
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record needs update: ' . $data['id']);
                
            } else {
                try {
                    $this->_recordBackend->checkETag($data['id'], $data['etag'], $container->getId());
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                            . ' Ignoring event from another container/organizer: ' . $data['id']);
                    continue;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                            . ' Found new record: ' . $data['id']);
                }
            }

            $updateResult['ics'][] = $ics;
            if (isset($existingIds[$data['id']])) {
                $updateResult['toupdate']++;
            } else {
                $updateResult['toadd']++;
            }
        }

        $this->existingRecordIds[$calUri] = $existingIds;
        
        // handle deletes/exdates
        foreach ($containerEtags as $id => $data) {
            if (isset($existingIds[$data['external_uid']])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record ' . $id . ' is exdate of ' . $data['external_uid']);
                continue;
            }
            if (! empty($data['etag'])) {
                // record has been deleted on server
                $updateResult['todelete'][] = $data['id'];
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record has been added in tine: ' . $id);
            }
        }
        
        return $updateResult;
    }
    
    protected function _fetchServerEtags(string $calUri, array $calICSs): array
    {
        $start = 0;
        $max = count($calICSs);
        
        $etags = [];
        do {
            $requestEnd = '';
            for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
            }
            $start = $i;
            $requestEnd .= '</b:calendar-multiget>';
            $result = $this->calDavRequest('REPORT', $calUri, self::getEventETagsRequest . $requestEnd, 1);
        
            foreach ($result as $key => $value) {
                if (isset($value['{DAV:}getetag'])) {
                    $name = explode('/', $key);
                    $name = end($name);
                    $id = $this->_getEventIdFromName($name);
                    $etags[$key] = array( 'id' => $id, 'etag' => $value['{DAV:}getetag']);
                }
            }
        } while($start < $max);

        return $etags;
    }
    
    protected function _getEventIdFromName(string $name): string
    {
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        if (strlen((string)$id) > 40) {
            $id = sha1($id);
        }
        return $id;
    }
    
    protected function _importIcs(string $calUri, array $ics, Tinebase_Model_Container $targetContainer, array $todo): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
            . ' Importing ics uris: ' . print_r(array_keys($ics), true));

        if ((!isset($todo[Tinebase_Controller_Record_Abstract::ACTION_UPDATE]) ||
                !$todo[Tinebase_Controller_Record_Abstract::ACTION_UPDATE]) && (
                !isset($todo[Tinebase_Controller_Record_Abstract::ACTION_CREATE]) ||
                !$todo[Tinebase_Controller_Record_Abstract::ACTION_CREATE])) {
            return;
        }
        
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();

        $oldExternalIdUid = Calendar_Controller_MSEventFacade::getInstance()->useExternalIdUid(true);
        $oldAssertUser = Calendar_Controller_MSEventFacade::getInstance()->assertCalUserAttendee(false);
        $oldExternalOrgContainer = Calendar_Controller_Event::getInstance()->useExternalOrganizerContainer(false);
        $msEventRaii = new Tinebase_RAII(function() use($oldExternalIdUid, $oldAssertUser, $oldExternalOrgContainer) {
            Calendar_Controller_MSEventFacade::getInstance()->useExternalIdUid($oldExternalIdUid);
            Calendar_Controller_MSEventFacade::getInstance()->assertCalUserAttendee($oldAssertUser);
            Calendar_Controller_Event::getInstance()->useExternalOrganizerContainer($oldExternalOrgContainer);
        });

        $start = 0;
        $max = count($ics);
        do {
            $etags = array();
            $requestEnd = '';
            if (!isset($todo[Tinebase_Controller_Record_Abstract::ACTION_UPDATE]) ||
                    !$todo[Tinebase_Controller_Record_Abstract::ACTION_UPDATE]) {
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $name = explode('/', $ics[$i]);
                    $name = end($name);
                    $id = $this->_getEventIdFromName($name);
                    if (isset($this->existingRecordIds[$calUri][$id])) {
                        ++$start;
                        continue;
                    }
                    $requestEnd .= '  <a:href>' . $ics[$i] . "</a:href>\n";
                }
            } elseif (!isset($todo[Tinebase_Controller_Record_Abstract::ACTION_CREATE]) ||
                    !$todo[Tinebase_Controller_Record_Abstract::ACTION_CREATE]) {
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $name = explode('/', $ics[$i]);
                    $name = end($name);
                    $id = $this->_getEventIdFromName($name);
                    if (!isset($this->existingRecordIds[$calUri][$id])) {
                        ++$start;
                        continue;
                    }
                    $requestEnd .= '  <a:href>' . $ics[$i] . "</a:href>\n";
                }
            } else {
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $requestEnd .= '  <a:href>' . $ics[$i] . "</a:href>\n";
                }
            }

            $start = $i;
            $requestEnd .= '</b:calendar-multiget>';
            $result = $this->calDavRequest('REPORT', $calUri, self::getAllCalendarDataRequest . $requestEnd, 1);

            foreach ($result as $key => $value) {
                if (! isset($value['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {
                    continue;
                }

                $data = $value['{urn:ietf:params:xml:ns:caldav}calendar-data'];

                if (strpos($data, 'BEGIN:' . $this->skipComonent) !== false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skipping ' . $this->skipComonent);
                    continue;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                        . ' Processing caldav record: ' . $key);

                $name = explode('/', $key);
                $name = end($name);
                $id = $this->_getEventIdFromName($name);
                try {
                    if (isset($this->existingRecordIds[$calUri][$id])) {
                        $webdavFrontend = new $this->webdavFrontend($targetContainer, $this->existingRecordIds[$calUri][$id]);
                        $webdavFrontend->_getConverter()->setOptionsValue(\Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_EXTERNAL_ID_UID, true);
                        $webdavFrontend->put($data);
                    } else {
                        $webdavFrontend = call_user_func_array([$this->webdavFrontend, 'create'], [
                            $targetContainer,
                            $name,
                            $data,
                            false,
                            [Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_EXTERNAL_ID_UID => true]
                        ]);
                    }

                    if ($webdavFrontend) {
                        $etags[$webdavFrontend->getRecord()->getId()] = $value['{DAV:}getetag'];
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not create event from data: ' . $data);
                    Tinebase_Exception::log($e, /* $suppressTrace = */ false);
                }
            }

            $this->_recordBackend->setETags($etags);
        } while($start < $max);
        unset($msEventRaii);
    }

    public function clearCurrentUserCalendarData()
    {
        $this->calendars = [];
        $this->calendarICSs = [];
    }

    public function getDecorator()
    {
        return $this->decorator;
    }

    protected const calendarDataKey = '{urn:ietf:params:xml:ns:caldav}calendar-data';
    protected const findAllCalendarsRequest =
        '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:resourcetype />
    <d:acl />
    <d:displayname />
    <x:supported-calendar-component-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';

    protected const findAllCalendarICSsRequest =
        '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <x:calendar-data xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';

 protected const getAllCalendarDataRequest =
        '<?xml version="1.0"?>
<b:calendar-multiget xmlns:a="DAV:" xmlns:b="urn:ietf:params:xml:ns:caldav">
  <a:prop>
    <b:calendar-data />
    <a:getetag />
  </a:prop>
';

    protected const getEventETagsRequest =
        '<?xml version="1.0"?>
<b:calendar-multiget xmlns:a="DAV:" xmlns:b="urn:ietf:params:xml:ns:caldav">
  <a:prop>
    <a:getetag />
  </a:prop>
';

    protected const findCurrentUserPrincipalRequest =
        '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:current-user-principal />
  </d:prop>
</d:propfind>';

    protected const findCalendarHomeSetRequest =
        '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <x:calendar-home-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';

    protected const resolvePrincipalRequest =
        '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:group-member-set />
    <d:displayname />
  </d:prop>
</d:propfind>';
}
