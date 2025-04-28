<?php declare(strict_types=1);
/**
 * OAuthDeviceCode controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * OAuthDeviceCode controller class for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 */
class SSO_Controller_OAuthDeviceCode extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = SSO_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => SSO_Model_OAuthDeviceCode::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => SSO_Model_OAuthDeviceCode::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => false,
        ]);
        $this->_modelName = SSO_Model_OAuthDeviceCode::class;
        $this->_purgeRecords = true;
        $this->_doContainerACLChecks = false;
    }
}
