<?php declare(strict_types=1);

/**
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;


class Inventory_Controller_ElectricalEquipment extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Inventory_Controller_ElectricalEquipment> */
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Inventory_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Inventory_Model_ElectricalEquipment::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Inventory_Model_ElectricalEquipment::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Inventory_Model_ElectricalEquipment::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        if (empty($_record->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE})) {
            if (empty($_record->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS})) {
                $_record->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE} = Tinebase_DateTime::today()->add(new DateInterval(Inventory_Config::getInstance()->{Inventory_Config::ELECTRICAL_SAFETY_TEST_INTERVAL}));
            } else {
                $_record->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE} = Tinebase_DateTime::today();
            }
        }
    }

    /**
     * @param Inventory_Model_ElectricalEquipment $_record
     * @param Inventory_Model_ElectricalEquipment $_oldRecord
     */
    protected function _updateDependentRecords(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord, $_property, $_fieldConfig)
    {
        parent::_updateDependentRecords($_record, $_oldRecord, $_property, $_fieldConfig);

        if (Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS === $_property) {
            $this->checkNextTestDue($_record, $_record);
        }
    }

    /**
     * @param Inventory_Model_ElectricalEquipment $_createdRecord
     * @param Inventory_Model_ElectricalEquipment $_record
     */
    protected function _createDependentRecords(Tinebase_Record_Interface $_createdRecord, Tinebase_Record_Interface $_record, $_property, $_fieldConfig)
    {
        parent::_createDependentRecords($_createdRecord, $_record, $_property, $_fieldConfig);

        if (Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS === $_property) {
            $this->checkNextTestDue($_record, $_createdRecord);
        }
    }

    protected function checkNextTestDue(Inventory_Model_ElectricalEquipment $_record, Inventory_Model_ElectricalEquipment $_createdRecord): void
    {
        if ($_record->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS} instanceof Tinebase_Record_RecordSet
            && $_record->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS}->count() > 0) {
            $latest = null;
            foreach ($_record->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS} as $safetyTest) {
                if (!$safetyTest->{Inventory_Model_ElectricalSafetyTest::FLD_TEST_PASSED}
                    || !$safetyTest->{Inventory_Model_ElectricalSafetyTest::FLD_VISUAL_INSPECTION_PASSED}) continue;
                if (null === $latest || $safetyTest->{Inventory_Model_ElectricalSafetyTest::FLD_TEST_DATE}->isLater($latest)) {
                    $latest = $safetyTest->{Inventory_Model_ElectricalSafetyTest::FLD_TEST_DATE};
                }
            }
            if (null !== ($latest = $latest?->getClone()->add(new DateInterval(Inventory_Config::getInstance()->{Inventory_Config::ELECTRICAL_SAFETY_TEST_INTERVAL}))) &&
                (null === $_createdRecord->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE} ||
                    $_createdRecord->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE}->isEarlier($latest))) {
                $_createdRecord->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE} = $latest;
                $this->getBackend()->updateMultiple([$_createdRecord->getId()], [Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE => $latest->format('Y-m-d')]);
            }
        }
    }
}
