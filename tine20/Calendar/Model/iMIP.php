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
 * @property    string                  $id               message <id>_<part> of iMIP mail part
 * @property    string                  $ics              ical string in UTF8
 * @property    ?Calendar_Model_Event   $event            iMIP message event
 * @property    string                  $method           method of iMIP transaction
 * @property    string                  $userAgent        userAgent origination iMIP transaction
 * @property    string                  $originator       originator /sender of iMIP transaction
 * @property    array                   $preconditions     array of checked processing preconditions
 * @property    ?Calendar_Model_Event   $existing_event
 * @property    bool                    $preconditionsChecked
 *
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
    public const FLD_PRECONDITIONS = 'preconditions';
    public const FLD_PRECONDITIONS_CHECKED = 'preconditionsChecked';

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
            self::FLD_ICS           => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_METHOD        => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_ORIGINATOR    => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_USER_AGENT    => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_EVENT         => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_EXISTING_EVENT => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_PRECONDITIONS => [
                self::TYPE              => self::TYPE_STRING,
            ],
            self::FLD_PRECONDITIONS_CHECKED => [
                self::TYPE              => self::TYPE_STRING,
            ],
        ],
    ];

    /**
     * (non-PHPdoc)
     * @throws Tinebase_Exception_Record_Validation
     * @see Tinebase_Record_Abstract::__set()
     */
    public function __set($_name, $_value)
    {
        if ($_name == self::FLD_ICS) {
            unset($this->{self::FLD_EVENT});
        }
        if ($_name == self::FLD_METHOD) {
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
        if ($_name == self::FLD_METHOD && !$this->_data[self::FLD_METHOD] && $this->_data[self::FLD_ICS]) {
            $this->getEvent();
        }
        
        return parent::__get($_name);
    }
    
    /**
     * @throws Tinebase_Exception_Record_NotDefined
     */
    public function getEvent(): Calendar_Model_Event
    {
        if (! $this->{self::FLD_EVENT} instanceof Calendar_Model_Event) {
            if (! $this->{self::FLD_ICS}) {
                throw new Tinebase_Exception_Record_NotDefined('ics is needed to generate event');
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Incoming iMIP ics \n"
                    . $this->{self::FLD_ICS});
            }

            $this->{self::FLD_EVENT} = $this->_getConverter()->toTine20Model($this->{self::FLD_ICS});

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Event: ' . print_r($this->{self::FLD_EVENT}->toArray(), true));
            }
            
            if (! $this->{self::FLD_METHOD}) {
                $this->{self::FLD_METHOD} = $this->_getConverter()->getMethod($this->{self::FLD_ICS});
            }
        }
        
        return $this->{self::FLD_EVENT};
    }

    /**
     * merge ics data into given event
     */
    public function mergeEvent(Calendar_Model_Event $_event): Calendar_Model_Event
    {
        return $this->_getConverter()->toTine20Model($this->{self::FLD_ICS}, $_event);
    }
    
    /**
     * get ics converter
     */
    protected function _getConverter(): Calendar_Convert_Event_VCalendar_Abstract
    {
        if (! $this->_converter) {
            $this->_converter = Calendar_Convert_Event_VCalendar_Factory::factory(
                ...Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($this->userAgent)
            );
        }
        
        return $this->_converter;
    }

    public function addFailedPrecondition(string $_preconditionName, string $_message): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . " Preconditions check failed for " . $_preconditionName . ' with message: ' . $_message);
        
        $this->_addPrecondition($_preconditionName, $_message);
    }
    
    /**
     * add precondition
     */
    protected function _addPrecondition(string $_preconditionName, string $_message): void
    {
        $preconditions = (is_array($this->{self::FLD_PRECONDITIONS})) ? $this->{self::FLD_PRECONDITIONS} : array();
        
        if (! isset($preconditions[$_preconditionName])) {
            $preconditions[$_preconditionName] = array();
        }
        
        $preconditions[$_preconditionName][] = array(
            'check'     => false,
            'message'    => $_message,
        );
        
        $this->{self::FLD_PRECONDITIONS} = $preconditions;
    }

    protected ?Calendar_Convert_Event_VCalendar_Abstract $_converter = null;
}
