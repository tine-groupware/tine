<?php declare(strict_types=1);

/**
 * Invoice Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Invoice Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Invoice extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_adminGrant = Sales_Model_DivisionGrants::GRANT_ADMIN_DOCUMENT_INVOICE;
    protected static $_readGrant = Sales_Model_DivisionGrants::GRANT_READ_DOCUMENT_INVOICE;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Invoice::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Invoice::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Invoice::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;

        $this->_documentStatusConfig = Sales_Config::DOCUMENT_INVOICE_STATUS;
        $this->_documentStatusTransitionConfig = Sales_Config::DOCUMENT_INVOICE_STATUS_TRANSITIONS;
        $this->_documentStatusField = Sales_Model_Document_Invoice::FLD_INVOICE_STATUS;
        $this->_oldRecordBookWriteableFields = [
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS,
            Sales_Model_Document_Invoice::FLD_EVAL_DIM_COST_CENTER,
            Sales_Model_Document_Invoice::FLD_EVAL_DIM_COST_BEARER,
            Sales_Model_Document_Invoice::FLD_DESCRIPTION,
            Sales_Model_Document_Invoice::FLD_REVERSAL_STATUS,
            'tags', 'attachments', 'relations',
        ];
        $this->_bookRecordRequiredFields = [
            Sales_Model_Document_Invoice::FLD_CUSTOMER_ID,
            Sales_Model_Document_Invoice::FLD_RECIPIENT_ID,
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
        $result = parent::documentNumberConfigOverride($document, Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER);
        if ($document->isBooked()) {
            $result['skip'] = true;
        }
        return $result;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        if ($_record->isBooked() &&
                (!$_record->{Sales_Model_Document_Invoice::FLD_POSITIONS} instanceof Tinebase_Record_RecordSet ||
                null === $_record->{Sales_Model_Document_Invoice::FLD_POSITIONS}->find(Sales_Model_DocumentPosition_Invoice::FLD_REVERSAL, true))) {
            throw new Tinebase_Exception_Record_Validation('document must not be booked');
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (!$_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} = $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER};
        }
    }

    /**
     * @param Sales_Model_Document_Invoice $_record
     * @param Sales_Model_Document_Invoice|null $_oldRecord
     */
    protected function _setAutoincrementValues(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_oldRecord = null)
    {
        if ($_oldRecord && $_record->isBooked() && !$_oldRecord->isBooked() &&
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} ===
                $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} = null;
            $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} = null;
        }
        parent::_setAutoincrementValues($_record, $_oldRecord);

        if (!$_record->isBooked()) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} =
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER};
        } elseif (null === $_oldRecord) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} =
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER};
        }
    }

    public function _inspectAfterSetRelatedDataCreate($createdRecord, $record)
    {
        parent::_inspectAfterSetRelatedDataCreate($createdRecord, $record);
        $this->createEInvoiceAttachment($createdRecord);
    }

    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);
        $this->createEInvoiceAttachment($updatedRecord, $currentRecord);
    }

    protected function createEInvoiceAttachment(Sales_Model_Document_Invoice $record, ?Sales_Model_Document_Invoice $oldRecord = null): void
    {
        if (!$record->isBooked() || ($oldRecord && $oldRecord->isBooked())) {
            return;
        }

        $stream = null;
        try {
            if (!($stream = fopen('php://temp', 'r+'))) {
                throw new Tinebase_Exception('cant create temp stream');
            }
            try {
                fwrite($stream, $record->toUbl());
            } catch (Throwable $t) {
                Tinebase_Exception::log($t);
                if (Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC}) {
                    throw $t;
                }
                return;
            }
            rewind($stream);

            if (Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC}) {
                try {
                    (new Sales_EDocument_Service_Validate())->validateXRechnung($stream);
                } catch (Tinebase_Exception_Record_Validation $e) {
                    throw new Tinebase_Exception_SystemGeneric('XRechnung Validierung fehlgeschlagen: ' . PHP_EOL . $e->getMessage());
                }
                rewind($stream); // redundant, but cheap and good for readability
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' edocument validation service not configured, skipping! created xrechnung is not validated!');
            }

            /*$baseName = 'xrechnung';
            $extention = '.xml';
            $attachmentName = $baseName . $extention;
            $count = 0;
            while (null !== $record->attachments->find('name', $attachmentName)) {
                $attachmentName = $baseName . ' (' . (++$count) . ')' . $extention;
            }*/
            $attachmentName = str_replace('/', '-', $record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . '-xrechnung.xml');
            if (null !== ($remove = $record->attachments?->find('name', $attachmentName))) {
                $record->attachments->removeRecord($remove);
                Tinebase_FileSystem_RecordAttachments::getInstance()->setRecordAttachments($record);
            }
            Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment($record, $attachmentName, $stream);
            Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
        } finally {
            if ($stream) {
                @fclose($stream);
            }
        }
    }
}
