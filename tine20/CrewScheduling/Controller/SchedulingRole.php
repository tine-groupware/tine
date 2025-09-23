<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * SchedulingRole controller for CrewScheduling
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_SchedulingRole extends Tinebase_Controller_Record_Container
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_modelName = CrewScheduling_Model_SchedulingRole::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => CrewScheduling_Model_SchedulingRole::class,
            'tableName'     => CrewScheduling_Model_SchedulingRole::TABLE_NAME,
            'modlogActive'  => true
        ));
        $this->_purgeRecords = FALSE;
        $this->_grantsModel = CrewScheduling_Model_SchedulingRoleGrants::class;
        $this->_manageRight = CrewScheduling_Acl_Rights::MANAGE_SCHEDULING_ROLES;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        // create needs ...
        if (self::ACTION_CREATE === $_action) {
            if (!Tinebase_Core::getUser()
                    ->hasRight(CrewScheduling_Config::APP_NAME, CrewScheduling_Acl_Rights::MANAGE_SCHEDULING_ROLES)) {
                throw new Tinebase_Exception_AccessDenied(CrewScheduling_Acl_Rights::MANAGE_SCHEDULING_ROLES .
                    ' right required to ' . $_action);
            }
        }
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // everybody can GET
        if (self::ACTION_GET === $_action) {
            return true;
        }

        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (self::ACTION_GET === $_action) {
            return;
        }

        parent::checkFilterACL($_filter, $_action);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $updateObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Container::class,
            'observable_identifier' => $_createdRecord->{CrewScheduling_Model_SchedulingRole::FLD_CONTAINER_ID},
            'observer_model'        => CrewScheduling_Model_SchedulingRole::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Update::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($updateObserver);

        $deleteObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Container::class,
            'observable_identifier' => $_createdRecord->{CrewScheduling_Model_SchedulingRole::FLD_CONTAINER_ID},
            'observer_model'        => CrewScheduling_Model_SchedulingRole::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Delete::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteObserver);
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if ($_eventObject instanceof Tinebase_Event_Observer_Abstract && $_eventObject->persistentObserver
                ->observable_model === Tinebase_Model_Container::class) {
            switch (get_class($_eventObject)) {
                case Tinebase_Event_Record_Update::class:
                    if ($_eventObject->observable->is_deleted) {
                        break;
                    }
                    try {
                        $schedulingRole = $this->get($_eventObject->persistentObserver->observer_identifier);
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        break;
                    }
                    if ($schedulingRole->{CrewScheduling_Model_SchedulingRole::FLD_NAME} !== $_eventObject->observable->name) {
                        $schedulingRole->{CrewScheduling_Model_SchedulingRole::FLD_NAME} = $_eventObject->observable->name;
                        $this->update($schedulingRole);
                    }
                    break;

                case Tinebase_Event_Record_Delete::class:
                    if (static::$_deletingRecordId !== $_eventObject->persistentObserver->observer_identifier) {
                        $this->delete($_eventObject->persistentObserver->observer_identifier);
                    }
                    break;
            }
        }
    }
}
