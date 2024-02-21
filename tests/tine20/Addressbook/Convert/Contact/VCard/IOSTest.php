<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Convert_Contact_VCard_IOS
 */
class Addressbook_Convert_Contact_VCard_IOSTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 addressbook CardDAV iOS contact tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
    }
    
    /**
     * test converting vcard from sogo connector to Addressbook_Model_Contact 
     * 
     * @return Addressbook_Model_Contact
     */
    public function testConvertToTine20Model()
    {
        $vcardStream = fopen(dirname(__FILE__) . '/../../../Import/files/ios_5_addressbook.vcf', 'r');
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_IOS);
        
        $contact = $converter->toTine20Model($vcardStream);
        
        $this->assertEquals('COUNTRY BUSINESS',        $contact->adr_one_countryname);
        $this->assertEquals('Hamburg',                 $contact->adr_one_locality);
        $this->assertEquals('20457',                   $contact->adr_one_postalcode);
        $this->assertEquals(null,                      $contact->adr_one_region);
        $this->assertEquals('Pickhuben 2',             $contact->adr_one_street);
        $this->assertEquals(null,                      $contact->adr_one_street2);
        $this->assertEquals('COUNTRY PRIVAT',          $contact->adr_two_countryname);
        $this->assertEquals('City Privat',             $contact->adr_two_locality);
        $this->assertEquals('12345',                   $contact->adr_two_postalcode);
        $this->assertEquals(null,                      $contact->adr_two_region);
        $this->assertEquals('Address Privat 1',        $contact->adr_two_street);
        $this->assertEquals(null,                      $contact->adr_two_street2);
        $this->assertEquals('l.kneschke@metaways.de',  $contact->email, 'email wrong');
        $this->assertEquals('lars@kneschke.de',        $contact->email_home, 'email_home wrong');
        $this->assertEquals('Kneschke',                $contact->n_family);
        $this->assertEquals('Kneschke, Lars',          $contact->n_fileas);
        $this->assertEquals('Lars',                    $contact->n_given);
        $this->assertEquals('Paul',                    $contact->n_middle);
        $this->assertEquals('Prefix',                  $contact->n_prefix);
        $this->assertEquals('Suffix',                  $contact->n_suffix);
        $this->assertEquals("Notes\nwith\nLine Break", $contact->note);
        $this->assertEquals('Organisation',            $contact->org_name);
        $this->assertEquals('Department',              $contact->org_unit);
        $this->assertEquals('+49 MOBIL',               $contact->tel_cell);
        $this->assertEquals('Tel Iphone',              $contact->tel_cell_private);
        $this->assertEquals('+49 FAX',                 $contact->tel_fax);
        $this->assertEquals('+49 FAX PRIVAT',          $contact->tel_fax_home);
        $this->assertEquals('+49 PRIVAT',              $contact->tel_home);
        $this->assertEquals('+49 PAGER',               $contact->tel_pager);
        $this->assertEquals('+49 BUSINESS',            $contact->tel_work);
        $this->assertEquals('Team Leader',             $contact->title);
        $this->assertEquals('www.work.de',             $contact->url);
        $this->assertEquals('www.private.de',          $contact->url_home);
        
        return $contact;
    }

    public function testConvertToVCard()
    {
        $contact = $this->testConvertToTine20Model();
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory(Addressbook_Convert_Contact_VCard_Factory::CLIENT_IOS);
        
        $vcard = $converter->fromTine20Model($contact)->serialize();
        
        $this->assertStringContainsString('VERSION:3.0', $vcard, $vcard);
        
        // @todo can not test for folded lines
        $this->assertStringContainsString('ADR;TYPE=WORK:;;Pickhuben 2;Hamburg;;20457;C', $vcard, $vcard);
        $this->assertStringContainsString('ADR;TYPE=HOME:;;Address Privat 1;City Privat;;12345;C', $vcard, $vcard);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET,HOME:lars@kneschke.de', $vcard, $vcard);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET,WORK,PREF:l.kneschke@metaways.de', $vcard, $vcard);
        $this->assertStringContainsString('N:Kneschke;Lars', $vcard, $vcard);
        $this->assertStringContainsString('NOTE:Notes\nwith\nLine Break', $vcard, $vcard);
        $this->assertStringContainsString('ORG:Organisation;Department', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=CELL,VOICE,PREF:+49 MOBIL', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=CELL,IPHONE:Tel Iphone', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=FAX,HOME:+49 FAX PRIVAT', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=FAX,WORK:+49 FAX', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=HOME,VOICE:+49 PRIVAT', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=PAGER:+49 PAGER', $vcard, $vcard);
        $this->assertStringContainsString('TEL;TYPE=WORK,VOICE:+49 BUSINESS', $vcard, $vcard);
        $this->assertStringContainsString('TITLE:Team Leader', $vcard, $vcard);
    }
}
