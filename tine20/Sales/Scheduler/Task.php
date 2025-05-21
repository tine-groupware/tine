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
     * remove update product lifespan task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeUpdateProductLifespanTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('Sales_Controller_Product::updateProductLifespan');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Sales_Controller_Product::updateProductLifespan from scheduler.');
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
     * remove create auto invoices daily task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeCreateAutoInvoicesDailyTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('Sales_Controller_Invoice::createAutoInvoicesTask');
        $_scheduler->removeTask('createAutoInvoicesDailyTask');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Sales_Controller_Invoice::createAutoInvoicesTask from scheduler.');
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

    /**
     * remove create auto invoices monthly task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeCreateAutoInvoicesMonthlyTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('Sales_Controller_Invoice::createAutoInvoicesTask');
        $_scheduler->removeTask('createAutoInvoicesMonthlyTask');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Sales_Controller_Invoice::createAutoInvoicesTask from scheduler.');
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

    public static function removeEMailDispatchResponseMinutelyTask(Tinebase_Scheduler $_scheduler): void
    {
        $_scheduler->removeTask(self::READ_EMAIL_DISPATCH_RESPONSES);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Sales_Controller_Invoice::createAutoInvoicesTask from scheduler.');
    }
}
