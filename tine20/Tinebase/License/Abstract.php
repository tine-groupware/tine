<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

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

    /**
     * set new license file
     *
     * @param string $licenseFile
     */
    public function setLicenseFile($licenseFile)
    {
        if (file_exists($licenseFile)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Read license from file: ' . $licenseFile);
            $this->_license = file_get_contents($licenseFile);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' License: ' . $this->_license);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' License file does not exist: ' . $licenseFile);
            $this->_license = null;
        }
        $this->_certData = null;
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

    /**
     * get license status
     *
     * @return mixed
     */
    public function getStatus()
    {
        if (! $this->isLicenseAvailable()) {
            $result = Tinebase_License::STATUS_NO_LICENSE_AVAILABLE;
        } else if (! $this->isValid()) {
            $result = Tinebase_License::STATUS_LICENSE_INVALID;
        } else {
            $result = Tinebase_License::STATUS_LICENSE_OK;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Current license status ' . $result);

        return $result;
    }

    public function getDefaultExpiryDate()
    {
        $validFrom = Tinebase_User::getInstance()->getFirstUserCreationTime();
        if (! $validFrom) {
            $validFrom = Tinebase_DateTime::now()->subMonth(1);
        }

        $result = array(
            'validFrom'    => $validFrom,
            'validTo'      => $validFrom->getClone()->addDay(30),
        );

        return $result;
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
        return $this->_license !== null;
    }
}
