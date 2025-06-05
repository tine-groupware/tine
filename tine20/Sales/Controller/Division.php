<?php declare(strict_types=1);
/**
 * Division controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * Division controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 *
 * @method Sales_Model_Division create(Sales_Model_Division $record)
 */
class Sales_Controller_Division extends Tinebase_Controller_Record_Container
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_modelName = Sales_Model_Division::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => Sales_Model_Division::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);

        $this->_grantsModel = Sales_Model_DivisionGrants::class;
        $this->_manageRight = Sales_Acl_Rights::MANAGE_DIVISIONS;
        $this->_purgeRecords = false;
    }

    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        parent::_checkRight($_action);

        // create needs MANAGE_DIVISIONS
        if (self::ACTION_CREATE === $_action) {
            if (!Tinebase_Core::getUser()
                    ->hasRight(Sales_Config::APP_NAME, Sales_Acl_Rights::MANAGE_DIVISIONS)) {
                throw new Tinebase_Exception_AccessDenied(Sales_Acl_Rights::MANAGE_DIVISIONS .
                    ' right required to ' . $_action);
            }
        }
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // standard actions are use for the division itself.
        // everybody can GET, create needs MANAGE_DIVISIONS which is checked in _checkRight, so nothing to do here, do not call parent!
        if (self::ACTION_GET === $_action || self::ACTION_CREATE === $_action) {
            return true;
        }
        // this needs admin
        if (self::ACTION_UPDATE === $_action || self::ACTION_DELETE === $_action) {
            if (Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                return true;
            } elseif ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            } else {
                return false;
            }
        }

        // delegated acl checks from employee, wtr, etc. come in here with non standard actions
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        // everybody can see all divisions
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
            'observable_identifier' => $_createdRecord->{Sales_Model_Division::FLD_CONTAINER_ID},
            'observer_model'        => Sales_Model_Division::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Update::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($updateObserver);

        $deleteObserver = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => Tinebase_Model_Container::class,
            'observable_identifier' => $_createdRecord->{Sales_Model_Division::FLD_CONTAINER_ID},
            'observer_model'        => Sales_Model_Division::class,
            'observer_identifier'   => $_createdRecord->getId(),
            'observed_event'        => Tinebase_Event_Record_Delete::class,
        ));
        Tinebase_Record_PersistentObserver::getInstance()->addObserver($deleteObserver);

        $this->_updateNumberables($_createdRecord);
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        if ($updatedRecord->{Sales_Model_Division::FLD_TITLE} !== $currentRecord->{Sales_Model_Division::FLD_TITLE}) {
            $this->_updateNumberables($updatedRecord);
        }
    }

    protected function _updateNumberables(Sales_Model_Division $division): void
    {
        /**
         * @var Tinebase_Record_Interface $model
         * @var array<string> $properties
         */
        foreach([
                    Sales_Model_Document_Delivery::class => [Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER],
                    Sales_Model_Document_Invoice::class => [Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER],
                    Sales_Model_Document_Offer::class => [Sales_Model_Document_Offer::FLD_DOCUMENT_NUMBER],
                    Sales_Model_Document_Order::class => [Sales_Model_Document_Order::FLD_DOCUMENT_NUMBER],
                ] as $model => $properties) {
            $fields = $model::getConfiguration()->getFields();
            foreach ($properties as $property) {
                $config = $fields[$property];
                unset($config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE]);
                $record = new $model([
                    Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY => new Sales_Model_Document_Category([
                        Sales_Model_Document_Category::FLD_DIVISION_ID => $division,
                    ], true),
                ], true);

                list($objectClass, $method) = explode('::', $config[TMCC::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE]);
                $object = call_user_func($objectClass . '::getInstance');
                $configOverride = call_user_func_array([$object, $method], [$record]);
                $config[TMCC::CONFIG] = array_merge($config[TMCC::CONFIG], $configOverride);

                Tinebase_Numberable::getCreateUpdateNumberableConfig($model, $property, $config);
            }
        }

        $config = Sales_Model_Debitor::getConfiguration()->getFields()[Sales_Model_Debitor::FLD_NUMBER];
        unset($config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE]);
        $record = new Sales_Model_Debitor([
            Sales_Model_Debitor::FLD_DIVISION_ID => $division,
        ], true);
        $config[TMCC::CONFIG] = array_merge($config[TMCC::CONFIG], Sales_Controller_Debitor::getInstance()->numberConfigOverride($record));
        Tinebase_Numberable::getCreateUpdateNumberableConfig(Sales_Model_Debitor::class, Sales_Model_Debitor::FLD_NUMBER, $config);
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
                        $division = $this->get($_eventObject->persistentObserver->observer_identifier);
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        break;
                    }
                    if ($division->{Sales_Model_Division::FLD_TITLE} !== $_eventObject->observable->name) {
                        $division->{Sales_Model_Division::FLD_TITLE} = $_eventObject->observable->name;
                        $this->update($division);
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

    public static function evalDimModelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        $expander = $mc->jsonExpander;

        $expander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][Tinebase_Model_EvaluationDimension::FLD_ITEMS]
            [Tinebase_Record_Expander::EXPANDER_PROPERTIES]['divisions'] = [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    Sales_Model_DivisionEvalDimensionItem::FLD_DIVISION_ID  => [],
                ]
            ];

        $mc->setJsonExpander($expander);
    }
}
