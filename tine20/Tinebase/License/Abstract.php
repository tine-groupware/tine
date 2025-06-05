<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

use Composer\Semver\Semver;

/**
 * Tine 2.0 Abstract License class
 *
 * @package     Tinebase
 */
abstract class Tinebase_License_Abstract
{
    /**
     * member vars
     *
     * @var array|null|string
     */
    protected $_license = null;
    protected $_certData = null;
    protected $_status = null;

    /**
     * @var array featureName => since Licence Version (semver)
     */
    protected $_featureNeedsPermission = [
        'CashBook'                                      => '*',
        'ContractManager'                               => '*',
        'DFCom'                                         => '>=2.0',
        'EFile'                                         => '*',
        'GDPR'                                          => '>=2.0',
        'HumanResources.workingTimeAccounting'          => '>=2.0',
        'KeyManager'                                    => '*',
        'MeetingManager'                                => '*',
        'OnlyOfficeIntegrator'                          => '*',
        'Tinebase.featureCreatePreviews'                => '>=2.0',
        'UserManual'                                    => '>=2.0',
    ];

    /**
     * object cache for already checked features
     *
     * @var array
     */
    static protected $_permittedFeatures = [];

    /**
     * set new license file
     *
     * @param string $licenseFile
     */
    public function setLicenseFile($licenseFile)
    {
        $this->reset();

        if (file_exists($licenseFile)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Read license from file: ' . $licenseFile);
            $this->_license = file_get_contents($licenseFile);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' License: ' . $this->_license);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' License file does not exist: ' . $licenseFile);
        }
    }

    /**
     * can we already check the license?
     * - returns TRUE if license might be available
     * - license can be set during initial, but only after Addressbook is installed (see \Addressbook_Setup_Initialize::_setLicense)
     *
     * @return bool
     */
    public static function isLicenseCheckable()
    {
        try {
            $result = Tinebase_Application::getInstance()->isInstalled('Addressbook');
        } catch (Exception) {
            Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' License handling needs Addressbook');
            return false;
        }
        return $result;
    }

    /**
     * check user limit
     *
     * @param Tinebase_Model_User|null $user
     * @return bool
     */
    public function checkUserLimit(Tinebase_Model_User $user = null)
    {
        try {
            $maxUsers = $this->getMaxUsers();
        } catch (Exception $e) {
            // we might have ldap issues (or others), so we catch this and return false
            Tinebase_Exception::log($e);
            return false;
        }

        if ($maxUsers === 0) {
            // 0 means unlimited users
            return true;
        }

        try {
            $currentUserCount = Tinebase_User::getInstance()->countNonSystemUsers();
        } catch (Exception $e) {
            // we might have ldap issues (or others), so we catch this and return false
            Tinebase_Exception::log($e);
            return false;
        }

        if ($currentUserCount > $maxUsers) {
            // check if user is in allowed users
            $user = $user ?: Tinebase_Core::getUser();
            if (! Tinebase_User::getInstance()->hasUserValidLicense($user, $maxUsers)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * @param string $feature
     * @return boolean
     */
    public function isPermitted(string $feature): bool
    {
        if (! self::isLicenseCheckable()) {
            // too early for license check
            return true;
        }

        if (isset(static::$_permittedFeatures[$feature])) {
            // use class cache
            return static::$_permittedFeatures[$feature];
        }

        if (! isset($this->_featureNeedsPermission[$feature])) {
            return true;
        }

        if ($this->getStatus() !== Tinebase_License::STATUS_LICENSE_OK) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                self::class . '::' . __METHOD__ . ' ' . __LINE__
                . ' Feature/application needs valid license: ' . $feature);
            return false;
        }

        if (Semver::satisfies($this->getVersion(), $this->_featureNeedsPermission[$feature])) {
            $hasFeature = $this->hasFeature($feature);
            if (! $hasFeature) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(
                        self::class . '::' . __METHOD__ . ' ' . __LINE__
                        . ' Feature/application not permitted by license: ' . $feature
                    );
                }
            }
            static::$_permittedFeatures[$feature] = $hasFeature;
            return $hasFeature;
        }

        // permit if not covered by featureNeedsPermission
        return true;
    }

    /**
     * get license status
     *
     * @return mixed
     */
    public function getStatus()
    {
        if ($this->_status === null) {
            if (!$this->isLicenseAvailable()) {
                $this->_status = Tinebase_License::STATUS_NO_LICENSE_AVAILABLE;
            } else if (!$this->isValid()) {
                $this->_status = Tinebase_License::STATUS_LICENSE_INVALID;
            } else {
                $this->_status = Tinebase_License::STATUS_LICENSE_OK;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Current license status ' . $this->_status);
        }
        return $this->_status;
    }

    /**
     * @return array with validFrom + validTo datetimes
     */
    public function getDefaultExpiryDate()
    {
        try {
            $userSql = new Tinebase_User_Sql();
            $validFrom = $userSql->getFirstUserCreationTime();
        } catch (Exception $e) {
            // we might have db issues, so we catch this
            Tinebase_Exception::log($e);
            $validFrom = null;
        }

        if (! $validFrom) {
            $validFrom = Tinebase_DateTime::now()->subMonth(1);
        }

        return array(
            'validFrom'    => $validFrom,
            'validTo'      => $validFrom->getClone()->addYear(5),
        );
    }

    public function getLicenseExpireEstimate()
    {
        $this->getCertificateData();

        if ($this->_certData) {
            $remainingDays = $this->_diffDatesToDays(Tinebase_DateTime::now(), $this->_certData['validTo']);
            return $remainingDays;
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

    public function isLicenseAvailable()
    {
        return $this->_getLicense() !== null;
    }

    /**
     * fetch current license string
     *
     * @return string|null
     */
    protected function _getLicense(): ?string
    {
        return $this->_license;
    }

    public function reset()
    {
        $this->_certData = null;
        $this->_license = null;
        $this->_status = null;
        static::$_permittedFeatures = [];
    }

    abstract public function getMaxUsers();
    abstract public function getVersion();
    abstract public function isValid();
    abstract public function hasFeature(string $feature);
    abstract public function getCertificateData();
}
