<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for NumberableConfig
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_NumberableConfig extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_NumberableConfig::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_NumberableConfig::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_NumberableConfig::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
        $this->_purgeRecords = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks || self::ACTION_GET === $_action) {
            return;
        }

        parent::_checkRight($_action);

        if (! Tinebase_Core::getUser()->hasRight(
                Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME),
                Tinebase_Acl_Rights::MANAGE_NUMBERABLES)) {
            throw new Tinebase_Exception_AccessDenied('no right to ' . $_action . ' ' . Tinebase_Model_NumberableConfig::class);
        }
    }
}
