<?php declare(strict_types=1);

/**
 * Invoice Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Sales_Model_Document_Invoice as SMD_Invoice;
use Tinebase_Model_Filter_Abstract as TMFA;
use Tinebase_Model_Filter_FilterGroup as TMFFG;

/**
 * Invoice Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 *
 * @extends Sales_Controller_Document_Abstract<Sales_Model_Document_Invoice>
 */
class Sales_Controller_Document_Invoice extends Sales_Controller_Document_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Sales_Controller_Document_Invoice> */
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
        $this->_backend = new Sales_Backend_Document_Invoice();
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
            Sales_Model_Document_Invoice::FLD_REVERSED_STATUS,
            Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY,
            Sales_Model_Document_Abstract::FLD_PURCHASE_ORDER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_BUYER_REFERENCE,
            Sales_Model_Document_Abstract::FLD_PROJECT_REFERENCE,
            Sales_Model_Document_Abstract::FLD_CONTACT_ID,
            Sales_Model_Document_Abstract::FLD_CONTRACT_NUMBER,
            Sales_Model_Document_Invoice::FLD_PAYMENT_REMINDERS,
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

    /**
     * @param SMD_Invoice $_record
     * @param SMD_Invoice $_oldRecord
     * @return void
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (!$_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} = $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER};
        }

        $newlyReversed = Sales_Config::DOCUMENT_REVERSED_STATUS_REVERSED === $_record->{Sales_Model_Document_Abstract::FLD_REVERSED_STATUS} &&
            Sales_Config::DOCUMENT_REVERSED_STATUS_REVERSED !== $_oldRecord->{Sales_Model_Document_Abstract::FLD_REVERSED_STATUS};

        if (null === $_record->relations && ($newlyReversed || $_record->isBooked() !== $_oldRecord->isBooked())) {
            $_record->relations = Tinebase_Relations::getInstance()->getRelations(get_class($_record), 'Sql', $_record->getId());
        }

        if (!$newlyReversed && $_record->isBooked() && !$_oldRecord->isBooked()) {
            if (! empty($_record->relations)) {
                foreach($_record->relations as $relation) {
                    if (in_array('Sales_Model_Accountable_Interface', class_implements($relation['related_model']))) {

                        if (is_array($relation['related_record'])) {
                            $rr = new $relation['related_model']($relation['related_record']);
                        } else {
                            $rr = $relation['related_record'];
                        }

                        /** @var Tinebase_Record_Interface&Sales_Model_Accountable_Interface $rr */
                        $rr->clearBillables($_record);

                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing billables ' . print_r($rr->toArray(), true));
                        }
                    }
                }
            }
        } elseif ($newlyReversed || (!$_record->isBooked() && $_oldRecord->isBooked())) {
            if (! empty($_record->relations)) {
                foreach($_record->relations as $relation) {
                    if (in_array('Sales_Model_Accountable_Interface', class_implements($relation['related_model']))) {

                        if (is_array($relation['related_record'])) {
                            $rr = new $relation['related_model']($relation['related_record']);
                        } else {
                            $rr = $relation['related_record'];
                        }

                        /** @var Tinebase_Record_Interface&Sales_Model_Accountable_Interface $rr */
                        $rr->unClearBillables($_record);

                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' UnClearing billables ' . print_r($rr->toArray(), true));
                        }
                    }
                }
            }
        }

        if ($newlyReversed) {
            $this->unrollInvoice(new Tinebase_Record_RecordSet($this->_modelName, [$_record]));
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

    protected function _inspectDelete(array $_ids)
    {
        $_ids = parent::_inspectDelete($_ids);
        $invoices = $this->_backend->getMultiple($_ids);
        $this->unrollInvoice($invoices);
        return $invoices;
    }

    /**
     * @param Tinebase_Record_RecordSet<SMD_Invoice> $invoices
     */
    protected function unrollInvoice(Tinebase_Record_RecordSet $invoices): void
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' unrollInvoices ' . print_r($invoices->getArrayOfIds(), true));
        }

        $_cachedProducts = new Tinebase_Record_RecordSet(Sales_Model_Product::class);
        $_ids = $invoices->getArrayOfIds();

        foreach ($invoices as $invoice) {
            if (null === $invoice->{SMD_Invoice::FLD_AUTO_INVOICE_BILLING_DATE}) {
                continue;
            }
            // find invoices after this one:
            if ($invoice->{SMD_Invoice::FLD_CONTRACT_ID}) {
                foreach ($this->search(TMFFG::getFilterForModel($this->_modelName, [
                    [TMFA::FIELD => SMD_Invoice::FLD_CONTRACT_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $invoice->{SMD_Invoice::FLD_CONTRACT_ID}],
                    [TMFA::FIELD => SMD_Invoice::FLD_AUTO_INVOICE_BILLING_DATE, TMFA::OPERATOR => 'after', TMFA::VALUE => $invoice->{SMD_Invoice::FLD_AUTO_INVOICE_BILLING_DATE}],
                    [TMFA::FIELD => SMD_Invoice::FLD_REVERSED_STATUS, TMFA::OPERATOR => 'not', TMFA::VALUE => Sales_Config::DOCUMENT_REVERSED_STATUS_REVERSED],
                    [TMFA::FIELD => SMD_Invoice::FLD_REVERSAL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => false],
                ]), _onlyIds: true) as $futureId) {
                    if (!$invoices->offsetExists($futureId)) {
                        throw new Sales_Exception_DeletePreviousInvoice();
                    }
                }
            }

            // remove invoice_id from billables
            $filter = new Sales_Model_InvoicePositionFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'document_id', 'operator' => 'equals', 'value' => $invoice->getId())));
            $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);

            $allModels = array_unique($invoicePositions->model);

            foreach ($allModels as $model) {

                if ($model == 'Sales_Model_ProductAggregate') {
                    continue;
                }

                $billableControllerName = $model::getBillableControllerName();
                $billableFilterName = $model::getBillableFilterName();

                $filterInstance = new $billableFilterName(array());
                $filterInstance->addFilter(new Tinebase_Model_Filter_Text(
                    array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())
                ));


                // TODO move this to Timetracker (as on delete hook/fn)
                if ($model == 'Timetracker_Model_Timeaccount') {
                    // prevent throwing of Tinebase_Exception_Confirmation for closed accounts (see \Timetracker_Controller_Timesheet::_checkGrant)
                    $billableControllerName::getInstance()->setRequestContext([
                        'skipClosedCheck' => true,
                    ]);
                    $billableControllerName::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                    $billableControllerName::getInstance()->setRequestContext([]);

                    // set invoice ids of the timeaccounts
                    $filterInstance = new Timetracker_Model_TimeaccountFilter(array());
                    $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())));
                    $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'status', 'operator' => 'equals', 'value' => Timetracker_Model_Timeaccount::STATUS_BILLED)));
                    $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'cleared_at', 'operator' => 'isnull', 'value' => '')));

                    Timetracker_Controller_Timeaccount::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL, 'status' => Timetracker_Model_Timeaccount::STATUS_TO_BILL));
                } else {
                    $billableControllerName::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                }
            }

            // set last_autobill a period back
            if ($invoice->{SMD_Invoice::FLD_CONTRACT_ID}) {
                $contract = Sales_Controller_Contract::getInstance()->get($invoice->getIdFromProperty(SMD_Invoice::FLD_CONTRACT_ID));
                //find the month of each productAggregate we have to set it back to
                $undoProductAggregates = [];
                $paController = Sales_Controller_ProductAggregate::getInstance();

                foreach ($invoicePositions as $inPos) {
                    if ($inPos->model != 'Sales_Model_ProductAggregate')
                        continue;

                    //if we didnt find a month for the productAggreagte yet or if the month found is greater than the one we have at hands
                    if (!isset($undoProductAggregates[$inPos->accountable_id]) || strcmp($undoProductAggregates[$inPos->accountable_id], $inPos->month) > 0) {
                        $undoProductAggregates[$inPos->accountable_id] = $inPos->month;
                    }
                }

                $isLastInvoice = false;
                if (0 === $this->_backend->searchCount(TMFFG::getFilterForModel($this->_modelName,
                        [
                            [TMFA::FIELD => SMD_Invoice::FLD_CONTRACT_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $invoice->getIdFromProperty(SMD_Invoice::FLD_CONTRACT_ID)],
                            [TMFA::FIELD => SMD_Invoice::ID, TMFA::OPERATOR => 'notin', TMFA::VALUE => $_ids],
                            [TMFA::FIELD => SMD_Invoice::FLD_REVERSED_STATUS, TMFA::OPERATOR => 'not', TMFA::VALUE => Sales_Config::DOCUMENT_REVERSED_STATUS_REVERSED],
                            [TMFA::FIELD => SMD_Invoice::FLD_REVERSAL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => false],
                        ]
                    ))) {
                    $isLastInvoice = true;
                }

                $filter = new Sales_Model_ProductAggregateFilter(array());
                $filter->addFilter(new Tinebase_Model_Filter_Text(
                    array('field' => 'contract_id', 'operator' => 'equals', 'value' => $invoice->getIdFromProperty(SMD_Invoice::FLD_CONTRACT_ID))
                ));

                foreach (Sales_Controller_ProductAggregate::getInstance()->search($filter) as $productAggregate) {

                    if (!$productAggregate->last_autobill)
                        continue;
                    if ($isLastInvoice) {
                        $productAggregate->last_autobill = NULL;
                    } elseif (!isset($undoProductAggregates[$productAggregate->id])) {
                        $product = $_cachedProducts->getById($productAggregate->product_id);
                        if (!$product) {
                            $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
                            $_cachedProducts->addRecord($product);
                        }

                        // $product->accountable == 'Sales_Model_Product'
                        if ($invoice->{SMD_Invoice::FLD_AUTO_INVOICE_BILLING_DATE}->isLater($productAggregate->last_autobill)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' skipping PA: ' . $invoice->{SMD_Invoice::FLD_AUTO_INVOICE_BILLING_DATE}->toString() . ' ' . $productAggregate->last_autobill->toString());
                            }
                            continue;
                        }

                        $productAggregate->last_autobill->subMonth($productAggregate->interval);
                    } else {

                        $productAggregate->last_autobill = new Tinebase_DateTime($undoProductAggregates[$productAggregate->id] . '-01 00:00:00', 'UTC');
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' new last autobill: ' . $productAggregate->last_autobill->toString());
                        }
                        if ($productAggregate->billing_point == 'begin') {
                            $productAggregate->last_autobill->subMonth($productAggregate->interval);
                        }
                        if ($productAggregate->start_date && $productAggregate->last_autobill < $productAggregate->start_date) {
                            if ($productAggregate->last_autobill < $productAggregate->start_date || ($productAggregate->billing_point == 'end' && $productAggregate->last_autobill == $productAggregate->start_date)) {
                                $productAggregate->last_autobill = NULL;
                            }
                        }
                    }
                    $paController->update($productAggregate);
                }
            }
        }
    }

    /**
     * @param Sales_Model_Document_Invoice $_record
     * @param Sales_Model_Document_Invoice|null $_oldRecord
     */
    protected function _setAutoincrementValues(Tinebase_Record_Interface $_record, ?\Tinebase_Record_Interface $_oldRecord = null)
    {
        if ($_oldRecord && $_record->isBooked() && !$_oldRecord->isBooked() &&
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} ===
                $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} = null;
            $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} = null;
        }

        if (!$_record->isBooked() && null !== $_oldRecord &&
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} !== $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} &&
                $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} === $_oldRecord->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) {
            $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} = $_record->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER};
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
