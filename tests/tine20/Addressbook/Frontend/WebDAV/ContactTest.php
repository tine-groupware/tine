<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Frontend_WebDAV_Contact
 */
class Addressbook_Frontend_WebDAV_ContactTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();

    protected $_transactionId;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        $_SERVER['HTTP_USER_AGENT'] = 'FooBar User Agent';

        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(FALSE);
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => Addressbook_Model_Contact::class,
        )));
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(TRUE);
        
        Tinebase_TransactionManager::getInstance()->rollBack();
    }

    public function testCreateContactWithContactProperties()
    {
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);

        $cpDef = null;
        try {
            $cpDef = Addressbook_Controller_ContactProperties_Definition::getInstance()->create(
                new Addressbook_Model_ContactProperties_Definition([
                    Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'unittest_adr',
                    Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Address::class,
                    Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD,
                    Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'UADR'],
                ])
            );

            Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            /** @var Addressbook_Model_Contact $record */
            $record = $this->testCreateContact('sogo_connector1.vcf')->getRecord();
            $record->preferred_address = $cpDef->{Addressbook_Model_ContactProperties_Definition::FLD_NAME};
            $adr = $record->getPreferredAddressObject();

            $this->assertNotNull($adr);
            $this->assertSame('City U', $adr->{Addressbook_Model_ContactProperties_Address::FLD_LOCALITY});

        } finally {
            Tinebase_TransactionManager::getInstance()->rollBack();

            if ($cpDef) {
                Addressbook_Controller_ContactProperties_Definition::getInstance()->delete($cpDef);
            }

            Tinebase_Container::getInstance()->delete($this->objects['initialContainer']);
        }
    }

    /**
     * test create contact
     *
     * @return Addressbook_Frontend_WebDAV_Contact
     */
    public function testCreateContact($fileName = 'sogo_connector.vcf')
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/' . $fileName, 'r');

        $id = Tinebase_Record_Abstract::generateUID();
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], "$id.vcf", $vcardStream);

        $record = $contact->getRecord();

        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);

        return $contact;
    }

    /**
     * test create contact with photo
     *
     * @return Addressbook_Frontend_WebDAV_Contact
     */
    public function testCreateContactWithPhoto()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/jan.vcf', 'r');

        $id = Tinebase_Record_Abstract::generateUID();
        $contact = Addressbook_Frontend_WebDAV_Contact::create($this->objects['initialContainer'], "$id.vcf", $vcardStream);
        $record = $contact->getRecord();

        $imgBlob = $record->getSmallContactImage();
        $standardSize = strlen($imgBlob);
        $this->assertTrue($standardSize > 0);
        $this->assertTrue($standardSize < Addressbook_Model_Contact::SMALL_PHOTO_SIZE);

        // test custom size
        $imgBlob = $record->getSmallContactImage(Addressbook_Model_Contact::SMALL_PHOTO_SIZE / 8);
        $this->assertTrue(strlen($imgBlob) < $standardSize, 'custom size error');

        return $contact;
    }

    /**
     * test get vcard
     */
    public function testGetContact()
    {
        $contact = $this->testCreateContact();
        
        $backend = new Addressbook_Frontend_WebDAV_Contact($this->objects['initialContainer'], $contact->getName());
        
        $vcard = \Tine20\VObject\Reader::read($backend->get());

        $data = $vcard->serialize();
        $this->assertStringContainsString('TEL;TYPE=WORK:+49 BUSINESS', $data);
        $this->assertContains('CATEGORY 1', $vcard->CATEGORIES->getParts());
        $this->assertContains('CATEGORY 2', $vcard->CATEGORIES->getParts());
    }

    /**
     * test updating existing contact from sogo connector
     * @depends testCreateContact
     */
    public function testPutContactFromThunderbird()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13';
        
        $contact = $this->testCreateContact();
        
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/sogo_connector.vcf', 'r');
        
        $contact->put($vcardStream);
        
        $record = $contact->getRecord();
        
        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }
    
    /**
     * test updating existing contact from MacOS X
     * @depends testCreateContact
     */
    public function testPutContactFromMacOsX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'AddressBook/6.0 (1043) CardDAVPlugin/182 CFNetwork/520.0.13 Mac_OS_X/10.7.1 (11B26)';
        
        $contact = $this->testCreateContact();
        
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/mac_os_x_addressbook.vcf', 'r');
    
        $contact->put($vcardStream);
    
        $record = $contact->getRecord();
    
        $this->assertEquals('l.kneschke@metaways.de', $record->email);
        $this->assertEquals('Kneschke', $record->n_family);
        $this->assertEquals('+49 BUSINESS', $record->tel_work);
    }
    
    /**
     * test updating existing contact from MacOS X
     * @depends testCreateContact
     */
    public function testPutContactFromGenericClient()
    {
        $contact = $this->testCreateContact();
    
        $vcardStream = fopen(dirname(__FILE__) . '/../../Import/files/mac_os_x_addressbook.vcf', 'r');
    
        $this->expectException('Tine20\DAV\Exception\Forbidden');
        
        $contact->put($vcardStream);
    }
    
    /**
     * test get name of vcard
     * @depends testCreateContact
     */
    public function testGetNameOfContact()
    {
        $contact = $this->testCreateContact();
        
        $record = $contact->getRecord();
        
        $this->assertEquals($contact->getName(), $record->getId() . '.vcf');
    }
}
