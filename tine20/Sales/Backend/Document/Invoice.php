<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for sales invoices
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_Document_Invoice extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = Sales_Model_Document_Invoice::TABLE_NAME;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = Sales_Model_Document_Invoice::class;

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = true;


    public function getInvoicesWithChangedContract($contractId = NULL)
    {
        //SELECT tsi.id, tsi.contract_id FROM `tine20_sales_document_invoice` as tsi
        //JOIN tine20_sales_contracts as tsc ON tsi.contract_id = tsc.id AND tsc.last_modified_time is NOT NULL
        //wHERE tsi.creation_time < tsc.last_modified_time

        $select = $this->getAdapter()->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), array($this->_tableName . '.' . 'id', $this->_tableName . '.contract_id'));
        $select->join(
        /* table  */ array('tsc' => $this->_tablePrefix . 'sales_contracts'),
            /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.contract_id') . ' = ' . $this->_db->quoteIdentifier('tsc.id') . ' AND tsc.last_modified_time IS NOT NULL'. (null!==$contractId?' AND ' . $this->_db->quoteIdentifier('tsc.id') . ' = ' . $this->_db->quote($contractId):''),
            /* select */ array()
        );
        $select->where($this->_tableName . '.creation_time < tsc.last_modified_time AND ' . $this->_tableName . '.' . Sales_Model_Document_Invoice::FLD_INVOICE_STATUS . ' = \'' . Sales_Model_Document_Invoice::STATUS_PROFORMA . '\' AND ' . $this->_tableName . '.is_deleted = 0');
        $select->order($this->_tableName . '.creation_time DESC');

        $stmt = $this->_db->query($select);
        $stmt->setFetchMode(Zend_Db::FETCH_NUM);
        return $stmt->fetchAll();
    }
}
