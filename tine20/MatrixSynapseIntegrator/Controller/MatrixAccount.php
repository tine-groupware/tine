<?php
/**
 * MatrixAccount controller for MatrixSynapseIntegrator application
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
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

    protected ?MatrixSynapseIntegrator_Backend_Corporal $_corporal = null;
    protected ?MatrixSynapseIntegrator_Backend_Synapse $_synapse = null;

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
        if (!$this->_doRightChecks
            || !$this->_doContainerACLChecks
            || $this->checkRight(Admin_Acl_Rights::MANAGE_ACCOUNTS)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ACL / right checks disabled for ' . $_filter->getModelName() . '.');
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
        if (! $this->_doRightChecks) {
            return;
        }

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

    /**
     * @param Tinebase_Model_User $user
     * @return MatrixSynapseIntegrator_Model_MatrixAccount
     * @throws Tinebase_Exception_NotFound
     */
    public function getMatrixAccountForUser(Tinebase_Model_User $user): MatrixSynapseIntegrator_Model_MatrixAccount
    {
        return $this->_getMatrixAccount(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID,
            $user->getId()
        );
    }

    protected function _getMatrixAccount(string $field, string $value): MatrixSynapseIntegrator_Model_MatrixAccount
    {
        $check = $this->doRightChecks(false);
        /** @var ?MatrixSynapseIntegrator_Model_MatrixAccount $result */
        $result = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            MatrixSynapseIntegrator_Model_MatrixAccount::class, [[
                Tinebase_Model_Filter_Abstract::FIELD => $field,
                Tinebase_Model_Filter_Abstract::VALUE => $value
            ]]
        ))->getFirstRecord();
        $this->doRightChecks($check);
        if (!$result) {
            throw new Tinebase_Exception_NotFound('No Matrix Account found');
        }
        return $result;
    }

    /**
     * @param string $matrixId
     * @return MatrixSynapseIntegrator_Model_MatrixAccount
     * @throws Tinebase_Exception_NotFound
     */
    public function getMatrixAccountByMatrixId(string $matrixId): MatrixSynapseIntegrator_Model_MatrixAccount
    {
        return $this->_getMatrixAccount(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID,
            $matrixId
        );
    }

    public function setCorporalBackend(
        ?MatrixSynapseIntegrator_Backend_Corporal $backend = null): MatrixSynapseIntegrator_Backend_Corporal
    {
        return $this->_corporal = $backend ?: new MatrixSynapseIntegrator_Backend_Corporal();
    }

    public function getCorporalBackend(): MatrixSynapseIntegrator_Backend_Corporal
    {
        return $this->_corporal ?: $this->setCorporalBackend();
    }

    public function setSynapseBackend(
        ?MatrixSynapseIntegrator_Backend_Synapse $backend = null): MatrixSynapseIntegrator_Backend_Synapse
    {
        return $this->_synapse = $backend ?: new MatrixSynapseIntegrator_Backend_Synapse();
    }

    public function getSynapseBackend(): MatrixSynapseIntegrator_Backend_Synapse
    {
        return $this->_synapse ?: $this->setSynapseBackend();
    }

    protected function _pushToCorporal(MatrixSynapseIntegrator_Model_MatrixAccount $matrixAccount)
    {
        if (! MatrixSynapseIntegrator_Config::getInstance()->get(MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN)) {
            return;
        }

        $this->getCorporalBackend()->push($matrixAccount);
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->_replaceStringInMatrixId($_record);

        $this->_setRandomBase64EncodedField($_record,
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_SESSION_KEY);
        if (empty($_record->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY})) {
            $this->_setRandomBase64EncodedField($_record,
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD);
        }
    }

    protected function _replaceStringInMatrixId(MatrixSynapseIntegrator_Model_MatrixAccount $record)
    {
        // TODO use twig (but how?)
//        $translation = Tinebase_Translation::getTranslation($this->_applicationName);
//        $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), $translation);
//        $templateString = MatrixSynapseIntegrator_Model_MatrixAccount::MATRIX_ID_TWIG;
//        $template = $twig->getEnvironment()->createTemplate($templateString);
//        $record->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID} =
//            $template->render($user->toArray());

        $user = Tinebase_User::getInstance()->getUserById(
            $record->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID}
        );
        $record->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID} =
            str_replace(MatrixSynapseIntegrator_Model_MatrixAccount::MATRIX_ID_TWIG, $user->getId(),
                $record->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID}
            );
    }

    protected function _setRandomBase64EncodedField(MatrixSynapseIntegrator_Model_MatrixAccount $record, string $field)
    {
        if (empty($record->$field)) {
            try {
                $record->$field = base64_encode(random_bytes(32));
            } catch (Exception) {
                // random_bytes might fail
                $record->$field = base64_encode(Tinebase_Record_Abstract::generateUID(32));
            }
        }
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        /** @var MatrixSynapseIntegrator_Model_MatrixAccount $_createdRecord */
        $this->_pushToCorporal($_createdRecord);
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        /** @var MatrixSynapseIntegrator_Model_MatrixAccount $updatedRecord */
        $this->_pushToCorporal($updatedRecord);
    }

    /**
     * inspect delete of one record (after delete)
     *
     * @param   Tinebase_Record_Interface $record          the just deleted record
     * @return  void
     */
    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        /** @var MatrixSynapseIntegrator_Model_MatrixAccount $record */
        $record->is_deleted = 1;
        $this->_pushToCorporal($record);
    }

    public function synapseLogin(): array
    {
        $account = $this->getMatrixAccountForUser(Tinebase_Core::getUser());
        return $this->getSynapseBackend()->login($account);
    }
}
