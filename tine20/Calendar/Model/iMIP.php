<?php
/**
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an iMIP (RFC 6047) Message
 *
 * @property    string                      $id                     message <id>_<part> of iMIP mail part
 * @property    string                      $ics                    ical string in UTF8
 * @property    ?Calendar_Model_Event       $event
 * @property    Tinebase_Record_RecordSet<Calendar_Model_Event>   $events                 Tinebase_Record_RecordSet iMIP message events
 * @property    ?Calendar_Model_Event       $existing_event
 * @property    array                       $existing_events
 * @property    string                      $method                 method of iMIP transaction
 * @property    string                      $userAgent              userAgent origination iMIP transaction
 * @property    string                      $originator             riginator /sender of iMIP transaction
 * @property    array                       $preconditions          checked processing preconditions
 * @property    array                       $preconditionsChecked   checked event recurIdOrUids
 * @property    array                       $attendeeContainersAvailable
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_iMIP extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'iMIP';

    /**
     * Used to publish an iCalendar object to one or more "Calendar Users".  
     * There is no interactivity between the publisher and any  other 
     * "Calendar User".
     */
    const METHOD_PUBLISH        = 'PUBLISH';
    
    /**
     * Used to schedule an iCalendar object with other "Calendar Users".  
     * Requests are interactive in that they require the receiver to 
     * respond using the reply methods.  Meeting requests, busy-time 
     * requests, and the assignment of tasks to other "Calendar Users" 
     * are all examples.  Requests are also used by the Organizer to 
     * update the status of an iCalendar object. 
     */
    const METHOD_REQUEST        = 'REQUEST';
    
    /**
     * A reply is used in response to a request to convey Attendee 
     * status to the Organizer. Replies are commonly used to respond 
     * to meeting and task requests. 
     */
    const METHOD_REPLY          = 'REPLY';
    
    /**
     * Add one or more new instances to an existing recurring iCalendar object.
     */
    const METHOD_ADD            = 'ADD';
    
    /**
     * Cancel one or more instances of an existing iCalendar object.
     */
    const METHOD_CANCEL         = 'CANCEL';
    
    /**
     * Used by an Attendee to request the latest version of an iCalendar object.
     */
    const METHOD_REFRESH        = 'REFRESH';
    
    /**
     * Used by an Attendee to negotiate a change in an iCalendar object.
     * Examples include the request to change a proposed event time or 
     * change the due date for a task.
     */
    const METHOD_COUNTER        = 'COUNTER';
    
    /**
     * Used by the Organizer to decline the proposedcounter proposal
     */
    const METHOD_DECLINECOUNTER = 'DECLINECOUNTER';
    
    /**
     * precondition that originator of iMIP is also:
     * 
     * organizer for PUBLISH/REQUEST/ADD/CANCEL/DECLINECOUNTER
     * attendee  for REPLY/REFRESH/COUNTER
     */
    const PRECONDITION_ORIGINATOR = 'ORIGINATOR';
    
    /**
     * precondition iMIP message is more recent than event stored in calendar backend
     */
    const PRECONDITION_RECENT     = 'RECENT';
    
    /**
     * precondition that current user is event attendee
     * 
     * for REQUEST/DECLINECOUNTER
     */
    const PRECONDITION_ATTENDEE   = 'ATTENDEE';
    
    /**
     * precondition that iMIP message is not already processed
     */
    const PRECONDITION_TOPROCESS = 'TOPROCESS';
    
    /**
     * precondition that event has an organizer
     */
    const PRECONDITION_ORGANIZER  = 'ORGANIZER';
    
    /**
     * precondition that method is supported
     */
    const PRECONDITION_SUPPORTED  = 'SUPPORTED';
    
    /**
     * precondition that event exists
     */
    const PRECONDITION_EVENTEXISTS  = 'EVENTEXISTS';
    
    /**
     * precondition that event is not deleted
     */
    const PRECONDITION_NOTDELETED     = 'NOTDELETED';

    /**
     * precondition that event is not cancelled
     */
    const PRECONDITION_NOTCANCELLED     = 'NOTCANCELLED';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;


    public const FLD_ICS = 'ics';
    public const FLD_METHOD = 'method';
    public const FLD_ORIGINATOR = 'originator';
    public const FLD_USER_AGENT = 'userAgent';
    public const FLD_EVENT = 'event';
    public const FLD_EXISTING_EVENT = 'existing_event';
    public const FLD_EVENTS = 'events';
    public const FLD_EXISTING_EVENTS = 'existing_events';
    public const FLD_PRECONDITIONS = 'preconditions';
    public const FLD_PRECONDITIONS_CHECKED = 'preconditionsChecked';
    public const FLD_ATTENDEE_CONTAINERS_AVAILABLE = 'attendeeContainersAvailable';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::HAS_XPROPS        => true,

        self::APP_NAME          => Calendar_Config::APP_NAME,
        self::MODEL_NAME        => self::MODEL_NAME_PART,

        self::FIELDS            => [
            self::FLD_ICS               => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_METHOD            => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_ORIGINATOR        => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_USER_AGENT        => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_EVENT             => [
                self::TYPE                  => self::TYPE_RECORD,
            ],
            self::FLD_EXISTING_EVENT    => [
                self::TYPE                  => self::TYPE_RECORD,
            ],
            self::FLD_EVENTS            => [
                self::TYPE                  => self::TYPE_RECORDS,
            ],
            self::FLD_EXISTING_EVENTS   => [
                self::TYPE                  => self::TYPE_JSON,
            ],
            self::FLD_PRECONDITIONS     => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_PRECONDITIONS_CHECKED => [
                self::TYPE                  => self::TYPE_STRING,
            ],
            self::FLD_ATTENDEE_CONTAINERS_AVAILABLE => [
                self::TYPE                  => self::TYPE_JSON,
            ],
        ],
    ];

    protected ?Calendar_Convert_Event_VCalendar2_Interface $_converter = null;

    protected array $_aggregatedAttendees = [];

    /**
     * (non-PHPdoc)
     * @throws Tinebase_Exception_Record_Validation
     * @see Tinebase_Record_Abstract::__set()
     */
    public function __set($_name, $_value)
    {
        if ($_name === self::FLD_ICS) {
            unset($this->{self::FLD_EVENTS});
        }
        if ($_name === self::FLD_METHOD) {
            if (empty($_value)) {
                $_value = self::METHOD_REQUEST;
            } else {
                $_value = trim(strtoupper($_value));
            }
        }
        
        parent::__set($_name, $_value);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::__get()
     * @throws Tinebase_Exception_Record_NotDefined
     */
    public function __get($_name) {
        if ($_name === self::FLD_METHOD && !$this->_data[self::FLD_METHOD] && $this->_data[self::FLD_ICS]) {
            $this->getEvents();
        }
        
        return parent::__get($_name);
    }

    /**
     * @return Tinebase_Record_RecordSet<Calendar_Model_Event>
     */
    public function getEvents(): Tinebase_Record_RecordSet
    {
        if (! $this->events instanceof Tinebase_Record_RecordSet) {
            if (! $this->ics) {
                throw new Tinebase_Exception_Record_NotDefined('ics is needed to generate event');
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Incoming iMIP ics \n"
                    . $this->{self::FLD_ICS});
            }

            $this->events = $this->_getConverter()->toTine20Models($this->ics);
            $this->existing_events = [];
            $this->event = null;
            $this->existing_event = null;
            $this->attendeeContainersAvailable = null;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Events: ' . print_r($this->events->toArray(), true));
            }
            
            if (null !== $this->_getConverter()->getMethod()) {
                $this->method = $this->_getConverter()->getMethod();
            }
        }
        
        return $this->events;
    }

    /**
     * @param Tinebase_Record_RecordSet<Calendar_Model_Event> $events
     * @return Tinebase_Record_RecordSet<Calendar_Model_Event>
     */
    public function mergeEvents(Tinebase_Record_RecordSet $events): Tinebase_Record_RecordSet
    {
        if (! $this->ics) {
            throw new Tinebase_Exception_Record_NotDefined('ics is needed to generate event');
        }
        return $this->_getConverter()->toTine20Models($this->ics, mergeEvents: $events);
    }


    public function getExistingEvent(Calendar_Model_Event $_event, bool $_refetch = false, bool $_getDeleted = false): ?Calendar_Model_Event
    {
        $recurIdOrUid = $_event->getRecurIdOrUid();
        if ($_refetch || !array_key_exists($recurIdOrUid, $this->existing_events ?? [])) {
            $event = Calendar_Controller_MSEventFacade::getInstance()->getExistingEventByUID($_event->uid,
                $recurIdOrUid !== $_event->uid ? $recurIdOrUid : null,
                Tinebase_Controller_Record_Abstract::ACTION_GET, Tinebase_Model_Grants::GRANT_READ, $_getDeleted);

            if (null !== $event) {
                Calendar_Model_Attender::resolveAttendee($event->attendee, true, $event);
            }

            $this->xprops('existing_events')[$recurIdOrUid] = $event;
        }

        if (!$_getDeleted && $this->existing_events[$recurIdOrUid] && $this->existing_events[$recurIdOrUid]->is_deleted) {
            return null;
        }

        return $this->existing_events[$recurIdOrUid];
    }

    protected function _getConverter(): Calendar_Convert_Event_VCalendar2_Interface
    {
        if (! $this->_converter) {
            list($backend, $version) = Calendar_Convert_Event_VCalendar2_Factory::parseUserAgent((string)$this->userAgent);
            $this->_converter = Calendar_Convert_Event_VCalendar2_Factory::factory($backend, $version);
        }
        
        return $this->_converter;
    }

    public function addFailedPrecondition(string $key, string $_preconditionName, string $_message): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . " Preconditions check failed for " . $_preconditionName . ' for ' . $key . ' with message: ' . $_message);

        $this->_addPrecondition($key, $_preconditionName, false, $_message);
    }

    public function addSuccessfulPrecondition(string $key, string $_preconditionName)
    {
        $this->_addPrecondition($key, $_preconditionName, true);
    }
    
    /**
     * add precondition
     */
    protected function _addPrecondition(string $key, string $_preconditionName, bool $_check, ?string $_message = null)
    {
        $preconditions = (is_array($this->preconditions)) ? $this->preconditions : [];
        
        $preconditions[$key][$_preconditionName][] = [
            'check'     => $_check,
            'message'    => $_message,
        ];
        
        $this->{self::FLD_PRECONDITIONS} = $preconditions;
    }

    /**
     * @param Tinebase_Record_RecordSet<Calendar_Model_Attender> $attendees
     * @return void
     */
    public function aggregateInternalAttendees(Tinebase_Record_RecordSet $attendees): void
    {
        foreach ($attendees as $attendee) {
            $key = $attendee->user_type . $attendee->user_id;
            if ($this->_aggregatedAttendees[$key] ?? false) {
                continue;
            }
            if (Calendar_Model_Attender::USERTYPE_RESOURCE === $attendee->user_type) {
                $this->_aggregatedAttendees[$key] = [$attendee->displaycontainer_id];
            } elseif (Calendar_Model_Attender::USERTYPE_USER === $attendee->user_type && $attendee->user_id instanceof Addressbook_Model_Contact
                    && $attendee->user_id->account_id) {
                $this->_aggregatedAttendees[$key] = Tinebase_Container::getInstance()->getPersonalContainer(
                    Tinebase_Core::getUser(), Calendar_Model_Event::class, $attendee->user_id->account_id,
                    Tinebase_Model_Grants::GRANT_ADD)->asArray();
            }
        }
    }

    public function finishInternalAttendeeAggregation(): void
    {
        $this->{self::FLD_ATTENDEE_CONTAINERS_AVAILABLE} = $this->_aggregatedAttendees;
        $this->_aggregatedAttendees = [];
    }
}
