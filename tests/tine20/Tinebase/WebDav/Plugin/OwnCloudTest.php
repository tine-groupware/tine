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
 * Test class for Tinebase_WebDav_Plugin_OwnCloud
 */
class Tinebase_WebDav_Plugin_OwnCloudTest extends Tinebase_WebDav_Plugin_AbstractBaseTest
{
    const REQUEST_BODY = '<?xml version="1.0" encoding="utf-8"?>
        <propfind xmlns="DAV:">
            <prop>
                <getlastmodified xmlns="DAV:"/>
                <getcontentlength xmlns="DAV:"/>
                <resourcetype xmlns="DAV:"/>
                <getetag xmlns="DAV:"/>
                <id xmlns="http://owncloud.org/ns"/>
            </prop>
        </propfind>';
    
    /**
     * base uri sent from owncloud client with different version
     *
     * @access public
     * @static
     */
    const BASE_URIV2_WEBDAV = '/remote.php/webdav';
    const BASE_URIV3_DAV_FILES_USERNAME = '/remote.php/dav/files/tine20admin';

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->server->httpRequest = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV, [
            'User-Agent' => 'Mozilla/5.0 (Macintosh) mirall/2.2.4 (build 3709)',
            'DEPTH' => '1',
        ]);

        $this->plugin = new Tinebase_WebDav_Plugin_OwnCloud();
        $this->server->addPlugin($this->plugin);
    }

    /**
     * tear down tests
     */
    protected function tearDown(): void
{
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, false);
    }

    /**
     * test getPluginName method
     */
    public function testGetPluginName()
    {
        $pluginName = $this->plugin->getPluginName();

        $this->assertEquals('Tinebase_WebDav_Plugin_OwnCloud', $pluginName);
    }

    /**
     * test testGetProperties method
     */
    public function testGetRootsV2()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?><d:propfind xmlns:d="DAV:"><d:prop><d:resourcetype/></d:prop>M</d:propfind>';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV, [
            'DEPTH' => '1',
        ]);
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->assertStringContainsString(self::BASE_URIV2_WEBDAV . '/shared', $responseDoc->textContent);
        $this->assertStringContainsString(self::BASE_URIV2_WEBDAV . '/Admin', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method
     */
    public function testGetRootsV3()
    {
        // fixme: owncloud client expect response path start with /dav/files/userLoginName too , /webdav/folder does not work anymore
        $body = '<?xml version="1.0" encoding="utf-8"?><d:propfind xmlns:d="DAV:"><d:prop><d:resourcetype/></d:prop></d:propfind>';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME, [
            'DEPTH' => '1',
        ]);

        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared', $responseDoc->textContent);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/Admin', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesV2()
    {
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName, [
            'DEPTH' => '0',
        ]);
        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesV3()
    {
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME, [
            'DEPTH' => '0',
        ]);
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    protected function _assertQueryResponse($responseDoc, $query, $nodeLength = 1)
    {
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('owncloud', 'http://owncloud.org/ns');

        $nodes = $xpath->query($query);
        $this->assertEquals($nodeLength, $nodes->length, $responseDoc->saveXML());
        
        for($i = 0 ; $i < $nodeLength; $i++) {
            $nodeValue = $nodes->item($i)->nodeValue;
            $this->assertNotNull($nodeValue, $responseDoc->saveXML());
        }
    }

    /**
     * @param string|null $body
     * @return DOMDocument
     */
    protected function _execPropfindRequest($body = null, $request = null, $expectedStatus = 207)
    {
        if (!$request) {
            $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName, [
                'DEPTH' => '0',
            ]);
        }
        
        $request->setBody($body ?: static::REQUEST_BODY);

        $this->server->httpRequest = $request;
        $this->server->exec();
        $this->assertSame($expectedStatus, $this->response->status);

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($this->response->body);
        return $responseDoc;
    }

    /**
     * test testGetSizeProperty
     */
    public function testGetSizePropertyV2()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
    <prop>
        <resourcetype xmlns="DAV:"/>
        <size xmlns="http://owncloud.org/ns"/>
    </prop>
</propfind>';
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:size';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName, [
            'DEPTH' => '0',
        ]);
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetSizeProperty
     */
    public function testGetSizePropertyV3()
    {
        $body = '<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
    <prop>
        <resourcetype xmlns="DAV:"/>
        <size xmlns="http://owncloud.org/ns"/>
    </prop>
</propfind>';
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:size';
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME . '/' . Tinebase_Core::getUser()->accountDisplayName, [
            'DEPTH' => '0',
        ]);
        
        $responseDoc = $this->_execPropfindRequest($body, $request);
        $this->_assertQueryResponse($responseDoc, $query);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesForSharedDirectoryV2()
    {
        $webdavTree = new \Sabre\DAV\Tree(new Tinebase_WebDav_Root());
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared');
        $node->createDirectory('unittestdirectory');
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        $node->createDirectory('subdir');

        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/shared/unittestdirectory', [
            'DEPTH' => '1',
        ]);
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query, 2);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/d:getetag';
        $this->_assertQueryResponse($responseDoc, $query, 2);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesForSharedDirectoryV3()
    {
        $webdavTree = new \Sabre\DAV\Tree(new Tinebase_WebDav_Root());
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared');
        $node->createDirectory('unittestdirectory');
        $node = $webdavTree->getNodeForPath('/webdav/Filemanager/shared/unittestdirectory');
        $node->createDirectory('subdir');

        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME . '/shared', [
            'DEPTH' => '1',
        ]);

        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory', $responseDoc->textContent);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query, 3);

        $query = '//d:multistatus/d:response/d:propstat/d:prop/d:getetag';
        $this->_assertQueryResponse($responseDoc, $query, 3);

        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory', [
            'DEPTH' => '1',
        ]);

        $responseDoc = $this->_execPropfindRequest(null, $request);
        $this->assertStringContainsString(self::BASE_URIV3_DAV_FILES_USERNAME . '/shared/unittestdirectory/subdir', $responseDoc->textContent);
    }

    /**
     * test testGetProperties method
     */
    public function testGetPropertiesForSharedDirectoryRights()
    {
        $fileManagerId = Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId();
        $requestBody = '<?xml version="1.0" encoding="utf-8"?>
        <propfind xmlns="DAV:">
            <prop>
                <getlastmodified xmlns="http://owncloud.org/ns"/>
                <getcontentlength xmlns="http://owncloud.org/ns"/>
                <resourcetype xmlns="http://owncloud.org/ns"/>
                <permissions xmlns="http://owncloud.org/ns"/>
                <checksums xmlns="http://owncloud.org/ns"/>
                <share-types xmlns="http://owncloud.org/ns"/>
                <data-fingerprint xmlns="http://owncloud.org/ns"/>
                <getetag xmlns="http://owncloud.org/ns"/>
                <id xmlns="http://owncloud.org/ns"/>
            </prop>
        </propfind>';
        try {
            $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME , ['DEPTH' => '1']);
            $responseDoc = $this->_execPropfindRequest($requestBody, $request);
            $this->assertStringContainsString('<owncloud:permissions>SCK</owncloud:permissions>', $responseDoc->saveXML());

            /** @var Tinebase_Model_Role $role */
            foreach (Tinebase_Acl_Roles::getInstance()->getAll() as $role) {
                $altered = false;
                $rights = array_filter(Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId()),
                    function($val) use($fileManagerId, &$altered) {
                        if ($fileManagerId === $val['application_id'] && $val['right'] ===
                            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS) {
                            $altered = true;
                            return false;
                        }
                        return true;
                    });
                if ($altered) {
                    Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $rights);
                }
            }
            Tinebase_Acl_Roles::unsetInstance();

            $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV3_DAV_FILES_USERNAME , ['DEPTH' => '1']);
            $responseDoc = $this->_execPropfindRequest($requestBody, $request);
            $this->assertStringContainsString('<owncloud:permissions>S</owncloud:permissions>', $responseDoc->saveXML());
        } finally {
            Tinebase_Acl_Roles::unsetInstance();
        }
    }

    /**
     * test testGetProperties method with an invalid client
     */
    public function testInvalidOwnCloudVersion()
    {
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountDisplayName, [
            'User-Agent' => 'Mozilla/5.0 (Macintosh) mirall/1.5.0 (build 1234)',
        ]);
        $this->server->httpRequest = $request;
        
        // re-init plugin, as header will be read in init
        $this->plugin = new Tinebase_WebDav_Plugin_OwnCloud();
        $this->server->addPlugin($this->plugin);

        $response = $this->_execPropfindRequest(request: $request, expectedStatus: 500)->saveXML();
        $this->assertStringContainsString(InvalidArgumentException::class, $response);
        $this->assertStringContainsString(sprintf(
            'OwnCloud client min version is "%s"!',
            Tinebase_WebDav_Plugin_OwnCloud::OWNCLOUD_MIN_VERSION
        ), $response);
    }

    /**
     * test testGetProperties method with alternate loginname config
     */
    public function testGetPropertiesWithAccountLoginName()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $request = new Sabre\HTTP\Request('PROPFIND', self::BASE_URIV2_WEBDAV . '/' . Tinebase_Core::getUser()->accountLoginName, [
            'DEPTH' => '0',
        ]);
        
        $responseDoc = $this->_execPropfindRequest(null, $request);
        
        $query = '//d:multistatus/d:response/d:propstat/d:prop/owncloud:id';
        $this->_assertQueryResponse($responseDoc, $query);
    }
}
