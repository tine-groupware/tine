<?php declare(strict_types=1);

/**
 * Delivery Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Delivery Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Delivery extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Delivery::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Delivery::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Delivery::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_DELIVERY_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_DELIVERY_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS;
        $this->_oldRecordBookWriteableFields = [
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS,
            Sales_Model_Document_Delivery::FLD_COST_CENTER_ID,
            Sales_Model_Document_Delivery::FLD_COST_BEARER_ID,
            Sales_Model_Document_Delivery::FLD_DESCRIPTION,
            Sales_Model_Document_Delivery::FLD_REVERSAL_STATUS,
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [
            Sales_Model_Document_Delivery::FLD_CUSTOMER_ID,
            Sales_Model_Document_Delivery::FLD_RECIPIENT_ID,
        ];
        parent::__construct();
    }

    /**
     * @param Sales_Model_Document_Delivery $document
     * @return array
     */
    public function documentNumberConfigOverride(Sales_Model_Document_Abstract $document)
    {
        if (!$document->isBooked()) {
            return ['skip' => true];
        }
        return [];
    }

    /**
     * @param Sales_Model_Document_Delivery $document
     * @return array
     */
    public function documentProformaNumberConfigOverride(Sales_Model_Document_Abstract $document)
    {
        if ($document->isBooked()) {
            return ['skip' => true];
        }
        return [];
    }

    /**
     * @param Sales_Model_Document_Delivery $_record
     * @param Sales_Model_Document_Delivery|null $_oldRecord
     */
    protected function _setAutoincrementValues(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord = null)
    {
        if ($_record->isBooked() && !$_oldRecord->isBooked() &&
                $_record->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER} ===
                $_oldRecord->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER} = null;
            $_oldRecord->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER} = null;
        }
        parent::_setAutoincrementValues($_record, $_oldRecord);

        if (!$_record->isBooked()) {
            $_record->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER} =
                $_record->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER};
        }
    }
}
