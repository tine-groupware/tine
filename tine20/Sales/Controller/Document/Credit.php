<?php declare(strict_types=1);

/**
 * Credit Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Credit Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Credit extends Sales_Controller_Document_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Sales_Controller_Document_Credit> */
    use Tinebase_Controller_SingletonTrait;

    protected static $_adminGrant = Sales_Model_DivisionGrants::GRANT_ADMIN_DOCUMENT_CREDIT;
    protected static $_readGrant = Sales_Model_DivisionGrants::GRANT_READ_DOCUMENT_CREDIT;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Credit::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Credit::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Credit::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_CREDIT_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_CREDIT_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_Credit::FLD_CREDIT_STATUS;
        $this->_oldRecordBookWriteableFields = [
            Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS,
            Sales_Model_Document_Abstract::FLD_BUYER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_CONTACT_ID,
            Sales_Model_Document_Abstract::FLD_CONTRACT_NUMBER,
            Sales_Model_Document_Abstract::FLD_DESCRIPTION,
            Sales_Model_Document_Abstract::FLD_DISPATCH_HISTORY,
            Sales_Model_Document_Abstract::FLD_EVAL_DIM_COST_BEARER,
            Sales_Model_Document_Abstract::FLD_EVAL_DIM_COST_CENTER,
            Sales_Model_Document_Abstract::FLD_PAYMENT_MEANS,
            Sales_Model_Document_Abstract::FLD_PROJECT_REFERENCE,
            Sales_Model_Document_Abstract::FLD_PURCHASE_ORDER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_REVERSED_STATUS,
            Sales_Model_Document_Credit::FLD_CREDIT_STATUS,
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [
            Sales_Model_Document_Credit::FLD_CUSTOMER_ID,
            Sales_Model_Document_Credit::FLD_RECIPIENT_ID,
        ];
        parent::__construct();
    }

    public function documentNumberConfigOverride(Sales_Model_Document_Abstract $document, string $property = Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER): array
    {
        $result = parent::documentNumberConfigOverride($document, $property);
        if (!$document->isBooked()) {
            $result['skip'] = true;
        }
        return $result;
    }

    public function documentProformaNumberConfigOverride(Sales_Model_Document_Abstract $document): array
    {
        $result = parent::documentNumberConfigOverride($document, Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER);
        if ($document->isBooked()) {
            $result['skip'] = true;
        }
        return $result;
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (!$_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER} = $_oldRecord->{Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER};
        }
    }

    /**
     * @param Sales_Model_Document_Credit $_record
     * @param Sales_Model_Document_Credit|null $_oldRecord
     */
    protected function _setAutoincrementValues(Tinebase_Record_Interface $_record, ?\Tinebase_Record_Interface $_oldRecord = null)
    {
        if ($_record->isBooked() && !$_oldRecord->isBooked() &&
                $_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_NUMBER} ===
                $_oldRecord->{Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_NUMBER} = null;
            $_oldRecord->{Sales_Model_Document_Credit::FLD_DOCUMENT_NUMBER} = null;
        }
        parent::_setAutoincrementValues($_record, $_oldRecord);

        if (!$_record->isBooked()) {
            $_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_NUMBER} =
                $_record->{Sales_Model_Document_Credit::FLD_DOCUMENT_PROFORMA_NUMBER};
        }
    }
}
