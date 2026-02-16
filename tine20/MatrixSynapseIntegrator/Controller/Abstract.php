<?php
/**
 * Abstract controller for MatrixSynapseIntegrator application
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Abstract controller class for MatrixSynapseIntegrator application
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 */
class MatrixSynapseIntegrator_Controller_Abstract extends Tinebase_Controller_Record_Abstract
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
        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = true;
    }

    /**
     * check if user has the right to manage records
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

    protected function _pushToCorporal()
    {
        if (! MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN)
        ) {
            return;
        }

        $this->getCorporalBackend()->push();
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
        $this->_pushToCorporal();
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
        $this->_pushToCorporal();
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
        $this->_pushToCorporal();
    }
}
