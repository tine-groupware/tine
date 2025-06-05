<?php declare(strict_types=1);

/**
 * Offer Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Offer Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Offer extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_adminGrant = Sales_Model_DivisionGrants::GRANT_ADMIN_DOCUMENT_OFFER;
    protected static $_readGrant = Sales_Model_DivisionGrants::GRANT_READ_DOCUMENT_OFFER;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Offer::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Offer::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Offer::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_OFFER_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_OFFER_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_Offer::FLD_OFFER_STATUS;
        $this->_oldRecordBookWriteableFields = [
            Sales_Model_Document_Offer::FLD_ATTACHED_DOCUMENTS,
            Sales_Model_Document_Offer::FLD_OFFER_STATUS,
            Sales_Model_Document_Offer::FLD_EVAL_DIM_COST_CENTER,
            Sales_Model_Document_Offer::FLD_EVAL_DIM_COST_BEARER,
            Sales_Model_Document_Offer::FLD_DESCRIPTION,
            Sales_Model_Document_Offer::FLD_FOLLOWUP_ORDER_CREATED_STATUS,
            Sales_Model_Document_Offer::FLD_FOLLOWUP_ORDER_BOOKED_STATUS,
            Sales_Model_Document_Offer::FLD_REVERSAL_STATUS,
            Sales_Model_Document_Offer::FLD_PAYMENT_MEANS,
            Sales_Model_Document_Offer::FLD_DISPATCH_HISTORY,
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID,
        ];
        parent::__construct();
    }

    protected function _inspectFollowUpStati(Sales_Model_Document_Abstract $_record, ?Sales_Model_Document_Abstract $_oldRecord = null): void
    {
        // we do not do this for offers, they are first in chain
    }
}
