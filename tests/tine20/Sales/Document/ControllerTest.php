<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Test class for Sales_Controller_Document_*
 */
class Sales_Document_ControllerTest extends Sales_Document_Abstract
{
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

    public function testInvoiceStorno()
    {
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
                    Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY => 1,
                    Sales_Model_DocumentPosition_Invoice::FLD_UNIT_PRICE => 1,
                ], true),
                new Sales_Model_DocumentPosition_Invoice([
                    Sales_Model_DocumentPosition_Invoice::FLD_TITLE => 'pos 2',
                    Sales_Model_DocumentPosition_Invoice::FLD_PRODUCT_ID => $product2->getId(),
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

        Tinebase_Record_Expander::expandRecord($invoice);

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        $this->assertNotNull($invoice->attachments->find('name', 'xrechnung.xml'));

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

        $this->assertNotNull($storno->attachments->find('name', 'xrechnung.xml'));

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

        $this->assertSame($cat->eval_dim_cost_center, $order->eval_dim_cost_center->getId());

        Tinebase_Record_Expander::expandRecord($order);
        $this->assertNotNull($order->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID});
        $this->assertSame($customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->getId(), $order->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID}->{Sales_Model_Document_Debitor::FLD_ORIGINAL_ID});
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

        $offer->{Sales_Model_Document_Offer::FLD_OFFER_STATUS} = Sales_Model_Document_Offer::STATUS_RELEASED;
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

        $order->{Sales_Model_Document_Abstract::FLD_POSITIONS}->removeFirst();
        $order = Sales_Controller_Document_Order::getInstance()->update($order);
        Tinebase_Record_Expander::expandRecord($order);

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

        $translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
            new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));

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

        $translate = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
            new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE}));

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
