<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * ActiveSync frontend class
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_ActiveSync extends ActiveSync_Frontend_Abstract implements Syncroton_Data_IDataCalendar
{
    /**
     * available filters
     * 
     * @var array
     */
    protected $_filterArray = array(
        Syncroton_Command_Sync::FILTER_2_WEEKS_BACK,
        Syncroton_Command_Sync::FILTER_1_MONTH_BACK,
        Syncroton_Command_Sync::FILTER_3_MONTHS_BACK,
        Syncroton_Command_Sync::FILTER_6_MONTHS_BACK
    );
    
    /**
     * mapping of attendee status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_attendeeStatusMapping = array(
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_UNKNOWN       => Calendar_Model_Attender::STATUS_NEEDSACTION,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_TENTATIVE     => Calendar_Model_Attender::STATUS_TENTATIVE,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_ACCEPTED      => Calendar_Model_Attender::STATUS_ACCEPTED,
        Syncroton_Model_EventAttendee::ATTENDEE_STATUS_DECLINED      => Calendar_Model_Attender::STATUS_DECLINED,
        //self::ATTENDEE_STATUS_NOTRESPONDED  => Calendar_Model_Attender::STATUS_NEEDSACTION
    );
    
    /**
     * mapping of attendee status in meeting response
     * @var array
     */
    protected $_meetingResponseAttendeeStatusMapping = array(
        Syncroton_Model_MeetingResponse::RESPONSE_ACCEPTED    => Calendar_Model_Attender::STATUS_ACCEPTED,
        Syncroton_Model_MeetingResponse::RESPONSE_TENTATIVE   => Calendar_Model_Attender::STATUS_TENTATIVE,
        Syncroton_Model_MeetingResponse::RESPONSE_DECLINED    => Calendar_Model_Attender::STATUS_DECLINED,
    );
    
    /**
     * mapping of busy status
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_busyStatusMapping = array(
        Syncroton_Model_Event::BUSY_STATUS_FREE      => Calendar_Model_Attender::STATUS_DECLINED,
        Syncroton_Model_Event::BUSY_STATUS_TENATTIVE => Calendar_Model_Attender::STATUS_TENTATIVE,
        Syncroton_Model_Event::BUSY_STATUS_BUSY      => Calendar_Model_Attender::STATUS_ACCEPTED
    );
    
    /**
     * mapping of attendee types
     * 
     * NOTE: recources need extra handling!
     * @var array
     */
    protected $_attendeeTypeMapping = array(
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_REQUIRED => Calendar_Model_Attender::ROLE_REQUIRED,
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_OPTIONAL => Calendar_Model_Attender::ROLE_OPTIONAL,
        Syncroton_Model_EventAttendee::ATTENDEE_TYPE_RESOURCE => Calendar_Model_Attender::USERTYPE_RESOURCE
    );
    
    /**
     * mapping of recur types
     *
     * NOTE: not surjektive
     * @var array
     */
    protected $_recurTypeMapping = array(
        Syncroton_Model_EventRecurrence::TYPE_DAILY          => Calendar_Model_Rrule::FREQ_DAILY,
        Syncroton_Model_EventRecurrence::TYPE_WEEKLY         => Calendar_Model_Rrule::FREQ_WEEKLY,
        Syncroton_Model_EventRecurrence::TYPE_MONTHLY        => Calendar_Model_Rrule::FREQ_MONTHLY,
        Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN   => Calendar_Model_Rrule::FREQ_MONTHLY,
        Syncroton_Model_EventRecurrence::TYPE_YEARLY         => Calendar_Model_Rrule::FREQ_YEARLY,
        Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN    => Calendar_Model_Rrule::FREQ_YEARLY,
    );
    
    /**
     * mapping of weekdays
     * 
     * NOTE: ActiveSync uses a bitmask
     * @var array
     */
    protected $_recurDayMapping = array(
        Calendar_Model_Rrule::WDAY_SUNDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_SUNDAY,
        Calendar_Model_Rrule::WDAY_MONDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_MONDAY,
        Calendar_Model_Rrule::WDAY_TUESDAY      => Syncroton_Model_EventRecurrence::RECUR_DOW_TUESDAY,
        Calendar_Model_Rrule::WDAY_WEDNESDAY    => Syncroton_Model_EventRecurrence::RECUR_DOW_WEDNESDAY,
        Calendar_Model_Rrule::WDAY_THURSDAY     => Syncroton_Model_EventRecurrence::RECUR_DOW_THURSDAY,
        Calendar_Model_Rrule::WDAY_FRIDAY       => Syncroton_Model_EventRecurrence::RECUR_DOW_FRIDAY,
        Calendar_Model_Rrule::WDAY_SATURDAY     => Syncroton_Model_EventRecurrence::RECUR_DOW_SATURDAY
    );
    
    /**
     * trivial mapping
     *
     * @var array
     */
    protected $_mapping = array(
        //'Timezone'          => 'timezone',
        'allDayEvent'       => 'is_all_day_event',
        //'BusyStatus'        => 'transp',
        //'OrganizerName'     => 'organizer',
        //'OrganizerEmail'    => 'organizer',
        //'DtStamp'           => 'last_modified_time',  // not used outside from Tine 2.0
        'endTime'           => 'dtend',
        'location'          => 'location',
        'reminder'          => 'alarms',
        'sensitivity'       => 'class',
        'subject'           => 'summary',
        'body'              => 'description',
        'startTime'         => 'dtstart',
        //'UID'               => 'uid',             // not used outside from Tine 2.0
        //'MeetingStatus'     => 'status_id',
        'attendees'         => 'attendee',
        'categories'        => 'tags',
        'recurrence'        => 'rrule',
        'exceptions'        => 'exdate',
    );
    
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Calendar';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Event';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR;
    
    /**
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder       = ActiveSync_Preference::DEFAULTCALENDAR;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_CALENDAR_USER_CREATED;
    
    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty      = 'calendarfilterId';
    
    /**
     * name of the contentcontoller class
     * 
     * @var string
     */
    protected $_contentControllerName = 'Calendar_Controller_MSEventFacade';

    /**
     * instance of the content specific controller
     *
     * @var Calendar_Controller_MSEventFacade
     */
    protected $_contentController;

    protected $_defaultContainerPreferenceName = Calendar_Preference::DEFAULTCALENDAR;
    
    /**
     * list of devicetypes with wrong busy status default (0 = FREE)
     * 
     * @var array
     */
    protected $_devicesWithWrongBusyStatusDefault = array(
        'samsunggti9100', // Samsung Galaxy S-2
        'samsunggtn7000', // Samsung Galaxy Note 
        'samsunggti9300', // Samsung Galaxy S-3
    );

    /**
     * folder id which is currenttly synced
     *
     * @var string
     */
    protected $_syncFolderId = null;

    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::__construct()
     */
    public function __construct(Syncroton_Model_IDevice $_device, DateTime $_syncTimeStamp)
    {
        parent::__construct($_device, $_syncTimeStamp);
        
        $this->_contentController->setEventFilter($this->_getContentFilter(0));
    }
    
    /**
     * (non-PHPdoc)
     * @see Syncroton_Data_IDataCalendar::setAttendeeStatus()
     */
    public function setAttendeeStatus(Syncroton_Model_MeetingResponse $response)
    {
        try {
            $event = $instance = $this->_contentController->get($response->requestId);
        } catch (Tinebase_Exception_NotFound $eventNotFound) {
            throw new Syncroton_Exception_Status_MeetingResponse("event not found", Syncroton_Exception_Status_MeetingResponse::MEETING_ERROR);
        }
        $method = 'attenderStatusUpdate';
        
        if ($response->instanceId instanceof DateTime) {
            $recurId = $event->uid . '-' . $response->instanceId->format(Tinebase_Record_Abstract::ISO8601LONG);
            $instance = $event->exdate->filter('recurid', $recurId)->getFirstRecord();
            if (! $instance) {
                $exceptions = $event->exdate;
                $event->exdate = $exceptions->getOriginalDtStart();

                /** @var Calendar_Model_Event $instance */
                $instance = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, new Tinebase_DateTime($response->instanceId));
                if (!$instance || !$instance->setRecurId($event->getId()) || $instance->recurid !== $recurId) {
                    throw new Syncroton_Exception_Status_MeetingResponse("event instance not found", Syncroton_Exception_Status_MeetingResponse::MEETING_ERROR);
                }
            }
            
            $method = 'attenderStatusCreateRecurException';
        }
        
        $attendee = Calendar_Model_Attender::getOwnAttender($instance->attendee);
        if (! $attendee) {
            throw new Syncroton_Exception_Status_MeetingResponse("party crushing not allowed", Syncroton_Exception_Status_MeetingResponse::INVALID_REQUEST);
        }
        if (isset($this->_meetingResponseAttendeeStatusMapping[$response->userResponse])) {
            $attendee->status = $this->_meetingResponseAttendeeStatusMapping[$response->userResponse];
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Status not supported: ' . $response->userResponse);
        }
        
        Calendar_Controller_Event::getInstance()->$method($instance, $attendee, $attendee->status_authkey);
        
        // return id of calendar event
        return $response->requestId;
    }

    /**
     * (non-PHPdoc)
     * @see ActiveSync_Frontend_Abstract::toSyncrotonModel()
     * @todo handle BusyStatus
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        /** @var Calendar_Model_Event $entry */
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " calendar data " . print_r($entry->toArray(), true));

        // fill attendee cache ATTENTION this is needed by some of the code below!
        Calendar_Model_Attender::resolveAttendee($entry->attendee, FALSE);
        
        $syncrotonEvent = new Syncroton_Model_Event();
        $syncrotonEvent->meetingStatus = $this->_getMeetingStatus($entry);
        $syncrotonEvent->responseType = $this->_getResponseType($entry);
        if (5 === $syncrotonEvent->responseType) {
            $syncrotonEvent->responseRequested = true;
        } else {
            $syncrotonEvent->responseRequested = false;
        }
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if ($this->_isEmptyValue($entry->$tine20Property)) {
                // skip empty values
                continue;
            }
            
            switch ($tine20Property) {
                case 'alarms':
                    $entry->alarms->sort('alarm_time');
                    $alarm = $entry->alarms->getFirstRecord();
                    
                    if ($alarm instanceof Tinebase_Model_Alarm) {
                        // NOTE: option minutes_before is always calculated by Calendar_Controller_Event::_inspectAlarmSet
                        $minutesBefore = (int) $alarm->getOption('minutes_before');
                        
                        // avoid negative alarms which may break phones
                        if ($minutesBefore >= 0) {
                            $syncrotonEvent->$syncrotonProperty = $minutesBefore;
                        }
                    }
                    
                    break;
                    
                case 'attendee':
                    // ios < 11 could could only cope with attendee in standard calendar
                    if ($this->_device->devicetype === Syncroton_Model_Device::TYPE_IPHONE &&
                        $this->_device->getMajorVersion() < 1501 &&
                        // note: might comparing an integer with a string here (at least with pgsql)
                        $this->_syncFolderId       != $this->_getDefaultContainerId()) {
                        break;
                    }
                    
                    $attendees = array();
                
                    foreach ($entry->attendee as $attenderObject) {
                        $attendee = new Syncroton_Model_EventAttendee();
                        $attendee->name = $attenderObject->getName();
                        $attendee->email = $attenderObject->getEmail();
                        
                        $acsType = array_search($attenderObject->role, $this->_attendeeTypeMapping);
                        $attendee->attendeeType = $acsType ? $acsType : Syncroton_Model_EventAttendee::ATTENDEE_TYPE_REQUIRED;
            
                        $acsStatus = array_search($attenderObject->status, $this->_attendeeStatusMapping);
                        $attendee->attendeeStatus = $acsStatus ? $acsStatus : Syncroton_Model_EventAttendee::ATTENDEE_STATUS_UNKNOWN;
                        
                        $attendees[] = $attendee;
                    }
                    
                    $syncrotonEvent->$syncrotonProperty = $attendees;
                    
                    // set own status
                    if (($ownAttendee = Calendar_Model_Attender::getOwnAttender($entry->attendee)) !== null && ($busyType = array_search($ownAttendee->status, $this->_busyStatusMapping)) !== false) {
                        $syncrotonEvent->busyStatus = $busyType;
                    }
                    
                    break;
                    
                case 'class':
                    $syncrotonEvent->$syncrotonProperty = $entry->class == Calendar_Model_Event::CLASS_PRIVATE ? 2 : 0;
                    
                    break;
                    
                case 'description':
                    $syncrotonEvent->$syncrotonProperty = new Syncroton_Model_EmailBody(array(
                        'type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT,
                        'data' => $entry->description
                    ));
                    
                    break;
                    
                case 'dtend':
                    if ($entry->dtend instanceof Tinebase_DateTime) {
                        if ($entry->is_all_day_event == true) {
                            // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
                            $dtend = clone $entry->dtend;
                            $dtend->addSecond($dtend->get('s') == 59 ? 1 : 0);
                            $dtend->addMinute($dtend->get('i') == 59 ? 1 : 0);

                            $syncrotonEvent->$syncrotonProperty = $dtend;
                        } else {
                            $syncrotonEvent->$syncrotonProperty = $entry->dtend;
                        }
                    }
                    
                    break;
                    
                case 'dtstart':
                    if ($entry->dtstart instanceof DateTime) {
                        $syncrotonEvent->$syncrotonProperty = $entry->dtstart;
                    }
                    
                    break;
                    
                case 'exdate':
                    // handle exceptions of repeating events
                    if ($entry->exdate instanceof Tinebase_Record_RecordSet && $entry->exdate->count() > 0) {
                        $exceptions = array();

                        /** @var Calendar_Model_Event $exdate */
                        foreach ($entry->exdate as $exdate) {
                            $exception = new Syncroton_Model_EventException();
                            
                            // send the Deleted element only, when needed
                            // HTC devices ignore the value(0 or 1) of the Deleted element
                            if ((int)$exdate->is_deleted === 1) { 
                                $exception->deleted        = 1;

                                // make sure event status is cancled for meetingStatus calculation
                                $exdate->status = Calendar_Model_Event::STATUS_CANCELED;

                                // None. The user's response to the meeting has not yet been received.
                                $exception->responseType = 0;
                            } else {
                                $exception->responseType = $this->_getResponseType($exdate);
                            }

                            if (5 === $exception->responseType) {
                                $exception->responseRequested = true;
                            } else {
                                $exception->responseRequested = false;
                            }
                            $exception->meetingStatus = $this->_getMeetingStatus($exdate);
                            $exception->exceptionStartTime = $exdate->getOriginalDtStart();
                            
                            if ((int)$exdate->is_deleted === 0) {
                                $exceptionSyncrotonEvent = $this->toSyncrotonModel($exdate);
                                foreach ($exception->getProperties() as $property) {
                                    if (isset($exceptionSyncrotonEvent->$property)) {
                                        $exception->$property = $exceptionSyncrotonEvent->$property;
                                    }
                                }
                                unset($exceptionSyncrotonEvent);
                            }
                            
                            $exceptions[] = $exception;
                        }
                        
                        $syncrotonEvent->$syncrotonProperty = $exceptions;
                    }
                    
                    break;
                    
                case 'rrule':
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . " calendar rrule " . $entry->rrule);
                        
                    $rrule = Calendar_Model_Rrule::getRruleFromString($entry->rrule);
                    
                    $recurrence = new Syncroton_Model_EventRecurrence();
                    
                    // required fields
                    switch ($rrule->freq) {
                        case Calendar_Model_Rrule::FREQ_DAILY:
                        case Calendar_Model_Rrule::FREQ_INDIVIDUAL:
                            $recurrence->type = Syncroton_Model_EventRecurrence::TYPE_DAILY;
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_WEEKLY:
                            $recurrence->type      = Syncroton_Model_EventRecurrence::TYPE_WEEKLY;
                            $recurrence->dayOfWeek = $this->_convertDayToBitMask($rrule->byday);
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_MONTHLY:
                            if (!empty($rrule->bymonthday)) {
                                $recurrence->type       = Syncroton_Model_EventRecurrence::TYPE_MONTHLY;
                                $recurrence->dayOfMonth = $rrule->bymonthday;
                            } else {
                                $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                                $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth;
                                $dayOfWeek   = substr($rrule->byday, -2);
                    
                                $recurrence->type        = Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN;
                                $recurrence->weekOfMonth = $weekOfMonth;
                                $recurrence->dayOfWeek   = $this->_convertDayToBitMask($dayOfWeek);
                            }
                            
                            break;
                    
                        case Calendar_Model_Rrule::FREQ_YEARLY:
                            if (!empty($rrule->bymonthday)) {
                                $recurrence->type        = Syncroton_Model_EventRecurrence::TYPE_YEARLY;
                                $recurrence->dayOfMonth  = $rrule->bymonthday;
                                $recurrence->monthOfYear = $rrule->bymonth;
                            } else {
                                $weekOfMonth = (int) substr($rrule->byday, 0, -2);
                                $weekOfMonth = ($weekOfMonth == -1) ? 5 : $weekOfMonth;
                                $dayOfWeek   = substr($rrule->byday, -2);
                    
                                $recurrence->type        = Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN;
                                $recurrence->weekOfMonth = $weekOfMonth;
                                $recurrence->dayOfWeek   = $this->_convertDayToBitMask($dayOfWeek);
                                $recurrence->monthOfYear = $rrule->bymonth;
                            }
                            
                            break;
                    }
                    
                    // required field
                    $recurrence->interval = $rrule->interval ? $rrule->interval : 1;
                    
                    if ($rrule->count) {
                        $recurrence->occurrences = $rrule->count;
                    } elseif ($rrule->until instanceof DateTime) {
                        $recurrence->until = $rrule->until;
                    }
                    
                    $syncrotonEvent->$syncrotonProperty = $recurrence;
                    
                    break;
                    
                case 'tags':
                    $syncrotonEvent->$syncrotonProperty = $entry->tags->name;;
                    
                    break;
                    
                default:
                    $syncrotonEvent->$syncrotonProperty = $entry->$tine20Property;
                    
                    break;
            }
        }
        
        $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
            Tinebase_Core::getLogger(),
            Tinebase_Core::get(Tinebase_Core::CACHE)
        );
        
        $syncrotonEvent->timezone = $timeZoneConverter->encodeTimezone($entry->originator_tz);
        $syncrotonEvent->dtStamp = $entry->creation_time;
        $syncrotonEvent->uID = $entry->uid;
        
        $this->_addOrganizer($syncrotonEvent, $entry);
        
        return $syncrotonEvent;
    }

    /**
     * @param Calendar_Model_Event $_event
     * @return int
     */
    protected function _getResponseType(Calendar_Model_Event $_event)
    {
        $organizer = $_event->resolveOrganizer();
        // if the organizer is null we put ourselves as organizer, so it is the same as if we would be the organizer
        if (null === $organizer || Tinebase_Core::getUser()->getId() === $organizer->account_id) {
            return 1;
        } else {
            if (null === ($ownAttendee = Calendar_Model_Attender::getOwnAttender($_event->attendee))) {
                return 0;
            }
            if (empty($ownAttendee->status)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . " Own attendee status empty - set to ACCEPTED");
                $ownAttendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
            }
            switch ($ownAttendee->status) {
                case Calendar_Model_Attender::STATUS_TENTATIVE:
                    return 2;
                case Calendar_Model_Attender::STATUS_ACCEPTED:
                    return 3;
                case Calendar_Model_Attender::STATUS_DECLINED:
                    return 4;
                case Calendar_Model_Attender::STATUS_NEEDSACTION:
                    return 5;
                default:
                    $e = new Tinebase_Exception_NotImplemented('unknown attender status: ' . $ownAttendee->status);
                    Tinebase_Exception::log($e);
                    return 0;
            }
        }
    }

    /**
     * @param Calendar_Model_Event $_event
     * @return int
     */
    protected function _getMeetingStatus(Calendar_Model_Event $_event)
    {
        $organizer = $_event->resolveOrganizer();
        // if the organizer is null we put ourself as organizer, so it is the same as if we would be the organizer
        if (null === $organizer || Tinebase_Core::getUser()->getId() === $organizer->account_id) {
            if (Calendar_Model_Event::STATUS_CANCELED === $_event->status) {
                return 5;
            } else {
                return 1;
            }
        } elseif (null === Calendar_Model_Attender::getOwnAttender($_event->attendee)) {
            if (Calendar_Config::getInstance()->{Calendar_Config::ASSIGN_ORGANIZER_MEETING_STATUS_ON_EDIT_GRANT} &&
                    $_event->{Tinebase_Model_Grants::GRANT_EDIT}) {
                if (Calendar_Model_Event::STATUS_CANCELED === $_event->status) {
                    return 5;
                } else {
                    return 1;
                }
            }
            return 0;
        } else {
            if (Calendar_Model_Event::STATUS_CANCELED === $_event->status) {
                return 7;
            } else {
                return 3;
            }
        }
    }
    
    /**
     * convert string of days (TU,TH) to bitmask used by ActiveSync
     *  
     * @param string $_days
     * @return int
     */
    protected function _convertDayToBitMask($_days)
    {
        $daysArray = explode(',', $_days);
        
        $result = 0;
        
        foreach($daysArray as $dayString) {
            $result = $result + $this->_recurDayMapping[$dayString];
        }
        
        return $result;
    }
    
    /**
     * convert bitmask used by ActiveSync to string of days (TU,TH) 
     *  
     * @param int $_days
     * @return string
     */
    protected function _convertBitMaskToDay($_days)
    {
        $daysArray = array();
        
        for($bitmask = 1; $bitmask <= Syncroton_Model_EventRecurrence::RECUR_DOW_SATURDAY; $bitmask = $bitmask << 1) {
            $dayMatch = $_days & $bitmask;
            if($dayMatch === $bitmask) {
                $daysArray[] = array_search($bitmask, $this->_recurDayMapping);
            }
        }
        $result = implode(',', $daysArray);
        
        return $result;
    }
    
    /**
     * @param Syncroton_Model_IEntry $data
     * @param Tinebase_Record_Interface $entry
     * @return Tinebase_Record_Interface
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        if ($entry instanceof Calendar_Model_Event) {
            $event = $entry;
        } else {
            $event = new Calendar_Model_Event(array(), true);
        }

        if ($data instanceof Syncroton_Model_Event) {
            $data->copyFieldsFromParent();
        }
        
        // Update seq to entries seq to prevent concurrent update
        $event->seq = isset($entry['seq']) ? $entry['seq'] : null;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " Event before mapping: " . print_r($event->toArray(), true));
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if (! isset($data->$syncrotonProperty)) {
                if ($this->_device->devicetype === Syncroton_Model_Device::TYPE_IPHONE) {
                    if ($tine20Property === 'description') {
                        // @see #8230: added alarm to event on iOS 6.1 -> description removed
                        // keep description
                    } else if ($tine20Property === 'dtstart' || $tine20Property === 'dtend') {
                        // dtstart & dtend should not be nulled
                    } else if ($tine20Property === 'attendee' && $entry &&
                        $this->_device->devicetype === Syncroton_Model_Device::TYPE_IPHONE &&
                        $this->_device->getMajorVersion() < 1501 &&
                        $this->_syncFolderId != $this->_getDefaultContainerId()) {
                        // ios < 11 could could only cope with attendee in standard calendar
                        // keep attendees as the are / they were not sent to the device before
                    } else {
                        // remove the value
                        $event->$tine20Property = null;
                    }
                } else {
                    // remove the value
                    $event->$tine20Property = null;
                }
                continue;
            }
            
            switch ($tine20Property) {
                case 'alarms':
                    // handled after switch statement
                    break;
                    
                case 'attendee':
                    // ios < 11 could could only cope with attendee in standard calendar
                    if ($entry && 
                        $this->_device->devicetype === Syncroton_Model_Device::TYPE_IPHONE &&
                        $this->_device->getMajorVersion() < 1501 &&
                        $this->_syncFolderId       != $this->_getDefaultContainerId()) {

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . " keep attendees as the are / they were not sent to the device before ");

                        break;
                    }

                    $newAttendees = array();
                    
                    foreach($data->$syncrotonProperty as $attendee) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . " attendee email " . $attendee->email);
                        
                        if(isset($attendee->attendeeType) && (isset($this->_attendeeTypeMapping[$attendee->attendeeType]) || array_key_exists($attendee->attendeeType, $this->_attendeeTypeMapping))) {
                            $role = $this->_attendeeTypeMapping[$attendee->attendeeType];
                        } else {
                            $role = Calendar_Model_Attender::ROLE_REQUIRED;
                        }
                        
                        // AttendeeStatus send only on repsonse
                        $parsedName = Addressbook_Model_Contact::splitName($attendee->name);

                        $status = intval($attendee->attendeeStatus);
                        if (isset($this->_attendeeStatusMapping[$status])) {
                            $status = $this->_attendeeStatusMapping[$status];
                        } else {
                            $status = null;
                        }

                        $newAttendees[] = array(
                            'userType'  => Calendar_Model_Attender::USERTYPE_USER,
                            'firstName' => $parsedName['n_given'],
                            'lastName'  => $parsedName['n_family'],
                            'role'      => $role,
                            'partStat'  => $status,
                            'email'     => $attendee->email,
                        );
                    }
                    
                    Calendar_Model_Attender::emailsToAttendee($event, $newAttendees);
                    
                    break;
                    
                case 'class':
                    $event->$tine20Property = $data->$syncrotonProperty == 2 ? Calendar_Model_Event::CLASS_PRIVATE : Calendar_Model_Event::CLASS_PUBLIC;
                    
                    break;
                    
                case 'exdate':
                    // handle exceptions from recurrence
                    $exdates = new Tinebase_Record_RecordSet('Calendar_Model_Event');
                    $oldExdates = $event->exdate instanceof Tinebase_Record_RecordSet ? $event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
                    
                    foreach ($data->$syncrotonProperty as $exception) {
                        $eventException = $this->_getRecurException($oldExdates, $exception, $entry);

                        if ($exception->deleted == 0) {
                            $eventException = $this->toTineModel($exception, $eventException);
                            $eventException->last_modified_time = new Tinebase_DateTime($this->_syncTimeStamp);
                        }

                        $eventException->is_deleted = (bool) $exception->deleted;
                        $eventException->seq = isset($entry['seq']) ? $entry['seq'] : null;
                        $exdates->addRecord($eventException);
                    }
                    
                    $event->$tine20Property = $exdates;
                    
                    break;
                    
                case 'description':
                    // @todo check $data->$fieldName->Type and convert to/from HTML if needed
                    if ($data->$syncrotonProperty instanceof Syncroton_Model_EmailBody) {
                        $event->$tine20Property = $data->$syncrotonProperty->data;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                            __METHOD__ . '::' . __LINE__ . ' Removing description.');
                        $event->$tine20Property = null;
                    }
                
                    break;
                    
                case 'rrule':
                    // handle recurrence
                    if ($data->$syncrotonProperty instanceof Syncroton_Model_EventRecurrence && isset($data->$syncrotonProperty->type)) {
                        $rrule = new Calendar_Model_Rrule();
                    
                        switch ($data->$syncrotonProperty->type) {
                            case Syncroton_Model_EventRecurrence::TYPE_DAILY:
                                $rrule->freq = Calendar_Model_Rrule::FREQ_DAILY;
                                
                                break;
                    
                            case Syncroton_Model_EventRecurrence::TYPE_WEEKLY:
                                $rrule->freq  = Calendar_Model_Rrule::FREQ_WEEKLY;
                                $rrule->byday = $this->_convertBitMaskToDay($data->$syncrotonProperty->dayOfWeek);
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_MONTHLY:
                                $rrule->freq       = Calendar_Model_Rrule::FREQ_MONTHLY;
                                $rrule->bymonthday = $data->$syncrotonProperty->dayOfMonth;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_MONTHLY_DAYN:
                                $rrule->freq = Calendar_Model_Rrule::FREQ_MONTHLY;
                    
                                $week   = $data->$syncrotonProperty->weekOfMonth;
                                $day    = $data->$syncrotonProperty->dayOfWeek;
                                $byDay  = $week == 5 ? -1 : $week;
                                $byDay .= $this->_convertBitMaskToDay($day);
                    
                                $rrule->byday = $byDay;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_YEARLY:
                                $rrule->freq       = Calendar_Model_Rrule::FREQ_YEARLY;
                                $rrule->bymonth    = $data->$syncrotonProperty->monthOfYear;
                                $rrule->bymonthday = $data->$syncrotonProperty->dayOfMonth;
                                
                                break;
                                 
                            case Syncroton_Model_EventRecurrence::TYPE_YEARLY_DAYN:
                                $rrule->freq    = Calendar_Model_Rrule::FREQ_YEARLY;
                                $rrule->bymonth = $data->$syncrotonProperty->monthOfYear;
                    
                                $week = $data->$syncrotonProperty->weekOfMonth;
                                $day  = $data->$syncrotonProperty->dayOfWeek;
                                $byDay  = $week == 5 ? -1 : $week;
                                $byDay .= $this->_convertBitMaskToDay($day);
                    
                                $rrule->byday = $byDay;
                                
                                break;
                        }
                        
                        $rrule->interval = isset($data->$syncrotonProperty->interval) ? $data->$syncrotonProperty->interval : 1;
                    
                        if(isset($data->$syncrotonProperty->occurrences)) {
                            $rrule->count = $data->$syncrotonProperty->occurrences;
                            $rrule->until = null;
                        } else if(isset($data->$syncrotonProperty->until)) {
                            $rrule->count = null;
                            $rrule->until = $this->_convertDateTime($data->$syncrotonProperty->until);
                        } else {
                            $rrule->count = null;
                            $rrule->until = null;
                        }
                        
                        $event->rrule = $rrule;
                    }
                    
                    break;
                    
                    
                default:
                    if ($data->$syncrotonProperty instanceof DateTime) {
                        $event->$tine20Property = $this->_convertDateTime($data->$syncrotonProperty);
                    } else {
                        $event->$tine20Property = $data->$syncrotonProperty;
                    }
                    
                    break;
            }
        }
        
        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in AS
        if (isset($event->is_all_day_event) && $event->is_all_day_event == 1) {
            $event->dtend->subSecond(1);
        }
        
        // decode timezone data
        if (isset($data->timezone)) {
            $timeZoneConverter = ActiveSync_TimezoneConverter::getInstance(
                Tinebase_Core::getLogger(),
                Tinebase_Core::get(Tinebase_Core::CACHE)
            );
        
            try {
                $timezone = $timeZoneConverter->getTimezone(
                    $data->timezone,
                    Tinebase_Core::getUserTimezone()
                );
                $event->originator_tz = $timezone;
            } catch (ActiveSync_TimezoneNotFoundException $e) {
                Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . " timezone data not found " . $data->timezone);
                $event->originator_tz = Tinebase_Core::getUserTimezone();
            }
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " timezone data " . $event->originator_tz);
        }
        
        $this->_handleAlarms($data, $event);
        
        $this->_handleBusyStatus($data, $event);
        
        // event should be valid now
        $event->isValid();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . " eventData " . print_r($event->toArray(), true));

        return $event;
    }

    protected function _convertDateTime($syncrotonDateTime)
    {
        if ($syncrotonDateTime instanceof DateTime) {
            // convert TZ from type 2 ('Z') to type 3 ('UTC')
            // TODO do we need to do this for more TZs?
            $tz = $syncrotonDateTime->getTimezone()->getName() === 'Z' ? new DateTimeZone('UTC') : null;
        } else {
            $tz = null;
        }
        return new Tinebase_DateTime($syncrotonDateTime, $tz);
    }
    
    /**
     * handle alarms / Reminder
     * 
     * @param SimpleXMLElement $data
     * @param Calendar_Model_Event $event
     */
    protected function _handleAlarms($data, $event)
    {
        // NOTE: existing alarms are already filtered for CU by MSEF
        $event->alarms = $event->alarms instanceof Tinebase_Record_RecordSet ? $event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $event->alarms->sort('alarm_time');
        
        $currentAlarm = $event->alarms->getFirstRecord();
        $alarm = NULL;
        
        if (isset($data->reminder)) {
            $dtstart = clone $event->dtstart;
            
            $alarm = new Tinebase_Model_Alarm(array(
                'alarm_time'        => $dtstart->subMinute($data->reminder),
                'minutes_before'    => in_array($data->reminder, array(0, 5, 15, 30, 60, 120, 720, 1440, 2880)) ? $data->reminder : 'custom',
                'model'             => 'Calendar_Model_Event'
            ));
            
            $alarmUpdate = Calendar_Controller_Alarm::getMatchingAlarm($event->alarms, $alarm);
            if (!$alarmUpdate) {
                // alarm not existing -> add it
                $event->alarms->addRecord($alarm);
                
                if ($currentAlarm) {
                    // ActiveSync supports one alarm only -> current got deleted
                    $event->alarms->removeRecord($currentAlarm);
                }
            }
        } else if ($currentAlarm) {
            // current alarm got removed
            $event->alarms->removeRecord($currentAlarm);
        }
    }

    /**
     * find a matching exdate or return an empty event record
     *
     * @param  Tinebase_Record_RecordSet        $oldExdates
     * @param  Syncroton_Model_EventException   $sevent
     * @return Calendar_Model_Event
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $oldExdates, Syncroton_Model_EventException $sevent,
        Calendar_Model_Event $baseEvent = null)
    {
        // we need to use the user timezone here if this is a DATE (like this: RECURRENCE-ID;VALUE=DATE:20140429)
        $originalDtStart = new Tinebase_DateTime($sevent->exceptionStartTime);

        foreach ($oldExdates as $id => $oldExdate) {
            if ($originalDtStart == $oldExdate->getOriginalDtStart()) {
                return $oldExdate;
            }
        }

        return new Calendar_Model_Event(array(
            'recurid'    => $originalDtStart,
            'attendee'   => $baseEvent ? $baseEvent->attendee : null,
        ));
    }

    /**
     * append organizer name and email
     *
     * @param Syncroton_Model_Event $syncrotonEvent
     * @param Calendar_Model_Event $event
     */
    protected function _addOrganizer(Syncroton_Model_Event $syncrotonEvent, Calendar_Model_Event $event)
    {
        $organizer = NULL;
        
        if(! empty($event->organizer)) {
            try {
                $organizer = $event->resolveOrganizer();
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " " . $tead);
            }
        }
    
        if ($organizer instanceof Addressbook_Model_Contact) {
            $organizerName = $organizer->n_fileas;
            $organizerEmail = $organizer->getPreferredEmailAddress();
        } else {
            // set the current account as organizer
            // if organizer is not set, you can not edit the event on the Motorola Milestone
            $organizerName = Tinebase_Core::getUser()->accountFullName;
            $organizerEmail = Tinebase_Core::getUser()->accountEmailAddress;
        }
    
        $syncrotonEvent->organizerName = $organizerName;
        if ($organizerEmail) {
            $syncrotonEvent->organizerEmail = $organizerEmail;
        }
    }
    
    /**
     * set status of own attender depending on BusyStatus
     * 
     * @param SimpleXMLElement $data
     * @param Calendar_Model_Event $event
     * 
     * @todo move detection of special handling / device type to device library
     */
    protected function _handleBusyStatus($data, $event)
    {
        if (! isset($data->busyStatus)) {
            return;
        }
        
        $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        if ($ownAttender === NULL) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' No own attender found.');
            return;
        }
        
        $busyStatus = $data->busyStatus;
        if (in_array(strtolower($this->_device->devicetype), $this->_devicesWithWrongBusyStatusDefault)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Device uses a bad default setting. BUSY and FREE are mapped to ACCEPTED.');
            $busyStatusMapping = array(
                Syncroton_Model_Event::BUSY_STATUS_BUSY      => Calendar_Model_Attender::STATUS_ACCEPTED,
                Syncroton_Model_Event::BUSY_STATUS_TENATTIVE => Calendar_Model_Attender::STATUS_TENTATIVE,
                Syncroton_Model_Event::BUSY_STATUS_FREE      => Calendar_Model_Attender::STATUS_ACCEPTED
            );
        } else {
            $busyStatusMapping = $this->_busyStatusMapping;
        }
        
        if (isset($busyStatusMapping[$busyStatus])) {
            $ownAttender->status = $busyStatusMapping[$busyStatus];
        } else {
            $ownAttender->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
    }
    
    /**
     * convert contact from xml to Calendar_Model_EventFilter
     *
     * @param SimpleXMLElement $_data
     * @return array
     */
    protected function _toTineFilterArray(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('uri:Calendar');
        
        $filterArray = array();
        
        foreach($this->_mapping as $fieldName => $field) {
            if(isset($xmlData->$fieldName)) {
                switch ($field) {
                    case 'dtend':
                    case 'dtstart':
                        $value = new Tinebase_DateTime((string)$xmlData->$fieldName);
                        break;
                        
                    default:
                        $value = (string)$xmlData->$fieldName;
                        break;
                        
                }
                $filterArray[] = array(
                    'field'     => $field,
                    'operator'  => 'equals',
                    'value'     => $value
                );
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * return contentfilter array
     * 
     * @param  int $_filterType
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getContentFilter($_filterType)
    {
        $filter = parent::_getContentFilter($_filterType);
        
        // no persistent filter set -> add default filter
        // NOTE: we use attender+status as devices always show declined events
        if ($filter->isEmpty()) {
            $attendeeFilter = $filter->createFilter('attender', 'equals', array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => Tinebase_Core::getUser()->contact_id,
            ));
            $statusFilter = $filter->createFilter('attender_status', 'notin', array(
                Calendar_Model_Attender::STATUS_DECLINED
            ));
            $containerFilter = $filter->createFilter('container_id', 'equals', array(
                'path' => '/personal/' . Tinebase_Core::getUser()->getId()
            ));
            
            $filter->addFilter($attendeeFilter);
            $filter->addFilter($statusFilter);
            $filter->addFilter($containerFilter);
        }

        // don't return more than the previous 6 months
        $from = Tinebase_DateTime::now()->subMonth(6);
        if (in_array($_filterType, $this->_filterArray)) {
            switch($_filterType) {
                case Syncroton_Command_Sync::FILTER_2_WEEKS_BACK:
                    $from = Tinebase_DateTime::now()->subWeek(2);
                    break;
                case Syncroton_Command_Sync::FILTER_1_MONTH_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(2);
                    break;
                case Syncroton_Command_Sync::FILTER_3_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(3);
                    break;
                case Syncroton_Command_Sync::FILTER_6_MONTHS_BACK:
                    $from = Tinebase_DateTime::now()->subMonth(6);
                    break;
            }
        }
        
        // next 10 years
        $to = Tinebase_DateTime::now()->addYear(10);
        
        // remove all 'old' period filters
        $filter->removeFilter('period');
        
        // add period filter
        $filter->addFilter(new Calendar_Model_PeriodFilter('period', 'within', array(
            'from'  => $from,
            'until' => $to
        )));
        
        return $filter;
    }

    /**
     * 
     * @return int     Syncroton_Command_Sync::FILTER...
     */
    public function getMaxFilterType()
    {
        return ActiveSync_Config::getInstance()->get(ActiveSync_Config::MAX_FILTER_TYPE_CALENDAR);
    }

    /**
     * NOTE: calendarFilter is based on contentFilter for ActiveSync
     *
     * @param string|Tinebase_Model_Container $folderId
     */
    protected function _assertContentControllerParams($folderId)
    {
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($folderId);
        } catch (Exception $e) {
            $containerId = Tinebase_Core::getPreference('ActiveSync')->{$this->_defaultFolder};
            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
        }

        $this->_syncFolderId = $container->getId();
        Calendar_Controller_MSEventFacade::getInstance()->assertEventFacadeParams($container, false);
    }
}
