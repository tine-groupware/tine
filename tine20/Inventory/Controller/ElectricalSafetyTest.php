<?php declare(strict_types=1);

/**
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;


class Inventory_Controller_ElectricalSafetyTest extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Inventory_Controller_ElectricalSafetyTest> */
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Inventory_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Inventory_Model_ElectricalSafetyTest::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Inventory_Model_ElectricalSafetyTest::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Inventory_Model_ElectricalSafetyTest::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        if (empty($_record->{Inventory_Model_ElectricalSafetyTest::FLD_INSPECTOR})) {
            $_record->{Inventory_Model_ElectricalSafetyTest::FLD_INSPECTOR} = Tinebase_Core::getUser();
        }
    }
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $inventoryItem = Inventory_Controller_InventoryItem::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Inventory_Model_InventoryItem::class, [
                [TMFA::FIELD => Inventory_Model_InventoryItem::FLD_ELECTRICAL_EQUIPMENTS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                        [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_createdRecord->getId()],
                    ]],
                ]],
            ]))->getFirstRecord();
        if (null === $inventoryItem) {
            throw new Tinebase_Exception_Backend('parent inventory item not found');
        }
        $electricalEquipment = Inventory_Controller_ElectricalEquipment::getInstance()->get($_createdRecord->getIdFromProperty(Inventory_Model_ElectricalSafetyTest::FLD_EQUIPMENT_ID));

        $export = new Inventory_Export_ElectricalSafetyTestPdf(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_createdRecord->getId()],
        ]), $this, [
            'template' => Inventory_Config::getInstance()->{Inventory_Config::ELECTRICAL_SAFETY_TEST_REPORT_TEMPLATE}
                ?: 'tine20:///' . Tinebase_Core::getTinebaseId() . '/folders/shared/export/templates/Inventory/electrical_safety_test_export.docx',
            'target' => [
                Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_ATTACHMENT,
                Tinebase_Model_Tree_FileLocation::FLD_RECORD_ID => $electricalEquipment->getIdFromProperty(Inventory_Model_ElectricalEquipment::FLD_INVENTORY_ITEM_ID),
                Tinebase_Model_Tree_FileLocation::FLD_MODEL => Inventory_Model_InventoryItem::class,
                Tinebase_Model_Tree_FileLocation::FLD_FILE_NAME =>
                    $_createdRecord->{Inventory_Model_ElectricalSafetyTest::FLD_TEST_DATE}->format('Y-m-d-') .
                        $electricalEquipment->{Inventory_Model_ElectricalEquipment::FLD_INVENTORY_ID} . '.pdf'
            ],
        ]);
        $export->generate();

        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(fn() => $export->writeToFileLocation());
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        foreach($_record::getConfiguration()->fieldKeys as $fieldKey) {
            $_record->{$fieldKey} = $_oldRecord->{$fieldKey};
        }
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
