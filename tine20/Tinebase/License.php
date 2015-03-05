<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Tine 2.0 Business Edition License class
 *
 * @package     Tinebase
 */
class Tinebase_License
{
    /**
     * license type constants
     *
     * 'limitedUser' => limited only by user count (days => 36500 = 100 years)
     * 'limitedTime' => limited only by time       (maxUsersExisting => 0)
     * 'limitedUserTime' => limited by time and users
     * 'onDemand'    => days = 365, maxUsersExisting = 0, separate way of reporting the current users
     */
    const LICENSE_TYPE_LIMITED_USER         = 'limitedUser';
    const LICENSE_TYPE_LIMITED_TIME         = 'limitedTime';
    const LICENSE_TYPE_LIMITED_USER_TIME    = 'limitedUserTime';
    const LICENSE_TYPE_ON_DEMAND            = 'onDemand';

    /**
     * license filename
     */
    const LICENSE_FILENAME = 'license.pem';

    /**
     * license status
     */
    const STATUS_NO_LICENSE_AVAILABLE = 'status_no_license_available';
    const STATUS_LICENSE_INVALID = 'status_license_invalid';
    const STATUS_LICENSE_OK = 'status_license_ok';

    /**
     * member vars
     *
     * @var array|null|string
     */
    protected $_license = null;
    protected $_caFiles = array();
    protected $_certData = null;
    
    public function __construct($licenseFile = null, $caFile = null)
    {
        $this->_license = $this->_readLicenseFromFile($licenseFile);

        $this->_caFiles = $this->_getCaFiles($caFile);
    }

    protected function _readLicenseFromFile($licenseFile = null)
    {
        if ($licenseFile) {
            return file_get_contents($licenseFile);
        } else if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            $fs = Tinebase_FileSystem::getInstance();
            if ($fs->fileExists($this->_getLicensePath())) {
                $licenseFileHandle = $fs->fopen($this->_getLicensePath(), 'r');
                if ($licenseFileHandle !== false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                            __METHOD__ . '::' . __LINE__ . " Fetching current license from vfs: " . $this->_getLicensePath());

                    $result = fread($licenseFileHandle, 8192);
                    $fs->fclose($licenseFile);

                    if (!empty($result)) {
                        return $result;
                    }
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
    const POLICY_DEFAULT_MAX_USERS              = 500;
    const POLICY_DEFAULT_MAX_CONCURRENT_USERS   = 500;
    const POLICY_DEFAULT_LICENSE_TYPE           = self::LICENSE_TYPE_LIMITED_USER_TIME;

    /**
     * get ca file(s)
     *
     * @param null $caFile
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getCaFiles($caFile = null)
    {
        $caFiles = $caFile ? array($caFile) : array(
            dirname(__FILE__) . '/License/cacert.pem',
            dirname(__FILE__) . '/License/cacert20150305.pem',
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
    
    public function getLicenseExpireEstimate()
    {
        if ($this->getStatus() !== self::STATUS_NO_LICENSE_AVAILABLE){
            return false;
        }
        
        $this->getCertificateData();
        
        if ($this->_certData) {
            return $this->_diffDatesToDays(Tinebase_DateTime::now(), $this->_certData['validTo']);
        }
        
        return false;
    }
    
    protected function _diffDatesToDays($date1, $date2)
    {
        if ($date1 instanceof Tinebase_DateTime && $date2 instanceof Tinebase_DateTime) {
            $diff = date_diff($date1, $date2);

            if ($diff->days > 0 && $diff->invert == 0) {
                return $diff->days;
            }
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
        $licensePath = $this->_getLicensePath();
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
    
    protected function _getLicensePath($filename = self::LICENSE_FILENAME)
    {
        $fs = Tinebase_FileSystem::getInstance();
        $appPath = $fs->getApplicationBasePath('Tinebase');
        if (!$fs->fileExists($appPath)) {
            $fs->mkdir($appPath);
        }
        
        return $appPath . '/' . $filename;
    }

    public function deleteCurrentLicense()
    {
        $fs = Tinebase_FileSystem::getInstance();
        $licensePath = $this->_getLicensePath();
        if ($fs->fileExists($licensePath)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Deleting license at " . $licensePath);
            $fs->unlink($licensePath);
        }
    }
    
    /**
     * @return boolean
     */
    public function isValid()
    {
        $isValid = $this->_license ? openssl_x509_checkpurpose($this->_license, X509_PURPOSE_SSL_CLIENT, $this->_caFiles) : false;
        
        return $isValid;
    }
    
    public function isLicenseAvailable()
    {
        return $this->_license !== null;
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
            
            $validFrom = Tinebase_User::getInstance()->getFirstUserCreationTime();
            if ($validFrom) {
                $this->_certData = array(
                    'validFrom'    => $validFrom,
                    'validTo'      => $validFrom->getClone()->addDay(20),
                );
            }
            
            if ($this->_license !== null) {
                $data = openssl_x509_parse($this->_license);
                if (is_array($data) && array_key_exists('validFrom_time_t', $data)
                    && array_key_exists('validTo_time_t', $data)
                    && array_key_exists('serialNumber', $data)
                ) {
                    $validFrom = new Tinebase_DateTime('@'. $data['validFrom_time_t']);
                    $validTo = new Tinebase_DateTime('@'. $data['validTo_time_t']);
                    $serialNumber = $data['serialNumber'];
                    $policies = $this->_parsePolicies($data['extensions']['certificatePolicies']);
                    $this->_certData = array(
                        'validFrom'    => $validFrom,
                        'validTo'      => $validTo,
                        'serialNumber' => $serialNumber,
                        'policies'     => $policies,
                        'contractId'   => isset($data['subject']) && isset($data['subject']['CN']) ? $data['subject']['CN'] : '',
                    );
                } else {
                    $this->_license = null;
                }
            } 
        }
        
        return $this->_certData;
    }

    /**
     * Parses the private key inside the certificate and returns data
     *
     * @return array
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
        return $this->_getPolicy(self::POLICY_LICENSE_TYPE, self::POLICY_DEFAULT_LICENSE_TYPE);
    }

    /**
     * fetch policy value from certificate data
     *
     * @param      $policyIndex number
     * @param null $default
     * @return number|string|null
     */
    protected function _getPolicy($policyIndex, $default = null)
    {
        if ($this->_license) {
            $certData = $this->getCertificateData();
            if (isset($certData['policies'][$policyIndex][1])) {
                return $certData['policies'][$policyIndex][1];
            }
        }
        return $default;
    }

    /**
     * check user limit
     *
     * @param $user
     * @return bool
     */
    public function checkUserLimit($user = null)
    {
        $maxUsers = $this->getMaxUsers();

        if ($maxUsers === 0) {
            // 0 means unlimited users
            return true;
        }

        $currentUserCount = Tinebase_User::getInstance()->countNonSystemUsers();
        if ($currentUserCount >= $maxUsers) {
            // check if user is in allowed users
            $user = $user ? $user : Tinebase_Core::getUser();
            if (! Tinebase_User::getInstance()->hasUserValidLicense($user, $maxUsers)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function getStatus()
    {
        if (! $this->isLicenseAvailable()) {
            return self::STATUS_NO_LICENSE_AVAILABLE;
        } else if (! $this->isValid()) {
            return self::STATUS_LICENSE_INVALID;
        } else {
            return self::STATUS_LICENSE_OK;
        }
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
            if (! $idx) continue;
            
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
