<?php
/**
 * MatrixAccount controller for MatrixSynapseIntegrator application
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MatrixAccount controller class for MatrixSynapseIntegrator application
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @todo        add acl (Admin.manageUser needed for some actions / rights / visibility)
 */
class MatrixSynapseIntegrator_Controller_MatrixAccount extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;
        $this->_modelName = MatrixSynapseIntegrator_Model_MatrixAccount::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME        => MatrixSynapseIntegrator_Model_MatrixAccount::class,
            Tinebase_Backend_Sql::TABLE_NAME        => MatrixSynapseIntegrator_Model_MatrixAccount::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = true;
    }

    /**
     * Removes records where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @return void
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks || $this->checkRight(Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        $_filter->addFilter(new Tinebase_Model_Filter_User(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID,
            Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
            Tinebase_Core::getUser()->getId()
        ));
    }

    /**
     * check if user has the right to manage MatrixAccounts
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'get':
                $this->checkRight(Admin_Acl_Rights::MANAGE_ACCOUNTS);
                break;
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight(Admin_Acl_Rights::MANAGE_ACCOUNTS);
                break;
            default;
                break;
        }

        parent::_checkRight($_action);
    }

    protected function _getApplicationRightsClass(): string
    {
        return Admin_Acl_Rights::class;
    }

    public function getMatrixAccountForCurrentUser(): MatrixSynapseIntegrator_Model_MatrixAccount
    {
        /** @var ?MatrixSynapseIntegrator_Model_MatrixAccount $result */
        $result = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            MatrixSynapseIntegrator_Model_MatrixAccount::class, [[
                Tinebase_Model_Filter_Abstract::FIELD => MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID,
                Tinebase_Model_Filter_Abstract::VALUE => Tinebase_Core::getUser()->getId()
            ]]
        ))->getFirstRecord();
        if (!$result) {
            throw new Tinebase_Exception_NotFound('No Matrix Account found');
        }
        return $result;
    }
}
