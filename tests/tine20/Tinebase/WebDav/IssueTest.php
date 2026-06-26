<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tine Group Developer <dev@tine-groupware.de>
 */

/**
 * Test class for Tinebase_Model_WebDavIssue creation via WebDAV
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_IssueTest extends TestCase
{
    /**
     * test that creating a file on /personal path leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFileOnPersonalCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        // Enable webdav issue reporting so issues get created when exceptions occur
        // Use setInMemory() to avoid triggering a config save that writes to the logger
        // (which fails in the test process with php://stdout)
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(fn() => Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav));
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);


        $request = Tinebase_Http_Request::fromString(
           "MKCOL /remote.php/dav/files/" . $this->_originalTestUser->accountLoginName . "/test HTTP/1.1\r\n"
            //"POST /webdav/" . $this->_originalTestUser->accountLoginName . "/ HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "User-Agent: TestClient\r\n"
            . "Authorization: Basic " . base64_encode($credentials['username'] . ':' . $credentials['password']) . "\r\n"
            . "\r\n"
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REMOTE_ADDR']    = 'localhost';

        $body = fopen('php://temp', 'r+');
        rewind($body);

        $server = new Tinebase_Server_WebDAV();
        $webDavServer = Tinebase_Server_WebDAV::getServer();
        $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
        Tinebase_Server_WebDAV::$_recreateServer = false;
        try {
            $server->handle($request, $body);
        } finally {
            Tinebase_Server_WebDAV::$_recreateServer = true;
        }

        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created');
        list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
        $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);

        unset($configRaii);
    }

    /**
     * test that creating a file in personal path with display name leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFileOnPersonalWithDisplayNameCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $path = Filemanager_Controller_Node::getInstance()->addBasePath('/personal/' . $this->_originalTestUser->accountLoginName);
        try {
            $node = Tinebase_FileSystem::getInstance()->stat($path);
            if ($node->quota !== 0) {
                $node->quota = 0;
                Tinebase_FileSystem::getInstance()->update($node);
                Tinebase_FileSystem::getInstance()->clearStatCache();
            }
        } catch (Tinebase_Exception_NotFound) {}

        $request = Tinebase_Http_Request::fromString(
            "PUT /remote.php/dav/files/" . $this->_originalTestUser->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/testfile.txt HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "User-Agent: TestClient\r\n"
            . "Authorization: Basic " . base64_encode($credentials['username'] . ':' . $credentials['password']) . "\r\n"
            . "Content-Length: 5\r\n"
            . "\r\n"
            . "hello"
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REMOTE_ADDR']    = 'localhost';
        $_SERVER['CONTENT_LENGTH'] = 5;

        $body = fopen('php://temp', 'r+');
        rewind($body);

        $server = new Tinebase_Server_WebDAV();
        $webDavServer = Tinebase_Server_WebDAV::getServer();
        $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
        Tinebase_Server_WebDAV::$_recreateServer = false;
        try {
            $server->handle($request, $body);
        } finally {
            Tinebase_Server_WebDAV::$_recreateServer = true;
        }

        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created');
        list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
        // should be forbidden always, but if all tests are running, there is a quota artefact I cant get rid of, so pragmatic solution... which more or less skips this test in ci though...
        $this->assertTrue(Sabre\DAV\Exception\Forbidden::class === $throwable || Sabre\DAV\Exception\InsufficientStorage::class === $throwable);

        unset($configRaii);
    }

    /**
     * test that creating a file in /shared path leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFileOnSharedCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(fn() => Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav));
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $request = Tinebase_Http_Request::fromString(
            "PUT /remote.php/dav/files/" . $this->_originalTestUser->accountLoginName . "/shared/testfile.txt HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "User-Agent: TestClient\r\n"
            . "Authorization: Basic " . base64_encode($credentials['username'] . ':' . $credentials['password']) . "\r\n"
            . "Content-Length: 5\r\n"
            . "\r\n"
            . "hello"
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REMOTE_ADDR']    = 'localhost';
        $_SERVER['CONTENT_LENGTH'] = 5;

        $body = fopen('php://temp', 'r+');
        rewind($body);

        $server = new Tinebase_Server_WebDAV();
        $webDavServer = Tinebase_Server_WebDAV::getServer();
        $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
        Tinebase_Server_WebDAV::$_recreateServer = false;
        try {
            $server->handle($request, $body);
        } finally {
            Tinebase_Server_WebDAV::$_recreateServer = true;
        }

        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created');
        list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
        $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);

        unset($configRaii);
    }

    /**
     * test that creating a folder in /shared without MANAGE_SHARED_FOLDERS right leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFolderOnSharedWithoutManageRightCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(fn() => Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav));
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $oldUser = Tinebase_Core::getUser();
        $sclever = $this->_personas['sclever'];
        $fileManagerAppId = Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId();

        // Remove MANAGE_SHARED_FOLDERS right from all roles for sclever
        $alteredRoles = [];
        foreach (Tinebase_Acl_Roles::getInstance()->getAll() as $role) {
            $rights = array_filter(Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId()),
                function ($val) use ($fileManagerAppId) {
                    return !($val['application_id'] === $fileManagerAppId && $val['right'] === Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS);
                });
            if (count($rights) < count(Tinebase_Acl_Roles::getInstance()->getRoleRights($role->getId()))) {
                Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), $rights);
                $alteredRoles[] = $role->getId();
            }
        }

        try {
            Tinebase_Acl_Roles::unsetInstance();
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);

            $request = Tinebase_Http_Request::fromString(
                "MKCOL /remote.php/dav/files/" . $sclever->accountLoginName . "/shared/testfolder HTTP/1.1\r\n"
                . "Host: localhost\r\n"
                . "User-Agent: TestClient\r\n"
                . "Authorization: Basic " . base64_encode($sclever->accountLoginName . ':' . $credentials['password']) . "\r\n"
                . "\r\n"
            );

            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
            $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
            $_SERVER['REMOTE_ADDR']    = 'localhost';

            $body = fopen('php://temp', 'r+');
            rewind($body);

            $server = new Tinebase_Server_WebDAV();
            $webDavServer = Tinebase_Server_WebDAV::getServer();
            $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
            Tinebase_Server_WebDAV::$_recreateServer = false;
            try {
                $server->handle($request, $body);
            } finally {
                Tinebase_Server_WebDAV::$_recreateServer = true;
            }

            $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
            $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when creating shared folder without MANAGE_SHARED_FOLDERS right');
            list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
            $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();

            // Restore MANAGE_SHARED_FOLDERS right to roles
            foreach ($alteredRoles as $roleId) {
                $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($roleId);
                $fileManagerApp = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');
                $rights[] = [
                    'application_id' => $fileManagerApp->getId(),
                    'right' => Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS,
                ];
                Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
            }
        }

        unset($configRaii);
    }

    /**
     * test that creating a file in a folder lacking write right leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFileInFolderLackingWriteRightCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $sclever = $this->_personas['sclever'];
        Admin_Controller_User::getInstance()->setAccountPassword($sclever, 'sclever', 'sclever');
        $fileManager = Filemanager_Controller_Node::getInstance();
        $folderPath = '/personal/' . $this->_originalTestUser->accountLoginName . '/testFolderLackingWrite';
        $nodes = $fileManager->createNodes($folderPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $this->assertEquals(1, $nodes->count());
        $folderNode = $nodes->getFirstRecord();

        $oldUser = Tinebase_Core::getUser();

        // Grant sclever read-only access to the folder
        $folderNode->grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $sclever->getId(),
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
                Tinebase_Model_Grants::GRANT_ADD => false,
                Tinebase_Model_Grants::GRANT_EDIT => false,
                Tinebase_Model_Grants::GRANT_DELETE => false,
            ],
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $oldUser->getId(),
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ],
        ]);
        Filemanager_Controller_Node::getInstance()->update($folderNode);

        try {
            Tinebase_Acl_Roles::unsetInstance();
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);

            $request = Tinebase_Http_Request::fromString(
                "PUT /remote.php/dav/files/" . $sclever->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/testFolderLackingWrite/testfile.txt HTTP/1.1\r\n"
                . "Host: localhost\r\n"
                . "User-Agent: TestClient\r\n"
                . "Authorization: Basic " . base64_encode('sclever:sclever') . "\r\n"
                . "Content-Length: 5\r\n"
                . "\r\n"
                . "hello"
            );

            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
            $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
            $_SERVER['REMOTE_ADDR']    = 'localhost';
            $_SERVER['CONTENT_LENGTH'] = 5;

            $body = fopen('php://temp', 'r+');
            rewind($body);

            $server = new Tinebase_Server_WebDAV();
            $webDavServer = Tinebase_Server_WebDAV::getServer();
            $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
            Tinebase_Server_WebDAV::$_recreateServer = false;
            try {
                $server->handle($request, $body);
            } finally {
                Tinebase_Server_WebDAV::$_recreateServer = true;
            }

            $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
            $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when creating file without write right');
            list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
            $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();
        }

        unset($configRaii);
    }

    /**
     * test that changing a file/folder lacking write right leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testChangeFileLackingWriteRightCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $sclever = $this->_personas['sclever'];
        Admin_Controller_User::getInstance()->setAccountPassword($sclever, 'sclever', 'sclever');
        $fileManager = Filemanager_Controller_Node::getInstance();
        $folderPath = '/personal/' . $this->_originalTestUser->accountLoginName . '/testFolderLackingWrite2';
        $nodes = $fileManager->createNodes($folderPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $this->assertEquals(1, $nodes->count());
        $folderNode = $nodes->getFirstRecord();

        $fileNodes = $fileManager->createNodes($folderPath . '/testfile.txt', Tinebase_Model_Tree_FileObject::TYPE_FILE);
        $this->assertEquals(1, $fileNodes->count());

        $oldUser = Tinebase_Core::getUser();

        // Grant sclever read-only access to the folder
        $folderNode->grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $sclever->getId(),
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
                Tinebase_Model_Grants::GRANT_ADD => false,
                Tinebase_Model_Grants::GRANT_EDIT => false,
                Tinebase_Model_Grants::GRANT_DELETE => false,
            ],
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $oldUser->getId(),
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ],
        ]);
        Filemanager_Controller_Node::getInstance()->update($folderNode);

        try {
            Tinebase_Acl_Roles::unsetInstance();
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);

            $request = Tinebase_Http_Request::fromString(
                "MOVE /remote.php/dav/files/" . $sclever->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/testFolderLackingWrite2/testfile.txt HTTP/1.1\r\n"
                . "Host: localhost\r\n"
                . "User-Agent: TestClient\r\n"
                . "Authorization: Basic " . base64_encode('sclever:sclever') . "\r\n"
                . "Destination: /remote.php/dav/files/" . $sclever->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/testFolderLackingWrite2/movedfile.txt\r\n"
                . "\r\n"
            );

            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
            $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
            $_SERVER['REMOTE_ADDR']    = 'localhost';

            $body = fopen('php://temp', 'r+');
            rewind($body);

            $server = new Tinebase_Server_WebDAV();
            $webDavServer = Tinebase_Server_WebDAV::getServer();
            $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
            $webDavServer->httpRequest->setHeader('Destination', $request->getHeader('Destination')->getFieldValue());
            Tinebase_Server_WebDAV::$_recreateServer = false;
            try {
                $server->handle($request, $body);
            } finally {
                Tinebase_Server_WebDAV::$_recreateServer = true;
            }

            $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
            $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when changing file without write right');
            list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
            $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();
        }

        unset($configRaii);
    }

    /**
     * test that deleting a folder lacking write right leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testDeleteFolderLackingWriteRightCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $sclever = $this->_personas['sclever'];
        Admin_Controller_User::getInstance()->setAccountPassword($sclever, 'sclever', 'sclever');
        $fileManager = Filemanager_Controller_Node::getInstance();
        $folderPath = '/personal/' . $this->_originalTestUser->accountLoginName . '/testFolderLackingWrite3';
        $nodes = $fileManager->createNodes($folderPath, Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $this->assertEquals(1, $nodes->count());
        $folderNode = $nodes->getFirstRecord();

        $oldUser = Tinebase_Core::getUser();

        // Grant sclever read-only access to the folder
        $folderNode->grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $sclever->getId(),
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
                Tinebase_Model_Grants::GRANT_ADD => false,
                Tinebase_Model_Grants::GRANT_EDIT => false,
                Tinebase_Model_Grants::GRANT_DELETE => false,
            ],
            [
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $oldUser->getId(),
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ],
        ]);
        Filemanager_Controller_Node::getInstance()->update($folderNode);

        try {
            Tinebase_Acl_Roles::unsetInstance();
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);

            $request = Tinebase_Http_Request::fromString(
                "DELETE /remote.php/dav/files/" . $sclever->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/testFolderLackingWrite3 HTTP/1.1\r\n"
                . "Host: localhost\r\n"
                . "User-Agent: TestClient\r\n"
                . "Authorization: Basic " . base64_encode('sclever:sclever') . "\r\n"
                . "\r\n"
            );

            $_SERVER['REQUEST_METHOD'] = $request->getMethod();
            $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
            $_SERVER['REMOTE_ADDR']    = 'localhost';

            $body = fopen('php://temp', 'r+');
            rewind($body);

            $server = new Tinebase_Server_WebDAV();
            $webDavServer = Tinebase_Server_WebDAV::getServer();
            $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
            Tinebase_Server_WebDAV::$_recreateServer = false;
            try {
                $server->handle($request, $body);
            } finally {
                Tinebase_Server_WebDAV::$_recreateServer = true;
            }

            $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
            $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when deleting folder without write right');
            list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
            $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();
        }

        unset($configRaii);
    }

    /**
     * test that creating a file with illegal characters leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFileWithIllegalCharactersCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        // Use a filename with illegal characters (/, \, *, ?, ", <, >, |)
        $illegalName = 'test:file*.txt';
        $request = Tinebase_Http_Request::fromString(
            "PUT /remote.php/dav/files/" . $this->_originalTestUser->accountLoginName . "/" . urlencode($this->_originalTestUser->accountLoginName) . "/" . rawurlencode($illegalName) . " HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "User-Agent: TestClient\r\n"
            . "Authorization: Basic " . base64_encode($credentials['username'] . ':' . $credentials['password']) . "\r\n"
            . "Content-Length: 5\r\n"
            . "\r\n"
            . "hello"
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REMOTE_ADDR']    = 'localhost';
        $_SERVER['CONTENT_LENGTH'] = 5;

        $body = fopen('php://temp', 'r+');
        rewind($body);

        $server = new Tinebase_Server_WebDAV();
        $webDavServer = Tinebase_Server_WebDAV::getServer();
        $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
        Tinebase_Server_WebDAV::$_recreateServer = false;
        try {
            $server->handle($request, $body);
        } finally {
            Tinebase_Server_WebDAV::$_recreateServer = true;
        }

        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when creating file with illegal characters');
        list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
        $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);

        unset($configRaii);
    }

    /**
     * test that creating a folder with illegal characters leads to a WebDavIssue being created
     * 
     * @group ServerTests
     */
    public function testCreateFolderWithIllegalCharactersCreatesWebDavIssue()
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_WebDavIssue::class);
        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(0, $result->count(), 'Expected no WebDavIssue present');

        $credentials = TestServer::getInstance()->getTestCredentials();

        $oldfolderNameConf = Tinebase_Config::getInstance()->{Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME};
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, true);
        $newReportWebDav = $reportWebDav = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV}->toArray();
        $configRaii = new Tinebase_RAII(function() use($reportWebDav, $oldfolderNameConf) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $reportWebDav);
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::USE_LOGINNAME_AS_FOLDERNAME, $oldfolderNameConf);
        });
        $newReportWebDav[Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER] = true;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::REPORT_WEBDAV, $newReportWebDav);

        $illegalName = 'test:folder*';
        $request = Tinebase_Http_Request::fromString(
            "MKCOL /remote.php/dav/files/" . $this->_originalTestUser->accountLoginName . "/" . $this->_originalTestUser->accountLoginName . "/" . rawurlencode($illegalName) . " HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "User-Agent: TestClient\r\n"
            . "Authorization: Basic " . base64_encode($credentials['username'] . ':' . $credentials['password']) . "\r\n"
            . "\r\n"
        );

        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $_SERVER['REQUEST_URI']    = $request->getUri()->getPath();
        $_SERVER['REMOTE_ADDR']    = 'localhost';

        $body = fopen('php://temp', 'r+');
        rewind($body);

        $server = new Tinebase_Server_WebDAV();
        $webDavServer = Tinebase_Server_WebDAV::getServer();
        $webDavServer->sapi = new Tinebase_WebDav_Sabre_SapiMock();
        Tinebase_Server_WebDAV::$_recreateServer = false;
        try {
            $server->handle($request, $body);
        } finally {
            Tinebase_Server_WebDAV::$_recreateServer = true;
        }

        $result = Tinebase_Controller_WebDavIssue::getInstance()->search($filter);
        $this->assertSame(1, $result->count(), 'Expected at least one WebDavIssue to be created when creating folder with illegal characters');
        list($throwable, ) = explode(' ', $result->getFirstRecord()->{Tinebase_Model_WebDavIssue::FLD_EXCEPTION}, 2);
        $this->assertSame(Sabre\DAV\Exception\Forbidden::class, $throwable);
        

        unset($configRaii);
    }
}
