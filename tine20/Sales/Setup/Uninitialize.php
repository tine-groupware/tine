<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     Sales
 */
class Sales_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit scheduler tasks
     */
    protected function _uninitializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Sales_Scheduler_Task::removeUpdateProductLifespanTask($scheduler);
        Sales_Scheduler_Task::removeCreateAutoInvoicesDailyTask($scheduler);
        Sales_Scheduler_Task::removeCreateAutoInvoicesMonthlyTask($scheduler);
        Sales_Scheduler_Task::removeEMailDispatchResponseMinutelyTask($scheduler);
    }

    protected function _uninitializeCustomFields()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
            'divisions', Tinebase_Model_EvaluationDimensionItem::class, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
            'divisions', Tinebase_Model_EvaluationDimension::class, true);
        if (null !== $cfc) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }
    }

    protected function _uninitializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Sales_Model_Invoice::class,
            Sales_Model_Product::class,
            Sales_Model_Contract::class,
            Sales_Model_PurchaseInvoice::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
            Sales_Model_Product::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
    }
}