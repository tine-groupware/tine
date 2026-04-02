<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Inventory initialization
 *
 * @package     Inventory
 */
class Inventory_Setup_Uninitialize extends Setup_Uninitialize
{
    public static function applicationUninstalled(Tinebase_Model_Application $app): void
    {
        if (Sales_Config::APP_NAME === $app->name) {
            static::removeInventoryItemInvoiceSysCF();
        }
    }

    protected function _uninitializeInventoryItemInvoiceSysCF(): void
    {
        static::removeInventoryItemInvoiceSysCF();
    }

    public static function removeInventoryItemInvoiceSysCF(): void
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Inventory_Config::APP_NAME)->getId(),
            Inventory_Model_InventoryItem::FLD_INVOICE, Inventory_Model_InventoryItem::class, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }
    }

    protected function _uninitializeCostCenter()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }
        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [Inventory_Model_InventoryItem::class]);
    }
}