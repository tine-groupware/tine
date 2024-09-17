<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Controller_List
 */
class Addressbook_Controller_ListTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * the controller
     * 
     * @var Addressbook_Controller_List
     */
    protected $_instance = null;

    protected $_oldXpropsSetting = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_instance = Addressbook_Controller_List::getInstance();
        
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Addressbook_Model_Contact::class,
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $container = $personalContainer[0];

        $this->objects['contact1'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'email'                 => 'unittests01@tine20.org',
            'email_home'            => 'unittests01home@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact1',
            'n_fileas'              => 'Contact1, List',
            'n_given'               => 'List',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));
        $this->objects['contact1'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact1'], FALSE);
        
        $this->objects['contact2'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Zend_Date???
            'email'                 => 'unittests02@tine20.org',
            'email_home'            => 'unittests02home@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $container->getId(),
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Contact2',
            'n_fileas'              => 'Contact2, List',
            'n_given'               => 'List',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));
        $this->objects['contact2'] = Addressbook_Controller_Contact::getInstance()->create($this->objects['contact2'], FALSE);
        
        $this->objects['initialList'] = new Addressbook_Model_List(array(
            'name'         => 'initial list',
            'container_id' => $container->getId(),
            'members'      => array($this->objects['contact1'], $this->objects['contact2']),
        ));
    }

    protected function tearDown(): void
    {
        foreach ([$this->objects['contact1'], $this->objects['contact2']] as $contact) {
            try {
                Addressbook_Controller_Contact::getInstance()->delete([$contact->getId()]);
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        }

        parent::tearDown();

        // remove instance to prevent acl pollution
        Admin_Controller_EmailAccount::destroyInstance();
    }

    /**
     * try to add a list
     * 
     * @return Addressbook_Model_List
     */
    public function testAddList()
    {
        $list = $this->objects['initialList'];

        $list = $this->_instance->create($list, FALSE);

        $this->assertEquals($this->objects['initialList']->name, $list->name);
        
        return $list;
    }

    public function testListAsMailinglist()
    {
        $list = $this->createAdbMailingList();
        $account = $this->searchMailinglistAccount($list);
        $accountCtrl = Felamimail_Controller_Account::getInstance();

        // assert email user
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertEquals($this->objects['initialList']->email, $userInBackend['loginname'], print_r($userInBackend, true));
        
        // test change email
        $list->email = 'shoo' . Tinebase_Record_Abstract::generateUID(8) .  '@' . TestServer::getPrimaryMailDomain();
        $this->_instance->update($list);
        
        // test account add grant and update keep_copy in list

        $account = $accountCtrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $list->getId()],
            ['field' => 'type',    'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_ADB_LIST],
        ]))->getFirstRecord();
        static::assertNotNull($account, 'could not get account');
        static::assertSame($list->email, $account->name);

        // test delete account
        $list->email = '';
        $this->_instance->update($list);

        $deletedAccount = $accountCtrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $list->getId()],
            ['field' => 'type',    'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_ADB_LIST],
        ]))->getFirstRecord();
        static::assertNull($deletedAccount, 'account was not deleted');

        // check if email user is removed, too
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        $userInBackend = $emailUserBackend->getRawUserById($emailUser);
        self::assertFalse($userInBackend, print_r($userInBackend, true));

        return $list;
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     */
    public function testDeleteMailingList()
    {
        $list = $this->createAdbMailingList();
        $mailAccount = $this->searchMailinglistAccount($list);
        Admin_Controller_EmailAccount::getInstance()->delete($mailAccount);
        $list = Addressbook_Controller_List::getInstance()->get($list->getId());
        self::assertNull($list->email, 'email not remove');
        self::assertEquals($list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST], false, 'xpros doesn´t change to false');
    }

    public function createAdbMailingList(): Addressbook_Model_List
    {
        $this->_testNeedsTransaction();

        $this->objects['initialList']->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] = 1;
        $this->objects['initialList']->email = 'testlist' . Tinebase_Record_Abstract::generateUID(8) .  '@' . TestServer::getPrimaryMailDomain();

        $list = $this->testAddList();
        $this->_listsToDelete[] = $list;
        return $list;
    }

    public function searchMailinglistAccount(Addressbook_Model_List $list): Felamimail_Model_Account
    {
        $accountCtrl = Felamimail_Controller_Account::getInstance();
        $account = $accountCtrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $list->getId()],
            ['field' => 'type',    'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_ADB_LIST],
        ]))->getFirstRecord();
        static::assertNotNull($account, 'could not get account');
        static::assertSame($list->email, $account->name);

        self::assertNotEmpty($account->xprops()[Addressbook_Model_List::XPROP_EMAIL_USERID_IMAP], 'xprops not set in list');
        return $account;
    }

    public function testChangeListEmailToAlreadyUsed()
    {
        $list = $this->testListAsMailinglist();
        // try to use already used email address
        $list->email = Tinebase_Core::getUser()->accountEmailAddress;
        try {
            $this->_instance->update($list);
            self::fail('exception expected for already used email address');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            self::assertEquals(Tinebase_Translation::getTranslation(Addressbook_Config::APP_NAME)
                ->_('E-Mail address is already given. Please choose another one.'), $tesg->getMessage());
        }
    }
    
    /**
     * try to get a list
     */
    public function testGetList()
    {
        $list = $this->_instance->get($this->testAddList()->getId());
        
        $this->assertEquals($this->objects['initialList']->name, $list->name);
    }
    
    /**
     * try to update a list
     */
    public function testUpdateList()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $this->assertEquals(1, count($list->members));
        $contactId = $list->members[0];
        $contact = Addressbook_Controller_Contact::getInstance()->get($contactId);
        
        $this->assertEquals($this->objects['contact2']->adr_one_locality, $contact->adr_one_locality);
    }

    /**
     * try to add list member
     */
    public function testAddListMember()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $list = $this->_instance->addListMember($list, $this->objects['contact1']);
        
        $this->assertTrue(in_array($this->objects['contact1']->getId(), $list->members), 'contact1 not found in members: ' . print_r($list->members, TRUE));
        $this->assertTrue(in_array($this->objects['contact2']->getId(), $list->members), 'contact2 not found in members: ' . print_r($list->members, TRUE));
    }

    /**
     * try to remove list member
     */
    public function testRemoveListMember()
    {
        $list = $this->testAddList();
        $list->members = array($this->objects['contact1'], $this->objects['contact2']);
        
        $list = $this->_instance->update($list);
        
        $list = $this->_instance->removeListMember($list, $this->objects['contact1']);
        $this->assertEquals($list->members, array($this->objects['contact2']->getId()));
    }

    /**
     * try to delete a list
     */
    public function testDeleteList()
    {
        $id = $this->testAddList()->getId();
        $this->_instance->delete($id);

        $this->expectException('Tinebase_Exception_NotFound');
        $list = $this->_instance->get($id);
    }
    
    /**
     * testHiddenMembers
     * 
     * @see 0007122: hide hidden users from lists
     */
    public function testHiddenMembers()
    {
        // sclever is deleted from sync backend, so we skip this with ldap backends
        $this->_skipIfLDAPBackend();

        $group = new Tinebase_Model_Group(array(
            'name'          => 'testgroup',
            'description'   => 'test group',
            'visibility'    => Tinebase_Model_Group::VISIBILITY_DISPLAYED
        ));
        $group = Admin_Controller_Group::getInstance()->create($group);
        $this->_groupIdsToDelete[] = $group->getId();
        $list = $this->_instance->get($group->list_id);
        
        $sclever = Tinebase_User::getInstance()->getFullUserByLoginName('sclever');
        $list->members = array($sclever->contact_id);
        $list = $this->_instance->update($list);
        
        // hide sclever
        $sclever->visibility = Tinebase_Model_User::VISIBILITY_HIDDEN;
        Admin_Controller_User::getInstance()->update($sclever, NULL, NULL);
        
        // fetch list and check hidden members
        $listGet = $this->_instance->get($list->getId());
        $listSearch = $this->_instance->search(new Addressbook_Model_ListFilter(array(array(
            'field'    => 'id',
            'operator' => 'in',
            'value'    => array($list->getId()),
        ))))->getFirstRecord();
        $listGetMultiple = $this->_instance->getMultiple(array($list->getId()))->getFirstRecord();
        foreach (array('get' => $listGet, 'search' => $listSearch, 'getMultiple' => $listGetMultiple) as $fn => $listRecord) {
            $this->assertTrue($listRecord instanceof Addressbook_Model_List, $fn . ' did not return a list: ' . var_export($listRecord, TRUE));
            if (Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_MAILINGLIST)) {
                $this->assertEquals(1, count($listRecord->members),
                    'Hidden sclever should appear in list members returned by ' . $fn. '(): ' .
                    print_r($listRecord->toArray(), true));
            } else {
                $this->assertEquals(0, count($listRecord->members),
                    'Hidden sclever should not appear in list members returned by ' . $fn. '(): ' .
                    print_r($listRecord->toArray(), true));
            }
        }
    }

    public function testListSieveRule()
    {
        $list = $this->testAddList();
        $emailHomeContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'email_home' => 'someaddress@home.me',
            'n_fn' => 'another contact'
        ]));
        $list->members = [$this->objects['contact2'], $emailHomeContact];
        $list->email = 'foo@bar.de';

        $list = $this->_instance->update($list);

        $list = $this->_instance->addListMember($list, $this->objects['contact1']);

        $sieveRule = Felamimail_Sieve_AdbList::createFromList($list);
        self::assertStringContainsString($this->objects['contact2']->email, $sieveRule);
        self::assertStringContainsString($emailHomeContact->email_home, $sieveRule);
    }

    public function testListUpdateSieveMasterPW()
    {
        if (! Tinebase_EmailUser::backendSupportsMasterPassword()) {
            self::markTestSkipped('test is only working with sieve master pw support');
        }

        $list = $this->createAdbMailingList();
        $account = $this->searchMailinglistAccount($list);

        Felamimail_Backend_SieveFactory::reset();

        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
        $emailUserBackend->inspectSetPassword($emailUser->getId(), 'somepw');

        // update list (sieve script should be updatable via sieve master pw)
        $list->members = [$this->_personas['sclever']->contact_id];

        $updatedList = $this->_instance->update($list);
        self::assertFalse(Felamimail_Sieve_AdbList::$adbListSieveAuthFailure, 'auth failure while trying to put sieve script');

        $script = Felamimail_Sieve_AdbList::getSieveScriptForAdbList($updatedList)->getSieve();
        self::assertStringContainsString('reject "' . Tinebase_Translation::getTranslation(Felamimail_Config::APP_NAME)
                ->_('Your email has been rejected') . '"', $script);
        self::assertStringContainsString($this->_personas['sclever']->accountEmailAddress, $script);
    }

    public function testSearchForListMembersOfEmptyList()
    {
        $this->objects['initialList']->members = [];
        $list = $this->testAddList();

        // this used to create an sql error once
        $this->assertSame(0, Addressbook_Controller_Contact::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Addressbook_Model_Contact::class, [
                    ['field' => 'list', 'operator' => 'equals', 'value' => $list->toArray(false)]
                ]
            ))->count());
    }

    /**
     * testInternalAddressbookConfig
     *
     * @see http://forge.tine20.org/mantisbt/view.php?id=5846
     */
    public function testInternalAddressbookConfig()
    {
        $list = $this->testAddList();
        $list->container_id = NULL;
        $listBackend = new Addressbook_Backend_List();
        $listBackend->update($list);

        Admin_Config::getInstance()->delete(Tinebase_Config::APPDEFAULTS);
        Addressbook_Controller_List::getInstance()->addListMember($list, $this->objects['contact1']);
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();

        $this->assertTrue(! empty($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK]), print_r($appConfigDefaults, TRUE));
    }

    /**
     * try to delete a contact
     */
    public function _testDeleteUserAccountContact()
    {
        $this->expectException('Addressbook_Exception_AccessDenied');
        $userContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        Addressbook_Controller_Contact::getInstance()->delete($userContact->getId());
    }

    /**
     * @see 0011522: improve handling of group-lists
     */
    public function testChangeListWithoutManageGrant()
    {
        // try to set memberships without MANAGE_ACCOUNTS
        $this->_removeRoleRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS, true);

        $listId = Tinebase_Group::getInstance()->getGroupByName('Secretary')->list_id;
        try {
            Addressbook_Controller_List::getInstance()->addListMember($listId, array($this->objects['contact1']->getId()));
            $this->fail('should not be possible to add list member to system group');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('No permission to add list member.', $tead->getMessage());
        }

        $listBeforeUpdate = Addressbook_Controller_List::getInstance()->get($listId);
        self::assertGreaterThan(0, count($listBeforeUpdate->members));
        $list = clone($listBeforeUpdate);
        // save the list and check if it still has its members
        Addressbook_Controller_List::getInstance()->update($list);
        $listBackend = new Addressbook_Backend_List();
        Tinebase_Core::getCache()->clean();
        $updatedList = $listBackend->get($listId);
        self::assertEquals($listBeforeUpdate->members, $updatedList->members);

        $updatedList->name = 'my new name';
        try {
            Addressbook_Controller_List::getInstance()->update($updatedList);
            $this->fail('should not be possible to set name of system group');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->assertEquals('This is a system group. To edit this group you need the Admin.ManageAccounts right.', $tead->getMessage());
        }
    }

    public function testAddSystemUserToList()
    {
        $list = $this->_createSystemList();
        $list->members = [Tinebase_Core::getUser()->contact_id];
        $updatedList = Addressbook_Controller_List::getInstance()->update($list);
        self::assertEquals(1, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // should be added to system group, too
        $groupMembers = Admin_Controller_Group::getInstance()->getGroupMembers($list->group_id);
        self::assertEquals(1, count($groupMembers),
            'user missing from group members: ' . print_r($groupMembers, true));

        // add another user and a non user contact to list
        $sclever = $this->_personas['sclever'];
        $updatedList->members = array_merge($updatedList->members, [$sclever->contact_id, $this->objects['contact1']->getId()]);
        $updatedListWithSclever = Addressbook_Controller_List::getInstance()->update($updatedList);
        self::assertEquals(3, count($updatedListWithSclever->members),
            'list members missing: ' . print_r($updatedListWithSclever->toArray(), true));

        $groupMembers = Admin_Controller_Group::getInstance()->getGroupMembers($list->group_id);
        self::assertEquals(2, count($groupMembers),
            'user missing from group members: ' . print_r($groupMembers, true));

        // set account_only in group -> user contacts should still be list member
        $adminJson = new Admin_Frontend_Json();
        $groupJson = $adminJson->getGroup($list->group_id);
        $groupJson['account_only'] = 1;
        $groupJson['members'] = $groupMembers;
        $groupJsonUpdated = $adminJson->saveGroup($groupJson);
        self::assertEquals(2, $groupJsonUpdated['members']['totalcount'], print_r($groupJsonUpdated, true));
    }

    protected function _createSystemList()
    {
        // create system group
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name'          => 'tine20phpunitgroup' . Tinebase_Record_Abstract::generateUID(6),
            'description'   => 'unittest group',
            'members'       => [],
        ]));

        // add system user contact to list
        $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->_listsToDelete[] = $list;
        return $list;
    }

    /**
     * @group nogitlabciad
     */
    public function testAddNonSystemContactAndUpdategroupCheckModlog()
    {
        // create system list
        $list = $this->_createSystemList();

        // contacts (non-system + system)
        $list->members = [
            $this->objects['contact1']->getId(),
            Tinebase_Core::getUser()->contact_id,
        ];
        $updatedList = Addressbook_Controller_List::getInstance()->update($list);
        self::assertEquals(2, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // update group
        $adminJson = new Admin_Frontend_Json();
        $groupJson = $adminJson->getGroup($list->group_id);
        self::assertEquals(1, $groupJson['members']['totalcount'], print_r($groupJson, true));
        $groupJson['name'] = 'updated unittest group';
        $groupJson['members'] = [];
        $adminJson->saveGroup($groupJson);

        // contact should still be in the list!
        $updatedList = Addressbook_Controller_List::getInstance()->get($list->getId());
        self::assertEquals(1, count($updatedList->members),
            'list members missing: ' . print_r($updatedList->toArray(), true));

        // check modlog
        $modlogs = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(
            'Addressbook',
            $list->getId(),
            Addressbook_Model_List::class
        );
        self::assertEquals(3, count($modlogs), 'should have 2 update and 1 create modlogs:'
            . print_r($modlogs->toArray(), true));
        $modlogs->sort('seq');
        self::assertEquals('created', $modlogs[0]->change_type);
        $diffSecondUpdate = json_decode($modlogs[2]->new_value);
        self::assertTrue(isset($diffSecondUpdate->diff->members));
        self::assertEquals(1, count($diffSecondUpdate->diff->members));
    }
}
