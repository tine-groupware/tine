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
            Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS,
            Sales_Model_Document_Invoice::FLD_EVAL_DIM_COST_CENTER,
            Sales_Model_Document_Invoice::FLD_EVAL_DIM_COST_BEARER,
            Sales_Model_Document_Invoice::FLD_DESCRIPTION,
            Sales_Model_Document_Invoice::FLD_REVERSAL_STATUS,
            Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY,
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

    protected function _inspectCategoryDebitor(Sales_Model_Document_Abstract $_record)
    {
        parent::_inspectCategoryDebitor($_record);

        if ($_record->{Sales_Model_Document_Invoice::FLD_PAYMENT_MEANS}->count() > 1) {
            $_record->{Sales_Model_Document_Invoice::FLD_PAYMENT_MEANS} =
                $_record->{Sales_Model_Document_Invoice::FLD_PAYMENT_MEANS}->filter(Sales_Model_PaymentMeans::FLD_DEFAULT, true);
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

    public function createEDocument(string $documentId): Sales_Model_Document_Invoice
    {
        /** @var Sales_Model_Document_Invoice $document */
        $document = $this->get($documentId);
        $this->_createEDocument($document);
        return $document;
    }

    protected function _createEDocument(Sales_Model_Document_Invoice $record, ?Sales_Model_Document_Invoice $oldRecord = null): ?Sales_Model_Document_Abstract
    {
        if (!$record->isBooked() || ($oldRecord && $oldRecord->isBooked())) {
            return null;
        }

        $stream = null;
        $attachmentName = (new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(Sales_Config::APP_NAME), [
            Tinebase_Twig::TWIG_LOADER =>
                new Tinebase_Twig_CallBackLoader(__METHOD__, time() - 1, fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_NAME_TMPL}),
            Tinebase_Twig::TWIG_AUTOESCAPE => false,
        ]))->load(__METHOD__)->render([
            'document' => $record,
        ]);

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
                return null;
            }
            rewind($stream);

            if (Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC}) {
                $validationResult = (new Sales_EDocument_Service_Validate())->validateXRechnung($stream);
                rewind($stream); // redundant, but cheap and good for readability
                if (!empty($validationResult['errors'])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' edocument validation service reported errors: ' . print_r($validationResult['errors'], true));
                    }
                    throw new Tinebase_Exception_HtmlReport($validationResult['html'], 'Validation Errors');
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' edocument validation service not configured, skipping! created xrechnung is not validated!');
            }

            if (($remove = $record->attachments->filter(fn($rec) => $attachmentName === $rec->name || str_ends_with($rec->name, '.validation.html')))->count() > 0) {
                if (Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL} && ($node = $remove->find('name', $attachmentName))) {
                    $replaceName = (new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(Sales_Config::APP_NAME), [
                        Tinebase_Twig::TWIG_LOADER =>
                            new Tinebase_Twig_CallBackLoader(Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL, time() - 1, fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL}),
                        Tinebase_Twig::TWIG_AUTOESCAPE => false,
                    ]))->load(Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL)->render([
                        'document' => $record,
                        'node' => $node,
                        'date' => Tinebase_DateTime::now()->format('Y-m-d'),
                    ]);
                    $path = Tinebase_FileSystem::getInstance()->getPathOfNode($node);
                    array_walk($path, fn(&$path) => $path = $path['name']);
                    $oldPath = '/' . implode('/', $path);
                    array_pop($path);
                    Tinebase_FileSystem::getInstance()->rename($oldPath, '/'. join('/', $path) . '/' . $replaceName);
                    Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
                    $remove = $record->attachments->filter(fn($rec) => str_ends_with($rec->name, '.validation.html'));
                }
                $record->attachments->removeRecords($remove);
                Tinebase_FileSystem_RecordAttachments::getInstance()->setRecordAttachments($record);
            }
            $node = Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment($record, $attachmentName, $stream);
            Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);

            $attachmentId = $node->getId();
            if (!$record->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}) {
                $record->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS} = new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class, []);
            }
            if (($attachedDocument = $record->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $attachmentId))) {
                $attachedDocument->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ} = $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1;
            } else {
                $record->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->addRecord(new Sales_Model_Document_AttachedDocument([
                    Sales_Model_Document_AttachedDocument::FLD_TYPE => Sales_Model_Document_AttachedDocument::TYPE_EDOCUMENT,
                    Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $attachmentId,
                    Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ => $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1,
                ], true));
            }
            $record->getCurrentAttachedDocuments()
                ->filter(fn ($rec) => $rec->{Sales_Model_Document_AttachedDocument::FLD_TYPE} === Sales_Model_Document_AttachedDocument::TYPE_PAPERSLIP)
                ->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ} = $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1;

            $updatedRecord = $this->update($record);
            $record->attachments = $updatedRecord->attachments;
            $record->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS} = $updatedRecord->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS};
            $record->seq = $updatedRecord->seq;
            $record->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} = $updatedRecord->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ};
            $record->last_modified_time = $updatedRecord->last_modified_time;
            return $updatedRecord;

        } catch (Tinebase_Exception_HtmlReport $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' ' . $e->getMessage());
            }

            Tinebase_TransactionManager::getInstance()->rollBack();
            $transaction = Tinebase_RAII::getTransactionManagerRAII();
            /** @var Sales_Model_Invoice $record */
            $record = $this->get($record->getId());

            if ($stream) {
                @fclose($stream);
            }
            $stream = fopen('php://temp', 'w+');
            fwrite($stream, $e->getHtml());
            rewind($stream);
            if (($remove = $record->attachments->filter(fn($rec) => $attachmentName === $rec->name || str_ends_with($rec->name, '.validation.html')))->count() > 0) {
                $record->attachments->removeRecords($remove);
            }

            $record->attachments->addRecord(new Tinebase_Model_Tree_Node([
                'name' => $attachmentName . '.validation.html',
                'tempFile' => $stream,
            ], true));

            $this->update($record);
            $transaction->release();

            throw $e;
        } finally {
            if ($stream) {
                @fclose($stream);
            }
        }
    }
}
