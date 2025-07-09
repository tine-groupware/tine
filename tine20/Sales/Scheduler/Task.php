<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Sales_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add update product lifespan task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addUpdateProductLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Sales_Controller_Product::class,
            'updateProductLifespan',
            self::TASK_TYPE_HOURLY,
            $_scheduler,
        );
    }

    /**
     * add create auto invoices daily task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCreateAutoInvoicesDailyTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Sales_Controller_Invoice::class,
            'createAutoInvoicesTask',
            '55 5 2-31 * *',
            $_scheduler,
            'createAutoInvoicesDailyTask'
        );
    }

    /**
     * add create auto invoices monthly task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCreateAutoInvoicesMonthlyTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Sales_Controller_Invoice::class,
            'createAutoInvoicesTask',
            '30 8 1 * *',
            $_scheduler,
            'createAutoInvoicesMonthlyTask'
        );
    }

    public const READ_EMAIL_DISPATCH_RESPONSES = 'SalesReadEmailDispatchResponses';
    public static function addEMailDispatchResponseMinutelyTask(Tinebase_Scheduler $_scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            Sales_Controller_Document_DispatchHistory::class,
            'readEmailDispatchResponses',
            self::TASK_TYPE_MINUTELY,
            $_scheduler,
            self::READ_EMAIL_DISPATCH_RESPONSES
        );
    }
}
