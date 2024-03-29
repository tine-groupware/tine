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
 * Test class for Addressbook_Convert_Contact_VCard_Factory
 */
class Addressbook_Convert_Contact_VCard_FactoryTest extends \PHPUnit\Framework\TestCase
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
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook WebDAV Factory Contact Tests');
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
     * test factory with useragent string from MacOS X
     */
    public function testUserAgentMacOSX()
    {
        $agents = array(
                array('version' => '10.7.1', 'identifier' => 'AddressBook/6.0 (1043) CardDAVPlugin/182 CFNetwork/520.0.13 Mac_OS_X/10.7.1 (11B26)'),
                array('version' => '10.9',   'identifier' => 'Mac OS X/10.9 (13A603) AddressBook/1365'),
                array('version' => '11.0',   'identifier' => 'macOS/11.0 (20A5343i) AddressBookCore/1'),
        );
    
        foreach($agents as $agent) {
            list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent($agent["identifier"]);
    
            $this->assertEquals(Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX, $backend, $agent["identifier"]);
            $this->assertEquals($agent["version"], $version, $agent["identifier"]);
        }
    }
    
    /**
     * test factory with useragent string from thunderbird 
     */
    public function testUserAgentThunderbird()
    {
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent('Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13');
        
        $this->assertEquals(Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO, $backend);
        $this->assertEquals('3.1.13', $version);
    }
    
    /**
     * test factory with useragent string from icedove 
     */
    public function testUserAgentIcedove()
    {
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent('Mozilla/5.0 (X11; Linux i686; rv:17.0) Gecko/20130529 Icedove/17.0.5');
        
        $this->assertEquals(Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO, $backend);
        $this->assertEquals('17.0.5', $version);
    }
    
    /**
     * test factory with useragent string from kde 
     */
    public function testUserAgentKDE()
    {
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent('Mozilla/5.0 (X11; Linux i686) KHTML/4.7.3 (like Gecko) Konqueror/4.7');
        
        $this->assertEquals(Addressbook_Convert_Contact_VCard_Factory::CLIENT_KDE, $backend);
        $this->assertEquals('4.7', $version);
        
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent('KDE DAV groupware client');
        
        $this->assertEquals(Addressbook_Convert_Contact_VCard_Factory::CLIENT_KDE, $backend);
        $this->assertEquals(null, $version);
    }
}
