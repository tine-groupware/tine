<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Admin
 */
class Admin_ControllerTest extends TestCase
{
    /**
     * testCustomFieldCreate
     *
     * @todo should create cf via Admin_Controller_Customfield
     */
    public function testCustomFieldCreate()
    {
        $cf = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => 'unittest_test',
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        )));

        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($cf->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);
    }

    public function testCustomFieldAddGrants()
    {
        $group = Tinebase_Group::getInstance()->addGroup(new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit' . Tinebase_Record_Abstract::generateUID(),
            'description'   => 'Group from test testAddGroup'
        )));

        $role = Tinebase_Acl_Roles::getInstance()->createRole(new Tinebase_Model_Role(array(
            'id'                    => '10',
            'name'                  => 'phpunitrole',
            'description'           => 'test role for phpunit',
        )));

        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();
        $result->grants = [
            [
                'customfield_id' => $result->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id'    => Tinebase_Core::getUser()->getId(),
                Tinebase_Model_CustomField_Grant::GRANT_READ => true,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE => true,
            ],
            [
                'customfield_id' => $result->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                'account_id'    => $group->getId(),
                Tinebase_Model_CustomField_Grant::GRANT_READ => true,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE => true,
            ],
            [
                'customfield_id' => $result->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE,
                'account_id'    => $role->getId(),
                Tinebase_Model_CustomField_Grant::GRANT_READ => true,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE => true,
            ]
        ];
        $updatedCF = Admin_Controller_Customfield::getInstance()->update($result);
        $this->assertCount(3,$updatedCF->grants, 'missing customField grants');
    }

    /**
     * testCustomFieldUpdate
     */
    public function testCustomFieldUpdate()
    {
        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        $result->name = 'changed name';
        $result->grants = [
            [
                'customfield_id' => $result->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id'    => Tinebase_Core::getUser()->getId(),
                Tinebase_Model_CustomField_Grant::GRANT_READ => true,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE => true,
            ]
        ];
        $updatedCF = Admin_Controller_Customfield::getInstance()->update($result);
        static::assertEquals($result->name, $updatedCF->name);

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instanceSeq);
        static::assertEquals(2, $modifications->count(), 'no replication modifications found');

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('rollback did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getFirstRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getLastRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals($updatedCF->name, $lookupCf->name);
    }

    /**
     * testCustomFieldDelete
     */
    public function testCustomFieldDelete()
    {
        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $result = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        $deleted = Admin_Controller_Customfield::getInstance()->delete([$result->getId()]);

        $this->assertEquals(1, count($deleted));

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instanceSeq);
        static::assertEquals(2, $modifications->count(), 'no replication modifications found');

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('rollback did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getFirstRecord()]));
        $lookupCf = Tinebase_CustomField::getInstance()->getCustomField($result->getId());
        $this->assertEquals('unittest_test', $lookupCf->name);

        Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet(
            $modifications->getRecordClassName(), [$modifications->getLastRecord()]));

        try {
            Tinebase_CustomField::getInstance()->getCustomField($result->getId());
            static::fail('delete replication did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    /**
     * testCustomFieldSearchAcl
     */
    public function testCustomFieldSearchAcl()
    {
        $this->testCustomFieldCreate();
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Addressbook');
        $customField = $cfs->filter('name', 'unittest_test')->getFirstRecord();

        // give the cf config to sclever
        $customField->name = 'changed name';
        $customField->grants = [
            [
                'customfield_id' => $customField->getId(),
                'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id'    => $this->_personas['sclever']->getId(),
                Tinebase_Model_CustomField_Grant::GRANT_READ => true,
                Tinebase_Model_CustomField_Grant::GRANT_WRITE => true,
            ]
        ];
        Admin_Controller_Customfield::getInstance()->update($customField);
        $result = Admin_Controller_Customfield::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CustomField_Config::class, [
                ['field' => 'id', 'operator' => 'equals', 'value' => $customField->getId()]
            ]));
        self::assertCount(1, $result, 'admin should see all cf configs');
    }

    public function testFailedListCreation()
    {
        $controllerMock = Admin_Controller_GroupMock::getInstance();
        $result = null;
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = null;
        try {
            try {
                $controllerMock->create(new Tinebase_Model_Group([
                    'name' => __FUNCTION__
                ]));
                static::fail('exception expected');
            } catch (Exception $e) {
                static::assertEquals('kabum', $e->getMessage());
            }

            $result = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter([
                ['field' => 'name', 'operator' => 'equals', 'value' => __FUNCTION__]
            ]));
            static::assertEquals(0, $result->count());
        } finally {
            if (null !== $result && $result->count() > 0) {
                $oldPurge = Addressbook_Controller_List::getInstance()->purgeRecords(true);
                try {
                    Addressbook_Controller_List::getInstance()->delete($result);
                } finally {
                    Addressbook_Controller_List::getInstance()->purgeRecords($oldPurge);
                }
            }
        }
    }

    public function testRoleUpdateReplication()
    {
        $adminRole = Tinebase_Acl_Roles::getInstance()->getRoleByName('admin role');
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $userGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $exampleApplication = Tinebase_Application::getInstance()->getApplicationByName('ExampleApplication');

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        foreach ($members as &$member) {
            $member['id'] = $member['account_id'];
            $member['type'] = $member['account_type'];
            unset($member['role_id']);
            unset($member['account_id']);
            unset($member['account_type']);
        }
        $members[] = [
            'id'    => $userGroup->getId(),
            'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
        ];

        $appRights = new Tinebase_Record_RecordSet(Tinebase_Model_RoleRight::class,
            Tinebase_Acl_Roles::getInstance()->getRoleRights($adminRole->getId()), true);
        $exampleRight = $appRights->filter('application_id', $exampleApplication->getId())->filter('right', 'admin')
            ->getFirstRecord();
        static::assertNotNull($exampleRight);

        $appRights->removeRecord($exampleRight);
        $appRights = $appRights->toArray();
        $appRights[] = [
            'application_id' => $exampleApplication->getId(),
            'right'          => 'foo'
        ];

        // this will add a role member, remove a right, add a right in that order
        Admin_Controller_Role::getInstance()->update($adminRole, $members, $appRights);

        $appRights = new Tinebase_Record_RecordSet(Tinebase_Model_RoleRight::class,
            Tinebase_Acl_Roles::getInstance()->getRoleRights($adminRole->getId()), true);
        $exampleRight = $appRights->filter('application_id', $exampleApplication->getId())->filter('right', 'foo')
            ->getFirstRecord();
        $appRights->removeRecord($exampleRight);
        $appRights = $appRights->toArray();

        // this will remove a right
        Admin_Controller_Role::getInstance()->update($adminRole, $members, $appRights);


        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instance_seq);
        static::assertEquals(4, $modifications->count(), 'modifications count unexpected');

        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        // add a role member
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        static::assertEquals(1, count($members));
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $members = Tinebase_Acl_Roles::getInstance()->getRoleMembers($adminRole->getId());
        static::assertEquals(2, count($members));

        // remove a right
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(3, count($rights));
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(2, count($rights));

        // add a right
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(3, count($rights));

        // remove a right
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(
            new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $rights = Tinebase_Acl_Roles::getInstance()->getApplicationRights('ExampleApplication',
            Tinebase_Core::getUser()->getId());
        static::assertEquals(2, count($rights));
    }
}
