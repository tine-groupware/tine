<?php declare(strict_types=1);
/**
 * External Idp controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * External Idp controller class for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 *
 * @method SSO_Model_ExternalIdp get(string $id)
 */
class SSO_Controller_ExternalIdp extends Tinebase_Controller_Record_Abstract
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => SSO_Model_ExternalIdp::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => SSO_Model_ExternalIdp::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = SSO_Model_ExternalIdp::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        if (in_array($_action, ['create', 'update', 'delete']) &&
            !Tinebase_Core::getUser()->hasRight(Admin_Config::APP_NAME, Admin_Acl_Rights::MANAGE_SSO)) {
            throw new Tinebase_Exception_AccessDenied('You do not have the right manage sso');
        }
        parent::_checkRight($_action);
    }
}
