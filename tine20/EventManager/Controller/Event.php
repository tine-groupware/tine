<?php

declare(strict_types=1);

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de> Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2020-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Event extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<EventManager_Controller_Event> */
    use Tinebase_Controller_SingletonTrait;

    protected static array $updateStatisticsCache = [];

    /** @var array<string, string> event id => folder name, captured before deletion */
    protected array $_eventNamesBeforeDelete = [];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
        $this->_modelName = EventManager_Model_Event::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Event::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Event::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $this->_assertUniqueEventName($_record);
        if ($_record->{EventManager_Model_Event::FLD_TOTAL_PLACES}) {
            $_record->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} =
                $_record->{EventManager_Model_Event::FLD_TOTAL_PLACES};
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        if ($this->getEventName($_record) !== $this->getEventName($_oldRecord)) {
            $this->_assertUniqueEventName($_record);
        }
    }

    public function updateStatistics(string $event_id, ?string $registration_id = null, bool $is_update = false): void
    {
        $this->_handleDependentRecords = false;
        try {
            if (static::$updateStatisticsCache[$event_id] ?? false) {
                return;
            }
            static::$updateStatisticsCache[$event_id] = true;

            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(fn()
            => $this->clearUpdateStatisticsCache());

            $event = $this->get($event_id);
            $registrations = $event->{EventManager_Model_Event::FLD_REGISTRATIONS};
            $accepted_registrations = [];
            foreach ($registrations as $registration) {
                if (
                    $registration->is_deleted !== 1
                    && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "3"
                ) {
                    $accepted_registrations[] = $registration;
                }
            }

            $event->{EventManager_Model_Event::FLD_BOOKED_PLACES} = count($accepted_registrations);
            $event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} =
                intval($event->{EventManager_Model_Event::FLD_TOTAL_PLACES}) -
                intval($event->{EventManager_Model_Event::FLD_BOOKED_PLACES});
            $today = Tinebase_DateTime::today();

            if (
                $event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 0
                || ($event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL}
                && $event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL} < $today)
            ) {
                foreach ($registrations as $registration) {
                    if (
                        $registration->id === $registration_id
                        && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "3"
                        && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "2"
                    ) {
                        if ($event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 0) {
                            $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 1;
                        } elseif ($event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL} < $today) {
                            if (!$is_update) {
                                $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            }
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 2;
                        } else {
                            $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 3;
                        }
                        EventManager_Controller_Registration::getInstance()->update($registration);
                    }
                }
            }
            $this->update($event);
        } catch (Tinebase_Exception_NotFound $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . 'Record should not be found when deleted: ' . $e->getMessage());
            }
        } finally {
            $this->_handleDependentRecords = true;
        }
    }

    private function clearUpdateStatisticsCache(): void
    {
        static::$updateStatisticsCache = [];
    }

    protected function _inspectAfterSetRelatedDataCreate($updatedRecord, $_record)
    {
        $this->_createImageWatermarks($updatedRecord);
        if (count($updatedRecord->{EventManager_Model_Event::FLD_APPOINTMENTS}) > 0) {
            $this->_createCalendarEvent(
                $updatedRecord,
                $_record,
                true,
                $updatedRecord->{EventManager_Model_Event::FLD_APPOINTMENTS}
            );
        } else {
            $this ->_createCalendarEvent($updatedRecord, $_record);
        }
    }

    public function getEventName($eventRecord)
    {
        $byLang = [];
        foreach ($eventRecord->{EventManager_Model_Event::FLD_NAME} ?? [] as $localized) {
            $byLang[$localized->language] ??= (string) $localized->text;
        }

        foreach (['de', 'en'] as $lang) {
            if (($byLang[$lang] ?? '') !== '') {
                return str_replace('/', '-', $byLang[$lang]);
            }
        }

        return str_replace('/', '-', reset($byLang) ?: '');
    }

    public function getEventFolderPath(string $eventName): string
    {
        $basePath = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_FOLDER_FILEMANAGER_PATH);
        return $basePath . '/' . $eventName;
    }

    protected function _renameEventFolder(string $oldName, string $newName): void
    {
        $fs = Tinebase_FileSystem::getInstance();
        $prefix = $fs->getApplicationBasePath('Filemanager') . '/folders/';
        $oldPath = $this->getEventFolderPath($oldName);
        $newPath = $this->getEventFolderPath($newName);

        if (!$fs->isDir($prefix . $oldPath)) {
            return;
        }

        $caseOnly = mb_strtolower($oldName) === mb_strtolower($newName);
        if (!$caseOnly && $fs->fileExists($prefix . $newPath)) {
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            throw new Tinebase_Exception_SystemGeneric(
                $translate->_('A folder with this event name already exists.')
            );
        }

        Filemanager_Controller_Node::getInstance()->moveNodes([$oldPath], [$newPath]);
    }

    protected function _assertUniqueEventName(Tinebase_Record_Interface $record): void
    {
        if ($record->{EventManager_Model_Event::FLD_IS_TEMPLATE}) {
            return;
        }

        $normalize = fn(string $name): string => mb_strtolower(trim($name));
        $newName = $normalize($this->getEventName($record));
        if ($newName === '') {
            return;
        }

        $events = $this->search();
        Tinebase_Record_Expander::expandRecords($events);

        foreach ($events as $existing) {
            if (
                $existing->getId() !== $record->getId()
                && !$existing->{EventManager_Model_Event::FLD_IS_TEMPLATE}
                && $normalize($this->getEventName($existing)) === $newName
            ) {
                $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
                throw new Tinebase_Exception_SystemGeneric(
                    $translate->_('An event with this name already exists. Please choose a different name.')
                );
            }
        }
    }

    protected function _createCalendarEvent($updatedRecord, $_record, $is_appointment = false, $appointments = [])
    {
        if ($updatedRecord->{EventManager_Model_Event::FLD_IS_TEMPLATE}) {
            return;
        }

        $eventName = $this->getEventName($updatedRecord);

        if ($is_appointment) {
            foreach ($appointments as $appointment) {
                [$dtstart, $dtend, $isAllDay] = $this->_getAppointmentTimes($appointment);
                $this->_createSingleCalendarEvent(
                    $updatedRecord,
                    $this->_buildAppointmentSummary($eventName, $appointment),
                    $dtstart,
                    $dtend,
                    $isAllDay,
                    $this->createTagForEvent(),
                    $appointment->getId()
                );
            }
            return;
        }

        $isAllDay = empty($updatedRecord->{EventManager_Model_Event::FLD_START});
        $dtstart = !$isAllDay
            ? $updatedRecord->{EventManager_Model_Event::FLD_START}
            : Tinebase_DateTime::now();
        $dtend = !empty($updatedRecord->{EventManager_Model_Event::FLD_END})
            ? $updatedRecord->{EventManager_Model_Event::FLD_END}
            : $dtstart->getClone()->addHour(1);

        $this->_createSingleCalendarEvent(
            $updatedRecord,
            $eventName,
            $dtstart,
            $dtend,
            $isAllDay,
            $this->createTagForEvent()
        );
    }

    protected function _createSingleCalendarEvent($updatedRecord, $summary, $dtstart, $dtend, $is_all_day_event = false, $tag = null, $appointmentId = null)
    {
        $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        try {
            $calendarName = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CALENDAR_NAME);
            $container = Tinebase_Container::getInstance()->getContainerByName(
                Calendar_Model_Event::class,
                $calendarName,
                Tinebase_Model_Container::TYPE_SHARED,
            );
        } catch (Tinebase_Exception_NotFound $e) {
            throw new Tinebase_Exception_SystemGeneric(
                $translate->_('To create the event there must be a shared calendar. Please create it first or contact your administrator.')
            );
        }

        if ($container) {
            $newEvent = new Calendar_Model_Event([
                'summary'           => $summary,
                'dtstart'           => $dtstart,
                'dtend'             => $dtend,
                'organizer'         => $updatedRecord->created_by,
                'uid'               => Calendar_Model_Event::generateUID(),
                'is_all_day_event'  => $is_all_day_event,
                'container_id'      => $container->getId(),

                Tinebase_Model_Grants::GRANT_READ    => true,
                Tinebase_Model_Grants::GRANT_EDIT    => true,
                Tinebase_Model_Grants::GRANT_DELETE  => true,
            ]);

            if ($tag) {
                $newEvent->tags = new Tinebase_Record_RecordSet(Tinebase_Model_Tag::class, [$tag]);
            }

            $grants = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $container);
            if ($grants->editGrant === true) {
                $calendarEvent = Calendar_Controller_Event::getInstance()->create($newEvent);
            } else {
                throw new Tinebase_Exception_SystemGeneric(
                    $translate->_('You do not have permission to add an event to the shared calendar. Please contact your administrator to update your permissions.')
                );
            }

            $relation = new Tinebase_Model_Relation([
                'own_model'         => EventManager_Model_Event::class,
                'own_backend'       => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'own_id'            => $updatedRecord->getId(),
                'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'     => Calendar_Model_Event::class,
                'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id'        => $calendarEvent->getId(),
                'type'              => 'CALENDAR_EVENT',
                'remark'            => $appointmentId,
            ]);

            Tinebase_Relations::getInstance()->addRelation($relation, $updatedRecord);
        }
    }

    /**
     * @return Tinebase_Model_Relation[] only the relations to synced calendar entries
     */
    protected function _getCalendarEventRelations(Tinebase_Record_Interface $record): array
    {
        $result = [];
        foreach ($record->relations ?? [] as $relation) {
            if (
                $relation->own_id === $record->getId()
                && $relation->type === 'CALENDAR_EVENT'
                && $relation->related_model === Calendar_Model_Event::class
            ) {
                $result[] = $relation;
            }
        }
        return $result;
    }

    protected function _getRelatedCalendarEvent(Tinebase_Model_Relation $relation): ?Calendar_Model_Event
    {
        try {
            return Calendar_Controller_Event::getInstance()->get($relation->related_id);
        } catch (Tinebase_Exception_NotFound $e) {
            return null;
        }
    }

    protected function _buildAppointmentSummary(string $eventName, $appointment): string
    {
        $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        return $eventName . ' ' . $translate->_('Session') . ' '
            . $appointment->{EventManager_Model_Appointment::FLD_SESSION_NUMBER};
    }

    protected function _getAppointmentTimes($appointment): array
    {
        $sessionDate = $appointment->{EventManager_Model_Appointment::FLD_SESSION_DATE};
        $startTime = $appointment->{EventManager_Model_Appointment::FLD_START_TIME};
        $endTime = $appointment->{EventManager_Model_Appointment::FLD_END_TIME};
        $isAllDay = empty($startTime);

        $dtstart = $sessionDate->getClone();
        if (!$isAllDay) {
            [$hour, $minute, $second] = array_pad(explode(':', $startTime), 3, 0);
            $dtstart->setTime((int)$hour, (int)$minute, (int)$second);
        }

        if (!empty($endTime)) {
            $dtend = $sessionDate->getClone();
            [$hour, $minute, $second] = array_pad(explode(':', $endTime), 3, 0);
            $dtend->setTime((int)$hour, (int)$minute, (int)$second);
        } else {
            $dtend = $dtstart->getClone()->addHour(1);
        }

        return [$dtstart, $dtend, $isAllDay];
    }

    protected function createTagForEvent()
    {
        try {
            $eventTag = Tinebase_Tags::getInstance()->getTagByName('automatic EventManager');
        } catch (Tinebase_Exception_NotFound $e) {
            $eventTag = new Tinebase_Model_Tag([
                'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                'name'  => 'automatic EventManager',
                'description' => 'this event was automatically created by the event manager',
                'color' => '#FF0000',
            ]);
            $eventTag = Tinebase_Tags::getInstance()->createTag($eventTag, true);
            $right = new Tinebase_Model_TagRight([
                'tag_id'        => $eventTag->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                'view_right'    => true,
                'use_right'     => true
            ]);
            Tinebase_Tags::getInstance()->setRights($right);
        }
        return $eventTag;
    }

    public function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);
        $this->_createImageWatermarks($updatedRecord);
        $eventName = $this->getEventName($updatedRecord);
        $oldEventName = $this->getEventName($currentRecord);
        if ($oldEventName !== '' && $eventName !== '' && $oldEventName !== $eventName) {
            $this->_renameEventFolder($oldEventName, $eventName);
        }

        // check if $currentrecord had options that have been deleted in $record
        // - those need to be removed from registrations
        $diff = $currentRecord->{EventManager_Model_Event::FLD_OPTIONS}
            ->diff($updatedRecord->{EventManager_Model_Event::FLD_OPTIONS});
        foreach ($diff->removed as $removed_option) {
            foreach ($updatedRecord->{EventManager_Model_Event::FLD_REGISTRATIONS} as $registration) {
                foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $booked_option) {
                    $option = $booked_option->{EventManager_Model_BookedOption::FLD_OPTION};
                    $option_id = is_object($option) ? $option->getId() : $option;
                    if ($removed_option->getId() === $option_id) {
                        $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}
                            ->removeRecord($booked_option);
                        EventManager_Controller_Registration::getInstance()->update($registration);
                    }
                }
            }
        }

        // changes in event that influence the calendar entries
        $changed_fields = $currentRecord->diff($updatedRecord)->diff;
        $nameChanged  = array_key_exists('name', $changed_fields);
        $startChanged = array_key_exists('start', $changed_fields);
        $endChanged   = array_key_exists('end', $changed_fields);
        $appointments = $updatedRecord->{EventManager_Model_Event::FLD_APPOINTMENTS};
        $wholeEventEntry = null;

        foreach ($this->_getCalendarEventRelations($updatedRecord) as $relation) {
            if (empty($relation->remark)) {
                if (!$calendarEvent = $this->_getRelatedCalendarEvent($relation)) {
                    continue;
                }
                $wholeEventEntry = $calendarEvent;
                if (!$nameChanged && !$startChanged && !$endChanged) {
                    continue;
                }
                if ($nameChanged) {
                    $calendarEvent->summary = $eventName;
                }
                if ($startChanged) {
                    $hasStart = !empty($updatedRecord->{EventManager_Model_Event::FLD_START});
                    $calendarEvent->dtstart = $hasStart
                        ? $updatedRecord->{EventManager_Model_Event::FLD_START}
                        : Tinebase_DateTime::now();
                    $calendarEvent->is_all_day_event = !$hasStart;
                }
                if ($startChanged || $endChanged) {
                    $calendarEvent->dtend = !empty($updatedRecord->{EventManager_Model_Event::FLD_END})
                        ? $updatedRecord->{EventManager_Model_Event::FLD_END}
                        : $calendarEvent->dtstart->getClone()->addHour(1);
                }
                $wholeEventEntry = Calendar_Controller_Event::getInstance()->update($calendarEvent);
            } elseif ($nameChanged) {
                $appointment = $appointments->getById($relation->remark);
                if (!$appointment || !$calendarEvent = $this->_getRelatedCalendarEvent($relation)) {
                    continue;
                }
                $calendarEvent->summary = $this->_buildAppointmentSummary($eventName, $appointment);
                Calendar_Controller_Event::getInstance()->update($calendarEvent);
            }
        }

        // changes in appointments that influence the calendar entries
        $appointments_diff = $currentRecord->{EventManager_Model_Event::FLD_APPOINTMENTS}
            ->diff($appointments);

        if (count($appointments_diff->added) > 0) {
            if ($wholeEventEntry) {
                Calendar_Controller_Event::getInstance()->delete($wholeEventEntry);
                $wholeEventEntry = null;
            }
            $this->_createCalendarEvent($updatedRecord, $record, true, $appointments_diff->added);
        }
        foreach ($appointments_diff->modified as $modified_appointment) {
            $this->_updateCalendarEvent($updatedRecord, $modified_appointment);
        }
        foreach ($appointments_diff->removed as $removed_appointment) {
            $this->_deleteCalendarEvent($updatedRecord, $removed_appointment);
        }

        if (
            $wholeEventEntry === null
            && count($appointments_diff->removed) > 0
            && count($appointments) === 0
        ) {
            $this->_createCalendarEvent($updatedRecord, $record);
        }
    }
    protected function _updateCalendarEvent($updatedRecord, $appointmentDiff)
    {
        $currentAppointment = $updatedRecord->{EventManager_Model_Event::FLD_APPOINTMENTS}
            ->getById($appointmentDiff->id);
        if (!$currentAppointment) {
            return;
        }

        $relevantFields = [
            EventManager_Model_Appointment::FLD_SESSION_NUMBER,
            EventManager_Model_Appointment::FLD_SESSION_DATE,
            EventManager_Model_Appointment::FLD_START_TIME,
            EventManager_Model_Appointment::FLD_END_TIME,
        ];
        if (!array_intersect_key(array_flip($relevantFields), (array) $appointmentDiff->diff)) {
            return;
        }

        foreach ($this->_getCalendarEventRelations($updatedRecord) as $relation) {
            if ($relation->remark !== $currentAppointment->getId()) {
                continue;
            }
            if (!$calendarEvent = $this->_getRelatedCalendarEvent($relation)) {
                return;
            }

            [$dtstart, $dtend, $isAllDay] = $this->_getAppointmentTimes($currentAppointment);
            $calendarEvent->summary = $this->_buildAppointmentSummary(
                $this->getEventName($updatedRecord),
                $currentAppointment
            );
            $calendarEvent->dtstart = $dtstart;
            $calendarEvent->dtend = $dtend;
            $calendarEvent->is_all_day_event = $isAllDay;

            Calendar_Controller_Event::getInstance()->update($calendarEvent);
            return;
        }
    }

    protected function _deleteCalendarEvent($updatedRecord, $appointment = null)
    {
        foreach ($this->_getCalendarEventRelations($updatedRecord) as $relation) {
            if ($appointment !== null && $relation->remark !== $appointment->getId()) {
                continue;
            }
            if ($calendarEvent = $this->_getRelatedCalendarEvent($relation)) {
                Calendar_Controller_Event::getInstance()->delete($calendarEvent);
            }
        }
    }

    public function delete($_ids)
    {
        if ($_ids instanceof Tinebase_Record_RecordSet) {
            $_ids = $_ids->getArrayOfIds();
        } elseif ($_ids instanceof Tinebase_Record_Interface) {
            $_ids = [$_ids->getId()];
        }
        $_ids = (array) $_ids;

        foreach ($_ids as $id) {
            try {
                $event = $this->get($id);
                if (!$event->{EventManager_Model_Event::FLD_IS_TEMPLATE}) {
                    $this->_eventNamesBeforeDelete[$id] = $this->getEventName($event);
                }
            } catch (Tinebase_Exception_NotFound $e) {
                // already gone, nothing to rename
            }
        }
        return parent::delete($_ids);
    }

    protected function _getFreeEventFolderName(string $eventName, string $label): string
    {
        $fs = Tinebase_FileSystem::getInstance();
        $prefix = $fs->getApplicationBasePath('Filemanager') . '/folders/';

        $name = "$eventName ($label)";
        for ($i = 2; $fs->fileExists($prefix . $this->getEventFolderPath($name)); $i++) {
            $name = "$eventName ($label $i)";
        }
        return $name;
    }
    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);
        $this->_deleteCalendarEvent($record);

        $id = $record->getId();
        $eventName = $this->_eventNamesBeforeDelete[$id] ?? '';
        unset($this->_eventNamesBeforeDelete[$id]);

        if ($eventName === '') {
            return;
        }

        try {
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            $deletedName = $this->_getFreeEventFolderName($eventName, $translate->_('deleted'));
            $this->_renameEventFolder($eventName, $deletedName);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
    }


    public function publicApiMainScreen($path = null)
    {
        $locale = Tinebase_Core::getLocale();
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=EventManager";

        $context = [
            'lang' => $locale,
        ];

        $jsFiles[] = 'EventManager/js/eventManagerWebsite/src/index.es6.js';

        $mainScreen = Tinebase_Frontend_Http_SinglePageApplication::getClientHTML(
            $jsFiles,
            EventManager_Config::APP_NAME,
            context: $context
        );

        return $mainScreen->withHeader(
            "Content-Security-Policy",
            preg_replace(
                '/frame-ancestors.*;?/',
                'frame-ancestors *;',
                implode(" ", $mainScreen->getHeader('Content-Security-Policy'))
            )
        );
    }

    public function publicApiStatic()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiEvents()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                EventManager_Model_Event::class,
                [
                    [
                        'field' => EventManager_Model_Event::FLD_STATUS,
                        'operator' => 'equals',
                        'value' => '1' // active events
                    ],
                ],
            );
            $eventListOfRecords = EventManager_Controller_Event::getInstance()
                ->search($filter);
            $events = $eventListOfRecords->getFirstRecord();
            $converter = Tinebase_Convert_Factory::factory($events);
            $eventsArray = $converter->fromTine20RecordSet($eventListOfRecords);

            for ($i = 0; $i < count($eventsArray); $i++) {
                $eventArray = $eventsArray[$i];

                // public endpoint: never expose other people's registrations or internal metadata
                unset(
                    $eventArray[EventManager_Model_Event::FLD_REGISTRATIONS],
                    $eventArray['relations'],
                    $eventArray['notes']
                );

                $localizationFields = ['name', 'subheading', 'description'];
                foreach ($localizationFields as $localizationField) {
                    if (empty($eventArray[$localizationField])) {
                        $eventArray[$localizationField] = null;
                    }
                    if (!empty($eventArray[$localizationField]) && is_array($eventArray[$localizationField])) {
                        foreach ($eventArray[$localizationField] as $field) {
                            if ($field['language'] === 'de') {
                                $eventArray[$localizationField] = $field['text'];
                            }
                        }
                    }
                }
                $eventsArray[$i] = $eventArray;
            }

            $response->getBody()->write(json_encode($eventsArray));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetEvent($event_id, $token = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $event = $this->get($event_id);
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($event);

            $converter = Tinebase_Convert_Factory::factory($event);
            $eventArray = $converter->fromTine20Model($event);

            // public endpoint: never expose other people's registrations or internal metadata
            unset(
                $eventArray[EventManager_Model_Event::FLD_REGISTRATIONS],
                $eventArray['relations'],
                $eventArray['notes']
            );

            $localizationFields = ['name', 'subheading', 'description'];
            foreach ($localizationFields as $localizationField) {
                if (empty($eventArray[$localizationField])) {
                    $eventArray[$localizationField] = null;
                }
                if (!empty($eventArray[$localizationField]) && is_array($eventArray[$localizationField])) {
                    foreach ($eventArray[$localizationField] as $field) {
                        if ($field['language'] === 'de') {
                            $eventArray[$localizationField] = $field['text'];
                        }
                    }
                }
            }

            $eventArray = $this->_enrichContactFields(
                $eventArray,
                'participant_contact_fields',
                'required_participant_contact_fields'
            );
            $eventArray = $this->_enrichContactFields(
                $eventArray,
                'registrant_contact_fields',
                'required_registrant_contact_fields'
            );

            $eventArray['country_list'] = Tinebase_Translation::getCountryList()['results'];
            $eventArray['guardian_required_age'] = (int) EventManager_Config::getInstance()
                ->get(EventManager_Config::GUARDIAN_REQUIRED_AGE);

            if (!empty($eventArray['options'])) {
                foreach ($eventArray['options'] as &$option) {
                    if (!isset($option['option_config'])) {
                        $option['option_config'] = [];
                    }
                }
            }

            $eventArray['registration_communication_preference'] = EventManager_Config::getInstance()
                ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->toArray()['records'];

            $response->getBody()->write(json_encode($eventArray));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    protected function _enrichContactFields(array $eventArray, string $fieldsKey, string $requiredKey): array
    {
        if (empty($eventArray[$fieldsKey]) || !is_array($eventArray[$fieldsKey])) {
            return $eventArray;
        }

        $contactModelConfig = Addressbook_Model_Contact::getConfiguration();
        $fields = $contactModelConfig->getFields();

        $enriched = [];
        $requiredFields = [];
        foreach ($eventArray[$fieldsKey] as $fieldName => $fieldConfig) {
            if (!is_array($fieldConfig)) { // legacy format: 'field_name' => bool
                $fieldConfig = ['optional' => (bool)$fieldConfig, 'required' => false];
            }
            $optional = isset($fieldConfig['optional']) ? (bool)$fieldConfig['optional'] : false;
            $required = isset($fieldConfig['required']) ? (bool)$fieldConfig['required'] : false;

            $enriched[$fieldName] = [
                'optional' => $optional,
                'required' => $required,
                'label'    => isset($fields[$fieldName]['label'])
                    ? Tinebase_Translation::getTranslation('Addressbook')
                        ->translate($fields[$fieldName]['label'])
                    : $fieldName,
            ];

            if ($required) {
                $requiredFields[] = $fieldName;
            }
        }

        $eventArray[$fieldsKey] = $enriched;
        $eventArray[$requiredKey] = $requiredFields;

        return $eventArray;
    }

    public function publicApiGetAccountDetails($token)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            if ($token) {
                if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                    throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                }

                if ($token != 'preview') {
                    try {
                        $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                    } catch (Exception $jwtException) {
                        $response = new \Laminas\Diactoros\Response('php://memory', 400);
                        $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
                        return $response;
                    }

                    $email_registrant = $decoded->email ?? '';
                    if ($email_registrant === '') {
                        $response = new \Laminas\Diactoros\Response('php://memory', 400);
                        $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
                        return $response;
                    }

                    $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email_registrant);
                    $dependant_participant = [];
                    $registrations_data = [];
                    $accountOwner = [];

                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                        EventManager_Model_Register_Contact::class,
                        [
                            [
                                'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE,
                                'operator' => 'equals',
                                'value' => 'registrant'
                            ],
                            [
                                'field' => 'email',
                                'operator' => 'equals',
                                'value' => $email_registrant
                            ],
                        ],
                    );
                    $registrantContacts = EventManager_Controller_Register_Contact::getInstance()
                        ->search($filter);
                    $registrationIds = array_values(array_unique(array_filter(
                        $registrantContacts->registration_id
                    )));

                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                        EventManager_Model_Register_Contact::class,
                        [
                            [
                                'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE,
                                'operator' => 'equals',
                                'value' => 'participant'
                            ],
                            [
                                'field' => 'email',
                                'operator' => 'equals',
                                'value' => $email_registrant
                            ],
                        ],
                    );
                    $ownParticipant = EventManager_Controller_Register_Contact::getInstance()
                        ->search($filter)->getFirstRecord();

                    if ($ownParticipant) {
                        $accountOwner[] = $ownParticipant->toArray();
                    }

                    foreach ($registrationIds as $registrationId) {
                        try {
                            $registration = EventManager_Controller_Registration::getInstance()
                                ->get($registrationId);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                    . ' Registration no longer exists, skipping: ' . $tenf->getMessage());
                            }
                            continue;
                        }

                        $statusId = $registration->{EventManager_Model_Registration::FLD_STATUS};
                        $status = EventManager_Config::getInstance()
                            ->get(EventManager_Config::REGISTRATION_STATUS)->records
                            ->getById($statusId);
                        $registration->{EventManager_Model_Registration::FLD_STATUS} = $status
                            ? $status->value
                            : $statusId;

                        $registrationArray = $registration->toArray();
                        $registrationArray['status_id'] = $statusId;
                        $registrations_data[] = $registrationArray;
                    }

                    if (!empty($contact)) {
                        if (count($registrations_data) === 0) {
                            $registrations_data = $contact->toArray();
                        }

                        $contact = Addressbook_Controller_Contact::getInstance()
                            ->get($contact->getId()); // necessary to get relations
                        $dependant_participant = $this->getRelatedContacts($contact);
                    } else {
                        $registrant = [
                            'email' => $email_registrant,
                        ];
                        $registrations_data = $registrant;
                    }

                    if (count($accountOwner) === 0 && !empty($contact)) {
                        $accountOwner[] = $contact->toArray();
                    }

                    $response = new \Laminas\Diactoros\Response();
                    $response->getBody()->write(json_encode([
                        $accountOwner,
                        $registrations_data,
                        $dependant_participant
                    ]));
                    return $response;
                }
            }
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode([]));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function getRelatedContacts($contact)
    {
        $related_contacts = [];
        foreach ($contact->relations as $related_contact) {
            try {
                if ($related_contact->related_model === Addressbook_Model_Contact::class) {
                    $related_contacts[] = Addressbook_Controller_Contact::getInstance()
                        ->get($related_contact->related_id)->toArray();
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Skipping dangling relation to missing contact ' . $related_contact->related_id
                        . ': ' . $tenf->getMessage());
                }
            }
        }
        return $related_contacts;
    }

    protected function _createImageWatermarks(EventManager_Model_Event $event): void
    {
        // TODO do we always want to overwrite? we would need to check if source changed...
        $overwrite = true;
        foreach ($event->images as $image) {
            $imageAttachment = $event->attachments->getById($image->{EventManager_Model_ImageMetadata::FLD_NODE_ID});
            if (!$imageAttachment) {
                // attachment already deleted - do nothing
                continue;
            }
            $node = Tinebase_FileSystem::getInstance()->get($image->{EventManager_Model_ImageMetadata::FLD_NODE_ID});
            $image->source = $image->source ?? 'Watermark Text';
            Tinebase_ActionQueue::getInstance()->queueAction(
                'Tinebase_FileSystem_RecordAttachments.createWatermark',
                $node,
                $image->source,
                $overwrite
            );
        }
    }

    public function publicApiGetImages()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $images = EventManager_Controller_ImageMetadata::getInstance()->search();
            foreach ($images as $image) {
                $image->image_vfs = EventManager_Controller_ImageMetadata::getImageUrl(
                    EventManager_Config::APP_NAME,
                    $image->node_id,
                    -1,
                    -1
                );
            }
            $imagesArray = [];
            foreach ($images as $image) {
                $converter = Tinebase_Convert_Factory::factory($image);
                $imagesArray[] = $converter->fromTine20Model($image);
            }
            $response->getBody()->write(json_encode($imagesArray));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetImage($imageId, $width = -1, $height = -1, $ratiomode = 0)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $image = Tinebase_Controller::getInstance()->getImage('Tinebase', $imageId, Tinebase_Model_Image::LOCATION_VFS_WATERMARK);
            if ($width != -1 && $height != -1) {
                Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
            }
            $response = new \Laminas\Diactoros\Response(headers: ['Content-Type' => $image->mime]);
            $response->getBody()->write($image->blob);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }
}
