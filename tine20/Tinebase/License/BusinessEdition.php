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
    public const LICENSE_FILENAME = 'license.pem';

    protected const LICENSE_CACHE_ID = 'license';

    /**
     * ca files
     *
     * @var ?array
     */
    protected $_caFiles = null;

    /**
     * @return array
     */

    /**
     * @return array|string[]
     * @throws Tinebase_Exception_NotFound
     */
    public function getCaFiles(): array
    {
        if ($this->_caFiles === null) {
            $this->_caFiles = $this->_getCaFiles();
        }
        return $this->_caFiles;
    }

    /**
     * fetch current license string
     *
     * @return string|null
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getLicense(): ?string
    {
        if ($this->_license) {
            return $this->_license;
        }

        if (! Setup_Controller::getInstance()->isInstalled()) {
            return null;
        }

        $cache = Tinebase_Core::getCache();
        if ($cache) {
            if ($cache->test(self::LICENSE_CACHE_ID)) {
                return $cache->load(self::LICENSE_CACHE_ID);
            }
        }
        $license = $this->_readLicenseFromVFS();
        if ($cache) {
            try {
                $cache->save($license, self::LICENSE_CACHE_ID);
            } catch (Zend_Cache_Exception $zce) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $zce->getMessage());
            }
        }
        $this->_license = $license;
        return $license;
    }

    /**
     * reads current license from vfs
     *
     * @return string|null
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _readLicenseFromVFS(): ?string
    {
        try {
            $fs = Tinebase_FileSystem::getInstance();
        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
            return null;
        }
        try {
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
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }

        return null;
    }

    /**
     * policies
     */
    public const POLICY_MAX_USERS                      = 101;
    public const POLICY_MAX_CONCURRENT_USERS           = 102;
    public const POLICY_LICENSE_TYPE                   = 103;
    public const POLICY_LICENSE_VERSION                = 104;
    public const POLICY_LICENSE_FEATURES               = 105;
    public const POLICY_DEFAULT_MAX_USERS              = 500;
    public const POLICY_DEFAULT_MAX_CONCURRENT_USERS   = 500;
    public const POLICY_DEFAULT_LICENSE_TYPE           = Tinebase_License::LICENSE_TYPE_LIMITED_USER_TIME;

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
            __DIR__ . '/cacert20240117.pem',
            __DIR__ . '/cacert20240311.pem',
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
    public function storeLicense(string $licenseString)
    {
        $fs = Tinebase_FileSystem::getInstance();
        $licensePath = $this->getLicensePath();
        if (empty($licenseString)) {
            throw new Tinebase_Exception('Empty license string');
        } else {
            $licenseFile = $fs->fopen($licensePath, 'w');
            if ($licenseFile !== false) {
                $this->reset();
                fwrite($licenseFile, $licenseString);
                $fs->fclose($licenseFile);
                $this->_license = $licenseString;

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . " Stored new license " . $licensePath);
                }
            } else {
                throw new Tinebase_Exception('Could not store file');
            }
        }
    }

    public function reset()
    {
        parent::reset();
        $cache = Tinebase_Core::getCache();
        if ($cache) {
            $cache->remove(self::LICENSE_CACHE_ID);
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
        $this->reset();
        Setup_Controller::getInstance()->clearCache(false);
    }
    
    /**
     * @return bool
     */
    public function isValid(): bool
    {
        try {
            $license = $this->_getLicense();
            return $license
                ? openssl_x509_checkpurpose($license, X509_PURPOSE_SSL_CLIENT, $this->_getCaFiles())
                : false;
        } catch (Tinebase_Exception_InvalidArgument|Tinebase_Exception_NotFound $te) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $te->getMessage());
        }

        return false;
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
     * @param string $feature
     * @return boolean
     */
    public function hasFeature(string $feature)
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

            if ($this->_getLicense() !== null) {
                $certData = $this->getCertDatafromLicenseString();
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
     * @return array|null
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getCertDatafromLicenseString(): ?array
    {
        $data = openssl_x509_parse($this->_getLicense());
        if (is_array($data) && array_key_exists('validFrom_time_t', $data)
            && array_key_exists('validTo_time_t', $data)
            && array_key_exists('serialNumber', $data)
        ) {
            $validFrom = new Tinebase_DateTime('@' . $data['validFrom_time_t']);
            if ($data['validTo_time_t'] > 0) {
                $validTo = new Tinebase_DateTime('@' . $data['validTo_time_t']);
            } else if (preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})/', (string) $data['validTo'], $matches)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " Got broken validTo_time_t, using validTo ..." . print_r($matches, true));
                $validTo = new Tinebase_DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            } else {
                throw Tinebase_Exception('Invalid License ValidTo');
            }
            $serialNumber = $data['serialNumber'];
            $policies = $this->_parsePolicies($data['extensions']['certificatePolicies']);
            $organization = $data['subject']['O'] ?? '';
            $numberOfMaxUsers = $policies[Tinebase_License_BusinessEdition::POLICY_MAX_USERS][1] ?? 0;
            $features = $policies[Tinebase_License_BusinessEdition::POLICY_LICENSE_FEATURES] ?? [];
            array_shift($features);
            
            return array(
                'validFrom' => $validFrom,
                'validTo' => $validTo,
                'serialNumber' => $serialNumber,
                'policies' => $policies,
                'contractId' => isset($data['subject']) && isset($data['subject']['CN']) ? $data['subject']['CN'] : '',
                'organization' => $organization,
                'maxUsers' => $numberOfMaxUsers,
                'features' => $features
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
        if ($this->_getLicense()) {
            return openssl_pkey_get_details(openssl_pkey_get_private($this->_getLicense()));
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
        if ($this->_getLicense()) {
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
