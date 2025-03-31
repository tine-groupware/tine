<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Calendar_Frontend_CalDAV_FixMultiGet404PluginTest extends Calendar_TestCase
{
    /**
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * @var Calendar_Frontend_WebDAV_EventTest
     */
    protected $calDAVTests;

    /**
     * @var Calendar_Frontend_CalDAV_FixMultiGet404Plugin
     */
    protected $plugin;

    protected $response;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp(): void
{
        $this->calDAVTests = new Calendar_Frontend_WebDAV_EventTest();
        $this->calDAVTests->setup();

        parent::setUp();

        $this->server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());

        $this->plugin = new Calendar_Frontend_CalDAV_FixMultiGet404Plugin();

        $this->server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPlugin());
        $this->server->addPlugin($this->plugin);

        $this->server->httpResponse = $this->response = new Tinebase_WebDav_Sabre_ResponseMock();
    }

    public function testInvitationToExceptionOnly()
    {
        $jmcblackContactId = $this->_personas['jmcblack']->contact_id;
        try {
            Addressbook_Controller_Contact::getInstance()->get($jmcblackContactId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::markTestSkipped('jmcblack contact not found / was deleted by another test');
        }

        $cctrl = Calendar_Controller_Event::getInstance();
        $event = $this->_getEvent(true);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;COUNT=5';
        $cctrl->create($event);

        $allEvents = $cctrl->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()],
        ]));
        Calendar_Model_Rrule::mergeRecurrenceSet($allEvents, Tinebase_DateTime::now()->setTime(0,0,0),
            Tinebase_DateTime::now()->addWeek(1));
        $allEvents[2]->dtstart->subMinute(1);
        $allEvents[2]->dtend->subMinute(1);
        $allEvents[2]->attendee->addRecord($this->_createAttender($jmcblackContactId));
        $recurException = $cctrl->createRecurException($allEvents[2]);

        Tinebase_Core::setUser($this->_personas['jmcblack']);
        $allEvents = $cctrl->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_personasDefaultCals['jmcblack']->getId()],
        ]));

        static::assertSame(1, $allEvents->count());
        $jmcblacksEvent = $allEvents->getFirstRecord();
        static::assertSame($recurException->getId(), $jmcblacksEvent->getId());

        $body = '<?xml version="1.0" encoding="UTF-8"?>
                    <B:calendar-multiget xmlns:B="urn:ietf:params:xml:ns:caldav">
                        <A:prop xmlns:A="DAV:">
                            <A:getetag/>
                            <B:calendar-data/>
                            <B:schedule-tag/>
                            <C:created-by xmlns:C="http://calendarserver.org/ns/"/>
                            <C:updated-by xmlns:C="http://calendarserver.org/ns/"/>
                        </A:prop>
                        <A:href xmlns:A="DAV:">/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId() . '/doesnotexist.ics</A:href>
                        <A:href xmlns:A="DAV:">/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId() . '/' . $jmcblacksEvent->getId() . '.ics</A:href>
                    </B:calendar-multiget>';

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId();
        $request = new Sabre\HTTP\Request('REPORT', $uri);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame(207, $this->response->status, $this->response->body);
        static::assertStringContainsString('SUMMARY:' . $jmcblacksEvent->summary, $this->response->body);
    }
}
