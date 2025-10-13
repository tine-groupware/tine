<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * EventRoleConfig controller for CrewScheduling
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_EventRoleConfig extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_modelName = CrewScheduling_Model_EventRoleConfig::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => CrewScheduling_Model_EventRoleConfig::class,
            'tableName'     => CrewScheduling_Model_EventRoleConfig::TABLE_NAME,
            'modlogActive'  => true
        ));
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var CrewScheduling_Controller_SchedulingRole
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return CrewScheduling_Controller_SchedulingRole
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new CrewScheduling_Controller_EventRoleConfig();
        }

        return self::$_instance;
    }
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_Abstract
            && $_eventObject->persistentObserver->observable_model === Calendar_Model_Event::class
        ) {
            switch (get_class($_eventObject)) {
                case Calendar_Event_InspectEvent::class:
                    $this->manageEventStatus($_eventObject->observable);
                    break;
            }
        }
    }

    // @TODO: what about recur events?
    public function manageEventStatus(Calendar_Model_Event $event) : Calendar_Model_Event
    {
        if ($event->rrule) return $event;

        $eventRoleConfigs = CrewScheduling_Model_EventRoleConfig::getFromEvent($event);

        /** @var array $RTKeysAttendeeMap RTKey => attendee[] */
        $RTKeysAttendeeMap = null;
        $action = 'none';
        $leadTime = 0;
        /** @var CrewScheduling_Model_EventRoleConfig $eventRoleConfig */
        foreach ($eventRoleConfigs as $eventRoleConfig) {
            if ($eventRoleConfig->{CrewScheduling_Model_EventRoleConfig::FLD_EXCEEDANCE_ACTION} !== CrewScheduling_Config::ACTION_NONE
                || $eventRoleConfig->{CrewScheduling_Model_EventRoleConfig::FLD_SHORTFALL_ACTION} !== CrewScheduling_Config::ACTION_NONE) {

                // compute map once
                if (! is_array($RTKeysAttendeeMap)) {
                    $RTKeysAttendeeMap = array();

                    // @TODO expand RTs first ? move to CrewScheduling_Model_AttendeeRole?
                    /** @var Calendar_Model_Attender $attendee */
                    foreach($event->attendee ?? [] as $attendee) {
                        if ($attendee->{Calendar_Model_Attender::FLD_STATUS} !== 'ACCEPTED') continue;
                        /** @var CrewScheduling_Model_AttendeeRole $attendeeRole */
                        foreach ($attendee->{CrewScheduling_Config::CREWSHEDULING_ROLES} as $attendeeRole) {
                            $attendeeKey = $attendeeRole->getRoleTypesKey();
                            if (!array_key_exists($attendeeKey, $RTKeysAttendeeMap)) {
                                $RTKeysAttendeeMap[$attendeeKey] = [];
                            }
                            $RTKeysAttendeeMap[$attendeeKey][] = $attendee;
                        }
                    }
                }

                $requiredCount = $eventRoleConfig->{CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE};
                $eRCKey = $eventRoleConfig->getRoleTypesKey();
                $actualCount = isset($RTKeysAttendeeMap[$eRCKey]) ? count($RTKeysAttendeeMap[$eRCKey]) : 0;
                if ($requiredCount === $actualCount) continue;
                $action = CrewScheduling_Model_EventRoleConfig::mergeActions($action, $eventRoleConfig->{$requiredCount > $actualCount ?
                    CrewScheduling_Model_EventRoleConfig::FLD_SHORTFALL_ACTION : CrewScheduling_Model_EventRoleConfig::FLD_EXCEEDANCE_ACTION});
                $leadTime = max($leadTime, $eventRoleConfig->{\CrewScheduling_Model_EventRoleConfig::FLD_ROLE}->{\CrewScheduling_Model_SchedulingRole::FLD_LEADTIME});
            }
        }

        $managedStatus = $event->xprops()['CS-MANAGED-EVENT-STATUS'] ?? null;
        if ($managedStatus && $managedStatus !== $event->status) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " NOT applying exceedance/shortfall action '{$action}' to event '{$event->id}' as event status is managed manually");
            }
        } else if ($managedStatus || $action !== 'none') {
            $eventStatus = CrewScheduling_Model_EventRoleConfig::ACTION_STATUS_MAP[$action];
            if ($eventStatus === 'CANCELLED' && Tinebase_DateTime::now()->addDay($leadTime) < $event->dtend) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " lead time not reached -> not applying exceedance/shortfall action '{$action}' (status '{$eventStatus}') to event '{$event->id}'");
                $eventStatus = 'TENTATIVE';
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " applying exceedance/shortfall action '{$action}' (status '{$eventStatus}') to event '{$event->id}'");
            }
            $event->status = $event->xprops()['CS-MANAGED-EVENT-STATUS'] = $eventStatus;
        }

        return $event;
    }

    public function applyActions() : void
    {
        // @TODO scheduler job to apply 'forbidden' action an set event_status form  TENTATIVE to CANCELLED
        //       if criteria is not met after leadtime has passed

        // so one of the roles leadtimes must be passed to cancel event
        // by event_type  / cs-role-config yet
        // iterate-by or work-on roles having forbidden action
        // search all events in period (now:now+leadtime) having eRC with corresponding role(s) (direct or via event-types) with event status TENTATIVE
        // NOTE: depends on shitty filter!!!
        // it's a CS search (by role) -> move CS search

        // run $this->manageEventStatus
        // update event if event_status changed
        // save events with ignoreACL (create exceptions if needed)

    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);
    }

    public static function modelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        $expanderDef = $mc->jsonExpander;
        $expanderDef[Tinebase_Record_Expander::EXPANDER_PROPERTIES][CrewScheduling_Config::EVENT_ROLES_CONFIGS] = [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                CrewScheduling_Model_EventRoleConfig::FLD_ROLE => [],
                CrewScheduling_Model_EventRoleConfig::FLD_EVENT_TYPES => [],
            ],
        ];
        $expanderDef[Tinebase_Record_Expander::EXPANDER_PROPERTIES]['attendee'][Tinebase_Record_Expander::EXPANDER_PROPERTIES][CrewScheduling_Config::CREWSHEDULING_ROLES] = [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                CrewScheduling_Model_AttendeeRole::FLD_ROLE => [],
                CrewScheduling_Model_AttendeeRole::FLD_EVENT_TYPES => [],
            ],
        ];
        $mc->setJsonExpander($expanderDef);
    }
}
