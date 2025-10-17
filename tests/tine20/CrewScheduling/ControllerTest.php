<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Test class for CrewScheduling
 */
class CrewScheduling_ControllerTest extends TestCase
{

    public function testCSEventsFilter()
    {
        $this->_setFeatureForTest(Calendar_Config::getInstance(), Calendar_Config::FEATURE_EVENT_TYPE, true);
        $role = CrewScheduling_Controller_SchedulingRole::getInstance()->create(new CrewScheduling_Model_SchedulingRole([
            CrewScheduling_Model_SchedulingRole::FLD_KEY => 'CSR',
            CrewScheduling_Model_SchedulingRole::FLD_NAME => 'CS role',
        ]));
        Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'CSE',
            'name' => 'CS Enabled eventType',
            'cs_role_configs' => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventTypeConfig::class, [ new CrewScheduling_Model_EventTypeConfig([
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $role,
                CrewScheduling_Model_EventTypeConfig::FLD_EVENT_TYPE => '...',
            ])])
        ]));
        Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'CSD',
            'name' => 'non CS eventType'
        ]));

        // search cs enabled eventTypes
        $eventTypes = Calendar_Controller_EventType::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Calendar_Model_EventType::class, [
            ['field' => 'cs_role_configs', 'operator' => 'definedBy', 'value' => [
                ['field' => "id", 'operator' => "not", 'value' => null]
            ]]
        ]));

        $this->assertEquals(1, $eventTypes->filter('short_name', 'CSE')->count());
        $this->assertEquals(0, $eventTypes->filter('short_name', 'CSD')->count());
    }

    public function testCalEventSysCF()
    {
        if (! Tinebase_Application::getInstance()->isInstalled('ChurchEdition')) {
            self::markTestSkipped('only works with ChurchEdition');
        }

        $allRoles = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll('order');
        $this->assertGreaterThan(1, $allRoles->count()); // we need at least 2
        $allTypes = Calendar_Controller_EventType::getInstance()->getAll();
        $this->assertGreaterThan(0, $allTypes->count()); // we need at least 1

        $container = $this->_getPersonalContainer(Calendar_Model_Event::class);
        $event = new Calendar_Model_Event([
            'summary' => 'test',
            'dtstart' => Tinebase_DateTime::now(),
            'dtend' => Tinebase_DateTime::now()->addHour(1),
            'container_id' => $container,
            'attendee' => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [
                [
                    'user_id'   => Tinebase_Core::getUser()->contact_id,
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class,[[
                        CrewScheduling_Model_AttendeeRole::FLD_ROLE => $allRoles->getFirstRecord(),
                    ]], _bypassFilters: true),
                ]], _bypassFilters: true),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [[
                CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $allRoles->getFirstRecord(),
                CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                CrewScheduling_Model_EventRoleConfig::FLD_EVENT_TYPES => new Tinebase_Record_RecordSet(Calendar_Model_EventType::class, [
                    $allTypes->getFirstRecord(),
                ]),
            ]], _bypassFilters: true),
        ]);

        $createdEvent = Calendar_Controller_Event::getInstance()->create($event);
        Tinebase_Record_Expander::expandRecord($createdEvent);

        $this->assertInstanceOf(Calendar_Model_EventType::class,
            $createdEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}?->getFirstRecord()?->{CrewScheduling_Model_EventRoleConfig::FLD_EVENT_TYPES}->getFirstRecord());

        $allAttendeeRoles = CrewScheduling_Controller_AttendeeRole::getInstance()->getAll();
        $this->assertSame(1, $allAttendeeRoles->count());
        $this->assertSame($allAttendeeRoles->getFirstRecord()->getId(), $createdEvent->attendee->getFirstRecord()->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->getFirstRecord()?->getId());
        $this->assertSame($allRoles->getFirstRecord()->getId(), $createdEvent->attendee->getFirstRecord()->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->getFirstRecord()?->{CrewScheduling_Model_AttendeeRole::FLD_ROLE}?->getId());

        $allEventRoleConfig = CrewScheduling_Controller_EventRoleConfig::getInstance()->getAll();
        $this->assertSame(1, $allEventRoleConfig->count());
        $this->assertSame($allEventRoleConfig->getFirstRecord()->getId(), $createdEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}?->getFirstRecord()?->getId());
        $this->assertSame($allRoles->getFirstRecord()->getId(), $createdEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}?->getFirstRecord()?->{CrewScheduling_Model_EventRoleConfig::FLD_ROLE}?->getId());

        $this->assertNotSame($allRoles->getFirstRecord()->getId(), $allRoles->getLastRecord()->getId());
        $createdEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}->getFirstRecord()->{CrewScheduling_Model_EventRoleConfig::FLD_ROLE} = $allRoles->getLastRecord();
        $createdEvent->attendee->getFirstRecord()->{CrewScheduling_Config::CREWSHEDULING_ROLES}->getFirstRecord()->{CrewScheduling_Model_AttendeeRole::FLD_ROLE} = $allRoles->getLastRecord();
        $updatedEvent = Calendar_Controller_Event::getInstance()->update($createdEvent);
        Tinebase_Record_Expander::expandRecord($updatedEvent);

        $this->assertSame($allAttendeeRoles->getFirstRecord()->getId(), $updatedEvent->attendee->getFirstRecord()->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->getFirstRecord()?->getId());
        $this->assertSame($allRoles->getLastRecord()->getId(), $updatedEvent->attendee->getFirstRecord()->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->getFirstRecord()?->{CrewScheduling_Model_AttendeeRole::FLD_ROLE}?->getId());

        $this->assertSame($allEventRoleConfig->getFirstRecord()->getId(), $updatedEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}?->getFirstRecord()?->getId());
        $this->assertSame($allRoles->getLastRecord()->getId(), $updatedEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}?->getFirstRecord()?->{CrewScheduling_Model_EventRoleConfig::FLD_ROLE}?->getId());

        Calendar_Controller_Event::getInstance()->delete([$createdEvent->getId()]);

        $this->assertSame(0, CrewScheduling_Controller_EventRoleConfig::getInstance()->getAll()->count());
    }

    public function testSearchAdbLists()
    {
        if (! Tinebase_Application::getInstance()->isInstalled('ChurchEdition')) {
            self::markTestSkipped('only works with ChurchEdition');
        }

        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), 'schedulingRole',
            Addressbook_Model_List::class);
        $allRoles = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll('order');
        $groupController = Admin_Controller_Group::getInstance();
        $listController = Addressbook_Controller_List::getInstance();

        /** @var CrewScheduling_Model_SchedulingRole $role */
        foreach ($allRoles as $role) {
            $newGroup = new Tinebase_Model_Group(array(
                'name' => $role->name,
            ));
            try {
                $group = $groupController->create($newGroup);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                self::markTestSkipped($tead->getMessage());
            }
            $list = $listController->get($group->list_id);
            $list->customfields = [$cfCfg->name => [$role->getIdProperty() => $role->getId()]];
            $listController->update($list);
        }

        $searchResult = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter([
            ['field' => 'customfield', 'operator' => 'AND', 'value' => [
                'cfId'  => $cfCfg->getId(),
                'value' => [
                    ['field' => 'id', 'operator' => 'in', 'value' => [
                        $allRoles->getFirstRecord()->getId(),
                        $allRoles->getByIndex(1)->getId()
                    ]]
                ]
            ]]
        ]));

        static::assertEquals(2, $searchResult->count());
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testFavoritePartner()
    {
        $contact1 = new Addressbook_Model_Contact();
        $contact1 = Addressbook_Controller_Contact::getInstance()->create($contact1);

        $contact2 = new Addressbook_Model_Contact();
        $contact2 = Addressbook_Controller_Contact::getInstance()->create($contact2);

        $contact1->customfields = [
            'favorite_partner' => [
                $contact2
            ]
        ];

        $contact1 = Addressbook_Controller_Contact::getInstance()->update($contact1);

        $contact2->customfields = [
            'favorite_partner' => [
                $contact1
            ]
        ];

        $contact2 = Addressbook_Controller_Contact::getInstance()->update($contact2);

        static::assertTrue(isset($contact1->customfields['favorite_partner'][0]['id'])
            && $contact1->customfields['favorite_partner'][0]['id'] === $contact2->getId(), print_r($contact1->toArray(), true));
        static::assertTrue(isset($contact2->customfields['favorite_partner'][0]['id'])
            && $contact2->customfields['favorite_partner'][0]['id'] === $contact1->getId(), print_r($contact2->toArray(), true));
    }

    public static function createPoll(array $schedulingRoleData = [], array $pollData = []): CrewScheduling_Model_Poll
    {
        $schedulingRole = CrewScheduling_Controller_SchedulingRole::getInstance()->create(
            new CrewScheduling_Model_SchedulingRole(array_merge([
                CrewScheduling_Model_SchedulingRole::FLD_NAME => 'unittest',
                CrewScheduling_Model_SchedulingRole::FLD_KEY => 'unittest',
            ], $schedulingRoleData), true)
        );

        return CrewScheduling_Controller_Poll::getInstance()->create(new CrewScheduling_Model_Poll(array_merge([
            CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE => $schedulingRole,
            CrewScheduling_Model_Poll::FLD_FROM => Tinebase_DateTime::now(),
            CrewScheduling_Model_Poll::FLD_UNTIL => Tinebase_DateTime::now()->addDay(3),
            CrewScheduling_Model_Poll::FLD_DEADLINE => Tinebase_DateTime::now()->addDay(3),
        ], $pollData)));
    }
    public function testPollGrants(): void
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $poll = static::createPoll();
        $schedulingRole = $poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE};

        $poll->{CrewScheduling_Model_Poll::FLD_FROM}->addDay(1);
        $poll = CrewScheduling_Controller_Poll::getInstance()->update($poll);

        Tinebase_Core::setUser($this->_personas['sclever']);
        CrewScheduling_Controller_Poll::getInstance()->get($poll->getId());
        CrewScheduling_Controller_Poll::getInstance()->search($filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_Poll::class, [
            [Tinebase_Model_Filter_Abstract::FIELD => 'id', Tinebase_Model_Filter_Abstract::OPERATOR => Tinebase_Model_Filter_Abstract::OP_EQUALS, Tinebase_Model_Filter_Abstract::VALUE => $poll->getId()],
        ]));

        $poll->{CrewScheduling_Model_Poll::FLD_FROM}->addDay(1);
        try {
            CrewScheduling_Controller_Poll::getInstance()->update($poll);
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class . ' exception');
        } catch (Tinebase_Exception_AccessDenied) {}

        try {
            CrewScheduling_Controller_Poll::getInstance()->create(new CrewScheduling_Model_Poll([
                CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE => $schedulingRole,
                CrewScheduling_Model_Poll::FLD_FROM => Tinebase_DateTime::now(),
                CrewScheduling_Model_Poll::FLD_UNTIL => Tinebase_DateTime::now()->addDay(3),
                CrewScheduling_Model_Poll::FLD_DEADLINE => Tinebase_DateTime::now()->addDay(3),
            ]));
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class . ' exception');
        } catch (Tinebase_Exception_AccessDenied) {}


        try {
            CrewScheduling_Controller_Poll::getInstance()->delete([$poll->getId()]);
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class . ' exception');
        } catch (Tinebase_Exception_AccessDenied) {}

        CrewScheduling_Controller_Poll::getInstance()->get($poll->getId());

        //Tinebase_Core::setUser($this->_originalTestUser);
    }
}
