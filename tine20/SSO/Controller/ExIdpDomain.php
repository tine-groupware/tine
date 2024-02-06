<?php declare(strict_types=1);
/**
 * External Idp Domain controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * External Idp Domain controller class for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 */
class SSO_Controller_ExIdpDomain extends Tinebase_Controller_Record_Abstract
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => SSO_Model_ExIdpDomain::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => SSO_Model_ExIdpDomain::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = SSO_Model_ExIdpDomain::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _checkRight($_action)
    {
        if (in_array($_action, ['create', 'update', 'delete']) &&
            !Tinebase_Core::getUser()->hasRight(Admin_Config::APP_NAME, Admin_Acl_Rights::MANAGE_SSO)) {
            return false;
        }
        parent::_checkRight($_action);
    }
}
