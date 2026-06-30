<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Calendar_Frontend_CalDAV_ScheduleOutboxTest extends Calendar_TestCase
{
    /**
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;


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
        parent::setUp();

        $this->server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());

        $this->plugin = new Calendar_Frontend_CalDAV_ScheduleOutbox();

        $this->server->addPlugin($this->plugin);
        $this->server->addPlugin(new \Sabre\CalDAV\Plugin());

        $this->server->httpResponse = $this->response = new Tinebase_WebDav_Sabre_ResponseMock();
    }

    public function testX()
    {
        Calendar_Controller_Event::getInstance()->create($this->_getEvent());

        $body = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Example Corp.//CalDAV Client//EN
METHOD:REQUEST
BEGIN:VFREEBUSY
UID:4FD3AD926350
DTSTAMP:20090602T190420Z
DTSTART:20090324T000000Z
DTEND:20090326T000000Z
ORGANIZER;CN="' . $this->_originalTestUser->accountDisplayName . '":mailto:' . $this->_originalTestUser->accountEmailAddress . '
ATTENDEE;CN="Wilfredo Sanchez Vega":mailto:wilfredo@example.com
ATTENDEE;CN="' . $this->_personas['sclever']->accountDisplayName . '":mailto:' . $this->_personas['sclever']->accountEmailAddress . '
ATTENDEE;CN="' . $this->_personas['jmcblack']->accountDisplayName . '":mailto:' . $this->_personas['jmcblack']->accountEmailAddress . '
END:VFREEBUSY
END:VCALENDAR
';

        $request = new Sabre\HTTP\Request('POST', '/calendars/' . Tinebase_Core::getUser()->contact_id . '/outbox');
        $request->setBody(join("\r\n", explode("\n", $body)));

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertSame(200, $this->response->status);

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        $responseDoc->formatOutput = true;
        $xmlAsString = $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);

        $nodes = $xpath->query('//C:schedule-response/C:response/C:recipient/D:href');
        $this->assertEquals(3, $nodes->length, $xmlAsString);
        $this->assertSame('mailto:wilfredo@example.com', $nodes->item(0)->nodeValue, $xmlAsString);

        $nodes = $xpath->query('//C:schedule-response/C:response/C:request-status');
        $this->assertEquals(3, $nodes->length, $xmlAsString);
        $this->assertSame('2.0;Success', $nodes->item(0)->nodeValue, $xmlAsString);

        $nodes = $xpath->query('//C:schedule-response/C:response/C:calendar-data');
        $this->assertEquals(3, $nodes->length, $xmlAsString);
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $xmlAsString);
        $this->assertStringNotContainsString('FREEBUSY;', $nodes->item(0)->nodeValue, $xmlAsString);
        $this->assertStringContainsString('FREEBUSY;', $nodes->item(1)->nodeValue, $xmlAsString);
    }
}
