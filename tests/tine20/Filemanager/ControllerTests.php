<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Filemanager_ControllerTests
 * 
 * @package     Filemanager
 */
class Filemanager_ControllerTests extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
        )));
    }

    /**
     * @throws Admin_Exception
     * @throws Exception
     */
    public function testCreatePersonalContainer()
    {
        $user = $this->_createTestUser();
        // check if personal folder exists
        $personalFolderPath = $this->_getPersonalPath($user);
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $personalFolderName = sprintf($translation->_("%s's personal files"), $user->accountFullName);

        $node = Tinebase_FileSystem::getInstance()->stat($personalFolderPath . '/' . $personalFolderName);
        $this->assertEquals($personalFolderName, $node->name);

        return $user;
    }

    /**
     * @throws Admin_Exception
     * @throws Exception
     */
    public function testDeletePersonalContainer()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION, new Tinebase_Config_Struct(array(
            Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_FOLDERS => true,
        )));

        $user = $this->testCreatePersonalContainer();
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete(array($user->getId()));

        // check if personal folder exists
        $personalFolderPath = $this->_getPersonalPath($user);
        $this->expectException('Tinebase_Exception_NotFound');
        $this->expectExceptionMessageMatches('/child:/');
        Tinebase_FileSystem::getInstance()->stat($personalFolderPath);
    }

    /**
     * @group nodockerci
     */
    public function testNotificationUpdateForReadOnly()
    {
        $oldUser = Tinebase_Core::getUser();
        /** @var Tinebase_Model_FullUser $sclever */
        $sclever = $this->_personas['sclever'];
        try {
            $fileSystem = Tinebase_FileSystem::getInstance();
            $fileManager = Filemanager_Controller_Node::getInstance();

            $node = Filemanager_Controller::getInstance()->createPersonalFolder($oldUser)->getFirstRecord();

            // try a failing update
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);
            $scleverNotificationProps = array(
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID => $sclever->getId(),
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE => true,
            );
            $node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)[] = $scleverNotificationProps;
            $failed = false;
            try {
                $fileManager->update($node);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                $failed = true;
            }
            static::assertTrue($failed);

            // now set grants so update can work
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            $node = $fileManager->get($node->getId());
            $node->grants = $fileSystem->getGrantsOfContainer($node);
            $node->grants->addRecord(new Tinebase_Model_Grants(array(
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id' => $sclever->getId(),
                Tinebase_Model_Grants::GRANT_READ => true,
            )));
            $node = $fileManager->update($node);

            // do update again, it should work now
            // test that updates to other things than own notification are silently dropped
            Tinebase_Core::set(Tinebase_Core::USER, $sclever);
            $notificationProps = array(array(
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID => $sclever->getId(),
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE => true,
            ), array(
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID => '1233',
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE => true,
            ), array(
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID => $sclever->getId(),
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE => true,
            ));
            $node->{Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION} = $notificationProps;
            $oldDescription = $node->description;
            static::assertNotEquals('test', $oldDescription, 'test data bad, the description must not be "test"');
            $node->description = 'test';

            $node = $fileManager->update($node);
            static::assertEquals($oldDescription, $node->description, 'description should not have been updated!');
            static::assertEquals(1, count($node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)));
            static::assertTrue(
                isset($node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)[0]) &&
                $scleverNotificationProps == $node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)[0]
            );

            $node->{Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION} = array();
            $node = $fileManager->update($node);
            static::assertEquals(0, count($node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION)));
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
        }
    }

    /**
     * @group nodockerci
     */
    public function testRenameFolderCaseSensitive()
    {
        // check if personal folder exists
        $personalFolderPath = $this->_getPersonalPath(Tinebase_Core::getUser());
        $node = Filemanager_Controller::getInstance()->createPersonalFolder(Tinebase_Core::getUser())->getFirstRecord();
        $personalFolderPath .= '/' . $node->name;
        $fileManager = Filemanager_Controller_Node::getInstance();

        $fileManager->createNodes($personalFolderPath . '/test', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $fileManager->moveNodes(array($personalFolderPath . '/test'), array($personalFolderPath . '/Test'));
    }

    public function testSearchPinProtectedNode()
    {
        $fileManager = Filemanager_Controller_Node::getInstance();
        $nodes = $fileManager->createNodes('/shared/test', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        $this->assertSame(1, $nodes->count());

        $node = $nodes->getFirstRecord();
        $this->assertNull($node->pin_protected_node);

        $this->_createAreaLockConfig([
            Tinebase_Model_AreaLockConfig::FLD_AREA_NAME => 'datasafe',
            Tinebase_Model_AreaLockConfig::FLD_AREAS => [Tinebase_Model_AreaLockConfig::AREA_DATASAFE],
            Tinebase_Model_AreaLockConfig::FLD_MFAS => ['pin'],
            Tinebase_Model_AreaLockConfig::FLD_VALIDITY => Tinebase_Model_AreaLockConfig::VALIDITY_SESSION,
        ]);

        $node->pin_protected_node = $node->getId();
        $node = $fileManager->update($node);

        $this->assertSame($node->getId(), $node->pin_protected_node);

        Filemanager_Controller_Node::destroyInstance();
        Tinebase_Filesystem::getInstance()->resetBackends();

        $this->expectException(Tinebase_Exception_AreaLocked::class);
        (new Filemanager_Frontend_Json())->searchNodes([
            ['field' => 'path', 'operator' => 'equals', 'value' => '/shared/test'],
        ], []);
    }

    public function testCreateSharedTopLevelFolder()
    {
        static::assertTrue(Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS),
            'admin user does not have manage_shared_folders right');

        $fileManager = Filemanager_Controller_Node::getInstance();
        $nodes = $fileManager->createNodes('/shared/test', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        static::assertEquals(1, $nodes->count());

        $oldUser = Tinebase_Core::getUser();
        /** @var Tinebase_Model_FullUser $sClever */
        $sClever = $this->_personas['sclever'];
        $fileManagerId = Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId();
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

        try {
            Tinebase_Acl_Roles::unsetInstance();
            Tinebase_Core::set(Tinebase_Core::USER, $sClever);

            try {
                $fileManager->createNodes('/shared/secondTest', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
                static::fail('creating shared folder in top level should require MANAGE_SHARED_FOLDERS right');
            } catch (Tinebase_Exception_AccessDenied $tead) {}
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
            Tinebase_Acl_Roles::unsetInstance();
        }
    }

    public function testSharedFolderACLs()
    {
        static::assertTrue(Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS),
            'admin user does not have manage_shared_folders right');

        $fileManager = Filemanager_Controller_Node::getInstance();
        $nodes = $fileManager->createNodes('/shared/test', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        static::assertEquals(1, $nodes->count());

        $oldUser = Tinebase_Core::getUser();
        /** @var Filemanager_Model_Node $node */
        $node = $nodes->getFirstRecord();
        /** @var Tinebase_Model_FullUser $sClever */
        $sClever = $this->_personas['sclever'];
        $node->grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [[
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'        => $sClever->getId(),
            Tinebase_Model_Grants::GRANT_READ => true,
        ], [
            'account_type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'        => $oldUser->getId(),
            Tinebase_Model_Grants::GRANT_ADMIN => true,
        ]]);
        $fileManager->update($node);

        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given' => 'test',
            'container_id' => Admin_Controller_User::getInstance()->getDefaultInternalAddressbook(),
            'relations' => [[
                'related_model'         => Filemanager_Model_Node::class,
                'related_backend'       => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_degree'        => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_id'            => $node->getId(),
                'type'                  => 't'
            ]]
        ]));

        static::assertEquals(1, $contact->relations->count());
        static::assertEquals($node->getId(), $contact->relations->getFirstRecord()->related_id);

        try {
            Tinebase_Core::set(Tinebase_Core::USER, $sClever);
            try {
                $fileManager->createNodes('/shared/test/subFolder', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
                static::fail('acl test failed');
            } catch (Tinebase_Exception_AccessDenied $tead) {}
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
        }

        try {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['jmcblack']);
            $jmcbContact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
            static::assertEquals(1, $jmcbContact->relations->count());
            static::assertTrue(! isset($jmcbContact->relations->getFirstRecord()->related_record));
        } finally {
            Tinebase_Core::set(Tinebase_Core::USER, $oldUser);
        }
    }
}
