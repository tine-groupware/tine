<?php declare(strict_types=1);

/**
 * Order Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Order Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Order extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_adminGrant = Sales_Model_DivisionGrants::GRANT_ADMIN_DOCUMENT_ORDER;
    protected static $_readGrant = Sales_Model_DivisionGrants::GRANT_READ_DOCUMENT_ORDER;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Order::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Order::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Order::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_ORDER_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_ORDER_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_Order::FLD_ORDER_STATUS;
        $this->_oldRecordBookWriteableFields = [
            Sales_Model_Document_Order::FLD_ATTACHED_DOCUMENTS,
            Sales_Model_Document_Order::FLD_ORDER_STATUS,
            Sales_Model_Document_Order::FLD_EVAL_DIM_COST_CENTER,
            Sales_Model_Document_Order::FLD_EVAL_DIM_COST_BEARER,
            Sales_Model_Document_Order::FLD_BUYER_REFERENCE,
            Sales_Model_Document_Order::FLD_CONTACT_ID,
            Sales_Model_Document_Order::FLD_DESCRIPTION,
            Sales_Model_Document_Order::FLD_SHARED_INVOICE,
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID,
            Sales_Model_Document_Order::FLD_SHARED_DELIVERY,
            Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID,
            Sales_Model_Document_Order::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS,
            Sales_Model_Document_Order::FLD_FOLLOWUP_DELIVERY_BOOKED_STATUS,
            Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS,
            Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS,
            Sales_Model_Document_Order::FLD_REVERSAL_STATUS,
            Sales_Model_Document_Order::FLD_PAYMENT_MEANS,
            Sales_Model_Document_Order::FLD_DISPATCH_HISTORY,
            Sales_Model_Document_Abstract::FLD_PURCHASE_ORDER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_BUYER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_PROJECT_REFERENCE,
            Sales_Model_Document_Abstract::FLD_CONTACT_ID,
            Sales_Model_Document_Abstract::FLD_CONTRACT_NUMBER,
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [
            Sales_Model_Document_Order::FLD_CUSTOMER_ID,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID,
        ];
        parent::__construct();
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        // important! after _inspectDenormalization in parent::_inspectBeforeUpdate
        // the recipient address is not part of a customer, debitor_id needs to refer to the local denormalized instance
        $this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID);
        $this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID);
    }

    /**
     * @param Sales_Model_Document_Abstract $_record
     * @param Sales_Model_Document_Abstract $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        // important! after _inspectDenormalization in parent::_inspectBeforeUpdate
        // the recipient address is not part of a customer, debitor_id needs to refer to the local denormalized instance
        $this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID);
        $this->_inspectAddressField($_record, Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID);
    }
}
