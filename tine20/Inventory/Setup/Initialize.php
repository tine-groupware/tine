<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2008-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Inventory initialization
 * 
 * @package     Setup
 */
class Inventory_Setup_Initialize extends Setup_Initialize
{
    public static function applicationInstalled(Tinebase_Model_Application $app): void
    {
        if (Sales_Config::APP_NAME === $app->name) {
            static::addInventoryItemInvoiceSysCF();
        }
    }

    protected function _initializeInventoryItemInvoiceSysCF(): void
    {
        static::addInventoryItemInvoiceSysCF();
    }

    public static function addInventoryItemInvoiceSysCF(): void
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        try {
            Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME);
        } catch (Tinebase_Exception_NotFound) {
            return;
        }
        $appId = Tinebase_Application::getInstance()->getApplicationByName(Inventory_Config::APP_NAME)->getId();

        if (null !== Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                Inventory_Model_InventoryItem::FLD_INVOICE, Inventory_Model_InventoryItem::class, true)) {
            return;
        }

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => Inventory_Model_InventoryItem::FLD_INVOICE,
            'application_id' => $appId,
            'model' => Inventory_Model_InventoryItem::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Purchase Invoice', // _('Purchase Invoice')
                    TMCC::TYPE              => TMCC::TYPE_RECORD,
                    TMCC::LENGTH            => 40,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => Sales_Config::APP_NAME,
                        TMCC::MODEL_NAME        => Sales_Model_PurchaseInvoice::MODEL_NAME_PART,
                    ],
                    TMCC::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                    TMCC::NULLABLE          => true,
                    TMCC::QUERY_FILTER      => true,
                ],
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [Inventory_Controller_InventoryItem::class, 'modelConfigHook'],
                ],
            ],
        ], true));
    }

    /**
     * init the default persistentfilters
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
            
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'model'             => 'Inventory_Model_InventoryItemFilter',
        );
        
        // default persistent filter for all records
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All Inventory Items", // _("All Inventory Items")
            'description'       => "All existing Inventory Items", // _("All existing Inventory Items")
            'filters'           => array(),
        ))));
    }

    public function initializeCostCenter()
    {
        $this->_initializeCostCenter();
    }

    protected function _initializeCostCenter()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [Inventory_Model_InventoryItem::class]);
    }
}
