<?php

/**
 * Room controller for MatrixSynapseIntegrator application
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Room controller class for MatrixSynapseIntegrator application
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Controller
 *
 * @todo        add more acl (Admin.manageUser needed for some actions / rights / visibility)?
 */
class MatrixSynapseIntegrator_Controller_Room extends MatrixSynapseIntegrator_Controller_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<MatrixSynapseIntegrator_Controller_Room> */
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        parent::__construct();
        $this->_modelName = MatrixSynapseIntegrator_Model_Room::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME        => MatrixSynapseIntegrator_Model_Room::class,
            Tinebase_Backend_Sql::TABLE_NAME        => MatrixSynapseIntegrator_Model_Room::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
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
        if (
            !$this->_doRightChecks
            || !$this->_doContainerACLChecks
            || $this->checkRight(Admin_Acl_Rights::MANAGE_ACCOUNTS)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    . ' ACL / right checks disabled for ' . $_filter->getModelName() . '.');
            }
            return;
        }

        // TODO add list_id filter - only allow lists user has access to?
        // TODO also add more acl checking to \MatrixSynapseIntegrator_Controller_Abstract::_checkRight?
//        $_filter->addFilter(new Tinebase_Model_Filter_User(
//            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID,
//            Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
//            Tinebase_Core::getUser()->getId()
//        ));
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

        $roomId = $this->getSynapseBackend()->createRoom($_record);
        $_record->{MatrixSynapseIntegrator_Model_Room::FLD_ROOM_ID} = $roomId;
    }

    public static function modelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        $expanderDef = $mc->jsonExpander;
        $expanderDef[Tinebase_Record_Expander::EXPANDER_PROPERTIES]
            [MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM] = [];
        $mc->setJsonExpander($expanderDef);
    }

    public function getRoomsForAccount(Tinebase_Model_FullUser $user): Tinebase_Record_RecordSet
    {
        $listIds = Addressbook_Controller_List::getInstance()->getMemberships($user->contact_id);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(MatrixSynapseIntegrator_Model_Room::class, [
            ['field' => MatrixSynapseIntegrator_Model_Room::FLD_LIST_ID, 'operator' => 'in', 'value' => $listIds],
        ]);
        return $this->search($filter);
    }
}
