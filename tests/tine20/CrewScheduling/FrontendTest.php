<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Test class for CrewScheduling Frontends / APIs
 */
class CrewScheduling_FrontendTest extends TestCase
{
    public function testJsonSearchEventsEvents(): void
    {
        $container = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $sharedCcontainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, shared: true);
        $from = Tinebase_DateTime::today();
        $until = $from->getClone()->addDay(3);
        $deadline = $until->getClone();

        $poll = CrewScheduling_ControllerTest::createPoll(pollData: [
            CrewScheduling_Model_Poll::FLD_FROM => $from,
            CrewScheduling_Model_Poll::FLD_UNTIL => $until,
            CrewScheduling_Model_Poll::FLD_DEADLINE => $deadline,
            CrewScheduling_Model_Poll::FLD_PARTICIPANTS => new Tinebase_Record_RecordSet(CrewScheduling_Model_PollParticipant::class, [
                new CrewScheduling_Model_PollParticipant([
                    CrewScheduling_Model_PollParticipant::FLD_CONTACT => $this->_personas['pwulf']->contact_id,
                ], true)
            ]),
        ]);
        $schedulingRole = $poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE};
        $outsideRole = CrewScheduling_Controller_SchedulingRole::getInstance()->create(new CrewScheduling_Model_SchedulingRole([
            CrewScheduling_Model_SchedulingRole::FLD_NAME => 'uttt',
            CrewScheduling_Model_SchedulingRole::FLD_KEY => 'uttt',
        ]));

        $eventType = Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'unt',
            'name' => 'unittest',
            CrewScheduling_Config::CS_ROLE_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventTypeConfig::class, [[
                CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE => $schedulingRole,
                CrewScheduling_Model_EventTypeConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
            ]], true),
        ]));

        $result = CrewScheduling_Controller_EventTypeConfig::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_EventTypeConfig::class, [
            [TMFA::FIELD => CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $schedulingRole->getId()],
        ]));
        $this->assertCount(1, $result);

        $fe = new CrewScheduling_Frontend_JsonPublic();
        $calCtrl = Calendar_Controller_Event::getInstance();
        $eventOutside = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'outside',
            'dtstart'     => $dt = (new Tinebase_DateTime($from->format('Y-m-d 00:00:00')))->subHour(10),
            'dtend'       => $dt->getClone()->addHour(1),
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));
        $eventWrongRole = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'wrong role',
            'dtstart'     => $dt = (new Tinebase_DateTime($from->format('Y-m-d 08:00:00'))),
            'dtend'       => $dt->getClone()->addHour(1),
            'container_id' => $container->getId(),
            // this wrong role should count
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $outsideRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
            // this event type should not count, its overwritten by the event roles config
            'event_types' => new Tinebase_Record_RecordSet(Calendar_Model_EventTypes::class, [
                new Calendar_Model_EventTypes([
                    Calendar_Model_EventTypes::FLD_EVENT_TYPE => $eventType->getId(),
                ], true)
            ]),
        ], true));
        $eventInside = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'inside',
            'dtstart'     => $dt = new Tinebase_DateTime($from->format('Y-m-d 00:00:00')),
            'dtend'       => $dt->getClone()->addHour(1),
            'event_types' => new Tinebase_Record_RecordSet(Calendar_Model_EventTypes::class, [
                new Calendar_Model_EventTypes([
                    Calendar_Model_EventTypes::FLD_EVENT_TYPE => $eventType->getId(),
                ], true)
            ]),
            'container_id' => $sharedCcontainer->getId(),
        ], true));

        $result = CrewScheduling_Controller_EventTypeConfig::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_EventTypeConfig::class, [
            [TMFA::FIELD => CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $schedulingRole->getId()],
        ]));
        $this->assertCount(1, $result);

        $eventInside1 = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'inside1',
            'dtstart'     => $dt = (new Tinebase_DateTime($from->format('Y-m-d 08:00:00')))->addDay(1),
            'dtend'       => $dt->getClone()->addHour(1),
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));
        $eventInside2 = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'inside2',
            'dtstart'     => $dt = (new Tinebase_DateTime($until->format('Y-m-d 23:00:00'))),
            'dtend'       => $dt->getClone()->addHour(1),
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));

        $eventRruleOutside = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'rruleOutside',
            'dtstart'     => $dt = (new Tinebase_DateTime($from->format('Y-m-d 08:00:00')))->subDay(1),
            'dtend'       => $dt->getClone()->addHour(1),
            'rrule'       => 'FREQ=WEEKLY;BYDAY=' . strtoupper(substr($dt->format('D'), 0, 2)) . ';INTERVAL=1',
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));

        $eventRruleInside = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'rruleInside',
            'dtstart'     => $dt = (new Tinebase_DateTime($from->format('Y-m-d 08:00:00')))->subDay(1),
            'dtend'       => $dt->getClone()->addHour(1),
            'rrule'       => 'FREQ=WEEKLY;BYDAY=' . strtoupper(substr($dt->format('D'), 0, 2)) . ',' . strtoupper(substr($from->format('D'), 0, 2)) . ';INTERVAL=1',
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));


        $result = $fe->searchEvents($poll->getId());
        $this->assertArrayHasKey('results', $result);
        $this->assertNotEmpty($result['results']);
        $this->assertArrayHasKey('totalcount', $result);
        $this->assertSame(4, $result['totalcount'], array_reduce($result['results'], fn($carry, $item) => $carry . ' ' . $item['summary']));

        ($checkEventPresence = function($result) use($eventOutside, $eventWrongRole, $eventRruleOutside, $eventInside, $eventInside1, $eventInside2, $eventRruleInside) {
            $eventIds = [];
            foreach ($result['results'] as $resultEvent) {
                $eventIds[$resultEvent['id']] = true;
            }
            $this->assertArrayNotHasKey($eventOutside->getId(), $eventIds);
            $this->assertArrayNotHasKey($eventWrongRole->getId(), $eventIds);
            $this->assertArrayNotHasKey($eventRruleOutside->getId(), $eventIds);
            $this->assertArrayHasKey($eventInside->getId(), $eventIds);
            $this->assertArrayHasKey($eventInside1->getId(), $eventIds);
            $this->assertArrayHasKey($eventInside2->getId(), $eventIds);
            $this->assertArrayHasKey('fakeid' . $eventRruleInside->getId() . '/' . $eventRruleInside->dtstart->getClone()->addDay(1)->getTimestamp(), $eventIds);
            $this->assertArrayNotHasKey($eventRruleInside->getId(), $eventIds);
        })($result);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        $result = $fe->searchEvents($poll->getId(), $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->getFirstRecord()->getId());
        $this->assertArrayHasKey('results', $result);
        $this->assertNotEmpty($result['results']);
        $checkEventPresence($result);

        foreach ($result['results'] as $resultEvent) {
            if ($eventInside->getId() === $resultEvent['id']) {
                $this->assertArrayHasKey('uid', $resultEvent);
            } else {
                $this->assertArrayNotHasKey('uid', $resultEvent);
            }
        }

        $eventTypeNoCsRole = Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType([
            'short_name' => 'unu',
            'name' => 'unittest2',
        ]));

       $result = (new Calendar_Frontend_Json)->searchEventTypes([
            [TMFA::FIELD => CrewScheduling_Config::CS_ROLE_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
            [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => $eventType->getId()],
        ], []);
        $this->assertSame($eventType->getId(), $result['results'][0]['id'], print_r($result['results'], true));
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEventTypes([
            [TMFA::FIELD => CrewScheduling_Config::CS_ROLE_CONFIGS, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
            [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => $eventType->getId()],
        ], []);
        $this->assertSame($eventType->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEventTypes([
            [TMFA::FIELD => CrewScheduling_Config::CS_ROLE_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
            [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => $eventTypeNoCsRole->getId()],
        ], []);
        $this->assertSame($eventTypeNoCsRole->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEventTypes([
            [TMFA::FIELD => CrewScheduling_Config::CS_ROLE_CONFIGS, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
            [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => $eventTypeNoCsRole->getId()],
        ], []);
        $this->assertSame($eventTypeNoCsRole->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);
    }

    public function testJsonSearchEventRolesConfig(): void
    {
        $schedulingRole = CrewScheduling_Controller_SchedulingRole::getInstance()->create(new CrewScheduling_Model_SchedulingRole([
            CrewScheduling_Model_SchedulingRole::FLD_NAME => 'unittest',
            CrewScheduling_Model_SchedulingRole::FLD_KEY => 'key',
        ]));
        $container = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $calCtrl = Calendar_Controller_Event::getInstance();
        $eventWith = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'unittest',
            'dtstart'     => Tinebase_DateTime::now(),
            'dtend'       => Tinebase_DateTime::now()->addMinute(15),
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));
        $eventWithOut = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'unittest',
            'dtstart'     => Tinebase_DateTime::now(),
            'dtend'       => Tinebase_DateTime::now()->addMinute(15),
            'container_id' => $container->getId(),
        ], true));

        $result = (new Calendar_Frontend_Json)->searchEvents([
            [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
        ], []);
        $this->assertSame($eventWith->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEvents([
            [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], []);
        $this->assertSame($eventWith->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEvents([
            [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], []);
        $this->assertSame($eventWithOut->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);

        $result = (new Calendar_Frontend_Json)->searchEvents([
            [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
        ], []);
        $this->assertSame($eventWithOut->getId(), $result['results'][0]['id']);
        $this->assertArrayHasKey(TMFA::VALUE, $result['filter'][0][TMFA::VALUE][0] ?? []);
        $this->assertNull($result['filter'][0][TMFA::VALUE][0][TMFA::VALUE]);
    }

    public function testJsonSearchEventsAcls(): void
    {
        $publicContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_fn' => 'unittest',
            'email' => 'external@unitest.external.tld',
        ], true));

        $fe = new CrewScheduling_Frontend_JsonPublic();

        // test poll not found
        try {
            $fe->searchEvents('unittest');
            $this->fail('expect ' . Tinebase_Exception_NotFound::class);
        } catch (Tinebase_Exception_NotFound) {
            // success
        } catch (Throwable $t) {
            $this->fail('expect ' . Tinebase_Exception_NotFound::class . ' got ' . get_class($t) . ' "' . $t->getMessage() . '"');
        }

        $poll = CrewScheduling_ControllerTest::createPoll(pollData: [
            CrewScheduling_Model_Poll::FLD_PARTICIPANTS => new Tinebase_Record_RecordSet(CrewScheduling_Model_PollParticipant::class, [
                new CrewScheduling_Model_PollParticipant([
                    CrewScheduling_Model_PollParticipant::FLD_CONTACT => $this->_personas['pwulf']->contact_id,
                ], true),
                new CrewScheduling_Model_PollParticipant([
                    CrewScheduling_Model_PollParticipant::FLD_CONTACT => $publicContact->getId(),
                ], true),
            ])
        ]);
        $pwulfParticipant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->find(fn($rec) => $rec->getIdFromProperty(CrewScheduling_Model_PollParticipant::FLD_CONTACT) === $this->_personas['pwulf']->contact_id, null);
        $externalParticipant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->find(fn($rec) => $rec->getIdFromProperty(CrewScheduling_Model_PollParticipant::FLD_CONTACT) === $publicContact->getId(), null);
        $this->assertNotNull($pwulfParticipant);
        //$schedulingRole = $poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE};

        // test empty poll
        $result = $fe->searchEvents($poll->getId());
        $this->assertArrayHasKey('results', $result);
        $this->assertEmpty($result['results']);
        $this->assertArrayHasKey('totalcount', $result);
        $this->assertSame(0, $result['totalcount']);

        // test missing manage poll grant
        Tinebase_Core::setUser($this->_personas['jmcblack']);
        try {
            $fe->searchEvents($poll->getId());
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class);
        } catch (Tinebase_Exception_AccessDenied) {
            // success
        } catch (Throwable $t) {
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class . ' got ' . get_class($t) . ' "' . $t->getMessage() . '"');
        }

        // test not own participant
        try {
            $fe->searchEvents($poll->getId(), $pwulfParticipant->getId());
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class);
        } catch (Tinebase_Exception_AccessDenied) {
            // success
        } catch (Throwable $t) {
            $this->fail('expect ' . Tinebase_Exception_AccessDenied::class . ' got ' . get_class($t) . ' "' . $t->getMessage() . '"');
        }

        // test public participant
        $result = $fe->searchEvents($poll->getId(), $externalParticipant->getId());
        $this->assertArrayHasKey('results', $result);
        $this->assertEmpty($result['results']);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        // test own participant
        $result = $fe->searchEvents($poll->getId(), $pwulfParticipant->getId());
        $this->assertArrayHasKey('results', $result);
        $this->assertEmpty($result['results']);
    }

    public function testJsonSaveEvent(): void
    {
        $container = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $from = Tinebase_DateTime::today();
        $until = $from->getClone()->addDay(3);
        $deadline = $until->getClone();

        $poll = CrewScheduling_ControllerTest::createPoll(pollData: [
            CrewScheduling_Model_Poll::FLD_FROM => $from,
            CrewScheduling_Model_Poll::FLD_UNTIL => $until,
            CrewScheduling_Model_Poll::FLD_DEADLINE => $deadline,
            CrewScheduling_Model_Poll::FLD_PARTICIPANTS => new Tinebase_Record_RecordSet(CrewScheduling_Model_PollParticipant::class, [
                new CrewScheduling_Model_PollParticipant([
                    CrewScheduling_Model_PollParticipant::FLD_CONTACT => $this->_personas['pwulf']->contact_id,
                ], true)
            ]),
        ]);
        $schedulingRole = CrewScheduling_Controller_SchedulingRole::getInstance()->get($poll->getIdFromProperty(CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE));
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $schedulingRole->grants);
        $schedulingRole->grants->addRecord(new CrewScheduling_Model_SchedulingRoleGrants([
            'account_id' => $this->_personas['sclever']->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            CrewScheduling_Model_SchedulingRoleGrants::ASSIGN_ATTENDEE => true,
        ]));
        $schedulingRole = CrewScheduling_Controller_SchedulingRole::getInstance()->update($schedulingRole);

        $fe = new CrewScheduling_Frontend_Json();
        $calCtrl = Calendar_Controller_Event::getInstance();
        $event = $calCtrl->create(new Calendar_Model_Event([
            'summary'     => 'inside',
            'dtstart'     => $dt = new Tinebase_DateTime($from->format('Y-m-d 00:00:00')),
            'dtend'       => $dt->getClone()->addHour(1),
            'container_id' => $container->getId(),
            CrewScheduling_Config::EVENT_ROLES_CONFIGS => new Tinebase_Record_RecordSet(CrewScheduling_Model_EventRoleConfig::class, [
                new CrewScheduling_Model_EventRoleConfig([
                    CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $schedulingRole->getId(),
                    CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
                ], true),
            ]),
        ], true));

        Tinebase_Core::setUser($this->_personas['sclever']);
        $event->attendee->addRecord(new Calendar_Model_Attender([
            Calendar_Model_Attender::FLD_USER_ID => $this->_personas['pwulf']->contact_id,
            Calendar_Model_Attender::FLD_USER_TYPE => Calendar_Model_Attender::USERTYPE_USER,
            Calendar_Model_Attender::FLD_STATUS => Calendar_Model_Attender::STATUS_ACCEPTED,
            CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class, [
                new CrewScheduling_Model_AttendeeRole([
                    CrewScheduling_Model_AttendeeRole::FLD_ROLE => $schedulingRole->getId(),
                ], true)
            ]),
        ]));

        $result = $fe->saveEvent($event->toArray());
        $this->assertArrayNotHasKey('uid', $result);
        $this->assertIsArray($result['attendee'] ?? null);
        foreach ($result['attendee'] as &$attendee) {
            if ($attendee['user_id'] === $this->_personas['pwulf']->contact_id) {
                break;
            }
        }
        $this->assertSame(Calendar_Model_Attender::STATUS_NEEDSACTION, $attendee['status']);
        $this->assertSame($schedulingRole->getId(), $attendee[CrewScheduling_Config::CREWSHEDULING_ROLES][0][CrewScheduling_Model_AttendeeRole::FLD_ROLE]['id'] ?? null);
        unset($attendee[CrewScheduling_Config::CREWSHEDULING_ROLES]);
        unset($attendee);

        $result = $fe->saveEvent($result);
        $this->assertArrayNotHasKey('uid', $result);
        $this->assertIsArray($result['attendee'] ?? null);
        $this->assertEmpty($result['attendee']);
    }
}