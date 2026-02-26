<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Calendar_Backend_CalDav_Client extends \Sabre\DAV\Client
{
    public const OPT_USERNAME = 'userName';
    public const OPT_EXTERNAL_SEQ_CHECK_BEFORE_UPDATE = 'extSeqCheckUpdate';
    public const OPT_SKIP_INTERNAL_OTHER_ORGANIZER = 'skipInternalOtherOrganizer';
    public const OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR = 'disableExternalOrganizerCalendar';
    public const OPT_KEEP_EXISTING_ATTENDEE = 'keepExistingAttendee';
    public const OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS = 'useOwnAttendeeForSkipInternalOtherOrganizerEvents';
    public const OPT_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS = 'allowPartyCrushForSkipInternalOtherOrganizerEvents';
    public const OPT_XPROPS_KEY = 'xpropsKey';

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


    protected Calendar_Backend_CalDav_Decorator_Abstract $decorator;
    
    protected $component = 'VEVENT';
    protected $skipComonent = 'VTODO';

    /**
     * @var class-string
     */
    protected string $webdavFrontend = Calendar_Frontend_WebDAV_EventImport::class;

    protected bool $_doExternalSeqCheckBeforeUpdate = false;
    protected bool $_skipInternalOtherOrganizer = false;
    protected bool $_useOwnAttendeeForSkipIOO = false;
    protected bool $_allowPartyCrushForSkipIOO = false;

    protected bool $_disableExternalOrganizerCalendar = false;
    protected bool $_importVTodos = false;
    protected ?Tinebase_Model_Container $_taskContainer = null;

    protected bool $_keepExistingAttendee = false;

    protected bool $enforceRecreateInTargetContainer = true;

    protected bool $_doCreate = true;
    protected bool $_doUpdate = true;
    protected bool $_doDelete = true;

    protected array $_serverETags = [];
    protected ?string $userName;

    protected string $xpropsKey = 'default';

    protected Tinebase_Backend_Sql_Abstract $_recordBackend;

    protected ?Calendar_Convert_Event_VCalendar_Abstract $_converter = null;
    protected array $settings;
    
    public function __construct(array $settings, string $flavor)
    {
        $this->settings = $settings;

        parent::__construct($settings);

        $this->userName = $settings[self::OPT_USERNAME] ?? null;

        if (is_int($settings['calDavRequestTries'] ?? null)) {
            $this->_requestTries = $settings['calDavRequestTries'];
        }
        if ($settings[self::OPT_EXTERNAL_SEQ_CHECK_BEFORE_UPDATE] ?? false) {
            $this->_doExternalSeqCheckBeforeUpdate = true;
        }
        if ($settings[self::OPT_SKIP_INTERNAL_OTHER_ORGANIZER] ?? false) {
            $this->_skipInternalOtherOrganizer = true;
        }
        if ($settings[self::OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR] ?? false) {
            $this->_disableExternalOrganizerCalendar = true;
        }
        if ($settings[self::OPT_KEEP_EXISTING_ATTENDEE] ?? false) {
            $this->_keepExistingAttendee = true;
        }
        if ($settings[self::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS] ?? false) {
            $this->_useOwnAttendeeForSkipIOO = true;
        }
        if ($settings[self::OPT_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS] ?? false) {
            $this->_allowPartyCrushForSkipIOO = true;
        }
        if ($settings[Calendar_Import_Abstract::OPTION_IMPORT_VTODOS] ?? false) {
            $this->_importVTodos = true;
        }
        if (!($settings[Calendar_Import_Abstract::OPTION_ENFORCE_RECREATE_IN_TARGET_CONTAINER] ?? true)) {
            $this->enforceRecreateInTargetContainer = false;
            $this->webdavFrontend = Calendar_Frontend_WebDAV_Event::class;
        }
        if ($settings[Calendar_Import_Abstract::OPTION_TASK_CONTAINER] ?? false) {
            /** @var Tinebase_Model_Container $container */
            $container = Tinebase_Container::getInstance()->get($settings[Calendar_Import_Abstract::OPTION_TASK_CONTAINER]);
            $this->_taskContainer = $container;
        }

        if ($this->_importVTodos && null === $this->_taskContainer) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' no task container provided, using default personal task container');
            $this->_taskContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tasks_Model_Task::class);
        }
        if (is_string($settings[self::OPT_XPROPS_KEY] ?? null)) {
            $this->xpropsKey = $settings[self::OPT_XPROPS_KEY];
        }
        if (is_bool($settings[Tinebase_Controller_Record_Abstract::ACTION_CREATE] ?? null)) {
            $this->_doCreate = $settings[Tinebase_Controller_Record_Abstract::ACTION_CREATE];
        }
        if (is_bool($settings[Tinebase_Controller_Record_Abstract::ACTION_UPDATE] ?? null)) {
            $this->_doUpdate = $settings[Tinebase_Controller_Record_Abstract::ACTION_UPDATE];
        }
        if (is_bool($settings[Tinebase_Controller_Record_Abstract::ACTION_DELETE] ?? null)) {
            $this->_doDelete = $settings[Tinebase_Controller_Record_Abstract::ACTION_DELETE];
        }

        if ($this->_importVTodos) {
            $flavor = 'Calendar_Backend_CalDav_Decorator_' . $flavor; // TODO change to Tasks?
            if (!class_exists($flavor)) {
                throw new Tinebase_Exception_UnexpectedValue('flavor ' . $flavor . ' not supported');
            }
            $this->decorator = new $flavor($this);
            $this->_recordBackend = Tasks_Controller_Task::getInstance()->getBackend();
        } else {
            $flavor = 'Calendar_Backend_CalDav_Decorator_' . $flavor;
            if (!class_exists($flavor)) {
                throw new Tinebase_Exception_UnexpectedValue('flavor ' . $flavor . ' not supported');
            }
            $this->decorator = new $flavor($this);
            $this->_recordBackend = Calendar_Controller_Event::getInstance()->getBackend();
        }
    }

    public function setVerifyPeer(bool $verify): void
    {
        $this->addCurlSetting(CURLOPT_PROXY_SSL_VERIFYPEER, $verify);
        $this->addCurlSetting(CURLOPT_PROXY_SSL_VERIFYHOST, $verify ? 2 : 0);
    }

    public function multiStatusRequest(string $method, string $uri, string $body, int $depth = 0): array
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Sending ' . $method . ' request for uri ' . $uri . ' ...');

        try {
            $response = $this->request($method, $uri, $body, array(
                'Depth' => $depth,
                'Content-Type' => 'text/xml',
            ));
        } catch (Throwable $t) {
            $e = new Tinebase_Exception($t->getMessage(), previous: $t);
            $e->setLogLevelMethod('warn');
            Tinebase_Exception::log($e);
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Caldav request failed: '
                    . '(' . $this->userName . ')' . $method . ' ' . $uri . PHP_EOL . $body);
            throw $e;
        }

        if (404 === (int)($response['statusCode'] ?? null)) {
            throw new Tinebase_Exception_NotFound('404');
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

        $newResult = [];
        foreach($result as $href => $statusList)
        {
            $newResult[$href] = $statusList[200] ?? [];
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
     * - result ($this->currentUserPrincipal) is not cached  //for 1 week
     */
    public function findCurrentUserPrincipal(): bool
    {
        /* $cacheId = Tinebase_Helper::convertCacheId(__METHOD__ . $this->userName);
        if (Tinebase_Core::getCache()->test($cacheId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Loading user principal from cache');

            if (($this->currentUserPrincipal = Tinebase_Core::getCache()->load($cacheId) ?: null)) {
                return true;
            }
        }*/

        $result = $this->multiStatusRequest('PROPFIND', '/principals/', self::findCurrentUserPrincipalRequest);
        if (isset($result['{DAV:}current-user-principal']))
        {
            $this->currentUserPrincipal = $result['{DAV:}current-user-principal'];

            //Tinebase_Core::getCache()->save($this->currentUserPrincipal, $cacheId, array(), /* 1 week */ 24*3600*7);
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
        if (null === $this->currentUserPrincipal && ! $this->findCurrentUserPrincipal()) {
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

        $result = $this->multiStatusRequest('PROPFIND', $this->currentUserPrincipal, self::findCalendarHomeSetRequest);

        if (isset($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'])) {
            $this->calendarHomeSet = rtrim($result['{urn:ietf:params:xml:ns:caldav}calendar-home-set'], '/') . '/';
            Tinebase_Core::getCache()->save($this->calendarHomeSet, $cacheId, array(), /* 1 week */ 24*3600*7);
            return true;
        }

        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' couldn\'t find calendar homeset');
        return false;
    }

    public function findAllCollections(): ?Tinebase_Record_RecordSet
    {
        if (null === $this->calendarHomeSet && ! $this->findCalendarHomeSet()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' No calendar home set found for user ' . $this->userName);
            return null;
        }

        $collections = new Tinebase_Record_RecordSet(Tinebase_Model_WebDAV_Collection::class);
        foreach($this->multiStatusRequest('PROPFIND', $this->calendarHomeSet, self::findAllCalendarsRequest, 1) as $url => $result) {
            if (!($compSet = $result['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] ?? null) instanceof \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet) {
                continue;
            }
            if (in_array('VEVENT', $compSet->getValue())) {
                $type = 'VEVENT';
            } elseif (in_array('VTODO', $compSet->getValue())) {
                $type = 'VTODO';
            } else {
                continue;
            }

            if ($result['{DAV:}acl'] instanceof Sabre\DAVACL\Xml\Property\Acl) {
                foreach ($result['{DAV:}acl']->getPrivileges() as $acl) {
                    // $acl['principal'] === '{DAV:}authenticated' || $this->currentUserPrincipal === $acl['principal'];
                    // what about groups? roles?
                    $acl['privilege'];
                }
            }

            $collections->addRecord(new Tinebase_Model_WebDAV_Collection([
                Tinebase_Model_WebDAV_Collection::FLD_URI => $url,
                Tinebase_Model_WebDAV_Collection::FLD_NAME => $result['{DAV:}displayname'] ?? null,
                Tinebase_Model_WebDAV_Collection::FLD_COLOR => $result['{http://apple.com/ns/ical/}calendar-color'] ?? null,
                Tinebase_Model_WebDAV_Collection::FLD_TYPE => $type,
                //Tinebase_Model_WebDAV_Collection::FLD_ACL => '',
            ]));
        }

        return $collections;
    }
    
    public function findAllCalendarICSs(string $uri): bool
    {
        $result = $this->multiStatusRequest('PROPFIND', $uri, self::findAllCalendarICSsRequest, 1);

        $this->calendarICSs[$uri] = [];
        foreach ($result as $ics => $value) {
            if (strpos($ics, '.ics') !== FALSE)
                $this->calendarICSs[$uri][] = $ics;
        }
        
        return true;
    }

    protected function getConverter(): Calendar_Convert_Event_VCalendar_Abstract
    {
        if (null === $this->_converter) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
            $this->_converter = Calendar_Convert_Event_VCalendar_Factory::factory($backend, $version);
        }
        return $this->_converter;
    }

    // TODO FIXME we should implement capability detection, the server might not support sync tokens
    public function syncWithSyncToken(string $calUri, Tinebase_Model_Container $container, string $token, ?bool &$syncSuccess): string
    {
        $this->decorator->initCalendarImport($this->settings);

        $syncSuccess = false;
        if ('' === $token) {
            try {
                $props = $this->propFind($calUri, ['{DAV:}sync-token']);
            } catch (Sabre\HTTP\ClientHttpException $che) {
                throw new Tinebase_Exception('PropFind failed on uri ' . $calUri . ' error: ' . $che->getMessage());
            }
            if (!empty($props['{DAV:}sync-token'] ?? null)) {
                return $props['{DAV:}sync-token'];
            }
            throw new Tinebase_Exception_NotImplemented();
        }

        $response = $this->request('REPORT', $calUri, sprintf(self::reportSyncTokenRequest, $token), [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);

        if (400 === $response['statusCode'] || 403 === $response['statusCode']) {
            return $this->syncWithSyncToken($calUri, $container, '', $syncSuccess);
        }
        if ($response['statusCode'] !== 207) {
            // TODO FIXME check for not supported and throw new Tinebase_Exception_NotImplemented()
            throw new Tinebase_Exception('sync token request for ' . $calUri . ' failed with '
                . $response['statusCode']);
        }

        /** @var \Sabre\DAV\Xml\Response\MultiStatus $multistatus */
        $multistatus = $this->xml->expect('{DAV:}multistatus', $response['body']);
        $result = [];
        foreach ($multistatus->getResponses() as $response) {
            if ('404' === $response->getHttpStatus()) {
                $result['404'][] = $this->_getEventIdFromName($response->getHref());
            } else {
                $props = $response->getResponseProperties();
                if (null !== ($etag = ($props['200']['{DAV:}getetag'] ?? null))) {
                    // https://www.rfc-editor.org/rfc/rfc2616#section-3.11
                    $etag = preg_replace(['#^\s*("W/")?\s*"#', '/"\s*$/'], '', $etag);
                    $result['200'][$response->getHref()] =  $etag;
                }
            }
        }

        $newToken = $multistatus->getSyncToken();


        Calendar_Controller_Event::getInstance()->skipSyncContainerCheck(true);
        $syncContainerRaii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->skipSyncContainerCheck(false));

        if ($this->_doDelete && isset($result['404'])) {
            $this->_deleteByExternalIds($container->getId(), $result['404']);
        }

        if (isset($result['200']) && ($this->_doCreate || $this->_doUpdate)) {
            $this->_createOrUpdateUris($container, $calUri, $result['200']);
        }

        unset($syncContainerRaii);
        $syncSuccess = true;
        return $newToken;
    }

    protected function _deleteByExternalIds(string $container_id, array $externalIds): void
    {
        Calendar_Controller_Event::getInstance()->deleteByFilter(new Calendar_Model_EventFilter([
            [TMFA::FIELD => 'external_id', TMFA::OPERATOR => 'in', TMFA::VALUE => $externalIds],
            [TMFA::FIELD => 'container_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $container_id],
        ]));
    }

    protected function _createOrUpdateUris(Tinebase_Model_Container $container, string $calUri, array $uriEtags): void
    {
        //$existingEtags = $this->_recordBackend->getEtagsForContainerId($container->getId());

        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);

        $oldExternalIdUid = Calendar_Controller_MSEventFacade::getInstance()->useExternalIdUid(true);
        $oldAssertUser = Calendar_Controller_MSEventFacade::getInstance()->assertCalUserAttendee(false);
        $oldExternalOrgContainer = Calendar_Controller_Event::getInstance()->useExternalOrganizerContainer(!$this->_disableExternalOrganizerCalendar);
        $msEventRaii = new Tinebase_RAII(function() use($oldExternalIdUid, $oldAssertUser, $oldExternalOrgContainer) {
            Calendar_Controller_MSEventFacade::getInstance()->useExternalIdUid($oldExternalIdUid);
            Calendar_Controller_MSEventFacade::getInstance()->assertCalUserAttendee($oldAssertUser);
            Calendar_Controller_Event::getInstance()->useExternalOrganizerContainer($oldExternalOrgContainer);
        });

        $bulkCount = 0;
        $requestEnd = '';
        $makeRequestFun = function() use(&$bulkCount, &$requestEnd, $calUri, $container) {
            $requestEnd .= '</b:calendar-multiget>';
            $result = $this->multiStatusRequest('REPORT', $calUri, self::getAllCalendarDataRequest . $requestEnd, 1);

            foreach ($result as $uri => $value) {
                if (! isset($value['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {
                    continue;
                }

                $data = $value['{urn:ietf:params:xml:ns:caldav}calendar-data'];
                $id = $this->_getEventIdFromName($name = basename($uri));

                if ($this->_importVTodos && strpos($data, 'BEGIN:VTODO') !== false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                            . ' Processing VTODO record: ' . $uri);
                    }

                    $oldUserAgent = $_SERVER['HTTP_USER_AGENT'];
                    $userAgentRAII = new Tinebase_RAII(fn() => $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent);
                    $_SERVER['HTTP_USER_AGENT'] = 'CalDavSynchronizer/tine';
                    Tasks_Frontend_WebDAV_Task::create($this->_taskContainer, $name, $data, $this->_serverETags[$id] ?? null);
                    unset($userAgentRAII);
                }

                if (strpos($data, 'BEGIN:VEVENT') === false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                        . ' no VEVENT found: ' . $uri);
                    continue;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' Processing VEVENT record: ' . $uri);

                try {
                    if (isset($this->existingRecordIds[$calUri][$id])) {
                        $webdavFrontend = new $this->webdavFrontend($container, $this->existingRecordIds[$calUri][$id]);
                        if ($this->_doExternalSeqCheckBeforeUpdate && $webdavFrontend instanceof Calendar_Frontend_WebDAV_EventImport) {
                            $webdavFrontend->setDoExternalSeqUpdateCheck(true);
                        }
                        if ($this->_keepExistingAttendee && $webdavFrontend instanceof Calendar_Frontend_WebDAV_EventImport) {
                            $webdavFrontend->setKeepExistingAttendee(true);
                        }
                        $webdavFrontend->_getConverter()->setOptionsValue(\Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_EXTERNAL_ID_UID, true);
                        $webdavFrontend->put($data);
                    } else {
                        $webdavFrontend = call_user_func_array([$this->webdavFrontend, 'create'], [
                            $container,
                            $name,
                            $data,
                            $this->_skipInternalOtherOrganizer,
                            [
                                Calendar_Convert_Event_VCalendar_Abstract::OPTION_USE_EXTERNAL_ID_UID => true,
                                Calendar_Frontend_WebDAV_Event::ALLOW_EXTERNAL_ORGANIZER_ANYWAY => $this->_skipInternalOtherOrganizer,
                            ],
                            $this->enforceRecreateInTargetContainer,
                            !$this->enforceRecreateInTargetContainer
                        ]);
                        // if the event was not created (due to skipInternalOtherOrganizer) we check useOwnAttenderForSkipInternalOtherOrganizer
                        if (null === $webdavFrontend && ($this->_useOwnAttendeeForSkipIOO || $this->_allowPartyCrushForSkipIOO) &&
                                Calendar_Frontend_WebDAV_Event::$lastEventCreated?->external_id &&
                                ($ownAttender = Calendar_Model_Attender::getOwnAttender(Calendar_Frontend_WebDAV_Event::$lastEventCreated->attendee))) {
                            $oldAclCheck = Calendar_Controller_Event::getInstance()->doContainerACLChecks(false);
                            $calCtrlAclRaii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->doContainerACLChecks($oldAclCheck));
                            $existingEvent = Calendar_Controller_Event::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
                                [TMFA::FIELD => 'external_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Calendar_Frontend_WebDAV_Event::$lastEventCreated->external_id],
                            ]))->getFirstRecord();

                            $updateRequired = false;
                            if ($existingEvent && $this->_useOwnAttendeeForSkipIOO && ($existingOwnAttender = Calendar_Model_Attender::getOwnAttender($existingEvent->attendee))) {
                                if ($ownAttender->status !== $existingOwnAttender->status) {
                                    $existingOwnAttender->status = $ownAttender->status;
                                }
                                if ($ownAttender->transp !== $existingOwnAttender->transp) {
                                    $existingOwnAttender->transp = $ownAttender->transp;
                                }
                                if ($existingOwnAttender->isDirty()) {
                                    $updateRequired = true;
                                }
                            } elseif ($existingEvent && $this->_allowPartyCrushForSkipIOO) {
                                $ownAttender->displaycontainer_id = Calendar_Controller_Event::getInstance()->getDefaultDisplayContainerId(Tinebase_Core::getUser());
                                $existingEvent->attendee->addRecord($ownAttender);
                                $updateRequired = true;
                            }
                            if ($updateRequired) {
                                $existingEvent = Calendar_Controller_Event::getInstance()->update($existingEvent);
                                $webdavFrontend = new Calendar_Frontend_WebDAV_Event($container, $existingEvent);
                            }
                            unset($calCtrlAclRaii);
                        }
                    }

                    if ($webdavFrontend) {
                        $this->_recordBackend->setETags([$webdavFrontend->getRecord()->getId() => $value['{DAV:}getetag']]);
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not create event from data: ' . $data);
                    Tinebase_Exception::log($e, /* $suppressTrace = */ false);
                }
            }

            $bulkCount = 0;
            $requestEnd = '';
        };

        foreach ($uriEtags as $uri => $etag) {
            $externalId = $this->_getEventIdFromName(basename($uri));
            $existingEtags = $this->_recordBackend->getEtagsForContainerId($container->getId(), $externalId);
            if (isset($existingEtags[$externalId])) {
                if (!$this->_doUpdate || $etag === $existingEtags[$externalId]) {
                    continue;
                }
            } elseif (!$this->_doCreate) {
                continue;
            }
            $requestEnd .= '  <a:href>' . $uri . '</a:href>' . PHP_EOL;
            if (++$bulkCount === $this->maxBulkRequest) {
                $makeRequestFun();
            }
        }

        if ($bulkCount > 0) {
            $makeRequestFun();
        }

        unset ($msEventRaii);
    }

    public function writeEvent(string $uri, Calendar_Model_Event $event): ?Calendar_Model_Event
    {
        $this->decorator->initCalendarImport($this->settings);

        $uri = '/' . trim($uri, '/');
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' Trying to write event to ' . $uri . ' with user ' . $this->userName);

        $body = $this->getConverter()->fromTine20Model($event)->serialize();
        if (null === ($id = $event->external_id ?: $event->getId())) {
            $event->setId($id = $event::generateUID());
        }
        $requestUrl = $this->getAbsoluteUrl($uri . '/' . $id . '.ics');
        $this->existingRecordIds[$uri][$id] = true;

        $readEventFromRemote = function() use($uri, $requestUrl, $event) {
            Calendar_Controller_Event::getInstance()->skipSyncContainerCheck(true);
            $raii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->skipSyncContainerCheck(false));
            $this->_createOrUpdateUris($event->container_id instanceof Tinebase_Model_Container ? $event->container_id : Tinebase_Container::getInstance()->get($event->container_id),
                $uri, [$requestUrl => '']);
            unset($raii);
        };

        try {
            $response = $this->request('PUT', $requestUrl, $body, array_merge([
                'Content-Type' => 'text/calendar; charset=utf-8',
                'User-Agent' => 'Tine20/2024.11', // TODO FIXME version!?
            ], $event->etag ? ['If-Match' => $event->etag] : []));

            if ($response['statusCode'] === 412) {
                // If-Match failed
                // TODO FIXME ? probably we want to read the event from remote...
                $readEventFromRemote();

                // TODO FIXME should we throw? read event from db and throw with attached event? which exception?
            }
            if ($response['statusCode'] !== 201 && $response['statusCode'] !== 204) {
                throw new Tinebase_Exception('status code wrong: ' . print_r($response, true));
            }
        } catch (Throwable $t) {
            if ($t instanceof Tinebase_Exception) {
                $e = $t;
            } else {
                $e = new Tinebase_Exception($t->getMessage(), previous: $t);
            }
            $e->setLogToSentry(false);
            $e->setLogLevelMethod('info');
            Tinebase_Exception::log($e);
            return null;
        }

        // get event or at least new etag and sequence
        $readEventFromRemote();

        return Calendar_Controller_Event::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
            [TMFA::FIELD => 'container_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $event->getIdFromProperty('container_id')],
            [TMFA::FIELD => 'uid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $event->uid],
            [TMFA::FIELD => 'recurid', TMFA::OPERATOR => $event->recurid ? TMFA::OP_EQUALS : 'isnull', TMFA::VALUE => $event->recurid],
        ]))->getFirstRecord();
    }

    public function syncCalendarEvents(string $uri, Tinebase_Model_Container $targetContainer, array $todo = [
            Tinebase_Controller_Record_Abstract::ACTION_CREATE => true,
            Tinebase_Controller_Record_Abstract::ACTION_UPDATE => true,
            Tinebase_Controller_Record_Abstract::ACTION_DELETE => true,
        ]): bool
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' Importing calendar ' . $uri . ' with user ' . $this->userName);

        // trying to use syncTokens
        $syncState = Calendar_Backend_CalDav_SyncState::getSyncStateFromContainer($targetContainer, $this->xpropsKey);
        if ($syncState->supportSyncToken()) {
            try {
                $syncState->setSyncToken(
                    $this->syncWithSyncToken($uri, $targetContainer, $syncState->getSyncToken() ?? '', $syncTokenSuccess)
                );

                if (true === $syncTokenSuccess) {
                    $syncState->storeInContainer($targetContainer);
                    return true;
                }

            } catch (Tinebase_Exception_NotImplemented) {
                $syncState->setSyncTokenSupport(false);
                $syncState->storeInContainer($targetContainer);
            }
        }

        $this->decorator->initCalendarImport($this->settings);

        if (!isset($this->calendarICSs[$uri]) && !$this->findAllCalendarICSs($uri)) {
            return false;
        }

        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);

        // sets $_SERVER['HTTP_USER_AGENT'] for ics import / conversion handling

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

            // TODO FIXME $todo! $this->_importIcs($uri, $updateResult['ics'], $targetContainer, $todo);
            $this->_createOrUpdateUris($targetContainer, $uri, $updateResult['ics']);
        }

        if ($syncState->supportSyncToken()) {
            $syncState->storeInContainer($targetContainer);
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
        $localIds = [];

        foreach ($serverEtags as $ics => $data) {

            if (isset($containerEtags[$data['id']])) {
                $tine20Etag = $containerEtags[$data['id']]['etag'];

                $localId = $containerEtags[$data['id']]['id'];
                $existingIds[$data['id']] = $localId;
                $localIds[$localId] = true;
                // remove from $containerEtags list to be able to tell deletes
                unset($containerEtags[$data['id']]);

                if ($tine20Etag === $data['etag']) {
                    continue; // same
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record needs update: ' . $data['id']);

                $updateResult['toupdate']++;
            } else {
                $updateResult['toadd']++;
            }
            $updateResult['ics'][$ics] = $data['etag'];
        }

        $this->existingRecordIds[$calUri] = $existingIds;
        
        // handle deletes/exdates
        foreach ($containerEtags as $id => $data) {
            if (isset($data['base_event_id']) && isset($localIds[$data['base_event_id']])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record ' . $id . ' is exdate of ' . $data['base_event_id']);
                continue;
            }
            // record has been deleted on server
            $updateResult['todelete'][] = $data['id'];
        }
        
        return $updateResult;
    }
    
    protected function _fetchServerEtags(string $calUri, array $calICSs): array
    {
        $this->_serverETags = [];
        $start = 0;
        $max = count($calICSs);
        
        $etags = [];
        do {
            $requestEnd = '';
            for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
            }
            $start += $this->maxBulkRequest;
            $requestEnd .= '</b:calendar-multiget>';

            $result = $this->multiStatusRequest('REPORT', $calUri, self::getEventETagsRequest . $requestEnd, 1);

            foreach ($result as $key => $value) {
                if (isset($value['{DAV:}getetag'])) {
                    $id = $this->_getEventIdFromName(basename($key));
                    $etags[$key] = array( 'id' => $id, 'etag' => $value['{DAV:}getetag']);
                    $this->_serverETags[$id] = $value['{DAV:}getetag'];
                }
            }
        } while($start < $max);

        return $etags;
    }
    
    protected function _getEventIdFromName(string $name): string
    {
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        if (strlen($id) > 40) {
            $id = sha1($id);
        }
        return $id;
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
    <calendar-color xmlns="http://apple.com/ns/ical/"/>
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

    protected const reportSyncTokenRequest =
        '<?xml version="1.0" encoding="utf-8" ?>
<d:sync-collection xmlns:d="DAV:">
  <d:sync-token>%s</d:sync-token>
  <d:sync-level>1</d:sync-level>
  <d:prop>
    <d:getetag/>
  </d:prop>
</d:sync-collection>';
}
