<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test helper
 */

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Calendar_Frontend_CalDAV_SpeedUpPropfindPluginTest extends Calendar_TestCase
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
     * @var Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin
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

        $this->plugin = new Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin();

        $this->server->addPlugin($this->plugin);
        $this->server->addPlugin(new \Sabre\CalDAV\Plugin());

        $this->server->httpResponse = $this->response = new Tinebase_WebDav_Sabre_ResponseMock();
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();

        $this->assertEquals('speedUpPropfindPlugin', $pluginName);
    }

    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $event = $this->calDAVTests->testCreateRepeatingEventAndPutExdate();

        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <getcontenttype/>
                        <getetag/>
                    </prop>
                 </propfind>';

        $request = new Sabre\HTTP\Request('PROPFIND', '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $event->getRecord()->container_id, [
            'DEPTH'     => '1',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertSame(207, $this->response->status);

        /*$responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        //echo $this->response->body;
        //$responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vevent-datetime');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vevent-date');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vtodo-datetime');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:default-alarm-vtodo-date');
        $this->assertEquals(1, $nodes->length, $responseDoc->saveXML());
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());*/
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
        /*$createdEvent = */$cctrl->create($event);

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
        static::assertSame($recurException->getId(), $allEvents->getFirstRecord()->getId());


        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <getcontenttype/>
                        <getetag/>
                    </prop>
                 </propfind>';

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId();
        $request = new Sabre\HTTP\Request('PROPFIND', $uri, [
            'DEPTH'     => '1',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame(207, $this->response->status);
        static::assertStringContainsString($uri . '/' . $recurException->getId(), $this->response->body);
    }

    /*public function testCalendarQuery()
    {
        $cctrl = Calendar_Controller_Event::getInstance();
        $event = $this->_getEvent(true);
        $createdEvent = $cctrl->create($event);

        $body = '<?xml version="1.0" encoding="utf-8" ?>
   <C:calendar-query xmlns:D="DAV:"
                 xmlns:C="urn:ietf:params:xml:ns:caldav">
     <D:prop>
       <D:getetag/>
       <D:getcontenttype/>
     </D:prop>
     <C:filter>
       <C:comp-filter name="VCALENDAR">
         <C:comp-filter name="VEVENT">
           <C:time-range start="20060104T000000Z"
                         end="20060105T000000Z"/>
         </C:comp-filter>
       </C:comp-filter>
     </C:filter>
   </C:calendar-query>';

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $this->_personasDefaultCals['jmcblack']->getId();
        $request = new Sabre\HTTP\Request('REPORT', $uri, [
            'DEPTH'     => '1',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame(207, $this->response->status);
        static::assertStringContainsString($uri . '/' . $createdEvent->getId(), $this->response->body);
    }*/
}
