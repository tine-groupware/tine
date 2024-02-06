<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Sales_Controller_Document_*
 */
class Sales_Document_ControllerTest extends Sales_Document_Abstract
{
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

        Tinebase_Record_Expander::expandRecord($invoice);

        $invoice->{Sales_Model_Document_Invoice::FLD_INVOICE_STATUS} = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

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

        Tinebase_Record_Expander::expandRecord($storno);
        $this->assertSame(-2, (int)$storno->{Sales_Model_Document_Invoice::FLD_NET_SUM});
        $this->assertSame($invoice->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID}, $storno->{Sales_Model_Document_Invoice::FLD_RECIPIENT_ID}->{Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID});
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
        ));
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
