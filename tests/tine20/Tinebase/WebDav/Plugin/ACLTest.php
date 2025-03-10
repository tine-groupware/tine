<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_WebDav_Plugin_ACLTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    /**
     * @var Tinebase_WebDav_Plugin_ACL
     */
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

        $this->server->addPlugin(
            new \Sabre\DAV\Auth\Plugin(new Tinebase_WebDav_Auth(), null)
        );

        $this->plugin = new Tinebase_WebDav_Plugin_ACL();
        $this->plugin->principalCollectionSet = [
            Tinebase_WebDav_PrincipalBackend::PREFIX_USERS,
            Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS,
            Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
        ];
        $this->server->addPlugin($this->plugin);
    }


    /**
     * test propfind sync-token request
     */
    public function testPropfindSyncToken()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
                 <A:propfind xmlns:A="DAV:">
                     <A:prop>
                        <A:sync-token/>
                     </A:prop>
                 </A:propfind>';

        $request = new Sabre\HTTP\Request('OPTIONS', '/webdav/Filemanager');
        $request->setBody($body);

        $this->server->httpRequest = $request;
        $this->server->exec();
        static::assertSame(200, $this->response->status);
        static::assertTrue(isset($this->response->getHeaders()['Allow']), 'allow header not set');
        static::assertStringContainsString('ACL', $this->response->getHeader('Allow'));
    }
}
