<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for BankAccount
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_BankAccount extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = Tinebase_Model_BankAccount::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_BankAccount::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_BankAccount::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }

    protected function _checkRight($_action)
    {
        if (!$this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        if (self::ACTION_GET === $_action) {
            return;
        }

        if (!Tinebase_Core::getUser()->hasRight(Tinebase_Core::getTinebaseId(), Tinebase_Acl_Rights::MANAGE_BANK_ACCOUNTS)) {
            throw new Tinebase_Exception_AccessDenied('no right to manage bank accounts');
        }
    }
}
