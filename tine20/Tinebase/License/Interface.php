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
 * Tine 2.0 License Interface
 *
 * @package     Tinebase
 */
interface Tinebase_License_Interface
{
    public function setLicenseFile($licenseFile);
    public function getLicenseExpiredSince();
    public function getLicenseExpireEstimate();
    public function isValid();
    public function isLicenseAvailable();
    public function getMaxUsers();
    public function getLicenseType();
    public function checkUserLimit(?\Tinebase_Model_User $user = null);

    /**
     * checks if feature is permitted by license
     *
     * @param String $feature   e.g. Calendar or Calendar.someFeature
     * @return boolean
     */
    public function isPermitted(string $feature): bool;

    /**
     * get version of license
     *
     * @return string semver
     */
    public function getVersion();

    /**
     * return true if license has the feature
     *
     * @param string $feature
     * @return boolean
     */
    public function hasFeature(string $feature);

    /**
     * @return array|null
     */
    public function getFeatures();

    public function getStatus();
    public function getCaFiles();
    public function getLicensePath();

    /**
     * stores license in vfs
     *
     * @param string $licenseString
     */
    public function storeLicense(string $licenseString);

    /**
     * fetch certificate data
     *
     * @return array
     */
    public function getCertificateData();

    public function deleteCurrentLicense();
}
