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

    const APPLIANCE_TYPE_HARDWARE = 'hardware';
    const APPLIANCE_TYPE_CLOUD_IMAGE = 'cloudimage';

    protected static $applianceType = null;
    protected $_modelFilename = '/opt/secudos/hwsupport/etc/model';

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
        if ($this->isValid() && $this->getApplianceType() === self::APPLIANCE_TYPE_HARDWARE) {
            // valid Secudos hardware box license never expires
            return false;
        }

        $expiryDate = $this->getExpiryDate();
        return $this->_diffDatesToDays($expiryDate, Tinebase_DateTime::now());
    }

    public function getLicenseExpireEstimate()
    {
        if ($this->getStatus() !== Tinebase_License::STATUS_NO_LICENSE_AVAILABLE && $this->getApplianceType() === self::APPLIANCE_TYPE_HARDWARE){
            return false;
        }

        $expiryDate = $this->getExpiryDate();
        $result = $this->_diffDatesToDays(Tinebase_DateTime::now(), $expiryDate);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' License expires in ' . $result . ' days');

        return $result;
    }

    /**
     * license expiry date
     *
     * @return Tinebase_DateTime
     */
    public function getExpiryDate()
    {
        $expiryDate = $this->getDefaultExpiryDate();
        $result = $expiryDate['validTo'];

        if ($this->getApplianceType() === self::APPLIANCE_TYPE_CLOUD_IMAGE) {
            $data = $this->getCertificateData();
            if (isset($data['validTo_time_t'])) {
                $result = new Tinebase_DateTime('@' . $data['validTo_time_t']);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Expiry date: ' . $result->toString());

        return $result;
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
        
        if (! $this->isValid() ||
            // @see #139806: [Hardware/Cloud] Secudos appliance image https://service.metaways.net/Ticket/Display.html?id=139806
            // if cloud image expires, user limit is set to 5 again
            ($this->getApplianceType() === self::APPLIANCE_TYPE_CLOUD_IMAGE && $this->getLicenseExpiredSince() > 0))
        {
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
        $result = $this->getApplianceType() === self::APPLIANCE_TYPE_HARDWARE
            ? Tinebase_License::LICENSE_TYPE_LIMITED_USER
            : Tinebase_License::LICENSE_TYPE_LIMITED_USER_TIME;

        return $result;
    }

    /**
     * get appliance type (one of APPLIANCE_TYPE_HARDWARE | APPLIANCE_TYPE_CLOUD_IMAGE)
     *
     * @return null|string
     */
    public function getApplianceType()
    {
        if (self::$applianceType === null) {
            // hardware box is default
            self::$applianceType = self::APPLIANCE_TYPE_HARDWARE;

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Looking for support model file at "' . dirname($this->_modelFilename) . '"');

            if (file_exists($this->_modelFilename)) {
                $model = trim(file_get_contents($this->_modelFilename));

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Got support model ' . $model . ' from file ' . $this->_modelFilename);

                switch ($model) {
                    case 'VIRTSYS':
                        self::$applianceType = self::APPLIANCE_TYPE_CLOUD_IMAGE;
                        break;
                    case 'APU.1':
                        self::$applianceType = self::APPLIANCE_TYPE_HARDWARE;
                        break;
                    default:
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' Got unknown support model - setting type to dafault: ' . self::$applianceType);
                }


            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' File does not exist: "' . $this->_modelFilename . '" - setting type to dafault: ' . self::$applianceType);
            }
        }

        return self::$applianceType;
    }
}
