<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Import_CalDAV
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_CalDAV extends Calendar_Import_Abstract
{
    protected $_calDAVClient = null;

    /**
     * Splits Uri into protocol + host, path
     *
     * @param $uri
     * @return array
     */
    protected function _splitUri($uri)
    {
        $uri = parse_url($uri);

        return array(
            'host' => $uri['scheme'] . '://' . $uri['host'],
            'path' => $uri['path']
        );
    }

    /**
     * import the data
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $raiis = [];
        $container = Tinebase_Container::getInstance()->getContainerById($this->_options['container_id']);

        $uri = $this->_splitUri($this->_options['url']);

        $cc = null;
        if (isset($this->_options['cc_id'])) {
            /** @var Tinebase_Model_CredentialCache $cc */
            $cc = Tinebase_Auth_CredentialCache::getInstance()->get($this->_options['cc_id']);
            $cc->key = Tinebase_Auth_CredentialCache_Adapter_Shared::getKey();
            Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);
        }

        $caldavClientOptions = array_merge([
            'baseUri' => $uri['host'],
            'calenderUri' => $uri['path'],
        ], $cc ? [
            'userName' => $cc->username,
            'password' => $cc->password,
        ] : ($this->_options['username'] && $this->_options['password'] ? [
            'userName' => $this->_options['username'],
            'password' => $this->_options['password'],
        ] : []));

        if (!$this->_options[self::OPTION_FORCE_UPDATE_EXISTING]) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_EXTERNAL_SEQ_CHECK_BEFORE_UPDATE] = true;
        }
        if ($this->_options[self::OPTION_SKIP_INTERNAL_OTHER_ORGANIZER]) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER] = true;
        }
        if ($this->_options[self::OPTION_DISABLE_EXTERNAL_ORGANIZER_CALENDAR]) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR] = true;
        }
        if (is_array($this->_options['overwriteOrganizer'])) {
            Calendar_Frontend_WebDAV_EventImport::addEventDeserializedHook(static::class . 'overwriteOrganizer', function(Calendar_Model_Event $event) {
                foreach($this->_options['overwriteOrganizer'] as $fieldName => $value) {
                    $event->{$fieldName} = $value;
                }
            });
            $raiis[] = new Tinebase_RAII(fn() => Calendar_Frontend_WebDAV_EventImport::removeEventDeserializedHook(static::class . 'overwriteOrganizer'));
        }
        if (is_array($this->_options['attendee'])) {
            if ($this->_options['attendeeStrategy'] === 'add' && is_array($this->_options['attendee'])) {
                Calendar_Frontend_WebDAV_EventImport::addEventDeserializedHook(static::class . 'attendee', function(Calendar_Model_Event $event) {
                    $attendees = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, $this->_options['attendee']);
                    foreach($attendees as $attendee) {
                        if (! Calendar_Model_Attender::getAttendee($event->attendee, $attendee)) {
                            $event->attendee->addRecord($attendee);
                        }
                    }
                });
                $raiis[] = new Tinebase_RAII(fn() => Calendar_Frontend_WebDAV_EventImport::removeEventDeserializedHook(static::class . 'attendee'));
            } else if ($this->_options['attendeeStrategy'] === 'replace') {
                Calendar_Frontend_WebDAV_EventImport::addEventDeserializedHook(static::class . 'attendee', function(Calendar_Model_Event $event) {
                    $event->attendee = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, $this->_options['attendee']);
                });
                $raiis[] = new Tinebase_RAII(fn() => Calendar_Frontend_WebDAV_EventImport::removeEventDeserializedHook(static::class . 'attendee'));
            }
        }
        if ($this->_options['keepExistingAttendee']) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_KEEP_EXISTING_ATTENDEE] = true;
        }
        if ($this->_options[self::OPTION_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS]) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS] = true;
        }
        if ($this->_options[self::OPTION_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS]) {
            $caldavClientOptions[Calendar_Import_CalDav_Client::OPT_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS] = true;
        }
        if ($this->_options[self::OPTION_IMPORT_VTODOS]) {
            $caldavClientOptions[self::OPTION_IMPORT_VTODOS] = true;
            if (null === $this->_options[self::OPTION_TASK_CONTAINER] && Tinebase_Model_Container::TYPE_SHARED === $container->type) {
                try {
                    $this->_options[self::OPTION_TASK_CONTAINER] = Tinebase_Container::getInstance()
                        ->getContainerByName(Tasks_Model_Task::class, $container->name, $container->type)->getId();
                } catch (Tinebase_Exception_NotFound) {
                    $this->_options[self::OPTION_TASK_CONTAINER] = Tinebase_Container::getInstance()
                        ->addContainer(new Tinebase_Model_Container([
                            'name' => $container->name,
                            'type' => $container->type,
                            'backend' => 'Sql',
                            'application_id' => Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME)->getId(),
                            'model' => Tasks_Model_Task::class,
                        ], true), Tinebase_Container::getInstance()->getGrantsOfContainer($container))->getId();
                }
            }
        }
        if ($this->_options[self::OPTION_TASK_CONTAINER]) {
            $caldavClientOptions[self::OPTION_TASK_CONTAINER] = $this->_options[self::OPTION_TASK_CONTAINER];
        }
        if ($this->_options['calDavRequestTries']) {
            $caldavClientOptions['calDavRequestTries'] = $this->_options['calDavRequestTries'];
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' Trigger CalDAV client with URI ' . $this->_options['url']);
        }
        
        $this->_calDAVClient = new Calendar_Import_CalDav_Client($caldavClientOptions, 'Generic');
        // TODO FIXME really? why? this is debug code...
        $this->_calDAVClient->setVerifyPeer(false);
        $this->_calDAVClient->getDecorator()->initCalendarImport($this->_options);
        $this->_calDAVClient->syncCalendarEvents($uri['path'], $container, [
            Tinebase_Controller_Record_Abstract::ACTION_CREATE => true,
            Tinebase_Controller_Record_Abstract::ACTION_UPDATE => $this->_options['updateExisting'],
            Tinebase_Controller_Record_Abstract::ACTION_DELETE => $this->_options['deleteMissing'],
        ]);

        unset($raiis);
    }

    protected function _getImportEvents($_resource, $container)
    {
        // not needed here
    }
}
