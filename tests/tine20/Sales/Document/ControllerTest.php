<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Sales_Model_Document_Order as SMDOrder;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Test class for Sales_Controller_Document_*
 */
class Sales_Document_ControllerTest extends Sales_Document_Abstract
{
    public function testExpanderFilter()
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_ACCEPTED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_SHARED_INVOICE => true,
            Sales_Model_Document_Order::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                ], true),
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 2',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                ], true),
            ],
        ]));

        $order = Sales_Controller_Document_Order::getInstance()->search($filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Order::class, [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $order->getId()],
            ]), _getRelations: new Tinebase_Record_Expander(Sales_Model_Document_Order::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    Sales_Model_Document_Order::FLD_POSITIONS => [
                        Tinebase_Record_Expander::EXPANDER_USE_FILTER => [
                            [TMFA::FIELD => Sales_Model_DocumentPosition_Order::FLD_TITLE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'pos 2'],
                        ],
                    ],
                ],
            ]))->getFirstRecord();

        $this->assertNotNull($order);
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $order->{Sales_Model_Document_Order::FLD_POSITIONS});
        $this->assertSame(1, $order->{Sales_Model_Document_Order::FLD_POSITIONS}->count());
        $this->assertSame('pos 2', $order->{Sales_Model_Document_Order::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Order::FLD_TITLE});

        $order = Sales_Controller_Document_Order::getInstance()->search($filter)->getFirstRecord();
        $this->assertNotNull($order);
        $this->assertNull($order->{Sales_Model_Document_Order::FLD_POSITIONS});
    }

    public function testReInvoiceAfterDelete()
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();

        $order1 = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_ACCEPTED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_SHARED_INVOICE => true,
            Sales_Model_Document_Order::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                ], true)
            ],
        ]));

        $order2 = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_ACCEPTED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_SHARED_INVOICE => true,
            Sales_Model_Document_Order::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                ], true)
            ],
        ]));

        $invoice = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Order::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order1,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Order::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order2,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));

        $oldSkip = Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        try {
            Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
                Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
                Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                    new Sales_Model_Document_TransitionSource([
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                            Sales_Model_Document_Order::class,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order1,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                    ]),
                    new Sales_Model_Document_TransitionSource([
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                            Sales_Model_Document_Order::class,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order2,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                    ]),
                ]
            ]));
            $this->fail('creating invoice on already invoiced order should not be possible');
        } catch (Tinebase_Exception_SystemGeneric) {
        } finally {
            Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack($oldSkip);
        }

        Sales_Controller_Document_Invoice::getInstance()->delete($invoice);
        Tinebase_Record_Expander_DataRequest::clearCache();

        $order1 = Sales_Controller_Document_Order::getInstance()->get($order1->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order1->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});
        $order2 = Sales_Controller_Document_Order::getInstance()->get($order2->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order2->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});

        $transition = new Sales_Model_Document_Transition([], true);
        $transitionData = (new Sales_Frontend_Json)->getMatchingSharedOrderDocumentTransition($order1->getId(), Sales_Model_Document_Invoice::class);
        $transition->setFromJsonInUsersTimezone($transitionData);
        Sales_Controller_Document_Abstract::executeTransition($transition);
    }

    public function testReInvoiceAfterStorno()
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_ACCEPTED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Order::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                ], true)
            ],
        ]));
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});

        $invoice = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Order::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));

        $order = Sales_Controller_Document_Order::getInstance()->get($order->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});

        $oldSkip = Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        try {
            Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
                Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
                Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                    new Sales_Model_Document_TransitionSource([
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                            Sales_Model_Document_Order::class,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                        Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                    ]),
                ]
            ]));
            $this->fail('creating invoice on already invoiced order should not be possible');
        } catch (Tinebase_Exception_SystemGeneric) {
        } finally {
            Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack($oldSkip);
        }

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        $order = Sales_Controller_Document_Order::getInstance()->get($order->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});

        Tinebase_Record_Expander_DataRequest::clearCache();
        $storno = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Invoice::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $invoice,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                    Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL => true,
                ]),
            ]
        ]));

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(Sales_Config::DOCUMENT_REVERSAL_STATUS_REVERSED, $invoice->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS});

        $order = Sales_Controller_Document_Order::getInstance()->get($order->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});

        Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Order::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));

        $order = Sales_Controller_Document_Order::getInstance()->get($order->getId());
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});
    }

    public function testFailedEmailDispatch()
    {

        if (null === ($smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP))
            || null === ($imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP))) {
            $this->markTestSkipped('email needs to be configured for test');
            // TODO FIXME is that so? create subset of test to work without email?
        }

        Tinebase_TransactionManager::getInstance()->rollBack();

        $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount(Tinebase_Core::getUser());
        Felamimail_Controller_Cache_Folder::getInstance()->update($account);
        $inbox = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account, 'INBOX');
        Felamimail_Controller_Cache_Message::getInstance()->updateCache($inbox, 10, getrandmax()); //  TODO FIXME better use update flag -1/0 or such

        $imapBackend = Felamimail_Backend_ImapFactory::factory($account);
        $imapBackend->selectFolder('INBOX');
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
            ['field' => 'folder_id', 'operator' => 'equals', 'value' => $inbox->getId()],
        ]);
        foreach (Felamimail_Controller_Message::getInstance()->search($filter) as $msg) {
            $imapBackend->removeMessage($msg->messageuid);
            Felamimail_Controller_Message::getInstance()->delete($msg);
        }

        $testCredentials = TestServer::getInstance()->getTestCredentials();
        if (null === ($dispatchFMAccount = Felamimail_Controller_Account::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                [TMFA::FIELD => 'email', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'dispatch@' . TestServer::getPrimaryMailDomain()]
            ]))->getFirstRecord())) {
            $dispatchFMAccount = Felamimail_Controller_Account::getInstance()->create(new Felamimail_Model_Account([
                'name' => 'unittest sales dispatch account',
                'email' => 'dispatch@' . TestServer::getPrimaryMailDomain(),
                'type' => Tinebase_EmailUser_Model_Account::TYPE_SHARED_INTERNAL,
                'user_id' => Tinebase_Core::getUser()->getId(),
                'host' => $imapConfig->hostname,
                'ssl' => $imapConfig->ssl,
                'port' => $imapConfig->port,
                'user' => $testCredentials['username'],
                'password' => $testCredentials['password'],
                'smtp_host' => $smtpConfig->hostname,
                'smtp_ssl' => $smtpConfig->ssl,
                'smtp_auth' => $smtpConfig->auth,
                'smtp_port' => $smtpConfig->port,
                'smtp_user' => $testCredentials['username'],
                'smtp_password' => $testCredentials['password'],
            ]));
        }

        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        $division->{Sales_Model_Division::FLD_DISPATCH_FM_ACCOUNT_ID} = $dispatchFMAccount->getId();
        Sales_Controller_Division::getInstance()->update($division);

        $invoice = $this->_createInvoice();
        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        if (!($oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
        }
        $previewRaii = new Tinebase_RAII(fn () => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc);
        Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
        $exportPdfRaii = new Tinebase_RAII(fn () => Sales_Export_DocumentPdf::$previewService = null);

        $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
        $app->status = Tinebase_Application::DISABLED;
        Tinebase_Application::getInstance()->updateApplication($app);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(0, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());

        (new Sales_Frontend_Json)->dispatchDocument(Sales_Model_Document_Invoice::class, $invoice->getId());

        unset($previewRaii);
        unset($exportPdfRaii);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());
        $this->assertSame(1, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->filter(Sales_Model_Document_DispatchHistory::FLD_TYPE, Sales_Model_Document_DispatchHistory::DH_TYPE_FAIL)->count());
    }

    public function testAttachmentNames(): void
    {
        $customer = $this->_createCustomer();
        $product = $this->_createProduct();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Invoice::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
            Sales_Model_Document_Invoice::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Invoice::FLD_POSITIONS => new Tinebase_Record_RecordSet(Sales_Model_DocumentPosition_Invoice::class, [
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product->getId(),
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                ], true),
            ])
        ]));

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        if (!($oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
        }
        $previewRaii = new Tinebase_RAII(fn () => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc);
        Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
        $exportPdfRaii = new Tinebase_RAII(fn () => Sales_Export_DocumentPdf::$previewService = null);

        $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
        $app->status = Tinebase_Application::DISABLED;
        Tinebase_Application::getInstance()->updateApplication($app);

        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(1, $invoice->attachments->count());
        $this->assertSame(Tinebase_DateTime::today()->format('Y-m-d')
            . '_Proforma-' . $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} . '.pdf', ($node = $invoice->attachments->getFirstRecord())->name);
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->getFirstRecord()->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});
        $this->assertSame($node->getId(), $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->getFirstRecord()->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID});

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(2, $invoice->attachments->count());
        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());
        $this->assertNotNull($attachedDoc = $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $node->getId()));
        $this->assertNotSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});
        $this->assertSame(Tinebase_DateTime::today()->format('Y-m-d')
            . '_Proforma-' . $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER} . '.pdf', ($proformaNode = $invoice->attachments->getById($node->getId()))->name);
        $this->assertSame(Tinebase_DateTime::today()->format('Y-m-d') . '_'
            . $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . '.pdf', ($node = $invoice->attachments->find(fn($rec) => $rec->getId() !== $node->getId(), null))?->name);
        $this->assertNotNull($attachedDoc = $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $node->getId()));
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});

        $modLogs = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(Sales_Config::APP_NAME, $invoice->getId())->sort('seq', 'DESC');
        $diff = new Tinebase_Model_Diff(json_decode($modLogs->getFirstRecord()->new_value, true));
        $this->assertArrayNotHasKey(Sales_Model_Document_Abstract::FLD_PAYMENT_MEANS, $diff->diff);

        // test replacement of attachment
        $oldPaperReplace = Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL};
        $oldPaperRaii = new Tinebase_RAII(fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL} = $oldPaperReplace);
        Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL} = '';
        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(2, $invoice->attachments->count());
        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());
        $this->assertGreaterThan($node->seq, $invoice->attachments->getById($attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID})->seq);
        $this->assertSame(Tinebase_DateTime::today()->format('Y-m-d') . '_'
            . $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . '.pdf', ($node = $invoice->attachments->find(fn($rec) => $rec->getId() !== $proformaNode->getId(), null))?->name);
        $this->assertNotNull($attachedDoc = $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $node->getId()));
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});

        // test rename of attachment
        Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL} = '{{ node.creation_time.toString() ~ "-" ~ node.name }}';
        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(3, $invoice->attachments->count());
        $this->assertSame(3, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());

        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(4, $invoice->attachments->count());
        $this->assertSame(4, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());

        // test replacement of attachment
        $oldEDocReplace = Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL};
        $oldEDocRaii = new Tinebase_RAII(fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL} = $oldEDocReplace);
        Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL} = '';
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(4, $invoice->attachments->count());
        $this->assertSame(4, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());

        // test rename of attachment
        Sales_Config::getInstance()->{Sales_Config::INVOICE_EDOCUMENT_RENAME_TMPL} = '{{ node.creation_time.toString() ~ "-" ~ node.name }}';
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(5, $invoice->attachments->count());
        $this->assertSame(5, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());

        unset($previewRaii);
        unset($exportPdfRaii);
        unset($oldPaperRaii);
        unset($oldEDocRaii);
    }

    public function testCustomDispatchDocument(): void
    {
        $customer = $this->_createCustomer();
        $customer->debitors->getFirstRecord()->{Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_TYPE} = Sales_Model_EDocument_Dispatch_Custom::class;
        $customer->debitors->getFirstRecord()->{Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_CONFIG} = new Sales_Model_EDocument_Dispatch_Custom([
            Sales_Model_EDocument_Dispatch_Custom::FLD_DISPATCH_CONFIGS => new Tinebase_Record_RecordSet(Sales_Model_EDocument_Dispatch_DynamicConfig::class, [
                new Sales_Model_EDocument_Dispatch_DynamicConfig([
                    Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_TYPE => Sales_Model_EDocument_Dispatch_Manual::class,
                    Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG => new Sales_Model_EDocument_Dispatch_Manual([
                        Sales_Model_EDocument_Dispatch_Manual::FLD_INSTRUCTIONS => 'do some of this and then that',
                        Sales_Model_EDocument_Dispatch_Manual::FLD_DOCUMENT_TYPES => new Tinebase_Record_RecordSet(Sales_Model_EDocument_Dispatch_DocumentType::class, [
                            new Sales_Model_EDocument_Dispatch_DocumentType([
                                Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_PAPERSLIP,
                            ]),
                        ]),
                    ]),
                ]),
            ]),
        ], true);
        $customer = Sales_Controller_Customer::getInstance()->update($customer);

        $product = $this->_createProduct();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Invoice::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
            Sales_Model_Document_Invoice::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Invoice::FLD_POSITIONS => new Tinebase_Record_RecordSet(Sales_Model_DocumentPosition_Invoice::class, [
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product->getId(),
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                ], true),
            ])
        ]));

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        if (!($oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
        }
        $previewRaii = new Tinebase_RAII(fn () => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc);
        Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
        $exportPdfRaii = new Tinebase_RAII(fn () => Sales_Export_DocumentPdf::$previewService = null);

        $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
        $app->status = Tinebase_Application::DISABLED;
        Tinebase_Application::getInstance()->updateApplication($app);

        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());

        unset($previewRaii);
        unset($exportPdfRaii);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());

        $this->assertSame(1, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());
        $this->assertSame(0, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());
        foreach ($invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS} as $attachedDoc) {
            $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_TYPE});
        }

        (new Sales_Frontend_Json)->dispatchDocument(Sales_Model_Document_Invoice::class, $invoice->getId());

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(3, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());
        $this->assertSame(1, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->filter(Sales_Model_Document_DispatchHistory::FLD_TYPE, Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS)->count());
    }

    public static function onCommitCallback(): void
    {
        throw new Tinebase_Exception();
    }

    public function testDispatchDocument()
    {
        if (null === ($smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP))
            || null === ($imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP))) {
            $this->markTestSkipped('email needs to be configured for test');
            // TODO FIXME is that so? create subset of test to work without email?
        }

        $this->_testNeedsTransaction();

        $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount(Tinebase_Core::getUser());
        Felamimail_Controller_Cache_Folder::getInstance()->update($account);
        $inbox = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account, 'INBOX');
        Felamimail_Controller_Cache_Message::getInstance()->updateCache($inbox, 10, getrandmax()); //  TODO FIXME better use update flag -1/0 or such

        $imapBackend = Felamimail_Backend_ImapFactory::factory($account);
        $imapBackend->selectFolder('INBOX');
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
            ['field' => 'folder_id', 'operator' => 'equals', 'value' => $inbox->getId()],
        ]);
        foreach (Felamimail_Controller_Message::getInstance()->search($filter) as $msg) {
            $imapBackend->removeMessage($msg->messageuid);
            Felamimail_Controller_Message::getInstance()->delete($msg);
        }

        $dispatchEmailAddress = 'dispatch@' . TestServer::getPrimaryMailDomain();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
            [TMFA::FIELD => 'email', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $dispatchEmailAddress],
            [TMFA::FIELD => 'type', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_EmailUser_Model_Account::TYPE_SHARED_INTERNAL],
        ]);
        if (null === ($dispatchFMAccount = Admin_Controller_EmailAccount::getInstance()->search($filter)
                ->getFirstRecord())
        ) {
            $sharedAccount = \Admin_Frontend_Json_EmailAccountTest::getSharedAccountData(
                data: ['email' => $dispatchEmailAddress]
            );
            $adminJson = new Admin_Frontend_Json();
            $adminJson->saveEmailAccount($sharedAccount);
            $dispatchFMAccount = Admin_Controller_EmailAccount::getInstance()->search($filter)->getFirstRecord();
        }

        // TODO improve test by using a different user to test mail account ignore acl
        //      -> but user needs right to manage customers
        // Tinebase_Core::setUser($this->_personas['jmcblack']);

        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $customer = $this->_createCustomer();
        $customer->debitors->getFirstRecord()->{Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_TYPE} = Sales_Model_EDocument_Dispatch_Email::class;
        $customer->debitors->getFirstRecord()->{Sales_Model_Debitor::FLD_EDOCUMENT_DISPATCH_CONFIG} = new Sales_Model_EDocument_Dispatch_Email([
            Sales_Model_EDocument_Dispatch_Email::FLD_EMAIL => $this->_originalTestUser->accountEmailAddress,
            Sales_Model_EDocument_Dispatch_Email::FLD_EXPECTS_FEEDBACK => true,
            Sales_Model_EDocument_Dispatch_Email::FLD_DOCUMENT_TYPES => new Tinebase_Record_RecordSet(Sales_Model_EDocument_Dispatch_DocumentType::class, [
                new Sales_Model_EDocument_Dispatch_DocumentType([
                    Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_PAPERSLIP,
                ]),
                new Sales_Model_EDocument_Dispatch_DocumentType([
                    Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_EDOCUMENT,
                ]),
            ]),
        ], true);
        $customer = Sales_Controller_Customer::getInstance()->update($customer);


        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        $division->{Sales_Model_Division::FLD_DISPATCH_FM_ACCOUNT_ID} = $dispatchFMAccount->getId();
        Sales_Controller_Division::getInstance()->update($division);

        $product = $this->_createProduct();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Invoice::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Invoice::FLD_PAYMENT_TERMS => 10,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
            Sales_Model_Document_Invoice::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Invoice::FLD_POSITIONS => new Tinebase_Record_RecordSet(Sales_Model_DocumentPosition_Invoice::class, [
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product->getId(),
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                ], true),
            ])
        ]));

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        if (!($oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
        }
        $previewRaii = new Tinebase_RAII(fn () => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc);
        Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
        $exportPdfRaii = new Tinebase_RAII(fn () => Sales_Export_DocumentPdf::$previewService = null);

        $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
        $app->status = Tinebase_Application::DISABLED;
        Tinebase_Application::getInstance()->updateApplication($app);

        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());

        unset($previewRaii);
        unset($exportPdfRaii);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());

        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());
        $this->assertSame(0, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());
        foreach ($invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS} as $attachedDoc) {
            $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ}, $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_TYPE});
        }

        (new Sales_Frontend_Json)->dispatchDocument(Sales_Model_Document_Invoice::class, $invoice->getId());

        Felamimail_Controller_Cache_Folder::getInstance()->update($account);
        $inbox = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account, 'INBOX');
        Felamimail_Controller_Cache_Message::getInstance()->updateCache($inbox, 10, getrandmax()); // TODO FIXME better use update flag -1/0 or such

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
            ['field' => 'folder_id', 'operator' => 'equals', 'value' => $inbox->getId()],
        ]);
        $dispatchMsgs = Felamimail_Controller_Message::getInstance()->search($filter);
        $cleanMsgsRaii = new Tinebase_RAII(function() use($dispatchMsgs, $imapBackend) {
            foreach ($dispatchMsgs->messageuid as $uid) {
                $imapBackend->removeMessage($uid);
            }
        });
        $this->assertSame(1, $dispatchMsgs->count(), print_r($dispatchMsgs->subject, true));
        $locale = new Zend_Locale($invoice->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE});
        $t = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $locale);
        /** @var Felamimail_Model_Message $msg */
        $msg = Felamimail_Controller_Message::getInstance()->getCompleteMessage($dispatchMsgs->getFirstRecord());
        $this->assertStringStartsWith($t->_('Invoice') . ' ' . $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER}, $msg->subject);
        $this->assertStringContainsString($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER}, $msg->getPlainTextBody());

        unset($cleanMsgsRaii);

        Tinebase_TransactionManager::getInstance()->registerOnCommitCallback([static::class, 'onCommitCallback']);
        try {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->fail('unreachable');
        } catch (Tinebase_Exception) {
            $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        }

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH, $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS});
        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->count());
        $this->assertSame(1, ($dispatchHistory = $invoice->{Sales_Model_Document_Invoice::FLD_DISPATCH_HISTORY}->find(Sales_Model_Document_DispatchHistory::FLD_TYPE, Sales_Model_Document_DispatchHistory::DH_TYPE_WAIT_FOR_FEEDBACK))?->attachments->count());
        $this->assertSame('email.eml', $dispatchHistory->attachments->getFirstRecord()->name);
        $this->assertArrayHasKey('sentMsgId', $dispatchHistory->xprops());
        $this->assertSame($msg->message_id, $dispatchHistory->xprops()['sentMsgId']);
        $this->assertSame($dispatchFMAccount->getId(), $dispatchHistory->xprops()['fmAccountId'] ?? null);

        // create reply
        $unitTestAccount = Felamimail_Controller_Account::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class,  [
                ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_SYSTEM],
                ['field' => 'user_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
            ]
        ))->getFirstRecord();
        $msg = new Felamimail_Model_Message([
            'account_id' => $unitTestAccount->getId(),
            'subject' => 'dispatch reply',
            'to' => 'dispatch@' . TestServer::getPrimaryMailDomain(),
            'body' => 'unittest',
            'headers' => [
                'X-zre-state' => 'Accepted',
                'In-Reply-to' => $dispatchHistory->xprops()['sentMsgId'],
            ],
        ], true);

        Felamimail_Controller_Message_Send::getInstance()->sendMessage($msg);

        Felamimail_Controller_Cache_Folder::getInstance()->update($dispatchFMAccount);
        Felamimail_Controller_Cache_Message::getInstance()->updateCache($inbox, 10, getrandmax()); // TODO FIXME better use update flag -1/0 or such

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Message::class, [
            ['field' => 'folder_id', 'operator' => 'equals', 'value' => $inbox->getId()],
        ]);
        $dispatchMsgs = Felamimail_Controller_Message::getInstance()->search($filter);
        $cleanMsgsRaii = new Tinebase_RAII(function() use($dispatchMsgs, $imapBackend) {
            foreach ($dispatchMsgs->messageuid as $uid) {
                $imapBackend->removeMessage($uid);
            }
        });

        $this->assertTrue(Sales_Controller_Document_DispatchHistory::getInstance()->readEmailDispatchResponses());
        unset($cleanMsgsRaii);

        Sales_Controller_Document_DispatchHistory::clearOnCommitCallbackCache();
        Tinebase_TransactionManager::getInstance()->registerOnCommitCallback([static::class, 'onCommitCallback']);
        try {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->fail('unreachable');
        } catch (Tinebase_Exception) {}

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
        $this->assertSame(Sales_Model_Document_Invoice::STATUS_DISPATCHED, $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS});
    }


    public function testCopyInvoice(): void
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();

        /** @var Sales_Model_Address $recipientId */
        $recipientId = $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord();
        $invoiceRecipientId = clone $recipientId;
        $invoiceRecipientId->{Sales_Model_Address::FLD_PREFIX3} = 'unittest prefix3';

        $path = Tinebase_FileSystem::getInstance()->getPathOfNode(
            Filemanager_Controller_Node::getInstance()->createNodes('/shared/test', Tinebase_Model_Tree_FileObject::TYPE_FOLDER)->getFirstRecord(), true);
        file_put_contents('tine20://' . $path . '/test.txt', 'unittest');

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_ACCEPTED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $recipientId,
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID => $invoiceRecipientId,
            Sales_Model_Document_Order::FLD_ATTACHMENTS => new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [
                Tinebase_FileSystem::getInstance()->stat($path . '/test.txt'),
            ]),
            Sales_Model_Document_Order::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Order([
                    Sales_Model_DocumentPosition_Order::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Order::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Order::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Order::FLD_UNIT_PRICE => 1,
                    Sales_Model_DocumentPosition_Order::FLD_NOTES => new Tinebase_Record_RecordSet(Tinebase_Model_Note::class, [
                            new Tinebase_Model_Note([
                                Tinebase_Model_Note::FLD_NOTE_TYPE_ID => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                                Tinebase_Model_Note::FLD_NOTE => 'order'
                            ], true)
                        ]),
                ], true)
            ],
        ]));
        $this->assertSame($recipientId->getId(), $order->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
        $this->assertSame($recipientId->getId(), $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
        $this->assertNotSame($order->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->getId(), $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->getId());
        $this->assertNotSame($recipientId->getId(), $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->getId());
        $this->assertNotSame('unittest prefix3', $order->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_PREFIX3});
        $this->assertSame('unittest prefix3', $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->{Sales_Model_Address::FLD_PREFIX3});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS});
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order->{Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS});
        $this->assertSame(1, $order->attachments->count());
        $this->assertNull($order->{Sales_Model_Document_Order::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Order::FLD_NOTES});
        Tinebase_Record_Expander::expandRecord($order, fullExpansion: true);
        $this->assertSame(1, $order->{Sales_Model_Document_Order::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Order::FLD_NOTES}->count());

        $copyOrder = Sales_Controller_Document_Order::getInstance()->copy($order->getId(), false);
        $this->assertInstanceOf(Sales_Model_Document_Address::class, $copyOrder->{Sales_Model_Document_Order::FLD_RECIPIENT_ID});
        $this->assertNull($copyOrder->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->getId());

        $copyOrder = (new Sales_Frontend_Json())->copyDocument_Order($order->getId(), false);
        $this->assertIsArray($copyOrder[Sales_Model_Document_Order::FLD_RECIPIENT_ID]);
        $this->assertNull($copyOrder[Sales_Model_Document_Order::FLD_RECIPIENT_ID]['id']);

        $invoice = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Order::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));
        $this->assertSame($recipientId->getId(), $invoice->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
        $this->assertNotSame($invoice->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->getId(), $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->getId());
        $this->assertSame('unittest prefix3', $invoice->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_PREFIX3});
        $this->assertSame(0, $invoice->attachments->count());
        $this->assertNull($invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES});
        Tinebase_Record_Expander::expandRecord($invoice, true);
        $this->assertSame(0, $invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->count());

        $invoice->attachments->addRecord(Tinebase_FileSystem::getInstance()->stat($path . '/test.txt'));
        $invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->addRecord(new Tinebase_Model_Note([
            Tinebase_Model_Note::FLD_NOTE_TYPE_ID => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            Tinebase_Model_Note::FLD_NOTE => 'invoice'
        ], true));
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $ublAttachmentId = $invoice->attachments->getFirstRecord()->getId();
        $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->addRecord(new Sales_Model_Document_AttachedDocument([
            Sales_Model_Document_AttachedDocument::FLD_TYPE => Sales_Model_Document_AttachedDocument::TYPE_EDOCUMENT,
            Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $ublAttachmentId,
            Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ => $invoice->seq,
        ], true));
        $invoice->attachments->addRecord(Tinebase_FileSystem::getInstance()->stat($path . '/test.txt'));
        $invoice->attachments->getLastRecord()->name = 'test1.text';
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $otherAttachmentId = array_values(array_diff($invoice->attachments->getArrayOfIds(), [$ublAttachmentId]))[0];
        $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->addRecord(new Sales_Model_Document_AttachedDocument([
            Sales_Model_Document_AttachedDocument::FLD_TYPE => Sales_Model_Document_AttachedDocument::TYPE_SUPPORTING_DOCUMENT,
            Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $otherAttachmentId,
            Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ => $invoice->seq,
        ], true));
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        $this->assertNull($invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES});
        Tinebase_Record_Expander::expandRecord($invoice, fullExpansion: true);
        $this->assertSame(1, $invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->count());

        $copy = Sales_Controller_Document_Invoice::getInstance()->copy($invoice->getId(), persist: true);

        $this->assertNotSame($invoice->getId(), $copy->getId());
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->count(), $copy->{Sales_Model_Document_Invoice::FLD_POSITIONS}->count());
        $this->assertNotSame($invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->getId(), $copy->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->getId());
        $this->assertNull($copy->{Sales_Model_Document_Invoice::FLD_PRECURSOR_DOCUMENTS});

        $this->assertSame($recipientId->getId(), $copy->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
        $this->assertNotSame($invoice->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->getId(), $copy->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->getId());
        $this->assertSame('unittest prefix3', $copy->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_PREFIX3});

        $this->assertSame(1, $copy->attachments->count());
        $this->assertFalse($invoice->attachments->getById($copy->attachments->getFirstRecord()->getId()));
        $this->assertSame(1, $copy->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());

        $this->assertNull($copy->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES});
        Tinebase_Record_Expander::expandRecord($copy, fullExpansion: true);
        $this->assertSame(1, $copy->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->count());
        $this->assertNotSame($invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->getFirstRecord()->getId(), $copy->{Sales_Model_Document_Invoice::FLD_POSITIONS}->getFirstRecord()->{Sales_Model_DocumentPosition_Invoice::FLD_NOTES}->getFirstRecord()->getId());
    }

    public function testInvoiceStorno()
    {
        $this->clear(Sales_Config::APP_NAME, Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART);

        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();
        $product2 = $this->_createProduct();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Invoice::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
            Sales_Model_Document_Invoice::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            Sales_Model_Document_Invoice::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                ], true),
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 2',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product2->getId(),
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                ], true),
            ],
        ]));

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'customer', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $customer->getId()],
                ]]
            ]));
        $this->assertSame(2, $result->count());

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'category', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT}],
                ]]
            ]));
        $this->assertSame(2, $result->count());

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'category', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT}],
                ]]
            ]));
        $this->assertSame(0, $result->count());

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'division', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION}],
                ]]
            ]));
        $this->assertSame(2, $result->count());

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'division', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION}],
                ]]
            ]));
        $this->assertSame(0, $result->count());

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        if (!($oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
        }
        $previewRaii = new Tinebase_RAII(fn () => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc);
        Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
        $exportPdfRaii = new Tinebase_RAII(fn () => Sales_Export_DocumentPdf::$previewService = null);

        $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
        $app->status = Tinebase_Application::DISABLED;
        Tinebase_Application::getInstance()->updateApplication($app);

        (new Sales_Frontend_Json)->createPaperSlip(Sales_Model_Document_Invoice::class, $invoice->getId());
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());

        unset($previewRaii);
        unset($exportPdfRaii);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());

        $this->assertNotNull($attachment = $invoice->attachments->find('name', $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . '-xrechnung.xml'));
        $this->assertSame(2, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}->count());
        $this->assertSame($attachment->getId(), $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}
            ->find(Sales_Model_Document_AttachedDocument::FLD_TYPE, Sales_Model_Document_AttachedDocument::TYPE_EDOCUMENT)->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}
            ->getFirstRecord()->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_SEQ}, $invoice->{Sales_Model_Document_Invoice::FLD_ATTACHED_DOCUMENTS}
            ->getLastRecord()->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ});

        Tinebase_Record_Expander_DataRequest::clearCache();

        /** @var Sales_Model_Document_Invoice $storno */
        $storno = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Invoice::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $invoice,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                    Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL => true,
                ]),
            ]
        ]));

        $this->assertNull($storno->attachments->find('name', $storno->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER} . '-xrechnung.xml'));

        Tinebase_Record_Expander::expandRecord($storno);
        $this->assertSame(-2, (int)$storno->{Sales_Model_Document_Invoice::FLD_NET_SUM});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID}, $storno->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID});

        Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $product1->getId()],
                ]]
            ]));
        $filter = $filter->toArray(true);
        $this->assertIsArray($filter[0]['value'][0]['value']['name'] ?? null);

        $result = Sales_Controller_DocumentPosition_Invoice::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_DocumentPosition_Invoice::class, [
                [TMFA::FIELD => 'customer', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $customer->getId()],
                ]]
            ]));
        $this->assertSame(4, $result->count());
    }

    public function testForeignIdFilter(): void
    {
        $orderCtrl = Sales_Controller_Document_Order::getInstance();
        $customer = $this->_createCustomer();

        $postal1 = clone $customer->postal;
        $postal1->{Sales_Model_Address::FLD_POSTALCODE} = 'unittest1';

        $postal2 = clone $customer->postal;
        $postal2->setId(null);
        $postal2->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        $postal2->{Sales_Model_Address::FLD_POSTALCODE} = 'unittest2';

        $postal3 = clone $customer->postal;
        $postal3->setId(null);
        $postal3->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        $postal3->{Sales_Model_Address::FLD_POSTALCODE} = 'unittest3';

        $commonFlds = [
            SMDOrder::FLD_CUSTOMER_ID => $customer,
            SMDOrder::FLD_ORDER_STATUS => SMDOrder::STATUS_RECEIVED,
        ];
        $orders = [
            // 0
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
            ]), true)),
            // 1
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal2,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal3,
            ]), true)),
            // 2
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
            ]), true)),
            // 3
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal1,
            ]), true)),
            // 4
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
            ]), true)),
            // 5
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal1,
            ]), true)),
            // 6
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal2,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal3,
            ]), true)),
        ];

        $assertFun = function(array $filter, $expectedResult) {
            $result = Sales_Controller_Document_Order::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(SMDOrder::class, $filter));
            sort($expectedResult);
            $resultIds = $result->getArrayOfIds();
            sort($resultIds);
            $this->assertSame($expectedResult, $resultIds);
        };

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
        ], [
            $orders[2]->getId(),
            $orders[3]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[2]->getId(),
            $orders[3]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'not', TMFA::VALUE => null],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[4]->getId(),
            $orders[5]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[4]->getId(),
            $orders[5]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[4]->getId(),
            $orders[5]->getId(),
            $orders[6]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
                ]],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
                ]],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
        ], [
            $orders[4]->getId(),
            $orders[5]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[4]->getId(),
            $orders[5]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'not', TMFA::VALUE => null],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[2]->getId(),
            $orders[3]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[2]->getId(),
            $orders[3]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
            ]],
        ], [
            $orders[0]->getId(),
            $orders[1]->getId(),
            $orders[2]->getId(),
            $orders[3]->getId(),
            $orders[6]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'equals', TMFA::VALUE => null],
                ]],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
                ]],
            ]],
        ], [
            $orders[1]->getId(),
            $orders[6]->getId(),
        ]);

        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => Sales_Model_Address::FLD_POSTALCODE, TMFA::OPERATOR => 'equals', TMFA::VALUE => 'unittest1'],
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => Sales_Model_Address::FLD_STREET, TMFA::OPERATOR => 'contains', TMFA::VALUE => 'teststreet'],
                ]],
            ]],
        ], [
            $orders[3]->getId(),
        ]);
        $assertFun([
            [TMFA::FIELD => SMDOrder::FLD_INVOICE_RECIPIENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                [TMFA::FIELD => Sales_Model_Address::FLD_POSTALCODE, TMFA::OPERATOR => 'equals', TMFA::VALUE => 'unittest1'],
                [TMFA::FIELD => 'original_id', TMFA::OPERATOR => 'notDefinedBy', TMFA::VALUE => [
                    [TMFA::FIELD => Sales_Model_Address::FLD_STREET, TMFA::OPERATOR => 'contains', TMFA::VALUE => 'teststreet'],
                ]],
            ]],
        ], []);
    }

    public function testInvoiceModelPriceCalcultionGrossPrice(): void
    {
        $invoice = new Sales_Model_Document_Invoice([
            Sales_Model_Document_Invoice::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_STANDARD,
            Sales_Model_Document_Invoice::FLD_POSITIONS => [
                $position = new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TYPE => Sales_Model_DocumentPosition_Invoice::POS_TYPE_PRODUCT,
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 11.9,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                    Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE => 19,
                ], true),
            ],
        ], true);

        $invoice->calculatePricesIncludingPositions();

        $this->assertSame(10.0, $position->{Sales_Model_DocumentPosition_Invoice::FLD_NET_PRICE});
        $this->assertSame(11.9, $position->{Sales_Model_DocumentPosition_Invoice::FLD_GROSS_PRICE});
        $this->assertSame(1.9, round($position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX}, 2));

        $this->assertSame(10.0, $invoice->{Sales_Model_Document_Invoice::FLD_NET_SUM});
        $this->assertSame(11.9, $invoice->{Sales_Model_Document_Invoice::FLD_GROSS_SUM});
        $this->assertSame(1.9, round($invoice->{Sales_Model_Document_Invoice::FLD_SALES_TAX}, 2));
    }
    
    public function testCategoryEvalDimensionCopy()
    {
        $customer = $this->_createCustomer();
        $cat = Sales_Controller_Document_Category::getInstance()->getAll()->getFirstRecord();
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->getAll()->find(Tinebase_Model_EvaluationDimension::FLD_NAME, Tinebase_Model_EvaluationDimension::COST_CENTER);
        $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS} = new Tinebase_Record_RecordSet(Tinebase_Model_EvaluationDimensionItem::class, [
            new Tinebase_Model_EvaluationDimensionItem([
                Tinebase_Model_EvaluationDimensionItem::FLD_NUMBER => '01',
                Tinebase_Model_EvaluationDimensionItem::FLD_NAME => 'foo',
            ], true),
        ]);
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->update($cc);

        $cat->eval_dim_cost_center = $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->getFirstRecord()->getId();
        Sales_Controller_Document_Category::getInstance()->update($cat);

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Order::FLD_DOCUMENT_CATEGORY => $cat->getId(),
        ]));

        self::assertIsObject($order->eval_dim_cost_center);
        self::assertSame($cat->eval_dim_cost_center, $order->eval_dim_cost_center->getId());

        Tinebase_Record_Expander::expandRecord($order);
        self::assertNotNull($order->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID});
        self::assertSame($customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->getId(), $order->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID}->{Sales_Model_Document_Debitor::FLD_ORIGINAL_ID});
    }

    public function testCustomerFilterForDocuments()
    {
        $customer = $this->_createCustomer();

        Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->postal,
        ]));

        $result = Sales_Controller_Customer::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Customer::class, [
                ['field' => 'document_order', 'operator' => 'definedBy', 'value' => [
                    ['field' => Sales_Model_Document_Order::FLD_ORDER_STATUS, 'operator' => 'equals', 'value' => Sales_Model_Document_Order::STATUS_RECEIVED],
                ]],
            ]));
        $this->assertSame(1, $result->count());

        $result = Sales_Controller_Customer::getInstance()->search($filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Customer::class, $filterArray = [
            ['field' => 'document_order', 'operator' => 'definedBy', 'value' => [
                ['field' => Sales_Model_Document_Order::FLD_ORDER_STATUS, 'operator' => 'equals', 'value' => Sales_Model_Document_Order::STATUS_DONE],
            ]],
        ]));
        $this->assertSame(0, $result->count());

        $feFilter = $filter->toArray(true);
        $this->assertSame($filterArray, $feFilter);

        $result = Sales_Controller_Document_Order::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Document_Offer::class, [
                ['field' => 'division_id', 'operator' => 'equals', 'value' => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION}],
            ]
        ), new Tinebase_Model_Pagination([
            Tinebase_Model_Pagination::FLD_SORT => Sales_Model_Document_Abstract::FLD_CUSTOMER_ID
        ]));
        $this->assertSame(1, $result->count());

        $result = Sales_Controller_Document_Order::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Document_Offer::class, [
                ['field' => 'division_id', 'operator' => 'not', 'value' => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION}],
            ]
        ));
        $this->assertSame(0, $result->count());

        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        $result = Sales_Controller_Document_Order::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Document_Offer::class, [
                ['field' => 'division_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'title', 'operator' => 'equals', 'value' => $division->title],
                ]],
            ]
        ));
        $this->assertSame(1, $result->count());

        $result = Sales_Controller_Document_Order::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Document_Offer::class, [
                ['field' => 'division_id', 'operator' => 'notDefinedBy', 'value' => [
                    ['field' => 'title', 'operator' => 'equals', 'value' => $division->title],
                ]],
            ]
        ));
        $this->assertSame(0, $result->count());
    }

    public function testQueryFilter()
    {
        $customer = $this->_createCustomer();

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Order::FLD_DOCUMENT_TITLE => 'unittest',
        ]));

        $result = Sales_Controller_Document_Order::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Order::class, [
                ['field' => 'query', 'operator' => 'contains', 'value' => 'nitt'],
            ]))->getFirstRecord();

        $this->assertNotNull($result);
        $this->assertSame($order->getId(), $result->getId());
    }

    public function testOrderAddresses()
    {
        $customer = $this->_createCustomer();

        $order = Sales_Controller_Document_Order::getInstance()->create(new Sales_Model_Document_Order([
            Sales_Model_Document_Order::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Order::FLD_ORDER_STATUS => Sales_Model_Document_Order::STATUS_RECEIVED,
            Sales_Model_Document_Order::FLD_RECIPIENT_ID => $customer->postal,
            ]));
        Tinebase_Record_Expander::expandRecord($order);

        $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID} = $customer->postal;
        $order->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID} = $customer->postal;
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

        $this->assertNotSame($order->getIdFromProperty(Sales_Model_Document_Order::FLD_RECIPIENT_ID),
            $order->getIdFromProperty(Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID));
        $this->assertNotSame($order->getIdFromProperty(Sales_Model_Document_Order::FLD_RECIPIENT_ID),
            $order->getIdFromProperty(Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID));
        $this->assertNotSame($order->getIdFromProperty(Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID),
            $order->getIdFromProperty(Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID));

        $this->assertSame($order->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID},
            $order->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
        $this->assertSame($order->{Sales_Model_Document_Order::FLD_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID},
            $order->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}->{Sales_Model_Document_Order::FLD_ORIGINAL_ID});
    }

    public function testTransitionOfferOrder()
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();
        $product2 = $this->_createProduct();

        $offer = Sales_Controller_Document_Offer::getInstance()->create(new Sales_Model_Document_Offer([
            Sales_Model_Document_Offer::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Offer::FLD_OFFER_STATUS => Sales_Model_Document_Offer::STATUS_DRAFT,
            Sales_Model_Document_Offer::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Offer::FLD_POSITIONS => [
                new Sales_Model_DocumentPosition_Offer([
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'pos 1',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product1->getId(),
                    Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 1,
                ], true),
                new Sales_Model_DocumentPosition_Offer([
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'pos 2',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product2->getId(),
                    Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 1,
                ], true),
            ],
        ]));

        $offer->{Sales_Model_Document_Offer::FLD_OFFER_STATUS} = Sales_Model_Document_Offer::STATUS_DISPATCHED;
        $offer = Sales_Controller_Document_Offer::getInstance()->update($offer);

        Tinebase_Record_Expander_DataRequest::clearCache();

        $order = Sales_Controller_Document_Abstract::executeTransition(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => Sales_Model_Document_Order::class,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL =>
                        Sales_Model_Document_Offer::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $offer,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS => null,
                ]),
            ]
        ]));

        return $order;
    }

    public function testPositionRemoval()
    {
        $order = $this->testTransitionOfferOrder();
        Tinebase_Record_Expander::expandRecord($order);

        $oldNotes = Tinebase_Notes::getInstance()->getNotesOfRecord(Sales_Model_Document_Order::class, $order->getId(), _onlyNonSystemNotes: false);

        $order->{Sales_Model_Document_Abstract::FLD_POSITIONS}->removeFirst();
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

        $newNotes = Tinebase_Notes::getInstance()->getNotesOfRecord(Sales_Model_Document_Order::class, $order->getId(), _onlyNonSystemNotes: false);
        $newNotes->removeRecordsById($oldNotes);
        $this->assertSame(1, $newNotes->count());
        $this->assertStringNotContainsString(Sales_Model_Document_Order::FLD_PRECURSOR_DOCUMENTS, $newNotes->getFirstRecord()->note);

        $this->assertCount(1, $order->{Sales_Model_Document_Abstract::FLD_POSITIONS});
        $this->assertCount(1, $order->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS});



        $order->{Sales_Model_Document_Abstract::FLD_POSITIONS}->removeFirst();
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

        $this->assertCount(0, $order->{Sales_Model_Document_Abstract::FLD_POSITIONS});
        $this->assertCount(0, $order->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS});
    }

    public function testDeleteInvoice()
    {
        $customer = $this->_createCustomer();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
        ]));

        Sales_Controller_Document_Invoice::getInstance()->delete([$invoice->getId()]);
    }

    public function testInvoiceNumbers()
    {
        $customer = $this->_createCustomer();

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
        ]));
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Invoice::getConfiguration()->jsonExpander);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Invoice::class, [$invoice]));

        $translate = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);

        $inTranslated = $translate->_('IN-');
        $piTranslated = $translate->_('PI-');

        $this->assertStringStartsWith($piTranslated, $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}, $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_DISCOUNT_TYPE} = Sales_Config::INVOICE_DISCOUNT_SUM;
        $updatedInvoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Invoice::class, [$updatedInvoice]));

        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}, $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});

        $updatedInvoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $updatedInvoice = Sales_Controller_Document_Invoice::getInstance()->update($updatedInvoice);

        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertNotEmpty($updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});
        $this->assertStringStartsWith($inTranslated, $updatedInvoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER});
    }

    public function testDeliveryNumbers()
    {
        $customer = $this->_createCustomer();

        $delivery = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer,
            Sales_Model_Document_Abstract::FLD_RECIPIENT_ID => $customer->postal,
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Delivery::getConfiguration()->jsonExpander);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Delivery::class, [$delivery]));

        $translate = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);

        $dnTranslated = $translate->_('DN-');
        $pdTranslated = $translate->_('PD-');

        $this->assertStringStartsWith($pdTranslated, $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER}, $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});

        $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_DATE} = Tinebase_DateTime::today()->subDay(1);
        $updatedDelivery = Sales_Controller_Document_Delivery::getInstance()->update($delivery);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Delivery::class, [$updatedDelivery]));

        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER}, $delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});

        $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS} = Sales_Model_Document_Delivery::STATUS_DELIVERED;
        $updatedDelivery = Sales_Controller_Document_Delivery::getInstance()->update($updatedDelivery);

        $this->assertSame($delivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER},
            $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER});
        $this->assertNotEmpty($updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});
        $this->assertStringStartsWith($dnTranslated, $updatedDelivery->{Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER});
    }
}
