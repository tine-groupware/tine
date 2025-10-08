<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_Json extends Tinebase_Convert_Json
{
    /**
    * converts Tinebase_Record_Interface to external format
    *
    * @param  Tinebase_Record_Interface $_record
    * @return mixed
    */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        self::resolveRelatedData($_record);
        return parent::fromTine20Model($_record);
    }
    
    /**
     * resolve related event data: attendee, rrule and organizer
     * 
     * @param Calendar_Model_Event $_record
     */
    static public function resolveRelatedData($_record)
    {
        if (! $_record instanceof Calendar_Model_Event) {
            return;
        }
        
        Calendar_Model_Attender::resolveAttendee($_record->attendee, TRUE, $_record);
        self::resolveRrule($_record);
        self::resolvePoll($_record);
        self::resolveOrganizer($_record);
        self::resolveLocationRecord($_record);
        self::resolveGrantsOfExternalOrganizers($_record);
    }
    
    /**
    * resolves rrule of given event(s)
    *
    * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
    */
    static public function resolveRrule($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ? $_events
            : new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$_events]);
        $candidates = $events->filter('rrule', "/^FREQ.*/", TRUE);
        $candidate = null;
        foreach ($events as $event) {
            if ($event->rrule) {
                $event->rrule = Calendar_Model_Rrule::getRruleFromString($event->rrule);

                if ($event->rrule_constraints instanceof Calendar_Model_EventFilter) {
                    $event->rrule_constraints = $event->rrule_constraints->toArray(true);
                }
            }
            if (!empty($event->base_event_id)) {
                if (count($candidates) > 0) {
                    $candidate = $candidates->getById($event->base_event_id);
                } else {
                    try {
                        $candidate = Calendar_Controller_Event::getInstance()->get($event->base_event_id);
                    } catch (Tinebase_Exception_AccessDenied $tead) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(__METHOD__ . ' '
                                . __LINE__ . ' Skipping candidate (base event id: ' . $event->base_event_id . '): '
                                . $tead->getMessage());
                        }
                        $candidate = null;
                    }
                }
                // exceptions need freq on FE to show/hide elements
                if ($candidate && isset($candidate->rrule->count)) {
                    $event->rrule = $candidate->rrule;
                }
            }
        }
    }

    /**
     * resolves poll of given event(s)
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolvePoll($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ?
            $_events :
            new Tinebase_Record_RecordSet(Calendar_Model_Event::class, array($_events));

        $pollIds = array_unique($events->poll_id);

        // read access to event means access to poll
        $protectedUsage = Calendar_Controller_Poll::getInstance()->assertPublicUsage();
        $polls = Calendar_Controller_Poll::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Poll::class,[
                ['field' => 'id', 'operator' => 'in', 'value' => $pollIds],
                ['field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ])
        );
        $protectedUsage();

        foreach ($events as $event) {
            if ($event->poll_id) {
                $event->poll_id = $polls->getById($event->poll_id);
            }
        }
    }
    
    /**
    * resolves organizer of given event
    *
    * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
    */
    static public function resolveOrganizer($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet
            ? $_events : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_events));
        
        self::resolveMultipleIdFields($events, array(
            'Addressbook_Model_Contact' => array(
                'options' => array('ignoreAcl' => TRUE),
                'fields'  => array('organizer'),
            )
        ));
    }

    /**
     * resolves organizer of given event
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolveLocationRecord($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet
            ? $_events : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_events));

        self::resolveMultipleIdFields($events, array(
            'Addressbook_Model_Contact' => array(
                'options' => array('ignoreAcl' => TRUE),
                'fields'  => array('location_record'),
            )
        ));
    }
    
    /**
     * resolves grants of external organizers events
     * NOTE: disable editGrant when organizer is external
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolveGrantsOfExternalOrganizers($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet || is_array($_events) ? $_events : array($_events);
    
        foreach ($events as &$event) {
            if ($event->organizer && $event->organizer instanceof Tinebase_Record_Interface
                && (!$event->organizer->has('account_id') || !$event->organizer->account_id)
                && $event->{Tinebase_Model_Grants::GRANT_EDIT} 
            ) {
                $event->{Tinebase_Model_Grants::GRANT_EDIT} = FALSE;
                $event->{Tinebase_Model_Grants::GRANT_READ} = TRUE;
            }
        }
    
    }
    
    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param Tinebase_Record_RecordSet         $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination         $_pagination
     *
     * @return mixed
     */
    public function fromTine20RecordSet(?\Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }

        $mc = $_records->getFirstRecord()::getConfiguration();
        $expander = new Tinebase_Record_Expander(Calendar_Model_Event::class, $mc->jsonExpander);
        $expander->expand($_records);
        $mc->setJsonExpander(null);

        if (null !== $_filter) {
            $rruleFilter = $_filter->getFilter('rrule', false, true);
            if ($rruleFilter && in_array($rruleFilter->getOperator(), ['in', 'notin'])) {
                foreach($_records as $record) {
                    $_records->merge(Calendar_Controller_Event::getInstance()->getRecurExceptions($record));
                }
            }
        }

        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records);
        Calendar_Model_Attender::resolveAttendee($_records->attendee, TRUE, $_records);
        Calendar_Convert_Event_Json::resolveRrule($_records);
        Calendar_Convert_Event_Json::resolvePoll($_records);
        Calendar_Convert_Event_Json::resolveLocationRecord($_records);
        Calendar_Controller_Event::getInstance()->getAlarms($_records);
        
        Calendar_Convert_Event_Json::resolveGrantsOfExternalOrganizers($_records);
        $removedEvents = Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($_records, $_filter);
        $removedResults = parent::fromTine20RecordSet($removedEvents, $_filter, $_pagination);

        $_records->sortByPagination($_pagination);

        $results = parent::fromTine20RecordSet($_records, $_filter, $_pagination);

        // NOTE: parent::fromTine20RecordSet does not expand values in recurring instances (tags, notes, attachments, (system) cf's
        //       therefore we copy anything not scheduling related manualy here (NOTE freebusy infos have no id)
        $baseEventMap = array_reduce(array_merge($results, $removedResults), function ($map, $result) {
            if (isset($result['id']) && !preg_match('/^fakeid/', $result['id'])) {
                $map[$result['id']] = $result;
            }
            return $map;
        }, []);

        // @see \Calendar_Model_Rrule::addRecurrence
        $excludeFields = ['id', 'dtstart', 'dtend', 'recurid', 'base_event_id', 'rrule', 'rrule_until', 'rrule_constraints', 'exdate', 'alarms', 'attendee'];
        foreach ($results as &$result) {
            if (isset($result['id']) && preg_match('/^fakeid/', $result['id'])) {
                if (isset($baseEventMap[$result['base_event_id']])) {
                    foreach($baseEventMap[$result['base_event_id']] as $field => $value) {
                        if (! in_array($field, $excludeFields)) {
                            $result[$field] = $value;
                        }
                    }
                }
            }
        }

        return $results;
    }
}
