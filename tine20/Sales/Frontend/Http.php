<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * This class handles all Http requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * application name
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';
    
    /**
     * export invoice positions by invoice id and accountable (php class name)
     * 
     * @param string $invoiceId
     * @param string $accountable
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function exportInvoicePositions($invoiceId, $accountable)
    {
        if (! (class_exists($accountable) && in_array('Sales_Model_Accountable_Interface', class_implements($accountable)))) {
            throw new Tinebase_Exception_InvalidArgument('The given accountable ' . $accountable . ' does not exist or doesn\'t implement Sales_Model_Accountable_Interface');
        }
        
        if ($accountable == 'Sales_Model_ProductAggregate') {
            $billableFilterName     = 'Sales_Model_InvoicePositionFilter';
            $billableControllerName = 'Sales_Controller_InvoicePosition';
        } else {
            $billableFilterName     = $accountable::getBillableFilterName();
            $billableControllerName = $accountable::getBillableControllerName();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export invoicepositions with the parameters:' .
                ' invoiceId: ' . $invoiceId .
                ' accountable: ' . $accountable .
                ' billableFilterName: ' . $billableFilterName .
                ' billableControllerName: ' . $billableControllerName
            );
        }
        
        $filter = new $billableFilterName(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoiceId)));
        
        if ($accountable == 'Sales_Model_ProductAggregate') {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'model', 'operator' => 'equals', 'value' => 'Sales_Model_ProductAggregate')));
        } elseif ($accountable == 'Timetracker_Model_Timeaccount') {
            $filter = new Timetracker_Model_TimesheetFilter(array(
                array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                    array('condition' => 'OR', 'filters' => array(
                        array('field' => 'budget', 'operator' => 'equals', 'value' => 0),
                        array('field' => 'budget', 'operator' => 'equals', 'value' => NULL),
                    )),
                    array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
                )),
            ));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoiceId)));
        }
        
        parent::_export($filter, array('format' => 'ods'), $billableControllerName::getInstance());
    }
}
