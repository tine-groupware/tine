<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use more functionality of Tinebase_Import_Abstract (import() and other fns)
 */

/**
 * Calendar_Import_Abstract
 * 
 * @package     Calendar
 * @subpackage  Import
 */
abstract class Calendar_Import_Abstract extends Tinebase_Import_Abstract
{
    public const OPTION_FORCE_UPDATE_EXISTING = 'forceUpdateExisting';
    public const OPTION_MATCH_ORGANIZER = 'matchOrganizer';
    public const OPTION_MATCH_ATTENDEES = 'matchAttendees';
    public const OPTION_SKIP_INTERNAL_OTHER_ORGANIZER = 'skipInternalOtherOrganizer';
    public const OPTION_DISABLE_EXTERNAL_ORGANIZER_CALENDAR = 'disableExternalOrganizerCalendar';
    public const OPTION_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS = 'useOwnAttendeeForSkipInternalOtherOrganizerEvents';
    public const OPTION_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS = 'allowPartyCrushForSkipInternalOtherOrganizerEvents';
    public const OPTION_IMPORT_VTODOS = 'importVTodos';
    public const OPTION_TASK_CONTAINER = 'taskContainer';

    /**
     * @var Calendar_Controller_Event
     */
    protected $_cc = null;
    
    /**
     * config options
     * 
     * @var array
     */
    protected $_options = array(
        /**
         * force update of existing events 
         * @var boolean
         */
        'updateExisting'        => TRUE,
        /**
         * update exiting events even if imported sequence number isn't higher
         * @var boolean
         */
        self::OPTION_FORCE_UPDATE_EXISTING   => FALSE,
        /**
         * list of event attendee to add or replace (see attendeeStrategy)
         * @var array of attendeeData
         */
        'attendee' => null,
        /**
         * what to do with attendee from attendee option
         *  add -> add to import data attendee
         *  replace ->  replace import data attendee with given attendee
         * @var string add|replace
         */
        'attendeeStrategy' => 'add',
        /**
         * keep existing attendee
         * @var boolean
         */
        'keepExistingAttendee'  => FALSE,
        /**
         * delete events missing in import file (future only)
         * @var boolean
         */
        'deleteMissing'         => FALSE,
        /**
         * overwrite organizer data
         * @var array with keys organizer, organizer_type, organizer_email, organizer_displayname
         */
        'overwriteOrganizer'    => null,
        /**
         * container the events should be imported in
         * @var string
         */
        'container_id'          => NULL,
        /**
         * remote url
         * @var string
         */
        'url'                   => null,
        /**
         * username for remote access
         * @var string
         */
        'username'              => null,
        /**
         * password for remote access
         * @var string
         */
        'password'              => null,
        /**
         * TODO needed? if yes: document!
         * @var string
         */
        'cid'                   => null,
        /**
         * credential cache key instead of username/password
         * @var string
         */
        'ckey'                  => null,
        /**
         * the model
         */
        'model'                 => 'Calendar_Model_Event',
        /**
         * credential cache id
         */
        'cc_id'                 => null,
        self::OPTION_MATCH_ATTENDEES => true,
        self::OPTION_MATCH_ORGANIZER => true,
        self::OPTION_SKIP_INTERNAL_OTHER_ORGANIZER => true,
        self::OPTION_DISABLE_EXTERNAL_ORGANIZER_CALENDAR => false,
        self::OPTION_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => false,
        self::OPTION_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => false,
        self::OPTION_IMPORT_VTODOS => false,
        self::OPTION_TASK_CONTAINER => null,
        'calDavRequestTries' => null,
    );

    protected function _getCalendarController()
    {
        if ($this->_cc === null) {
            $this->_cc = Calendar_Controller_Event::getInstance();
        }

        return $this->_cc;
    }

    /**
     * import the data
     *
     * @param  mixed $_resource
     * @param array $_clientRecordData
     * @return array :
     *  'results'           => Tinebase_Record_RecordSet, // for dryrun only
     *  'totalcount'        => int,
     *  'failcount'         => int,
     *  'duplicatecount'    => int,
     *
     *  @see 0008334: use vcalendar converter for ics import
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $this->_initImportResult();
        $this->_cc = $this->_getCalendarController();
        $ccAssertions = [
            'assertCalUserAttendee' => $this->_cc->assertCalUserAttendee(),
        ];
        
        // make sure container exists
        $container = Tinebase_Container::getInstance()->getContainerById($this->_options['container_id']);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Import into calendar: ' . print_r($this->_options['container_id'], true));

        $events = $this->_getImportEvents($_resource, $container);
        $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);

        // search uid's and remove already existing -> only in import cal?
        $existingEventsFilter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_options['container_id']),
            array('field' => 'uid', 'operator' => 'in', 'value' => array_unique($events->uid)),
        ));
        $existingEvents = $this->_cc->search($existingEventsFilter);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Found ' . count($existingEvents) . ' existing events');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Filter: ' . print_r($existingEventsFilter->toArray(), true));

        $this->_cc->assertCalUserAttendee(false);

        // insert one by one in a single transaction
        foreach ($events as $event) {
            $existingEvent = $existingEvents->find('uid', $event->uid);
            if (is_array($this->_options['overwriteOrganizer'])) {
                foreach($this->_options['overwriteOrganizer'] as $fieldName => $value) {
                    $event->{$fieldName} = $value;
                }
            }

            if ($this->_options['attendeeStrategy'] === 'add' && is_array($this->_options['attendee'])) {
                $attendees = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, $this->_options['attendee']);
                foreach($attendees as $attendee) {
                    if (! Calendar_Model_Attender::getAttendee($event->attendee, $attendee)) {
                        $event->attendee->addRecord($attendee);
                    }
                }

            } else if ($this->_options['attendeeStrategy'] === 'replace') {

                $event->attendee = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, $this->_options['attendee']);
            }

            try {
                if (! $existingEvent) {
                    $event->container_id = $this->_options['container_id'];
                    $event = $this->_cc->create($event, FALSE);
                    $this->_importResult['totalcount'] += 1;
                    $this->_importResult['results']->addRecord($event);
                } else if ($this->_doUpdateExisting($event, $existingEvent)) {
                    $this->_updateEvent($event, $existingEvent);
                } else {
                    $this->_importResult['duplicatecount'] += 1;
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                    . ' Import failed for Event ' . $event->summary);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . print_r($event->toArray(), TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . $e);
                $this->_importResult['failcount'] += 1;
            }
        }

        $this->_deleteMissing($events);

        Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' totalcount: ' . $this->_importResult['totalcount']
            . ' / duplicates: ' . $this->_importResult['duplicatecount']
            . ' / fails: ' . $this->_importResult['failcount']);

        foreach ($ccAssertions as $method => $oldValue) {
            $this->_cc->{$method}($oldValue);
        }

        return $this->_importResult;
    }

    protected function _updateEvent(Calendar_Model_Event $event, Calendar_Model_Event $existingEvent)
    {
        $event->container_id = $this->_options['container_id'];
        $event->id = $existingEvent->getId();
        $event->last_modified_time = ($existingEvent->last_modified_time instanceof Tinebase_DateTime) ? clone $existingEvent->last_modified_time : NULL;
        $event->seq = $existingEvent->seq;

        if ($this->_options['keepExistingAttendee']) {
            static::checkForExistingAttendee($event, $existingEvent);
        }

        $diff = $event->diff($existingEvent, array(
            'seq',
            'creation_time',
            'created_by',
            // allow organizer or transp change?
            'transp',
            'organizer',
            'originator_tz',
            // why are they here?
            'freebusyGrant',
            'readGrant',
            'syncGrant',
            'exportGrant',
            'editGrant',
            'deleteGrant',
            'privateGrant',
        ));
        if (! $diff->isEmpty()) {
            $event = $this->_cc->update($event, FALSE);
            $this->_importResult['updatecount'] += 1;
        }
        $this->_importResult['results']->addRecord($event);
    }

    public static function checkForExistingAttendee(Calendar_Model_Event $event, Calendar_Model_Event $existingEvent)
    {
        $existingAttendee = $existingEvent->attendee instanceof Tinebase_Record_RecordSet
            ? $existingEvent->attendee
            : new Tinebase_Record_RecordSet('Calendar_Model_Attendee');
        foreach ($event->attendee as $attender) {
            if (Calendar_Model_Attender::getAttendee($existingAttendee, $attender) === null) {
                $existingAttendee->addRecord($attender);
            }
        }
        $event->attendee = $existingAttendee;
    }

    /**
     * get import events
     *
     * @param mixed $_resource
     * @return Tinebase_Record_RecordSet
     */
    abstract protected function _getImportEvents($_resource, $container);

    /**
     * @param $event
     * @param $existingEvent
     * @return bool
     *
     * TODO do we always check the seq here?
     */
    protected function _doUpdateExisting($event, $existingEvent)
    {
        return $this->_options['forceUpdateExisting'] || ($this->_options['updateExisting'] && $event->seq > $existingEvent->seq);
    }

    /**
     * delete missing events
     *
     * @param Tinebase_Record_RecordSet $importedEvents
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _deleteMissing(Tinebase_Record_RecordSet $importedEvents): void
    {
        if ($this->_options['deleteMissing']) {
            $container = Tinebase_Container::getInstance()->getContainerById($this->_options['container_id']);
            if (Tinebase_Core::isReplica() && isset($container->xprops()[Calendar_Model_Event::XPROPS_REPLICATABLE])
                && $container->xprops()[Calendar_Model_Event::XPROPS_REPLICATABLE]) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__
                        . ' We cannot delete missing events from a replicable container on a replica');
                }
                return;
            }

            $missingEventsFilter = new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()),
                array('field' => 'uid', 'operator' => 'notin', 'value' => array_unique($importedEvents->uid)),
                array('field' => 'period', 'operator' => 'within', 'value' => array(
                    'from'  => new Tinebase_DateTime('now'),
                    'until' => new Tinebase_DateTime('+ 100 years'),
                ))
            ));
            $missingEvents = Calendar_Controller_Event::getInstance()->search($missingEventsFilter);
            if ($missingEvents->count() > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Deleting ' . count($missingEvents) . ' missing events');
                }
                Calendar_Controller_Event::getInstance()->delete($missingEvents->id);
            }
        }
    }

    /**
     * function is not used
     *
     * @param  mixed $_resource
     * @return array|boolean|null
     */
    protected function _getRawData(&$_resource)
    {
        return null;
    }
}
