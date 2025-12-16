<?php
/**
 * tine-groupware - http://www.tine-groupware.de
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 */

/**
 * Test class for CrewScheduling
 */
class CrewScheduling_Model_EventRoleConfigTest extends CrewScheduling_TestCase
{
    public function testCreateFromEventTypes() : void
    {
        $event = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Event summary',
            'dtstart' => Tinebase_DateTime::now(),
            'dtend' => Tinebase_DateTime::now()->addHour(1),
            'event_types' => new Tinebase_Record_RecordSet(Calendar_Model_EventTypes::class, array_map(function($eventType) {
                return [
                    Calendar_Model_EventTypes::FLD_EVENT_TYPE => $eventType->getId(),
                    Calendar_Model_EventTypes::FLD_RECORD => '...',
                ];
            }, $this->eventTypes->asArray())),
        ]));

        $eRCs = CrewScheduling_Model_EventRoleConfig::getFromEvent($event);

        $this->assertCount(3, $eRCs);
        $this->assertCount(2, $eRCs->filter(function(CrewScheduling_Model_EventRoleConfig $eRC) { return $eRC['role']['key'] === 'R1'; }));
        $this->assertCount(1, $eRCs->filter(function(CrewScheduling_Model_EventRoleConfig $eRC) { return $eRC['role']['key'] === 'R2'; }));
    }

    public function testEventStatusManagement(): void
    {
        // create event
        $event = Calendar_Controller_Event::getInstance()->create(new Calendar_Model_Event([
            'summary' => 'Event summary',
            'dtstart' => Tinebase_DateTime::now()->addDay(10),
            'dtend' => Tinebase_DateTime::now()->addDay(10)->addHour(1),
            'rrule' => 'FREQ=DAILY;INTERVAL=1',
            'event_types' => new Tinebase_Record_RecordSet(Calendar_Model_EventTypes::class, [[
                Calendar_Model_EventTypes::FLD_EVENT_TYPE => $this->eventTypes->getFirstRecord()->getId(),
                Calendar_Model_EventTypes::FLD_RECORD => '...',
            ]]),
        ]));

        // autostatus for non recur / exceptions only
        $this->assertEquals('CONFIRMED', $event->status);

        // first instance exception
        $event = Calendar_Controller_Event::getInstance()->createRecurException($event);
        $this->assertEquals('TENTATIVE', $event->status);

        // test manual cancel of events
        $event->status = 'CANCELLED';
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $this->assertEquals('CANCELLED', $event->status);

        // regular recurring must not be touched
        $baseEvent = Calendar_Controller_Event::getInstance()->getRecurBaseEvent($event);
        $events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$baseEvent]);
        Calendar_Model_Rrule::mergeRecurrenceSet($events, Tinebase_DateTime::now()->addDay(11), Tinebase_DateTime::now()->addDay(11));
        $event = $events->getFirstRecord();
        $this->assertEquals('CONFIRMED', $event->status);

        // but exception
        $event = Calendar_Controller_Event::getInstance()->createRecurException($event);
        $this->assertEquals('TENTATIVE', $event->status);
        
        // fulfill criteria
        $event->attendee = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [new Calendar_Model_Attender([
            Calendar_Model_Attender::FLD_USER_TYPE => Calendar_Model_Attender::USERTYPE_EMAIL,
            Calendar_Model_Attender::FLD_USER_EMAIL => 'test@example.com',
            Calendar_Model_Attender::FLD_STATUS => 'ACCEPTED',
            CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class, [new CrewScheduling_Model_AttendeeRole([
                CrewScheduling_Model_AttendeeRole::FLD_ATTENDEE => '...',
                CrewScheduling_Model_AttendeeRole::FLD_ROLE => $this->roles->getFirstRecord()->getId(),
                CrewScheduling_Model_AttendeeRole::FLD_EVENT_TYPES => [ $this->eventTypes->getFirstRecord()->getId() ],
            ])]),
        ])]);
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $this->assertEquals('CONFIRMED', $event->status);

        // rise criteria
        $event->{CrewScheduling_Config::EVENT_ROLES_CONFIGS} = CrewScheduling_Model_EventRoleConfig::getFromEvent($event);
        $event->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}->getFirstRecord()->{CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE} = 2;
        $event->{CrewScheduling_Config::EVENT_ROLES_CONFIGS}->getFirstRecord()->{CrewScheduling_Model_EventRoleConfig::FLD_SHORTFALL_ACTION} = 'forbidden';
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $this->assertEquals('TENTATIVE', $event->status);

        // after lead time
        $event->dtstart->subDay(5);
        $event->dtend->subDay(5);
        $event = Calendar_Controller_Event::getInstance()->update($event);
        $this->assertEquals('CANCELLED', $event->status);
    }
}