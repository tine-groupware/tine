<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Facade for Calendar_Controller_Event
 * 
 * Adopts Tine 2.0 internal event representation to the iTIP (RFC 5546) representations
 * 
 * In iTIP event exceptions are tranfered together/supplement with/to their baseEvents.
 * So with this facade event exceptions are part of the baseEvent and stored in their exdate property:
 * -> Tinebase_Record_RecordSet Calendar_Model_Event::exdate
 * 
 * deleted recur event instances (fall outs) have the property:
 * -> Calendar_Model_Event::is_deleted set to TRUE (MSEvents)
 * 
 * when creating/updating events, make sure to have the original start time (ExceptionStartTime)
 * of recur event instances stored in the property:
 * -> Calendar_Model_Event::recurid
 * 
 * In iTIP Event handling is based on the perspective of a certain user. This user is the 
 * current user per default, but can be switched with
 * Calendar_Controller_MSEventFacade::setCalendarUser(Calendar_Model_Attender $_calUser)
 * 
 * @package     Calendar
 * @subpackage  Controller
 *
 * @implements Tinebase_Controller_Record_Interface<Calendar_Model_Event>
 */
class Calendar_Controller_MSEventFacade implements Tinebase_Controller_Record_Interface
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_eventController = NULL;
    
    /**
     * @var Calendar_Model_Attender
     */
    protected $_calendarUser = NULL;
    
    /**
     * @var Calendar_Model_EventFilter
     */
    protected $_eventFilter = NULL;
    
    /**
     * @var Calendar_Controller_MSEventFacade
     */
    private static $_instance = NULL;
    
    protected static $_attendeeEmailCache = array();

    protected $_currentEventFacadeContainer;

    /**
     * @var bool
     */
    protected $_useExternalIdUid = false;

    /**
     * @var bool
     */
    protected $_assertCalUserAttendee = true;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_eventController = Calendar_Controller_Event::getInstance();
        
        // set default CU
        $this->setCalendarUser(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => self::getCurrentUserContactId()
        )));
    }

    /**
     * don't clone. Use the singleton.
     */
    private function __clone() 
    {
        
    }
    
    /**
     * singleton
     *
     * @return Calendar_Controller_MSEventFacade
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_MSEventFacade();
        }
        return self::$_instance;
    }

    public static function unsetInstance()
    {
        self::$_instance = null;
    }
    
    /**
     * get user contact id
     * - NOTE: creates a new user contact on the fly if it did not exist before
     * 
     * @return string
     */
    public static function getCurrentUserContactId()
    {
        $currentUser = Tinebase_Core::getUser();

        if (empty($currentUser->contact_id)) {
            if ($currentUser instanceof Tinebase_Model_FullUser) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Creating user contact for ' . $currentUser->accountDisplayName . ' on the fly ...');
                Addressbook_Controller_Contact::getInstance()->inspectAddUser($currentUser, $currentUser);
                $currentUser = Tinebase_User::getInstance()->getFullUserById($currentUser->getId());
            }
        }

        return $currentUser->contact_id;
    }

    public function get($_id, bool $_getDeleted = false): Calendar_Model_Event
    {
        $event = $this->_eventController->get($_id, _getDeleted: $_getDeleted);
        $this->_resolveData($event);
        
        return $this->_toiTIP($event);
    }

    /**
     * Returns a set of events identified by their id's
     *
     * @param $_ids
     * @param bool $_ignoreACL
     * @param Tinebase_Record_Expander $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     * @internal param array $array of record identifiers
     */
    public function getMultiple($_ids, $_ignoreACL = false, ?\Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $_ids)
        ));
        return $this->search($filter);
    }
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        $filter = new Calendar_Model_EventFilter();
        $pagination = new Tinebase_Model_Pagination(array(
            'sort' => $_orderBy,
            'dir'  => $_orderDirection
        ));
        return $this->search($filter, $pagination);
    }
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup             $_filter
     * @param Tinebase_Model_Pagination                     $_pagination
     * @param bool                                          $_getRelations
     * @param boolean                                       $_onlyIds
     * @param string                                        $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(?\Tinebase_Model_Filter_FilterGroup $_filter = NULL, ?\Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $events = $this->getExdateResolvedEvents($_filter, $_action);

        if ($_pagination instanceof Tinebase_Model_Pagination && ($_pagination->start || $_pagination->limit) ) {
            $eventIds = $events->id;
            $numEvents = count($eventIds);
            
            $offset = min($_pagination->start, $numEvents);
            $length = min($_pagination->limit, $offset+$numEvents);
            
            $eventIds = array_slice($eventIds, $offset, $length);
            $eventSlice = new Tinebase_Record_RecordSet('Calendar_Model_Event');
            foreach($eventIds as $eventId) {
                $eventSlice->addRecord($events->getById($eventId));
            }
            $events = $eventSlice;
        }
        
        if (! $_onlyIds) {
            // NOTE: it would be correct to wrap this with the search filter, BUT
            //       this breaks webdasv as it fetches its events with a search id OR uid.
            //       ActiveSync sets its syncfilter generically so it's not problem either
//             $oldFilter = $this->setEventFilter($_filter);
            $events = $this->_toiTIP($events);
//             $this->setEventFilter($oldFilter);
        }
        
        return $_onlyIds ? $events->id : $events;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * NOTE: we don't count exceptions where the user has no access to base event here
     *       so the result might not be precise
     *       
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get') 
    {
        $eventIds = $this->getExdateResolvedEvents($_filter, $_action);
        
        return count ($eventIds);
    }
    
    /**
     * fetches all events and sorts exceptions into exdate prop for given filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string                            $action
     */
    public function getExdateResolvedEvents($_filter, $_action)
    {
        if (! $_filter instanceof Calendar_Model_EventFilter) {
            $_filter = new Calendar_Model_EventFilter();
        }

        $events = $this->_eventController->search($_filter, NULL, FALSE, FALSE, $_action);

        // if an id filter is set, we need to fetch exceptions in a second query
        if ($_filter->getFilter('id', true, true)) {
            $events->merge($this->_eventController->search(new Calendar_Model_EventFilter(array(
                array('field' => 'base_event_id', 'operator' => 'in',      'value' => $events->id),
                array('field' => 'id',            'operator' => 'notin',   'value' => $events->id),
                array('field' => 'recurid',       'operator' => 'notnull', 'value' => null),
            )), NULL, FALSE, FALSE, $_action));
        }

        $this->_eventController->getAlarms($events);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($events);

        $baseEventMap = array(); // id => baseEvent
        $exceptionSets = array(); // id => exceptions
        $exceptionMap = array(); // idx => event

        foreach($events as $event) {
            if ($event->rrule) {
                $eventId = $event->id;
                $baseEventMap[$eventId] = $event;
                $exceptionSets[$eventId] = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
            } else if ($event->recurid) {
                $exceptionMap[] = $event;
            }
        }

        foreach($exceptionMap as $exception) {
            $baseEventId = $exception->base_event_id;
            $baseEvent = array_key_exists($baseEventId, $baseEventMap) ? $baseEventMap[$baseEventId] : false;
            if ($baseEvent) {
                $exceptionSet = $exceptionSets[$baseEventId];
                $exceptionSet->addRecord($exception);
                $events->removeRecord($exception);
            }
        }

        foreach($baseEventMap as $id => $baseEvent) {
            $exceptionSet = $exceptionSets[$id];
            $this->_eventController->fakeDeletedExceptions($baseEvent, $exceptionSet);
            $baseEvent->exdate = $exceptionSet;
        }

        return $events;
    }

    /*************** add / update / delete *****************/    

    /**
     * add one record
     *
     * @param   Calendar_Model_Event $_event
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_event, bool $allowRecurExceptions = false)
    {
        if (!$allowRecurExceptions && $_event->recurid) {
            throw new Tinebase_Exception_UnexpectedValue('recur event instances must be saved as part of the base event');
        }

        $this->_fromiTIP($_event, new Calendar_Model_Event(array(), TRUE));
        
        $exceptions = $_event->exdate;
        $_event->exdate = NULL;

        if ($this->_assertCalUserAttendee) {
            $_event->assertAttendee($this->_calendarUser);
        }

        $attenderStatusRaii = null;
        // recur exceptions for external events that have not been rescheduled should take the baseevents attendee status for internal attendees
        while ($_event->hasExternalOrganizer() && $_event->base_event_id) {
            Calendar_Model_Attender::resolveAttendee($_event->attendee, false);
            while ($_event->dtstart->equals($_event->getOriginalDtStart())) {
                try {
                    $baseEvent = $this->_eventController->get($_event->base_event_id);
                } catch (Tinebase_Exception_NotFound) {
                    break;
                }
                if (!$_event->dtend->equals($_event->getOriginalDtStart($baseEvent->dtstart->diff($baseEvent->dtend)))) {
                    break;
                }
                Calendar_Model_Attender::resolveAttendee($baseEvent->attendee, false);
                foreach ($_event->attendee as $a) {
                    if ($a->getIdFromProperty('user_id') === $this->_calendarUser->getIdFromProperty('user_id')) {
                        continue;
                    }
                    if ($a->user_id instanceof Addressbook_Model_Contact && $a->user_id->account_id) {
                        if ($baseAttender = $baseEvent->attendee->find('user_id', $a->user_id)) {
                            $a->status = $baseAttender->status;
                            if (null === $attenderStatusRaii) {
                                $oldKeepAttenderStatusValue = $this->_eventController->keepAttenderStatus(true);
                                $attenderStatusRaii = new Tinebase_RAII(fn() => $this->_eventController->keepAttenderStatus($oldKeepAttenderStatusValue));
                            }
                        } else {
                            $a->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
                        }
                    }
                }
                break 2;
            }
            $_event->attendee->filter(fn(Calendar_Model_Attender $a) => $a->user_id instanceof Addressbook_Model_Contact && $a->user_id->account_id)
                ->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
            break;
        }

        $savedEvent = $this->_eventController->create($_event);
        unset($attenderStatusRaii);
        
        if ($exceptions instanceof Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' About to create ' . count($exceptions) . ' exdates for event ' . $_event->summary . ' (' . $_event->dtstart . ')');
            
            foreach ($exceptions as $exception) {
                if ($this->assertCalUserAttendee()) {
                    $exception->assertAttendee($this->getCalendarUser());
                }
                $this->_prepareException($savedEvent, $exception);
                $this->_preserveMetaData($savedEvent, $exception, true);
                $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
            }
        }

        // NOTE: exdate creation changes baseEvent, so we need to refetch it here
        return $this->get($savedEvent->getId());
    }
    
    /**
     * update one record
     * 
     * NOTE: clients might send their original (creation) data w.o. our adoptions for update
     *       therefore we need reapply them
     *       
     * @param   Tinebase_Record_Interface $_event
     * @param   bool                 $_checkBusyConflicts
     * @return  Calendar_Model_Event
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_event, $_checkBusyConflicts = FALSE)
    {
        if (! $_event->dtstart || ! $_event->dtend) {
            throw new Tinebase_Exception_Record_Validation('dtstart or dtend missing from event!');
        }

        $currentOriginEvent = $this->_eventController->get($_event->getId());
        $this->_fromiTIP($_event, $currentOriginEvent);

        // NOTE:  create an update must be handled equally as apple devices do not fetch events after creation.
        //        an update from the creating device would change defaults otherwise
        // NOTE2: Being organizer without attending is not possible when sync is in use as every update
        //        from a sync device of the organizer adds the organizer as attendee :-(
        //        -> in the sync world this is scenario is called delegation and handled differently
        //        -> it might be consequent to have the same behavior (organizer is always attendee with role chair)
        //           in tine20 in general. This is how Thunderbird handles it as well
        if ($this->_assertCalUserAttendee) {
            $_event->assertAttendee($this->getCalendarUser());
        }
        
        $exceptions = $_event->exdate instanceof Tinebase_Record_RecordSet ? $_event->exdate : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exceptions->addIndices(array('is_deleted'));
        
        $currentPersistentExceptions = $_event->rrule ? $this->_eventController->getRecurExceptions($_event, FALSE) : new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $newPersistentExceptions = $exceptions->filter('is_deleted', 0);

        $dtStartDiff = $_event->dtstart->getClone()->setTimezone($_event->originator_tz)
            ->diff($currentOriginEvent->dtstart->getClone()->setTimezone($currentOriginEvent->originator_tz));
        $migration = $this->_getExceptionsMigration($currentPersistentExceptions, $newPersistentExceptions, $dtStartDiff);

        $this->_eventController->delete($migration['toDelete']->getId());

        // NOTE: we need to exclude the toCreate exdates here to not confuse computations in createRecurException!
        $_event->exdate = array_diff($exceptions->getOriginalDtStart(), $migration['toCreate']->getOriginalDtStart($dtStartDiff));

        $skipRecurAdoptions = $this->_eventController->skipRecurAdoptions(true);
        try {
            $updatedBaseEvent = $this->_eventController->update($_event, $_checkBusyConflicts);
        } finally {
            $this->_eventController->skipRecurAdoptions($skipRecurAdoptions);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Found ' . count($migration['toCreate']) . ' exceptions to create and ' . count($migration['toUpdate']) . ' to update.');
        
        foreach ($migration['toCreate'] as $exception) {
            if ($this->_assertCalUserAttendee) {
                $exception->assertAttendee($this->getCalendarUser());
            }
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_preserveMetaData($updatedBaseEvent, $exception, true);
            $this->_eventController->createRecurException($exception, !!$exception->is_deleted);
        }

        $updatedExceptions = array();
        foreach ($migration['toUpdate'] as $exception) {

            if (in_array($exception->getId(),$updatedExceptions )) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                    . ' Exdate ' . $exception->getId() . ' already updated');
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Update exdate ' . $exception->getId() . ' at ' . $exception->dtstart->toString());

            if ($this->_assertCalUserAttendee) {
                $exception->assertAttendee($this->getCalendarUser());
            }
            $this->_prepareException($updatedBaseEvent, $exception);
            $this->_preserveMetaData($updatedBaseEvent, $exception, false);
            $this->_addStatusAuthkeyForOwnAttender($exception);
            
            // skip concurrency check here by setting the seq of the current record
            $currentException = $currentPersistentExceptions->getById($exception->getId());
            $exception->seq = $currentException->seq;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Updating exception: ' . print_r($exception->toArray(), TRUE));
            $this->_eventController->update($exception, $_checkBusyConflicts);
            $updatedExceptions[] = $exception->getId();
        }
        
        // NOTE: we need to refetch here, otherwise eTag fail's as exception updates change baseEvents seq
        return $this->get($updatedBaseEvent->getId());
    }
    
    /**
     * add status_authkey for own attender
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _addStatusAuthkeyForOwnAttender($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        $ownAttender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        if ($ownAttender) {
            $currentEvent = $this->_eventController->get($event->id);
            $currentAttender = Calendar_Model_Attender::getAttendee($currentEvent->attendee, $ownAttender);
            if ($currentAttender) {
                $ownAttender->status_authkey = $currentAttender->status_authkey;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' currentAttender not found in currentEvent: ' . print_r($currentEvent->toArray(), true));
            }
        }
    }
    
    /**
     * asserts correct event filter and calendar user in MSEventFacade
     * 
     * NOTE: this is nessesary as MSEventFacade is a singleton and in some operations (e.g. move) there are 
     *       multiple instances of self
     */
    public function assertEventFacadeParams(Tinebase_Model_Container $container, $setEventFilter=true)
    {
        if (!$this->_currentEventFacadeContainer ||
             $this->_currentEventFacadeContainer->getId() !== $container->getId()
        ) {
            $this->_currentEventFacadeContainer = $container;

            try {
                $calendarUserId = $container->type == Tinebase_Model_Container::TYPE_PERSONAL ?
                Addressbook_Controller_Contact::getInstance()->getContactByUserId($container->getOwner(), true)->getId() :
                Tinebase_Core::getUser()->contact_id;
            } catch (Exception $e) {
                $calendarUserId = Calendar_Controller_MSEventFacade::getCurrentUserContactId();
            }
            
            $calendarUser = new Calendar_Model_Attender(array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $calendarUserId,
            ));
            

            $this->setCalendarUser($calendarUser);

            if ($setEventFilter) {
                $eventFilter = new Calendar_Model_EventFilter(array(
                    array('field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId())
                ));
                $this->setEventFilter($eventFilter);
            }
        }
    }

    public function attenderStatusUpdate(Calendar_Model_Event $_event, Calendar_Model_Attender $_attendee): Calendar_Model_Event
    {
        if (!($attendeeFound = Calendar_Model_Attender::getAttendee($_event->attendee, $_attendee))) {
            return $_event;
        }
        $attendeeFound->displaycontainer_id = $_attendee->displaycontainer_id;
        $attendeeFound->xprops = $_attendee->xprops;
        $attendeeFound->status = $_attendee->status;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($_event, $attendeeFound, $attendeeFound->status_authkey);

        return $this->get($_event->getId());
    }

    /**
     * update multiple records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_data
     * @param Tinebase_Model_Pagination $_pagination
     * @throws Tinebase_Exception_NotImplemented
     */
    public function updateMultiple($_filter, $_data, $_pagination = null)
    {
        throw new Tinebase_Exception_NotImplemented('Calendar_Conroller_MSEventFacade::updateMultiple not yet implemented');
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids)
    {
        $ids = array_unique((array)$_ids);
        $events = $this->getMultiple($ids);
        
        foreach ($events as $event) {
            if ($event->exdate !== null) {
                foreach ($event->exdate as $exception) {
                    $exceptionId = $exception->getId();
                    if ($exceptionId) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Found exdate to be deleted (id: ' . $exceptionId . ')');
                        array_unshift($ids, $exceptionId);
                    }
                }
            }
        }
        
        $this->_eventController->delete($ids);
        return $events;
    }

    /**
     * get and resolve all alarms of given record(s)
     * 
     * @param  Tinebase_Record_Interface|Tinebase_Record_RecordSet $_record
     */
    public function getAlarms($_record)
    {
        $events = $_record instanceof Tinebase_Record_RecordSet ? $_record->getClone(true) : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_record));
        
        foreach($events as $event) {
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
//                 $event->exdate->addIndices(array('is_deleted'));
                $events->merge($event->exdate->filter('is_deleted', 0));
            }
        }
        
        $this->_eventController->getAlarms($events);
    }
    
    /**
     * set displaycontainer for given attendee 
     * 
     * @param Calendar_Model_Event    $_event
     * @param string                  $_container
     * @param Calendar_Model_Attender $_attendee    defaults to calendarUser
     */
    public function setDisplaycontainer($_event, $_container, $_attendee = NULL)
    {
        if ($_event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach ($_event->exdate as $idx => $exdate) {
                self::setDisplaycontainer($exdate, $_container, $_attendee);
            }
        }
        
        $attendeeRecord = Calendar_Model_Attender::getAttendee($_event->attendee, $_attendee ? $_attendee : $this->getCalendarUser());
        
        if ($attendeeRecord) {
            $attendeeRecord->displaycontainer_id = $_container;
        }
    }
    
    /**
     * sets current calendar user
     * 
     * @param Calendar_Model_Attender $_calUser
     * @return Calendar_Model_Attender oldUser
     */
    public function setCalendarUser(Calendar_Model_Attender $_calUser)
    {
        if (! in_array($_calUser->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
            throw new Tinebase_Exception_UnexpectedValue('Calendar user must be a contact');
        }
        $oldUser = $this->_calendarUser;
        $this->_calendarUser = $_calUser;
        $this->_eventController->setCalendarUser($_calUser);
        
        return $oldUser;
    }
    
    /**
     * get current calendar user
     * 
     * @return Calendar_Model_Attender
     */
    public function getCalendarUser()
    {
        return $this->_calendarUser;
    }
    
    /**
     * set current event filter for exdate computations
     * 
     * @param  Calendar_Model_EventFilter
     * @return Calendar_Model_EventFilter
     */
    public function setEventFilter($_filter)
    {
        $oldFilter = $this->_eventFilter;
        
        if ($_filter !== NULL) {
            if (! $_filter instanceof Calendar_Model_EventFilter) {
                throw new Tinebase_Exception_UnexpectedValue('not a valid filter');
            }
            $this->_eventFilter = clone $_filter;
            
            $periodFilters = $this->_eventFilter->getFilter('period', TRUE, TRUE);
            foreach((array) $periodFilters as $periodFilter) {
                $periodFilter->setDisabled();
            }
        } else {
            $this->_eventFilter = NULL;
        }
        
        return $oldFilter;
    }
    
    /**
     * get current event filter
     * 
     * @return Calendar_Model_EventFilter
     */
    public function getEventFilter()
    {
        return $this->_eventFilter;
    }
    
    /**
     * filters given eventset for events with matching dtstart
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @param array                     $_dtstarts
     */
    protected function _filterEventsByDTStarts($_events, $_dtstarts, $dtStartDiff=null)
    {
        $filteredSet = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $allDTStarts = $_events->getOriginalDtStart($dtStartDiff);
        
        $existingIdxs = array_intersect($allDTStarts, $_dtstarts);
        
        foreach($existingIdxs as $idx => $dtstart) {
            $filteredSet->addRecord($_events[$idx]);
        }
        
        return $filteredSet;
    }

    protected function _resolveData($events)
    {
        $eventSet = $events instanceof Tinebase_Record_RecordSet
            ? $events->getClone(true)
            : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($events));

        // get recur exceptions
        foreach ($eventSet as $event) {
            if ($event->rrule && !$event->exdate instanceof Tinebase_Record_RecordSet) {
                $exdates = $this->_eventController->getRecurExceptions($event, TRUE, $this->getEventFilter());
                $event->exdate = $exdates;
                $eventSet->merge($exdates);
            }
        }

        $this->_eventController->getAlarms($eventSet);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($eventSet);
    }

    /**
     * converts a tine20 event to an iTIP event
     * 
     * @param  Calendar_Model_Event $_event - must have exceptions, alarms & attachments resovled
     * @return Calendar_Model_Event 
     */
    protected function _toiTIP($_event)
    {
        $events = $_event instanceof Tinebase_Record_RecordSet
            ? $_event
            : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_event));

        foreach ($events as $idx => $event) {
            // get exdates
            if ($event->getId() && $event->rrule) {
                $this->_toiTIP($event->exdate);
            }

            $this->_filterAttendeeWithoutEmail($event);
            
            $CUAttendee = Calendar_Model_Attender::getAttendee($event->attendee, $this->_calendarUser);
            $isOrganizer = $event->isOrganizer($this->_calendarUser);
            
            // apply perspective
            if ($CUAttendee && !$isOrganizer) {
                $event->transp = $CUAttendee->transp ? $CUAttendee->transp : $event->transp;
            }
            
            if ($event->alarms instanceof Tinebase_Record_RecordSet) {
                foreach($event->alarms as $alarm) {
                    if (! Calendar_Model_Attender::isAlarmForAttendee($this->_calendarUser, $alarm, $event)) {
                        $event->alarms->removeRecord($alarm);
                    }
                }
            }
        }
        
        return $_event;
    }
    
    /**
     * filter out attendee w.o. email
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _filterAttendeeWithoutEmail($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        foreach ($event->attendee as $attender) {
            if (Calendar_Model_Attender::USERTYPE_EMAIL === $attender->user_type) {
                continue;
            }
            $cacheId = $attender->user_type . $attender->user_id;
            
            // value is in array and true
            if (isset(self::$_attendeeEmailCache[$cacheId])) {
                continue;
            }
            
            // add value to cache if not existing already
            if (!array_key_exists($cacheId, self::$_attendeeEmailCache)) {
                $this->_fillResolvedAttendeeCache($event);

                try {
                    self::$_attendeeEmailCache[$cacheId] = !!$attender->getEmail($event);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // skipping
                    self::$_attendeeEmailCache[$cacheId] = false;
                }
                
                // limit class cache to 100 entries
                if (count(self::$_attendeeEmailCache) > 100) {
                    array_shift(self::$_attendeeEmailCache);
                }
            }
            
            if (!self::$_attendeeEmailCache[$cacheId]) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' remove entry because attender has no email address: ' . $attender->user_id);
                $event->attendee->removeRecord($attender);
            }
        }
    }

    /**
     * re add attendee w.o. email
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _addAttendeeWithoutEmail($event, $currentEvent)
    {
        if (! $currentEvent->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        $this->_fillResolvedAttendeeCache($currentEvent);
        
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        foreach ($currentEvent->attendee->getEmail() as $idx => $email) {
            if (! $email) {
                $event->attendee->addRecord($currentEvent->attendee[$idx]);
            }
        }
    }
    
    /**
     * this fills the resolved attendee cache without changing the event attendee recordset
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _fillResolvedAttendeeCache($event)
    {
        if (! $event->attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        Calendar_Model_Attender::fillResolvedAttendeesCache($event->attendee);
    }
    
    /**
     * converts an iTIP event to a tine20 event
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_currentEvent (not iTIP!)
     */
    protected function _fromiTIP($_event, $_currentEvent)
    {
        if (! $_event->rrule) {
            $_event->exdate = NULL;
        }
        
        if ($_event->exdate instanceof Tinebase_Record_RecordSet) {
            
            try{
                $currExdates = $this->_eventController->getRecurExceptions($_event, TRUE);
                $this->getAlarms($currExdates);
                $currClientExdates = $this->_eventController->getRecurExceptions($_event, TRUE, $this->getEventFilter());
                $this->getAlarms($currClientExdates);
            } catch (Tinebase_Exception_NotFound $e) {
                $currExdates = NULL;
                $currClientExdates = NULL; 
            }
            
            foreach ($_event->exdate as $idx => $exdate) {
                try {
                    $this->_prepareException($_event, $exdate);
                } catch (Exception $e){}

                $currExdate = $currExdates instanceof Tinebase_Record_RecordSet ?
                    $currExdates->find('recurid', $exdate->recurid) : null;

                $this->_preserveMetaData($_event, $exdate, null === $currExdate);
                
                if ($exdate->is_deleted) {
                    // reset implicit filter fallouts and mark as don't touch (seq = -1)
                    $currClientExdate = $currClientExdates instanceof Tinebase_Record_RecordSet ? $currClientExdates->find('recurid', $exdate->recurid) : NULL;
                    if ($currClientExdate && $currClientExdate->is_deleted) {
                        $_event->exdate[$idx] = $currExdate;
                        $currExdate->seq = -1;
                        continue;
                    }
                }
                $this->_fromiTIP($exdate, $currExdate ? $currExdate : clone $_currentEvent);
            }
        }
        
        // assert organizer
        // if no organizer is set, set current calendarUser
        if (Calendar_Model_Event::ORGANIZER_TYPE_EMAIL !== $_event->organizer_type && !$_event->organizer) {
            if (Calendar_Model_Event::ORGANIZER_TYPE_EMAIL === $_currentEvent->organizer_type) {
                $_event->organizer_type = Calendar_Model_Event::ORGANIZER_TYPE_EMAIL;
                $_event->organizer_email = $_currentEvent->organizer_email;
                $_event->organizer_displayname = $_currentEvent->organizer_displayname;
            } else {
                $_event->organizer = $_currentEvent?->organizer ?: $this->_calendarUser->user_id;
            }
        }
        // if event moved from old organizers personal calendar to calendarUsers personal calendar => change organizer
        while (Calendar_Model_Event::ORGANIZER_TYPE_EMAIL !== $_event->organizer_type && $_event->container_id && $_currentEvent->container_id &&
                $_event->organizer !== $this->_calendarUser->user_id && $_currentEvent->container_id !== $_event->container_id) {
            /** @var Tinebase_Model_Container $currentContainer */
            $currentContainer = Tinebase_Container::getInstance()->get($_currentEvent->container_id);
            /** @var Addressbook_Model_Contact $currentOrganizer */
            $currentOrganizer = Addressbook_Controller_Contact::getInstance()->get($_currentEvent->organizer);
            if ($currentContainer->owner_id !== $currentOrganizer->account_id) {
                break;
            }

            /** @var Tinebase_Model_Container $newContainer */
            $newContainer = Tinebase_Container::getInstance()->get($_event->container_id);
            /** @var Addressbook_Model_Contact $currentOrganizer */
            $newOrganizer = Addressbook_Controller_Contact::getInstance()->get($this->_calendarUser->user_id);
            if ($newContainer->owner_id !== $newOrganizer->account_id) {
                break;
            }
            
            $_event->organizer = $this->_calendarUser->user_id;
            break;
        }

        $this->_addAttendeeWithoutEmail($_event, $_currentEvent);
        
        $CUAttendee = Calendar_Model_Attender::getAttendee($_event->attendee, $this->_calendarUser);
        $currentCUAttendee  = Calendar_Model_Attender::getAttendee($_currentEvent->attendee, $this->_calendarUser);
        $isOrganizer = $_event->isOrganizer($this->_calendarUser);
        
        // remove perspective 
        if ($CUAttendee && !$isOrganizer) {
            $CUAttendee->transp = $_event->transp;
            $_event->transp = $_currentEvent->transp ? $_currentEvent->transp : $_event->transp;
            if (empty($_event->transp)) {
                // set default transparency if empty
                $_event->transp = Calendar_Model_Event::TRANSP_OPAQUE;
            }
        }
        
        // apply changes to original alarms
        $_currentEvent->alarms  = $_currentEvent->alarms instanceof Tinebase_Record_RecordSet
            ? $_currentEvent->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $_event->alarms  = $_event->alarms instanceof Tinebase_Record_RecordSet
            ? $_event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        foreach($_currentEvent->alarms as $currentAlarm) {
            if (Calendar_Model_Attender::isAlarmForAttendee($this->_calendarUser, $currentAlarm)) {
                $alarmUpdate = Calendar_Controller_Alarm::getMatchingAlarm($_event->alarms, $currentAlarm);
                
                if ($alarmUpdate) {
                    // we could map the alarm => save ack & snooze options
                    if ($dtAck = Calendar_Controller_Alarm::getAcknowledgeTime($alarmUpdate)) {
                        Calendar_Controller_Alarm::setAcknowledgeTime($currentAlarm, $dtAck, $this->getCalendarUser()->user_id);
                    }
                    if ($dtSnooze = Calendar_Controller_Alarm::getSnoozeTime($alarmUpdate)) {
                        Calendar_Controller_Alarm::setSnoozeTime($currentAlarm, $dtSnooze, $this->getCalendarUser()->user_id);
                    }
                    $_event->alarms->removeRecord($alarmUpdate);
                } else {
                    // alarm is to be skiped/deleted
                    if (! $currentAlarm->getOption('attendee')) {
                        Calendar_Controller_Alarm::skipAlarm($currentAlarm, $this->_calendarUser);
                    } else {
                        $_currentEvent->alarms->removeRecord($currentAlarm);
                    }
                }
            }
        }
        if (! $isOrganizer) {
            $_event->alarms->setOption('attendee', Calendar_Controller_Alarm::attendeeToOption($this->_calendarUser));
        }
        $_event->alarms->merge($_currentEvent->alarms);

        // in MS world only cal_user can do status updates
        if ($CUAttendee) {
            $CUAttendee->status_authkey = $currentCUAttendee ? $currentCUAttendee->status_authkey : NULL;
        }
    }
    
    /**
     * computes an returns the migration for event exceptions
     * 
     * @param Tinebase_Record_RecordSet $_currentPersistentExceptions
     * @param Tinebase_Record_RecordSet $_newPersistentExceptions
     * @param DateInterval              $dtStartDiff
     * @return array
     */
    protected function _getExceptionsMigration($_currentPersistentExceptions, $_newPersistentExceptions, $dtStartDiff)
    {
        $migration = array();
        
        // add indices and sort to speedup things
        $_currentPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        $_newPersistentExceptions->addIndices(array('dtstart'))->sort('dtstart');
        
        // get dtstarts
        $currDtStart = $_currentPersistentExceptions->getOriginalDtStart();
        $newDtStart = $_newPersistentExceptions->getOriginalDtStart($dtStartDiff);

        // compute migration in terms of dtstart
        $toDeleteDtStart = array_diff($currDtStart, $newDtStart);
        $toCreateDtStart = array_diff($newDtStart, $currDtStart);
        $toUpdateDtSTart = array_intersect($currDtStart, $newDtStart);
        
        $migration['toDelete'] = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toDeleteDtStart);
        $migration['toCreate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toCreateDtStart, $dtStartDiff);
        $migration['toUpdate'] = $this->_filterEventsByDTStarts($_newPersistentExceptions, $toUpdateDtSTart, $dtStartDiff);
        
        // get ids for toUpdate
        $idxIdMap = $this->_filterEventsByDTStarts($_currentPersistentExceptions, $toUpdateDtSTart)->getId();
        $migration['toUpdate']->setByIndices('id', $idxIdMap, /* $skipMissing = */ true);
        
        // filter exceptions marked as don't touch 
        foreach ($migration['toUpdate'] as $toUpdate) {
            if ($toUpdate->seq === -1) {
                $migration['toUpdate']->removeRecord($toUpdate);
            }
        }

        return $migration;
    }

    /**
     * copies customfields, tags, relations, notes from base event to exception
     *
     * @param Calendar_Model_Event $_baseEvent
     * @param Calendar_Model_Event $_exception
     * @param boolean $_create
     */
    protected function _preserveMetaData(Calendar_Model_Event $_baseEvent, Calendar_Model_Event $_exception, $_create)
    {
        if ($_create) {
            $refEvent = $_baseEvent;
        } else {
            $refEvent = $this->_eventController->get($_exception->getId());
        }

        // initialize customfields from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->customfields) && !empty($refEvent->customfields)) {
            foreach ($refEvent->customfields as $name => $val) {
                $_exception->xprops('customfields')[$name] = $val;
            }
        }

        // initialize tags from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->tags) && !empty($refEvent->tags)) {
            $_exception->tags = $refEvent->tags;
        }

        // initialize relations from base event as clients don't support that and otherwise would lose them
        if (isset($refEvent->relations) && !empty($refEvent->relations)) {
            $relations = [];
            foreach (is_array($refEvent->relations) ?: $refEvent->relations->toArray() as $relation) {
                $relations[] = [
                    'related_id' => $relation['related_id'],
                    'related_model' => $relation['related_model'],
                    'related_degree' => $relation['related_degree'],
                    'related_backend' => $relation['related_backend'],
                    'type' => isset($relation['type']) ? $relation['type'] : null,
                ];
            }
            $_exception->relations = $relations;
        }

        // notes?!?
        if (isset($refEvent->notes) && !empty($refEvent->notes)) {
            if ($refEvent->notes instanceof Tinebase_Record_RecordSet) {
                $notes = clone $refEvent->notes;
                $notes->id = null;
                $notes = $notes->toArray();
            } else {
                $notes = [];
                foreach ($refEvent->notes as $note) {
                    if (is_array($note) && isset($note['id'])) {
                        unset($note['id']);
                    }
                    $notes[] = $note;
                }
            }
            $_exception->notes = $notes;
        }
    }

    /**
     * prepares an exception instance for persistence
     * 
     * @param  Calendar_Model_Event $_baseEvent
     * @param  Calendar_Model_Event $_exception
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _prepareException(Calendar_Model_Event $_baseEvent, Calendar_Model_Event $_exception)
    {
        if (! $_baseEvent->uid) {
            throw new Tinebase_Exception_InvalidArgument('base event has no uid');
        }
        
        if ($_exception->is_deleted == false) {
            $_exception->container_id = $_baseEvent->container_id;
        }
        $_exception->uid = $_baseEvent->uid;
        $_exception->base_event_id = $_baseEvent->getId();
        $_exception->recurid = $_baseEvent->uid . '-' . $_exception->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG);
        
        // NOTE: we always refetch the base event as it might be touched in the meantime
        $currBaseEvent = $this->_eventController->get($_baseEvent, null, false);
        $_exception->last_modified_time = $currBaseEvent->last_modified_time;
    }

    public function assertCalUserAttendee(?bool $b = null): bool
    {
        $oldValue = $this->_assertCalUserAttendee;
        if (null !== $b) {
            $this->_assertCalUserAttendee = $b;
        }
        return $oldValue;
    }

    public function useExternalIdUid(?bool $b = null): bool
    {
        $oldValue = $this->_useExternalIdUid;
        if (null !== $b) {
            $this->_useExternalIdUid = $b;
        }
        return $oldValue;
    }

    public function getExistingEventFromExternalEventData(Calendar_Model_Event $_event, string $_containerId, string $_action, bool $_getDeleted = false, array $requiredGrants = []): ?Calendar_Model_Event
    {
        $result = null;
        $foundWithoutGrant = false;
        $checkGrantFun = function(bool $throw) use($requiredGrants, &$result, &$foundWithoutGrant) {
            foreach ($requiredGrants as $grant) {
                if (!$result->hasGrant($grant)) {
                    $result = null;
                    $foundWithoutGrant = true;
                    if ($throw) {
                        throw new Tinebase_Exception_AccessDenied('access denied');
                    }
                }
            }
        };

        if ($_event->isRecurException() && (!is_string($_event->recurid) || !str_starts_with($_event->recurid, $_event->uid))) {
            $_event->setRecurId($_event->base_event_id);
        }

        if ($this->_useExternalIdUid) {
            if ($_event->external_id) {
                $filter = new Calendar_Model_EventFilter([
                    [TMFA::FIELD => 'external_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->external_id],
                ]);

                if ($_event->isRecurException()) {
                    $filter->addFilter($filter->createFilter(
                        [TMFA::FIELD => 'recurid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->recurid]
                    ));
                } else {
                    $filter->addFilter($filter->createFilter(
                        [TMFA::FIELD => 'recurid', TMFA::OPERATOR => 'isnull', TMFA::VALUE => null]
                    ));
                }
                if ($_getDeleted) {
                    $filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', TMFA::OP_EQUALS,
                        Tinebase_Model_Filter_Bool::VALUE_NOTSET));
                }
                if ($result = $this->search($filter, _action: $_action)->getFirstRecord()) {
                    $checkGrantFun(false);
                }
            }
            if (null === $result && $_event->external_uid) {
                $filter = new Calendar_Model_EventFilter([
                    [TMFA::FIELD => 'external_uid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->external_uid],
                ]);

                if ($_event->isRecurException()) {
                    $filter->addFilter($filter->createFilter(
                        [TMFA::FIELD => 'recurid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->recurid]
                    ));
                } else {
                    $filter->addFilter($filter->createFilter(
                        [TMFA::FIELD => 'recurid', TMFA::OPERATOR => 'isnull', TMFA::VALUE => null]
                    ));
                }
                if ($_getDeleted) {
                    $filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', TMFA::OP_EQUALS,
                        Tinebase_Model_Filter_Bool::VALUE_NOTSET));
                }
                if ($result = $this->search($filter, _action: $_action)->getFirstRecord()) {
                    $checkGrantFun(false);
                }
            }
        }

        if (null === $result && $_event->getId()) {
            try {
                $result = $this->get($_event->getId(), $_getDeleted);
                $checkGrantFun(true);
            } catch (Tinebase_Exception_NotFound|Tinebase_Exception_AccessDenied) {}
        }

        if (null === $result) {
            $filter = new Calendar_Model_EventFilter([
                [TMFA::FIELD => 'uid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->uid],
                [TMFA::FIELD => 'container_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_containerId],
            ]);
            if ($_event->isRecurException()) {
                $filter->addFilter($filter->createFilter(
                    [TMFA::FIELD => 'recurid', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_event->recurid]
                ));
            } else {
                $filter->addFilter($filter->createFilter(
                    [TMFA::FIELD => 'recurid', TMFA::OPERATOR => 'isnull', TMFA::VALUE => null]
                ));
            }
            if ($_getDeleted) {
                $filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', TMFA::OP_EQUALS,
                    Tinebase_Model_Filter_Bool::VALUE_NOTSET));
            }
            if ($result = $this->search($filter, _action: $_action)->getFirstRecord()) {
                $checkGrantFun(false);
            }

            if (null === $result && $_event->hasExternalOrganizer() && ($_event->organizer_email || $_event->resolveOrganizer())) {
                $filter->getFilter('container_id')->setValue(
                    Calendar_Controller::getInstance()->getInvitationContainer($_event->organizer_email ? null : $_event->resolveOrganizer(), $_event->organizer_email)->getId()
                );
                if ($result = $this->search($filter, _action: $_action)->getFirstRecord()) {
                    $checkGrantFun(false);
                }
            }
            if (null === $result) {
                $filter->removeFilter('container_id');
                $filter->addFilterGroup(new Calendar_Model_EventFilter([
                    ['field' => 'organizer', 'operator' => 'equals', 'value' => $this->getCalendarUser()->user_id],
                    ['field' => 'attender', 'operator' => 'equals', 'value' => $this->getCalendarUser()],
                ], Calendar_Model_EventFilter::CONDITION_OR));
                if ($result = $this->search($filter, _action: $_action)->getFirstRecord()) {
                    $checkGrantFun(false);
                }
            }
        }
        if (null === $result && $foundWithoutGrant) {
            throw new Tinebase_Exception_AccessDenied('access denied');
        }
        return $result;
    }

    public function getExistingEventByUID(string $_uid, ?string $_recurid, string $_action, string $_grant, bool $_getDeleted = false): ?Calendar_Model_Event
    {
        $filters = new Calendar_Model_EventFilter([
            ['field' => 'uid', 'operator' => 'equals', 'value' => $_uid],
            ['field' => 'recurid', 'operator' => null === $_recurid ? 'isnull' : 'equals', 'value' => $_recurid],
        ]);
        if ($_getDeleted) {
            $filters->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals',
                Tinebase_Model_Filter_Bool::VALUE_NOTSET));
        }

        if (null !== ($event = $this->_getExistingEventByUID($filters, $_action, $_grant)) && $event->rrule) {
            $event->exdate->mergeById($this->_eventController->search(new Calendar_Model_EventFilter([
                ['field' => 'base_event_id', 'operator' => 'equals', 'value' => $event->getId()],
            ]), new Tinebase_Model_Pagination([
                'sort' => 'dtstart',
                'dir' => 'ASC',
            ]), _action: $_action)->filter($_grant, true));
        }
        return $event;
    }

    protected function _getExistingEventByUID(Tinebase_Model_Filter_FilterGroup $_filter, string $_action, string $_grant): ?Calendar_Model_Event
    {
        $events = $this->search($_filter, _action: $_action);
        $events = $events->filter(fn(Calendar_Model_Event $event) => $event->hasExternalOrganizer() || $event->{$_grant});

        /** @var Calendar_Model_Event $event */
        foreach ($events as $event) {
            if (!$event->hasExternalOrganizer()) {
                return $event;
            }
        }

        return $events->getFirstRecord();
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * returns the model name
     *
     * @return string
     */
    public function getModel()
    {
        return Calendar_Model_Event::class;
    }

    public function getEventController(): Calendar_Controller_Event
    {
        return $this->_eventController;
    }

    public function prepareAttendeesView(Calendar_Model_Event &$event, Calendar_Model_Attender $attendee): void
    {
        if ($event->isRecurException() || $event->rrule) {
            $oldAclCheck = $this->getEventController()->doContainerACLChecks(false);
            try {
                $baseEvent = $this->getExdateResolvedEvents(new Calendar_Model_EventFilter([
                    ['field' => 'id', 'operator' => 'equals', 'value' => $event->isRecurException() ? $event->base_event_id : $event->getId()],
                ]), 'get')->getFirstRecord();
                if ($baseEvent && Calendar_Model_Attender::getAttendee($baseEvent->attendee, $attendee)) {
                    $event = $baseEvent;
                    if ($baseEvent->exdate) {
                        $eventLength = $baseEvent->dtstart->diff($baseEvent->dtend);
                        $remove = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
                        $add = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);

                        /** @var Calendar_Model_Event $exdate */
                        foreach ($baseEvent->exdate as $exdate) {
                            if (!Calendar_Model_Attender::getAttendee($exdate->attendee, $attendee)) {
                                $remove->addRecord($exdate);

                                $fakeEvent = new Calendar_Model_Event([
                                    'uid' => $exdate->uid,
                                    'dtstart' => $exdate->getOriginalDtStart(),
                                    'dtend' => $exdate->getOriginalDtStart()->add($eventLength),
                                    'is_deleted' => true,
                                ], true);
                                $fakeEvent->setRecurId($baseEvent->getId());
                                $add->addRecord($fakeEvent);
                            }
                        }
                        $baseEvent->exdate->removeRecords($remove);
                        $baseEvent->exdate->merge($add);
                    }
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
            } catch (Tinebase_Exception_AccessDenied $tead) {
            } finally {
                $this->getEventController()->doContainerACLChecks($oldAclCheck);
            }
        }
    }

    public function copy(string $id, bool $persist): Tinebase_Record_Interface
    {
        throw new Tinebase_Exception_NotImplemented();
    }
}
