<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Tine 2.0 Business Edition License class
 *
 * @package     Tinebase
 */
class Tinebase_License_BusinessEdition extends Tinebase_License_Abstract implements Tinebase_License_Interface
{
    /**
     * license filename
     */
    const LICENSE_FILENAME = 'license.pem';

    /**
     * ca files
     *
     * @var array
     */
    protected $_caFiles = array();

    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_license = $this->_readLicenseFromVFS();
        $this->_caFiles = $this->_getCaFiles();
    }

    /**
     * @return array
     */
    public function getCaFiles()
    {
        return $this->_caFiles;
    }


    /**
     * reads current license from vfs
     *
     * @return null|string
     */
    protected function _readLicenseFromVFS()
    {
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            return null;
        }
        try {
            $fs = Tinebase_FileSystem::getInstance();
        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
            return null;
        }
        if ($fs->fileExists($this->getLicensePath())) {
            $licenseFileHandle = $fs->fopen($this->getLicensePath(), 'r');
            if ($licenseFileHandle !== false) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Fetching current license from vfs: " . $this->getLicensePath());

                $result = fread($licenseFileHandle, 8192);
                $fs->fclose($licenseFileHandle);

                if (!empty($result)) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * policies
     */
    const POLICY_MAX_USERS                      = 101;
    const POLICY_MAX_CONCURRENT_USERS           = 102;
    const POLICY_LICENSE_TYPE                   = 103;
    const POLICY_LICENSE_VERSION                = 104;
    const POLICY_LICENSE_FEATURES               = 105;
    const POLICY_DEFAULT_MAX_USERS              = 500;
    const POLICY_DEFAULT_MAX_CONCURRENT_USERS   = 500;
    const POLICY_DEFAULT_LICENSE_TYPE           = Tinebase_License::LICENSE_TYPE_LIMITED_USER_TIME;

    /**
     * get ca file(s)
     *
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getCaFiles()
    {
        $caFiles = array(
            __DIR__ . '/cacert.pem',
            __DIR__ . '/cacert20150305.pem',
        );

        foreach ($caFiles as $index => $file) {
            if (! file_exists($file)) {
                unset($caFiles[$index]);
            }
        }

        if (empty($caFiles)) {
            throw new Tinebase_Exception_NotFound('No valid CA file found');
        }

        return $caFiles;
    }

    /**
     * returns number of days the license is expired or false if it's still valid or we have no expiry date
     * 
     * @return number|boolean
     */
    public function getLicenseExpiredSince()
    {
        $this->getCertificateData();
        
        if ($this->_certData) {
            return $this->_diffDatesToDays($this->_certData['validTo'], Tinebase_DateTime::now());
        }
        return false;
    }

    /**
     * stores license in vfs
     *
     * @param string $licenseString
     * @throws Tinebase_Exception
    */
    public function storeLicense($licenseString)
    {
        $fs = Tinebase_FileSystem::getInstance();
        $licensePath = $this->getLicensePath();
        if (empty($licenseString)) {
            throw new Tinebase_Exception('Empty license string');
        } else {
            $licenseFile = $fs->fopen($licensePath, 'w');
            if ($licenseFile !== false) {
                fwrite($licenseFile, $licenseString);
                $fs->fclose($licenseFile);
                $this->_license = $licenseString;
                $this->_certData = null;
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Stored new license " . $licensePath);
            } else {
                throw new Tinebase_Exception('Could not store file');
            }
        }
    }

    /**
     * @param string $filename
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getLicensePath($filename = self::LICENSE_FILENAME)
    {
        $fs = Tinebase_FileSystem::getInstance();
        $appPath = $fs->getApplicationBasePath('Tinebase');
        if (!$fs->fileExists($appPath)) {
            $this->_assertValidUser();
            $fs->mkdir($appPath);
        }
        
        return $appPath . '/' . $filename;
    }

    /**
     * asserts valid user for filesystem modlog
     *
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _assertValidUser()
    {
        if (! is_object(Tinebase_Core::getUser())) {
            $user = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
            if ($user) {
                Tinebase_Core::set(Tinebase_Core::USER, $user);
            } else {
                throw new Tinebase_Exception_NotFound('could not find valid user');
            }
        }
    }

    public function deleteCurrentLicense()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $licensePath = $this->getLicensePath();
        if ($fs->fileExists($licensePath)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Deleting license at " . $licensePath);
            $this->_assertValidUser();
            $fs->unlink($licensePath);
        }

        $this->_certData = null;
        $this->_license = null;
        $this->_permittedFeatures = [];
    }
    
    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->_license
            ? openssl_x509_checkpurpose($this->_license, X509_PURPOSE_SSL_CLIENT, $this->_caFiles)
            : false;
    }

    /**
     * get version of license
     *
     * @return string|null semver
     */
    public function getVersion()
    {
        return $this->_getPolicy(Tinebase_License_BusinessEdition::POLICY_LICENSE_VERSION, '1.0.0');
    }

    /**
     * return true if license has the feature
     *
     * @param $feature
     * @return boolean
     */
    public function hasFeature($feature)
    {
        $features = $this->getFeatures();
        return in_array($feature, $features);
    }

    /**
     * fetch certificate data
     * 
     * @return array
     */
    public function getCertificateData()
    {
        if ($this->_certData === null) {
            $this->_certData = array();
            if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
                // no information without Tinebase / DB
                return $this->_certData;
            }

            $this->_certData = $this->getDefaultExpiryDate();

            if ($this->_license !== null) {
                $certData = $this->getCertDatafromLicenseString($this->_license);
                if ($certData) {
                    $this->_certData = $certData;
                } else {
                    $this->_license = null;
                }
            }
        }
        
        return $this->_certData;
    }

    /**
     * @param string $license
     * @return array|null
     */
    public function getCertDatafromLicenseString($license)
    {
        $data = openssl_x509_parse($license);
        if (is_array($data) && array_key_exists('validFrom_time_t', $data)
            && array_key_exists('validTo_time_t', $data)
            && array_key_exists('serialNumber', $data)
        ) {
            $validFrom = new Tinebase_DateTime('@' . $data['validFrom_time_t']);
            if ($data['validTo_time_t'] > 0) {
                $validTo = new Tinebase_DateTime('@' . $data['validTo_time_t']);
            } else if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})/', $data['validTo'], $matches)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " Got broken validTo_time_t, using validTo ..." . print_r($matches, true));
                $validTo = new Tinebase_DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            } else {
                throw Tinebase_Exception('Invalid License ValidTo');
            }
            $serialNumber = $data['serialNumber'];
            $policies = $this->_parsePolicies($data['extensions']['certificatePolicies']);
            return array(
                'validFrom' => $validFrom,
                'validTo' => $validTo,
                'serialNumber' => $serialNumber,
                'policies' => $policies,
                'contractId' => isset($data['subject']) && isset($data['subject']['CN']) ? $data['subject']['CN'] : '',
            );
        } else {
            return null;
        }
    }

    /**
     * Parses the private key inside the certificate and returns data
     *
     * @return array|boolean
     */
    public function getInstallationData() {
        if ($this->_license) {
            return openssl_pkey_get_details(openssl_pkey_get_private($this->_license));
        }

        return false;
    }
    
    /**
     * @return number
     */
    public function getMaxUsers()
    {
        return $this->_getPolicy(self::POLICY_MAX_USERS, self::POLICY_DEFAULT_MAX_USERS);
    }

    /**
     * get license type
     *
     * @return string
     */
    public function getLicenseType()
    {
        $type = $this->_getPolicy(self::POLICY_LICENSE_TYPE, self::POLICY_DEFAULT_LICENSE_TYPE);

        // care for alternative type names
        if ($type === 'ON_DEMAND') {
            $type = Tinebase_License::LICENSE_TYPE_ON_DEMAND;
        }
        return $type;
    }

    /**
     * fetch policy value from certificate data
     *
     * @param int $policyIndex number
     * @param null $default
     * @param boolean $_getAll fetch all policy values as array (index 0 is always the policy description)
     * @return number|string|null|array
     */
    protected function _getPolicy($policyIndex, $default = null, $_getAll = false)
    {
        if ($this->_license) {
            $certData = $this->getCertificateData();
            if ($_getAll && isset($certData['policies'][$policyIndex])) {
                return $certData['policies'][$policyIndex];
            } else if (isset($certData['policies'][$policyIndex][1])) {
                return $certData['policies'][$policyIndex][1];
            }
        }
        return $default;
    }

    /**
     * @return array
     */
    public function getFeatures()
    {
        $features = $this->_getPolicy(Tinebase_License_BusinessEdition::POLICY_LICENSE_FEATURES, null, true);
        if (is_array($features)) {
            array_shift($features);
        } else {
            $features = [];
        }
        return $features;
    }

    /**
     * parse structured policies from policy string
     *
     * @param  string $policiesString
     * @return array
     */
    protected function _parsePolicies($policiesString)
    {
        $policies = array();
        $oidPrefix = '1.5.6.79.';
        $rawPolicies = explode('Policy: ' . $oidPrefix, $policiesString);

        foreach ($rawPolicies as $idx => $rawPolicy) {
            if (! $idx) {
                continue;
            }

            $lines = explode("\n", $rawPolicy);
            
            $id = array_shift($lines);
            $data = array();
            
            foreach($lines as $line) {
                if (preg_match('/^\s+CPS:\s+(.*)$/', $line, $matches)) {
                    $data[] = $matches[1];
                }
            }
            $policies[$id]= $data;
        }
        
        return $policies;
    }
}
