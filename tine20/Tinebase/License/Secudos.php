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
 * Tine 2.0 Secudos License class
 *
 * @package     Tinebase
 */
class Tinebase_License_Secudos extends Tinebase_License_Abstract implements Tinebase_License_Interface
{
    /**
     * license filename
     */
    const LICENSE_FILENAME = '/opt/secudos/DomosConf/license/lic.crt';

    /**
     * the constructor
     */
    public function __construct()
    {
        if (Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            $this->setLicenseFile(self::LICENSE_FILENAME);
        }
    }

    /**
     * returns number of days the license is expired or false if it's still valid or we have no expiry date
     *
     * @return number|boolean
     */
    public function getLicenseExpiredSince()
    {
        if ($this->isValid()) {
            // valid Secudos license never expires
            return false;
        }

        $expiryDate = $this->getDefaultExpiryDate();
        return $this->_diffDatesToDays($expiryDate['validTo'], Tinebase_DateTime::now());
    }

    public function getLicenseExpireEstimate()
    {
        if ($this->getStatus() !== Tinebase_License::STATUS_NO_LICENSE_AVAILABLE){
            return false;
        }

        $expiryDate = $this->getDefaultExpiryDate();
        return $this->_diffDatesToDays(Tinebase_DateTime::now(), $expiryDate['validTo']);
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        $data = $this->getCertificateData();
        if ($data && isset($data['issuer']) && isset($data['issuer']['CN']) && isset($data['subject']['OU'])) {
            if ($data['issuer']['CN'] !== 'SECUDOS D4 ServerCA') {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' License has invalid issuer: ' . $data['issuer']['CN']);
                return false;
            }

            $ou = $this->_getLicenseOU();
            if (! $ou) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Invalid license subject: ' . print_r($data['subject'], true));
                return false;
            }

            // valid
            return true;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
            . ' Missing or invalid license data');

        return false;
    }

    protected function _getLicenseOU()
    {
        $data = $this->getCertificateData();
        if ($data && isset($data['subject']['OU'][1]) && preg_match('/^TINE20/', $data['subject']['OU'][1])) {
            return $data['subject']['OU'][1];
        }

        return null;
    }
    
    /**
     * fetch certificate data
     * 
     * @return array
     */
    public function getCertificateData()
    {
        if ($this->_certData === null && $this->_license) {
            $this->_certData = openssl_x509_parse($this->_license);
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
            // TODO implement
        }

        return false;
    }
    
    /**
     * @return number
     * @throws Tinebase_Exception
     */
    public function getMaxUsers()
    {
        $maxUsers = 25;
        
        if (! $this->isValid()) {
            return 5;
        }

        $ou = $this->_getLicenseOU();
        if (! $ou) {
            throw new Tinebase_Exception('Missing license OU');
        }

        // find out max users from ou (for example: TINE20-50 means 50 Users
        if (preg_match('/TINE20-([0-9]+)/', $ou, $matches)) {
            $maxUsers = $matches[1];
        }

        return $maxUsers;
    }

    /**
     * get license type
     *
     * @return string
     */
    public function getLicenseType()
    {
        $result = Tinebase_License::LICENSE_TYPE_LIMITED_USER;

        return $result;
    }
}
