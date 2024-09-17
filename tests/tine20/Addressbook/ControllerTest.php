<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Controller
 */
class Addressbook_ControllerTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = [];

    /**
     * @var Addressbook_Controller_Contact
     */
    protected $_instance = null;

    protected $_oldFileSystemConfig = null;

    protected $_container;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        $this->_instance = Addressbook_Controller_Contact::getInstance();

        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};

        $this->_container = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');
        
        $this->objects['initialContact'] = new Addressbook_Model_Contact(array(
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
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'jpegphoto'             => file_get_contents(dirname(__FILE__) . '/../Tinebase/ImageHelper/phpunit-logo.gif'),
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $this->_container->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.mundundzähne.de',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Laars',
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
        
        $this->objects['updatedContact'] = new Addressbook_Model_Contact(array(
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
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'jpegphoto'             => '',
            'note'                  => 'Bla Bla Bla',
            'container_id'          => $this->_container->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
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
                
        $this->objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',    
        ));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        if ($this->_instance) {
            $this->_instance->useNotes(true);
            if ((isset($this->objects['contact']) || array_key_exists('contact', $this->objects))) {
                $this->_instance->delete($this->objects['contact']);
            }
        }

        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;

        parent::tearDown();

        if ($this->_container) {
            Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'container', 'id = "' . $this->_container->getId() . '"');
        }
    }
    
    /**
     * adds a contact
     *
     * @return Addressbook_Model_Contact
     */
    protected function _addContact()
    {
        $contact = $this->objects['initialContact'];
        $contact->notes = array($this->objects['note']);
        $contact = $this->_instance->create($contact);
        $this->objects['contact'] = $contact;
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
        
        return $contact;
    }
    
    /**
     * try to get a contact
     */
    public function testGetContact()
    {
        $contact = $this->_addContact();
        
        $this->assertEquals($this->objects['initialContact']->adr_one_locality, $contact->adr_one_locality);
    }
    
    /**
     * test getImage function
     *
     */
    public function testGetImage()
    {
        $contact = $this->_addContact();
        
        $image = Addressbook_Controller::getInstance()->getImage($contact->getId());
        $this->assertEquals('Tinebase_Model_Image', get_class($image));
        $this->assertEquals($image->width, 94);
    }
    
    /**
     * try to get count of contacts
     *
     */
    public function testGetCountByAddressbookId()
    {
        $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id', 'operator' => 'equals',   'value' => '/all'),
        ));
        $count = $this->_instance->searchCount($filter);
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * try to get count of contacts
     */
    public function testGetCountOfAllContacts()
    {
        $this->objects['initialContact']['n_family'] = 'testUser';
        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'query',         'operator' => 'contains', 'value' => $contact->n_family),
            array('field' => 'container_id', 'operator' => 'equals',   'value' => '/all'),
        ));
        $count = $this->_instance->searchCount($filter);
        
        self::assertGreaterThanOrEqual(1, $count);
    }
    
    /**
     * try to update a contact
     */
    public function testUpdateContact()
    {
        $contact = $this->_addContact();
        
        $this->objects['updatedContact']->setId($contact->getId());
        $date = Tinebase_DateTime::now()->subSecond(1);
        $contact = $this->_instance->update($this->objects['updatedContact']);

        $this->assertEquals($this->objects['updatedContact']->adr_one_locality, $contact->adr_one_locality);
        $this->assertEquals($this->objects['updatedContact']->n_given." ".$this->objects['updatedContact']->n_family, $contact->n_fn);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_by', 'operator' => 'equals', 'value' => Zend_Registry::get('currentAccount')->getId())
        ));
        $count = $this->_instance->searchCount($filter);
        $this->assertTrue($count > 0);
        
        $date = Tinebase_DateTime::now()->subSecond(5);
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'last_modified_time', 'operator' => 'after', 'value' => $date)
        ));
        $count = $this->_instance->searchCount($filter);
        $this->assertTrue($count > 0);
    }

    /**
     * try to update a contact with missing postalcode
     * 
     * @see 0009424: missing postalcode prevents saving of contact
     */
    public function testUpdateContactWithMissingPostalcode()
    {
        if (! Tinebase_Config::getInstance()->get(Tinebase_Config::USE_NOMINATIM_SERVICE, TRUE)) {
            $this->markTestSkipped('Nominatim disabled');
        }
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);
        $contact = $this->_addContact();
        $contact->adr_two_street = null;
        $contact->adr_two_postalcode = null;
        $contact->adr_two_locality = 'Münster';
        $contact->adr_two_region = 'Nordrhein-Westfalen';
        
        $updatedContact = $this->_instance->update($contact);
        $this->assertTrue(48143 == $updatedContact->adr_two_postalcode || is_null($updatedContact->adr_two_postalcode));
    }

    public function testCreatedByFilter()
    {
        $filterCUser = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()]
        ]);
        $filterInCUserSC = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'in', 'value' => [Tinebase_Core::getUser()->getId(), $this->_personas['sclever']->getId()]]
        ]);
        $filterNotInScPw = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'notin', 'value' => [$this->_personas['sclever']->getId(), $this->_personas['pwulf']->getId()]]
        ]);
        $filterNotInCUser = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'notin', 'value' => [Tinebase_Core::getUser()->getId()]]
        ]);
        $oldSearchCountCUser = $this->_instance->searchCount($filterCUser);
        $oldSearchCountInCUserSC = $this->_instance->searchCount($filterInCUserSC);
        $oldSearchCountNotInScPw = $this->_instance->searchCount($filterNotInScPw);
        $oldSearchCountNotInCUser = $this->_instance->searchCount($filterNotInCUser);

        $this->_addContact();

        $this->assertSame(1 + $oldSearchCountCUser, $this->_instance->searchCount($filterCUser), 'search for created_by with equals did not work');
        $this->assertSame(1 + $oldSearchCountInCUserSC, $this->_instance->searchCount($filterInCUserSC), 'search for created_by with in did not work');
        $this->assertSame(1 + $oldSearchCountNotInScPw, $this->_instance->searchCount($filterNotInScPw), 'search for created_by with not in did not work');
        $this->assertSame($oldSearchCountNotInCUser, $this->_instance->searchCount($filterNotInCUser), 'search for created_by with not in did not work');
    }

    public function testCreatedByFilterWithRecordValues()
    {
        $filterCUser = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()]
        ]);
        $filterInCUserSC = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'in', 'value' => [Tinebase_Core::getUser(), $this->_personas['sclever']]]
        ]);
        $filterNotInScPw = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'notin', 'value' => [$this->_personas['sclever'], $this->_personas['pwulf']]]
        ]);
        $filterNotInCUser = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'created_by', 'operator' => 'notin', 'value' => [Tinebase_Core::getUser()]]
        ]);
        $oldSearchCountCUser = $this->_instance->searchCount($filterCUser);
        $oldSearchCountInCUserSC = $this->_instance->searchCount($filterInCUserSC);
        $oldSearchCountNotInScPw = $this->_instance->searchCount($filterNotInScPw);
        $oldSearchCountNotInCUser = $this->_instance->searchCount($filterNotInCUser);

        $this->_addContact();

        $this->assertSame(1 + $oldSearchCountCUser, $this->_instance->searchCount($filterCUser), 'search for created_by with equals did not work');
        $this->assertSame(1 + $oldSearchCountInCUserSC, $this->_instance->searchCount($filterInCUserSC), 'search for created_by with in did not work');
        $this->assertSame(1 + $oldSearchCountNotInScPw, $this->_instance->searchCount($filterNotInScPw), 'search for created_by with not in did not work');
        $this->assertSame($oldSearchCountNotInCUser, $this->_instance->searchCount($filterNotInCUser), 'search for created_by with not in did not work');
    }

    public function testSearchContactCS()
    {
        $this->objects['initialContact']->adr_one_street = 'caseSensitivityIsVerySensitive';
        $contact = $this->_addContact();
        $this->assertEquals($this->objects['initialContact']->adr_one_street, $contact->adr_one_street);

        $this->assertSame(1, $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'isverys']
        ]))->count(), 'ci search did not work');
        $this->assertSame(0, $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'isverys']
            ], '', [Tinebase_Model_Filter_Text::CASE_SENSITIVE => true]))->count(), 'cs search did not work');
        $this->assertSame(1, $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'IsVeryS']
        ], '', [Tinebase_Model_Filter_Text::CASE_SENSITIVE => true]))->count(), 'cs search did not work');
    }
    
    /**
     * test remove image
     */
    public function testRemoveContactImage()
    {
        $contact = $this->_addContact();
        
        $contact->jpegphoto = '';
        $contact = $this->_instance->update($contact);
        
        $this->expectException('Addressbook_Exception_NotFound');
        $image = Addressbook_Controller::getInstance()->getImage($contact->id);
    }
    
    /**
     * try to delete a contact
     *
     */
    public function testDeleteContact()
    {
        $contact = $this->_addContact();
        
        $this->_instance->delete($contact->getId());
        unset($this->objects['contact']);

        $this->expectException('Tinebase_Exception_NotFound');
        $contact = $this->_instance->get($contact->getId());
    }

    /**
     * try to delete a contact
     *
     */
    public function testDeleteUserAccountContact()
    {
        $this->expectException('Addressbook_Exception_AccessDenied');
        $userContact = $this->_instance->getContactByUserId(Tinebase_Core::getUser()->getId());
        $this->_instance->delete($userContact->getId());
    }
    
    /**
     * try to create a personal folder 
     *
     */
    public function testCreatePersonalFolder()
    {
        $account = Zend_Registry::get('currentAccount');
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account);
        $this->assertEquals(1, count($folder));
        $folder = Addressbook_Controller::getInstance()->createPersonalFolder($account->getId());
        $this->assertEquals(1, count($folder));
    }
    
    /**
     * test in week operator of creation time filter
     */
    public function testCreationTimeWeekOperator()
    {
        $this->_skipSundayNight();

        $contact = $this->_addContact();
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id',  'operator' => 'equals',   'value' => $contact->getIdFromProperty('container_id')),
        ));
        $count1 = $this->_instance->searchCount($filter);
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'creation_time', 'operator' => 'inweek',   'value' => 0),
            array('field' => 'container_id',  'operator' => 'equals',   'value' => $contact->getIdFromProperty('container_id')),
        ));
        $count2 = $this->_instance->searchCount($filter);
        $this->assertEquals($count1, $count2);
    }
    
    /**
     * test useNotes
     */
    public function testUseNotes()
    {
        $contact = $this->objects['initialContact'];
        $contact1 = clone $contact;
    
        $contact1->notes = array(new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note'])));
        $contact->notes = array(new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->objects['note'])));
    
        $newcontact1 = $this->_instance->create($contact1);
        $this->_instance->delete($newcontact1);
    
        $this->_instance->useNotes(false);
        $this->objects['contact'] = $this->_instance->create($contact);
    
        $compStr = 'Array
(
    [0] => Array
        (
            [note_type_id] => note
            [note] => phpunit test note
            [record_backend] => Sql
            [id] => 
        )

)';
        
        $this->assertTrue($newcontact1->has('notes'));
        $this->assertEquals($compStr, $newcontact1->notes[0]->note);
        
        $this->expectException('Tinebase_Exception_NotFound');
        $this->objects['contact']->notes[0]->note = 'note';
    }

    /**
     * @see 0012744: allow to configure when user contacts are hidden
     */
    public function testContactHiddenFilter()
    {
        $user = Tinebase_Core::getUser();

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_fileas',      'operator' => 'equals', 'value' => $user->accountDisplayName),
            array('field' => 'showDisabled',  'operator' => 'equals', 'value' => 0),
        ));

        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'contact should be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);

        // test case: disabled
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(0, $count, 'disabled contact should not be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'expired contact should be found');

        // test case: expired
        Addressbook_Config::getInstance()->set(Addressbook_Config::CONTACT_HIDDEN_CRITERIA, 'expired');
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(0, $count, 'expired contact should not be found');

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_DISABLED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count, 'disabled contact be found');

        // test case: never
        Addressbook_Config::getInstance()->set(Addressbook_Config::CONTACT_HIDDEN_CRITERIA, 'never');
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count);

        Tinebase_User::getInstance()->setStatus($user, Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $count = $this->_instance->searchCount($filter);
        $this->assertEquals(1, $count);
    }

    public function testContactPropertyDefinitionReplication()
    {
        $raii = new Tinebase_RAII(function() {
            Addressbook_Model_ContactProperties_Definition::$doNotApplyToContactModel = false;
        });

        $name = 'unittest_adr';
        $appId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();
        $cfCtrl = Tinebase_CustomField::getInstance();
        $cfc = $cfCtrl->getCustomFieldByNameAndApplication($appId, $name, Addressbook_Model_Contact::class, true);
        $this->assertNull($cfc);

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        /** @var Addressbook_Model_ContactProperties_Definition $cpDef */
        $cpDef = Addressbook_Controller_ContactProperties_Definition::getInstance()->create(
            new Addressbook_Model_ContactProperties_Definition([
                Addressbook_Model_ContactProperties_Definition::FLD_NAME => $name,
                Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Address::class,
                Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD,
            ])
        );

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $this->assertSame(1, $modifications->count());

        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $cfc = $cfCtrl->getCustomFieldByNameAndApplication($appId, $name, Addressbook_Model_Contact::class, true);
        $this->assertNull($cfc);

        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs($modifications);
        $this->assertTrue($result, 'applyReplicationModLogs failed');

        /** @var Addressbook_Model_ContactProperties_Definition $cpDef */
        $cpDef = Addressbook_Controller_ContactProperties_Definition::getInstance()->get($cpDef->getId());
        $cpDef->applyToContactModel();

        $cfc = $cfCtrl->getCustomFieldByNameAndApplication($appId, $name, Addressbook_Model_Contact::class, true);
        $this->assertNull($cfc);
        unset($raii);
    }

    public function testCustomFieldRelationLoop()
    {
        $contact = $this->_addContact();

        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Addressbook_Model_Contact',
            'definition'        => [
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'record',
                'recordConfig' => ['value' => ['records' => 'Tine.Crm.Model.Lead']],
                'uiconfig' => [
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                ]
            ]
        ]));

        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'),
            Crm_Model_Lead::class,
            Zend_Registry::get('currentAccount'),
            Tinebase_Model_Grants::GRANT_EDIT
        );
        if($personalContainer->count() === 0) {
            $personalContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $personalContainer = $personalContainer[0];
        }

        $lead = Crm_Controller_Lead::getInstance()->create(new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'  => $personalContainer->id,
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Tinebase_DateTime::now(),
            'relations'     => [[
                'related_model' => Addressbook_Model_Contact::class,
                'related_backend' => 'sql',
                'related_id' => $contact->getId(),
                'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type' => 'y'
            ]]
        )));

        // this lead to an infinite loop before it was fixed
        $contact->customfields = [
            $cField1->name => $lead->getId()
        ];
        $contact->relations = null;
        $contact = $this->_instance->update($contact);
        static::assertEquals(1, count($contact->customfields));
    }

    /**
     * 0013014: Allow to manage resources in addressbook module
     */
    public function testEnableResourcesFeature()
    {
        $this->_setFeatureForTest(Addressbook_Config::getInstance(), Addressbook_Config::FEATURE_LIST_VIEW);
        $this->assertTrue(Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_LIST_VIEW));
    }

    public function testModLogUndo()
    {
        // activate ModLog in FileSystem!
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $filesystem = Tinebase_FileSystem::getInstance();
        $filesystem->resetBackends();
        Tinebase_Core::clearAppInstanceCache();

        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
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
        $cField2 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
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
        $user = Tinebase_Core::getUser();
        /** @var Addressbook_Model_Contact $contact */
        $contact = $this->objects['initialContact'];

        // create contact with notes, relations, tags, attachments, customfield
        $contact->notes = array($this->objects['note']);
        $contact->relations = array(array(
            'related_id'        => $user->contact_id,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'foo'
        ));
        $contact->tags = array(array('name' => 'testtag1'));
        $this->_addRecordAttachment($contact);
        $contact->customfields = array(
            $cField1->name => 'test field1'
        );

        $createdContact = $this->_instance->create($contact);

        // update contact, add more notes, relations, tags, attachments, customfields
        /** @var Addressbook_Model_Contact $updateContact */
        $updateContact = $this->objects['updatedContact'];
        $updateContact->setId($createdContact->getId());
        $notes = $createdContact->notes->toArray();
        $notes[] = array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note 2',
        );
        $updateContact->notes = $notes;
        $relations = $createdContact->relations->toArray();
        $relations[] = array(
            'related_id'        => $user->contact_id,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'bar'
        );
        $updateContact->relations = $relations;
        $updateContact->tags = clone $createdContact->tags;
        $updateContact->tags->addRecord(new Tinebase_Model_Tag(array('name' => 'testtag2'), true));
        $updateContact->attachments = clone $createdContact->attachments;
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'moreTestAttachmentData');
        $updateContact->attachments->addRecord(new Tinebase_Model_Tree_Node(array(
                'name'      => 'moreTestAttachmentData.txt',
                'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
        ), true));
        $updateContact->xprops('customfields')[$cField2->name] = 'test field2';

        $contact = $this->_instance->update($updateContact);

        // delete it
        $this->_instance->delete($contact->getId());

        $oldSequence = $contact->seq;
        $contact->seq = 0;
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationById('Addressbook')->getId(), $contact, 10000);

        // undelete it
        $oldContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undeletedContact = $this->_instance->get($contact->getId());
        static::assertEquals(2, $undeletedContact->notes->count());
        static::assertEquals(2, $undeletedContact->relations->count());
        static::assertEquals(2, $undeletedContact->tags->count());
        static::assertEquals(2, $undeletedContact->attachments->count());
        static::assertEquals(2, count($undeletedContact->customfields));
        static::assertGreaterThan($oldSequence, $undeletedContact->seq);
        $undeletedContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($oldContentSequence, $undeletedContentSequence);

        // undo update
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidContact = $this->_instance->get($contact->getId());
        static::assertEquals(1, $undidContact->notes->count());
        static::assertEquals(1, $undidContact->relations->count());
        static::assertEquals(1, $undidContact->tags->count());
        static::assertEquals(1, $undidContact->attachments->count());
        static::assertEquals(1, count($undidContact->customfields));
        static::assertGreaterThan($undeletedContact->seq, $undidContact->seq);
        $undidContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($undeletedContentSequence, $undidContentSequence);

        // undo create
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        try {
            $this->_instance->get($contact->getId());
            static::fail('undo create did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        $uncreateContentSequence = Tinebase_Container::getInstance()->getContentSequence($contact->container_id);
        static::assertGreaterThan($undidContentSequence, $uncreateContentSequence);
    }

    public function testUpdateInternalContactHiddenListMembership()
    {
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $defaultGroup->visibility = Tinebase_Model_Group::VISIBILITY_HIDDEN;
        Tinebase_Group::getInstance()->updateGroup($defaultGroup);

        $adminContact = $this->_instance->get(Tinebase_Core::getUser()->contact_id);

        $adminContact->tel_car = '0132451566';

        $result = $this->_instance->update($adminContact);

        static::assertEquals($adminContact->tel_car, $result->tel_car);
    }

    public function testContactWithBackslashAndWildcardsAndPipes()
    {
        foreach (['|', '_', '%', '*', '\\'] as $char) {
            $created = $this->_instance->create(new Addressbook_Model_Contact(['org_name' => 'my org with ' . $char . 'fun'], true));
            $result = $this->_instance->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
                    ['field' => 'org_name', 'operator' => 'equals', 'value' => 'my org with ' . $char . 'fun'],
                ]));
            $this->assertSame(1, $result->count(), $char);
            $this->assertSame($created->getId(), $result->getFirstRecord()->getId(), $char);
        }
    }

    public function testContactModelPerformance()
    {
        self::markTestSkipped('this test has no assertions - just for performance measurement');

        $container = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');

        $memory = memory_get_usage();
        $timeStarted = microtime(true);
        $recordData = array(
            'adr_one_countryname' => 'DE',
            'adr_one_locality' => 'Hamburg',
            'adr_one_postalcode' => '24xxx',
            'adr_one_region' => 'Hamburg',
            'adr_one_street' => 'Pickhuben 4',
            'adr_one_street2' => 'no second street',
            'adr_two_countryname' => 'DE',
            'adr_two_locality' => 'Hamburg',
            'adr_two_postalcode' => '24xxx',
            'adr_two_region' => 'Hamburg',
            'adr_two_street' => 'Pickhuben 4',
            'adr_two_street2' => 'no second street2',
            'assistent' => 'Cornelius Weiß',
            'bday' => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email' => 'unittests@tine20.org',
            'email_home' => 'unittests@tine20.org',
            'note' => 'Bla Bla Bla',
            'container_id' => $container->id,
            'role' => 'Role',
            'title' => 'Title',
            'url' => 'http://www.tine20.org',
            'url_home' => 'http://www.mundundzähne.de',
            'n_family' => 'Kneschke',
            'n_fileas' => 'Kneschke, Lars',
            'n_given' => 'Laars',
            'n_middle' => 'no middle name',
            'n_prefix' => 'no prefix',
            'n_suffix' => 'no suffix',
            'org_name' => 'Metaways Infosystems GmbH',
            'org_unit' => 'Tine 2.0',
            'tel_assistent' => '+49TELASSISTENT',
            'tel_car' => '+49TELCAR',
            'tel_cell' => '+49TELCELL',
            'tel_cell_private' => '+49TELCELLPRIVATE',
            'tel_fax' => '+49TELFAX',
            'tel_fax_home' => '+49TELFAXHOME',
            'tel_home' => '+49TELHOME',
            'tel_pager' => '+49TELPAGER',
            'tel_work' => '+49TELWORK',
        );

        for($i =0; $i < 100; ++$i) {
            $contact = new Addressbook_Model_Contact(null, true);
            $data = $recordData;
            unset($data['tel_work']);
            $contact->hydrateFromBackend($data);
        }

        $timeEnd = microtime(true);
        $memoryEnd = memory_get_usage();

        echo PHP_EOL . 'time: ' . (($timeEnd - $timeStarted) * 1000) . 'ms, memory: ' . ($memoryEnd - $memory) .
            PHP_EOL;
    }

    public function testAccountEmailUpdate2ContactUpdate2EmailListSieveUpdate()
    {
        if (empty(Tinebase_Config::getInstance()->{Tinebase_Config::IMAP})) {
            self::markTestSkipped('no imap config found');
        }

        $this->_testNeedsTransaction();

        $newUser = $this->_createTestUser();

        $newContact = $this->_instance->get($newUser->contact_id);

        $list = $this->_createMailinglist();
        Addressbook_Controller_List::getInstance()->addListMember($list->getId(), [$newContact->getId()]);

        $newUser->accountEmailAddress = $newUser->accountLoginName . '@' . TestServer::getPrimaryMailDomain();
        $newUser = Admin_Controller_User::getInstance()->update($newUser, 'pwd', 'pwd');

        $updatedContact = $this->_instance->get($newUser->contact_id);
        static::assertSame($newUser->accountLoginName . '@' . TestServer::getPrimaryMailDomain(),
            $newUser->accountEmailAddress);
        static::assertSame($newUser->accountEmailAddress, $updatedContact->email);

        /** TODO add sieve asserts! */
    }

    public function testListMemberFilterEquals()
    {
        $listContainer = $this->_getTestContainer('Addressbook', 'Addressbook_Model_List');
        $list1 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));
        $list2 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));

        $contact1 = $this->_instance->get($this->_personas['sclever']->contact_id);
        $contact2 = $this->_instance->get($this->_personas['pwulf']->contact_id);

        Addressbook_Controller_List::getInstance()->addListMember($list1->getId(), [$contact1->getId()]);
        Addressbook_Controller_List::getInstance()->addListMember($list2->getId(), [$contact2->getId()]);

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'list', 'operator' => 'equals', 'value' => $list1->getId()]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'equals', 'value' => [$list1->getId()]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'equals', 'value' => $list2->getId()]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact2->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'equals', 'value' => [$list1->getId(), $list2->getId()]]
        ]));
        static::assertSame(2, $result->count(), 'search result count mismatch');


        $contact = $this->_instance->create($this->objects['initialContact']);
        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'equals', 'value' => null]
        ]));
        static::assertNotNull($result->find('id', $contact->getId()), 'search did not find unlisted contact');
        static::assertNull($result->find('id', $contact1->getId()), 'search did find listed contact');
        static::assertNull($result->find('id', $contact2->getId()), 'search did find listed contact');


        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'equals', 'value' => [null]]
        ]));
        static::assertSame(0, $result->count(), 'broken search should never find anything');
    }

    public function testListMemberFilterIn()
    {
        $listContainer = $this->_getTestContainer('Addressbook', 'Addressbook_Model_List');
        $list1 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));
        $list2 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));

        $contact1 = $this->_instance->get($this->_personas['sclever']->contact_id);
        $contact2 = $this->_instance->get($this->_personas['pwulf']->contact_id);

        Addressbook_Controller_List::getInstance()->addListMember($list1->getId(), [$contact1->getId()]);
        Addressbook_Controller_List::getInstance()->addListMember($list2->getId(), [$contact2->getId()]);

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => $list1->getId()]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => [$list1->getId()]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => $list2->getId()]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact2->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => [$list1->getId(), $list2->getId()]]
        ]));
        static::assertSame(2, $result->count(), 'search result count mismatch');


        $contact = $this->_instance->create($this->objects['initialContact']);
        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => null]
        ]));
        static::assertNotNull($result->find('id', $contact->getId()), 'search did not find unlisted contact');
        static::assertNull($result->find('id', $contact1->getId()), 'search did find listed contact');
        static::assertNull($result->find('id', $contact2->getId()), 'search did find listed contact');


        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => [null]]
        ]));
        static::assertSame(0, $result->count(), 'broken search should never find anything');
    }

    public function testListMemberFilterAnd()
    {
        $listContainer = $this->_getTestContainer('Addressbook', 'Addressbook_Model_List');
        $list1 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));
        $list2 = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => Tinebase_Record_Abstract::generateUID(),
            'container_id' => $listContainer->getId(),
        ]));

        $contact1 = $this->_instance->get($this->_personas['sclever']->contact_id);
        $contact2 = $this->_instance->get($this->_personas['pwulf']->contact_id);

        Addressbook_Controller_List::getInstance()->addListMember($list1->getId(), [$contact1->getId()]);
        Addressbook_Controller_List::getInstance()->addListMember($list2->getId(), [$contact2->getId()]);

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'AND', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list1->getId()]
            ]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'definedBy', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list1->getId()]
            ]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');
        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'definedBy?condition=and&setOperator=allOf', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list1->getId()]
            ]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact1->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'AND', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list2->getId()]
            ]]
        ]));
        static::assertSame(1, $result->count(), 'search result count mismatch');
        static::assertSame($contact2->getId(), $result->getFirstRecord()->getId(), 'search result id mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'AND', 'value' => [
                ['field' => 'id', 'operator' => 'in', 'value' => [$list1->getId(), $list2->getId()]]
            ]]
        ]));
        static::assertSame(2, $result->count(), 'search result count mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'definedBy?condition=and&setOperator=allOf', 'value' => [
                ['field' => 'id', 'operator' => 'in', 'value' => [$list1->getId(), $list2->getId()]]
            ]]
        ]));
        static::assertSame(0, $result->count(), 'search result count mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'definedBy?condition=or&setOperator=allOf', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list1->getId()],
                ['field' => 'id', 'operator' => 'equals', 'value' => $list2->getId()]
            ]]
        ]));
        static::assertSame(0, $result->count(), 'search result count mismatch');

        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'definedBy?condition=or', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $list1->getId()],
                ['field' => 'id', 'operator' => 'equals', 'value' => $list2->getId()]
            ]]
        ]));
        static::assertSame(2, $result->count(), 'search result count mismatch');

        // create contact without any lists
        $this->_instance->create($this->objects['initialContact']);
        $result = $this->_instance->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
            ['field' => 'list', 'operator' => 'in', 'value' => [
                ['field' => 'id', 'operator' => 'in', 'value' => ['asdfadfa']]
            ]]
        ]));
        static::assertSame(0, $result->count(), 'broken search should never find anything');
    }
}
