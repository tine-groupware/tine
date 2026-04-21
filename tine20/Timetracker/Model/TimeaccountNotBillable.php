<?php
/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 */
class Timetracker_Model_TimeaccountNotBillable extends Timetracker_Model_Timeaccount
{
    const MODEL_NAME_PART = 'TimeaccountNotBillable';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);
        unset($_definition[self::VERSION]);
        unset($_definition[self::TABLE]);
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
    }

    protected function _getBillableTimesheetsFilter(Tinebase_DateTime $date, ?\Sales_Model_Contract $contract = NULL)
    {
        $filter = parent::_getBillableTimesheetsFilter($date, $contract);
        $filter->findFilterWithoutOr('is_billable')->setValue(false);
        return $filter;
    }

    protected function _cleanToBillWithInvoiceId()
    {
        $this->invoice_id = null;
        Timetracker_Controller_Timeaccount::getInstance()->update($this);

        // we unassign all assigned TS
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'is_cleared', 'operator' => 'equals', 'value' => false),
            array('field' => 'is_billable', 'operator' => 'equals', 'value' => false),
        ), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())
        ));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' TS Filter: ' . print_r($filter->toArray(), true));
        Timetracker_Controller_Timesheet::getInstance()->updateMultiple($filter, array('invoice_id' => null));
    }

    /**
     * returns true if this invoice needs to be recreated because data changed
     *
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @param Sales_Model_Invoice $invoice
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
    public function needsInvoiceRecreation(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate, Sales_Model_Invoice $invoice, Sales_Model_Contract $contract)
    {
        if (intval($this->budget) > 0) {

            // we dont touch cleared TAs at all
            if ($this->cleared_at) {
                return false;
            }

            if ($this->invoice_id === null) {
                if ($this->status === self::STATUS_TO_BILL) {
                    // we should bill this TA
                    return true;
                }
                // nothing to do
                return false;

                // a sanity checks required to fix old data...
            } elseif($this->status === self::STATUS_TO_BILL) {

                $this->_cleanToBillWithInvoiceId();

                // time to bill this TA now
                return true;

                // we are a relation of all invoices, but we will only be billed in one. If this is the one, we continue, else its not our business
            } elseif ($this->invoice_id != $invoice->getId()) {
                return false;
            }

            // did the status change? or anything else?
            if ($this->status !== self::STATUS_BILLED || $this->last_modified_time->isLater($invoice->creation_time)) {
                return true;
            }

            // we just assign all unassigned TS to our invoice silently and gracefully
            $filter = new Timetracker_Model_TimesheetFilter(array(
                array('field' => 'is_cleared', 'operator' => 'equals', 'value' => false),
                array('field' => 'is_billable', 'operator' => 'equals', 'value' => false),
            ), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())
            ));
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'invoice_id', 'operator' => 'not', 'value' => $invoice->getId())
            ));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' TS Filter: ' . print_r($filter->toArray(), true));
            Timetracker_Controller_Timesheet::getInstance()->updateMultiple($filter, array('invoice_id' => $invoice->getId()));

            return false;
        }

        $filter = new Timetracker_Model_TimesheetFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())
        ));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' TS Filter: ' . print_r($filter->toArray(), true));
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);
        foreach($timesheets as $timesheet)
        {
            if ($timesheet->last_modified_time && $timesheet->last_modified_time->isLater($invoice->creation_time) && !$timesheet->is_cleared) {
                return true;
            }
        }

        return false;
    }

    public function loadBillables(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate)
    {
        parent::loadBillables($date, $productAggregate);
        foreach ($this->_billables as &$billables) {
            $result = [];
            foreach ($billables as $ts) {
                $result[] = new Timetracker_Model_TimesheetNotBillable($ts->toArray());
            }
            $billables = $result;
        }
    }
}
