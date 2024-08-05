<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */

/**
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Calendar_Frontend_CalDAV_PluginDefaultAlarmsTest extends TestCase
{
    /**
     * 
     * @var \Sabre\DAV\Server
     */
    protected $server;

    protected $plugin;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()),
            new Tinebase_WebDav_Sabre_SapiMock());
        
        $this->plugin = new Calendar_Frontend_CalDAV_PluginDefaultAlarms();
        
        $this->server->addPlugin($this->plugin);

        $this->server->httpResponse = $this->response = new Tinebase_WebDav_Sabre_ResponseMock();
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();
        
        $this->assertEquals('calendarDefaultAlarms', $pluginName);
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <propfind xmlns="DAV:">
                    <prop>
                        <default-alarm-vevent-date xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vevent-datetime xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vtodo-date xmlns="urn:ietf:params:xml:ns:caldav"/>
                        <default-alarm-vtodo-datetime xmlns="urn:ietf:params:xml:ns:caldav"/>
                    </prop>
                 </propfind>';

        $request = new Sabre\HTTP\Request('PROPFIND', '/calendars/' . Tinebase_Core::getUser()->contact_id, [
            'DEPTH'     => '0',
        ]);
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        //var_dump($this->response->body);
        $this->assertSame(207, $this->response->status);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
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
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $responseDoc->saveXML());
    }
}
