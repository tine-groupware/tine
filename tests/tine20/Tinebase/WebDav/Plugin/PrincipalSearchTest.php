<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  WebDav
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
class Tinebase_WebDav_Plugin_PrincipalSearchTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('only working in non-AD setups');
        }

        parent::setUp();
        
        $mockBackend = new Tinebase_WebDav_Sabre_AuthBackendMock();
        $mockBackend->principal = 'principals/users/' . Tinebase_Core::getUser()->contact_id;
        
        $plugin = new Sabre\DAV\Auth\Plugin($mockBackend,'realm');
        $this->server->addPlugin($plugin);

        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $aclPlugin->principalCollectionSet = array (Tinebase_WebDav_PrincipalBackend::PREFIX_USERS, Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS);
        $this->server->addPlugin($aclPlugin);
        
        $this->server->addPlugin(new \Sabre\CalDAV\Plugin());
        $this->server->addPlugin(new \Sabre\DAV\Sharing\Plugin());
        $this->server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
        
        $this->plugin = new Tinebase_WebDav_Plugin_PrincipalSearch();
        $this->server->addPlugin($this->plugin);
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();
        
        $this->assertEquals('calendarserverPrincipalSearch', $pluginName);
    }
    
    /**
     * test testGetProperties method
     */
    public function testGetProperties()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <A:calendarserver-principal-search xmlns:A="http://calendarserver.org/ns/" context="attendee">
                    <A:search-token>Administrators</A:search-token>
                    <A:limit>
                        <A:nresults>50</A:nresults>
                    </A:limit>
                    <B:prop xmlns:B="DAV:">
                        <C:calendar-user-address-set xmlns:C="urn:ietf:params:xml:ns:caldav"/>
                        <C:calendar-user-type xmlns:C="urn:ietf:params:xml:ns:caldav"/>
                        <A:record-type/>
                        <A:first-name/>
                        <A:last-name/>
                    </B:prop>
                </A:calendarserver-principal-search>';

        $request = new Sabre\HTTP\Request('REPORT', '/principals');
        $request->setBody($body);

        $this->server->addPlugin(new Calendar_Frontend_CalDAV_FixMultiGet404Plugin());
        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertEquals(207, $this->response->status, $this->response->body);
        
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-user-address-set');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $this->response->body);
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $this->response->body);
        
        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-user-type');
        $this->assertGreaterThanOrEqual(1, $nodes->length, $this->response->body);
        $this->assertNotEmpty($nodes->item(0)->nodeValue, $this->response->body);
    }
}
