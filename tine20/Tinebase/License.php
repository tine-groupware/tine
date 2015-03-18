<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 */

/**
 * Tine 2.0 License class
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
     * license status
     */
    const STATUS_NO_LICENSE_AVAILABLE = 'status_no_license_available';
    const STATUS_LICENSE_INVALID = 'status_license_invalid';
    const STATUS_LICENSE_OK = 'status_license_ok';

    /**
     * @var Tinebase_License_Interface
     */
    protected static $_license = null;

    /**
     * gets the license instance
     *
     * @return Tinebase_License_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public static function getInstance()
    {
        if (! self::$_license) {
            $licenseClass = 'Tinebase_License_' . Tinebase_Config::getInstance()->get(Tinebase_Config::LICENSE_TYPE);
            if (! class_exists($licenseClass)) {
                throw new Tinebase_Exception_NotFound('License class not found');
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' License class used: ' . $licenseClass);

            self::$_license = new $licenseClass();
        }

        return self::$_license;
    }

    /**
     * resets license, allows to recreate the license instance via getInstance
     */
    public static function resetLicense()
    {
        self::$_license = null;
    }
}
