<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_License_Secudos
 * 
 * @package     Tinebase
 */
class Tinebase_License_SecudosTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_License_Secudos
     */
    protected $_uit = null;

    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();

        Tinebase_Config::getInstance()->set(Tinebase_Config::LICENSE_TYPE, 'Secudos');

        Tinebase_License::resetLicense();
        $this->_uit = Tinebase_License::getInstance();
        $this->_uit->setLicenseFile(dirname(__FILE__) . '/lic.crt');
    }

    /**
     * reset license after test suite
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        Tinebase_Config::getInstance()->set(Tinebase_Config::LICENSE_TYPE, 'BusinessEdition');
        Tinebase_License::resetLicense();
    }

    public function testGetCertificateData()
    {
        $data = $this->_uit->getCertificateData();
        $this->assertTrue(isset($data['subject']['OU'][1]));
        $this->assertEquals($data['subject']['OU'][1], 'TINE20');
        $this->assertEquals($data['serialNumber'], 1065);
    }
    
    public function testIsValid()
    {
        $this->assertTrue($this->_uit->isValid());
    }

    public function testLicenseType()
    {
        $this->assertEquals(Tinebase_License::LICENSE_TYPE_LIMITED_USER, $this->_uit->getLicenseType());
    }

    public function testCreateUserWithLimitExceeded()
    {
        $this->_createUsersToLimit();
        try {
            Admin_Controller_User::getInstance()->create($this->_getUser(), 'test', 'test');
            $this->fail('user creation should fail for the 26th user');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_SystemGeneric);
        }
    }

    /**
     * TODO move to generic TestCase
     *
     * @param $limit
     * @return Tinebase_Model_FullUser
     */
    protected function _createUsersToLimit($limit = 25)
    {
        $userCount = Tinebase_User::getInstance()->countNonSystemUsers();
        $newUser = null;
        while ($userCount < $limit) {
            $newUser = Admin_Controller_User::getInstance()->create($this->_getUser(), 'test', 'test');
            $this->_usernamesToDelete[] = $newUser->accountLoginName;
            $userCount++;
            if ($userCount == ($limit - 1)) {
                // sleep to make sure the last user has a different creation time
                sleep(1);
            }
        }

        return $newUser;
    }

    /**
     * TODO move to generic TestCase
     *
     * @return Tinebase_Model_FullUser
     */
    protected function _getUser()
    {
        return new Tinebase_Model_FullUser(array(
            'accountLoginName' => Tinebase_Record_Abstract::generateUID(),
            'accountPrimaryGroup' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountDisplayName' => Tinebase_Record_Abstract::generateUID(),
            'accountLastName' => Tinebase_Record_Abstract::generateUID(),
            'accountFullName' => Tinebase_Record_Abstract::generateUID(),
        ));
    }

    public function testUserLimitExceeded()
    {
        Tinebase_License::resetLicense();
        try {
            $lastUser = $this->_createUsersToLimit(26);
            $this->fail('invalid license not detected');
        } catch (Tinebase_Exception $te) {
            // switch to BE license
            Tinebase_Config::getInstance()->set(Tinebase_Config::LICENSE_TYPE, 'BusinessEdition');
            Tinebase_License::resetLicense();
            $lastUser = $this->_createUsersToLimit(26);
        }

        Tinebase_Config::getInstance()->set(Tinebase_Config::LICENSE_TYPE, 'Secudos');
        $this->_uit->setLicenseFile(dirname(__FILE__) . '/lic.crt');
        $this->assertFalse($this->_uit->checkUserLimit($lastUser), 'user limit should be reached');
    }

    public function testLicenseStatusInRegistry()
    {
        $tfj = new Tinebase_Frontend_Json();
        $registry = $tfj->getRegistryData();
        $this->assertEquals(Tinebase_License::STATUS_LICENSE_OK, $registry['licenseStatus']);
    }

    /**
     * test default/hardware appliance type
     *
     * @see #139806: [Hardware/Cloud] Secudos appliance image
     */
    public function testSecudosApplianceType()
    {
        $this->assertEquals(Tinebase_License_Secudos::APPLIANCE_TYPE_HARDWARE, $this->_uit->getApplianceType());
    }

    /**
     * test cloud image appliance type
     *
     * @see #139806: [Hardware/Cloud] Secudos appliance image
     */
    public function testSecudosApplianceTypeCloudImage()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::LICENSE_TYPE, 'SecudosMock');
        Tinebase_License::resetLicense();
        $this->_uit = Tinebase_License::getInstance();
        $this->_uit->setLicenseFile(dirname(__FILE__) . '/lic.crt');

        $this->assertEquals(Tinebase_License_Secudos::APPLIANCE_TYPE_CLOUD_IMAGE, $this->_uit->getApplianceType());
    }

    /**
     * test cloud image appliance type expiry dates (since & estimate)
     *
     * @see #139806: [Hardware/Cloud] Secudos appliance image
     */
    public function testSecudosApplianceTypeCloudImageExpiry()
    {
        $this->testSecudosApplianceTypeCloudImage();

        $this->assertFalse($this->_uit->getLicenseExpiredSince());
        $expireEstimate = $this->_uit->getLicenseExpireEstimate();
        $this->assertGreaterThan(0, $expireEstimate);
        $expiryDate = new Tinebase_DateTime('2016-12-30 00:00:00');
        $now = Tinebase_DateTime::now()->setTime(0,0,0);

        // we might have a difference of 1 day depending on the time of day (license expires at 12:42:02
        $this->assertTrue($now->addDay($expireEstimate)->equals($expiryDate) || $now->addDay(1)->equals($expiryDate),
            'expiry date mismatch: ' . $now . '!=' . $expiryDate);
    }
}
