<?php

abstract class CrewScheduling_TestCase extends TestCase
{

    public Tinebase_Record_RecordSet $eventTypes;
    public Tinebase_Record_RecordSet $roles;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventTypes = new Tinebase_Record_RecordSet(Calendar_Model_EventType::class);
        $this->roles = new Tinebase_Record_RecordSet(CrewScheduling_Model_SchedulingRole::class);

        $this->_setFeatureForTest(Calendar_Config::getInstance(), Calendar_Config::FEATURE_EVENT_TYPE, true);
        $this->roles->addRecord(CrewScheduling_Controller_SchedulingRole::getInstance()->create(new CrewScheduling_Model_SchedulingRole([
            CrewScheduling_Model_SchedulingRole::FLD_KEY => 'R1',
            CrewScheduling_Model_SchedulingRole::FLD_NAME => 'CS role 1',
        ])));
        $this->roles->addRecord(CrewScheduling_Controller_SchedulingRole::getInstance()->create(new CrewScheduling_Model_SchedulingRole([
            CrewScheduling_Model_SchedulingRole::FLD_KEY => 'R2',
            CrewScheduling_Model_SchedulingRole::FLD_NAME => 'CS role 2',
        ])));

        $this->eventTypes->addRecord(Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'ET1',
            'name' => 'CS enabled eventType 1',
            'cs_role_configs' => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventTypeConfig::class, [ new CrewScheduling_Model_EventTypeConfig([
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $this->roles[0],
                CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE => '...',
                CrewScheduling_Model_EventTypeConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                CrewScheduling_Model_EventTypeConfig::FLD_SHORTFALL_ACTION => CrewScheduling_Config::ACTION_EVENT_TENTATIVE,
            ])])
        ])));

        $this->eventTypes->addRecord(Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'ET2',
            'name' => 'CS enabled eventType 2',
            'cs_role_configs' => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventTypeConfig::class, [ new CrewScheduling_Model_EventTypeConfig([
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $this->roles[1],
                CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE => '...',
                CrewScheduling_Model_EventTypeConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
            ])])
        ])));

        $this->eventTypes->addRecord(Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'ET3',
            'name' => 'CS enabled eventType 3',
            'cs_role_configs' => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventTypeConfig::class, [ new CrewScheduling_Model_EventTypeConfig([
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $this->roles[0],
                CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE => '...',
                CrewScheduling_Model_EventTypeConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 2,
                CrewScheduling_Model_EventTypeConfig::FLD_SAME_ROLE_SAME_ATTENDEE => CrewScheduling_Config::OPTION_MUST_NOT,
            ]), new CrewScheduling_Model_EventTypeConfig([
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $this->roles[1],
                CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE => '...',
                CrewScheduling_Model_EventTypeConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 2,
                CrewScheduling_Model_EventTypeConfig::FLD_OTHER_ROLE_SAME_ATTENDEE => false,
            ])])
        ])));

    }
}
