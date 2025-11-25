<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Sales_Model_Customer as SMC;
use Sales_Model_Debitor as SMDN;
use Sales_Model_Document_Order as SMDOrder;
use Sales_Model_Document_Offer as SMDOffer;

/**
 * Test class for Sales_Frontend_Json
 */
class Sales_Document_JsonTest extends Sales_Document_Abstract
{
    /**
     * @var Sales_Frontend_Json
     */
    protected $_instance = null;

    protected function setUp(): void
    {
        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $this->_instance = new Sales_Frontend_Json();
    }

    public function testGetMatchingSharedOrderDocumentTransition()
    {
        $contractCtrl = Sales_Controller_Contract::getInstance();
        $orderCtrl = Sales_Controller_Document_Order::getInstance();
        $customer = $this->_createCustomer();

        $contract1 = $contractCtrl->create(new Sales_Model_Contract([
            'title' => 'contract1',
        ], true));
        $contract2 = $contractCtrl->create(new Sales_Model_Contract([
            'title' => 'contract1',
        ], true));

        $postal1 = clone $customer->postal;
        $postal1->{Sales_Model_Address::FLD_POSTALCODE} = 'unittest';

        $commonFlds = [
            SMDOrder::FLD_CUSTOMER_ID => $customer,
            SMDOrder::FLD_ORDER_STATUS => SMDOrder::STATUS_ACCEPTED,
            SMDOrder::FLD_SHARED_INVOICE => true,
        ];
        $orders = [
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal1,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal1,
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_CONTRACT_ID => $contract1->getId(),
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal1,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal1,
                SMDOrder::FLD_CONTRACT_ID => $contract1->getId(),
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_CONTRACT_ID => $contract2->getId(),
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $customer->postal,
                SMDOrder::FLD_CONTRACT_ID => $contract2->getId(),
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $postal1,
                SMDOrder::FLD_INVOICE_RECIPIENT_ID => $postal1,
                SMDOrder::FLD_CONTRACT_ID => $contract2->getId(),
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
            ]), true)),
            $orderCtrl->create(new SMDOrder(array_merge($commonFlds, [
                SMDOrder::FLD_RECIPIENT_ID => $customer->postal,
            ]), true)),
        ];

        $expectedResult = [
            ['count' => 2, 'ids' => [$orders[0]->getId(), $orders[1]->getId()]],
            ['count' => 2, 'ids' => [$orders[0]->getId(), $orders[1]->getId()]],
            ['count' => 1, 'ids' => [$orders[2]->getId()]],
            ['count' => 1, 'ids' => [$orders[3]->getId()]],
            ['count' => 1, 'ids' => [$orders[4]->getId()]],
            ['count' => 2, 'ids' => [$orders[5]->getId(), $orders[6]->getId()]],
            ['count' => 2, 'ids' => [$orders[5]->getId(), $orders[6]->getId()]],
            ['count' => 1, 'ids' => [$orders[7]->getId()]],
            ['count' => 2, 'ids' => [$orders[8]->getId(), $orders[9]->getId()]],
            ['count' => 2, 'ids' => [$orders[8]->getId(), $orders[9]->getId()]],
        ];

        foreach ($orders as $idx => $order) {
            $result = $this->_instance->getMatchingSharedOrderDocumentTransition($order->getId(), Sales_Model_Document_Invoice::class);
            $this->assertNotEmpty($result);
            $this->assertCount($expectedResult[$idx]['count'], $result[Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS]);
            foreach ($result[Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS] as $src) {
                $this->assertContains($src[Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT]['id'], $expectedResult[$idx]['ids']);
            }
        }
    }

    public function testOfferPaperSlip(): void
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();

        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        try {
            if ((!$oldSvc = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL})) {
                Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://here.there/path';
            }
            Sales_Export_DocumentPdf::$previewService = new Tinebase_FileSystem_TestPreviewService();
            $app = Tinebase_Application::getInstance()->getApplicationByName(OnlyOfficeIntegrator_Config::APP_NAME);
            $app->status = Tinebase_Application::DISABLED;
            Tinebase_Application::getInstance()->updateApplication($app);
            $document = $this->_instance->createPaperSlip(SMDOffer::class, $document['id']);
            $this->assertSame($document[Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ], $document[Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS][0][Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ]);
            $document = $this->_instance->getDocument_Offer($document['id']);
            $this->assertSame($document[Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ], $document[Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS][0][Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ]);

            $newDocument = $this->_instance->createPaperSlip(SMDOffer::class, $document['id']);
            $this->assertNotSame($newDocument['seq'], $document['seq']);
            $this->assertSame($newDocument[Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ], $newDocument[Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS][0][Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ]);
            $newDocument = $this->_instance->getDocument_Offer($document['id']);
            $this->assertSame($newDocument[Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ], $newDocument[Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS][0][Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ]);

        } finally {
            Sales_Export_DocumentPdf::$previewService = null;
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = $oldSvc;
        }
    }

    public function testOfferBoilerplates()
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate());

        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_BOILERPLATES => [
                $boilerplate->toArray()
            ],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_BOILERPLATES]);
        $this->assertCount(1, $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES]);
        $this->assertNotSame($boilerplate->getId(), $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0]['id']);
        $this->assertEquals('0', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE} = 'cascading changes?';
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->update($boilerplate);
        $document = $this->_instance->getDocument_Offer($document['id']);

        $this->assertNotSame($boilerplate->getId(), $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0]['id']);
        $this->assertEquals('0', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE] =
            'local stuff';
        $document = $this->_instance->saveDocument_Offer($document);
        $this->assertEquals('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);

        $boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE} = 'not cascading';
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->update($boilerplate);
        $document = $this->_instance->getDocument_Offer($document['id']);
        $this->assertEquals('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertNotSame($boilerplate->{Sales_Model_Boilerplate::FLD_BOILERPLATE},
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);
        $this->assertSame('local stuff', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);

        $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0] =
            $boilerplate = Sales_BoilerplateControllerTest::getBoilerplate()->toArray(false);
        $document = $this->_instance->saveDocument_Offer($document);
        $this->assertEquals('1', $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Document_Abstract::FLD_LOCALLY_CHANGED]);
        $this->assertSame($boilerplate[Sales_Model_Boilerplate::FLD_BOILERPLATE],
            $document[Sales_Model_Document_Abstract::FLD_BOILERPLATES][0][Sales_Model_Boilerplate::FLD_BOILERPLATE]);
    }

    public function testOfferDocumentModLog()
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $product = $this->_createProduct();

        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_RECIPIENT_ID => $customerData[SMC::FLD_DEBITORS][0][Sales_Model_Debitor::FLD_DELIVERY][0],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
            SMDOffer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray()
                ]
            ],
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        $updatedDocument = $this->_instance->saveDocument_Offer($document);
        $modLogs = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId(),
            new SMDOffer($document), $updatedDocument['seq']);
        $this->assertSame(0, $modLogs->count(), print_r($modLogs->toArray(), true));
    }

    public function testOfferDocumentWithoutRecipient()
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_RECIPIENT_ID => '',
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));

        $this->assertFalse(isset($document[SMDOffer::FLD_RECIPIENT_ID]));
    }

    public function testDeleteDocument()
    {
        $boilerplate = Sales_Controller_Boilerplate::getInstance()->create(
            Sales_BoilerplateControllerTest::getBoilerplate());
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_RECIPIENT_ID => '',
            SMDOffer::FLD_BOILERPLATES => [
                $boilerplate->toArray()
            ],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]);
        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
        $this->_instance->deleteDocument_Offers($document['id']);
    }

    public function testOfferCustomerChange()
    {
        $document = $this->testOfferDocumentCustomerCopy(true);
        $document[SMDOffer::FLD_CUSTOMER_ID]['url'] = 'http://there.tld/home';
        $document = $this->_instance->saveDocument_Offer($document);

        $customer = $this->_createCustomer();
        $document[SMDOffer::FLD_CUSTOMER_ID] = $customer->toArray();
        $document[SMDOffer::FLD_DEBITOR_ID] = null;
        $document = $this->_instance->saveDocument_Offer($document);

        $this->assertSame($customer->getId(), $document[SMDOffer::FLD_CUSTOMER_ID]['original_id']);
    }

    public function testOfferDocumentCustomerCopy($noAsserts = false)
    {
        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customerData,
            SMDOffer::FLD_RECIPIENT_ID => $customerData[SMC::FLD_DEBITORS][0][SMDN::FLD_DELIVERY][0],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]);

        $document = $this->_instance->saveDocument_Offer($document->toArray(true));
        if ($noAsserts) {
            return $document;
        }

        $this->assertSame($customerData['number'] . ' - ' . $customerData['name'], $customerData['fulltext']);

        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID], 'customer_id is not an array');
//        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'], 'customer_id.billing is not an array');
//        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['delivery'], 'customer_id.delivery is not an array');
//        $this->assertIsArray($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal'], 'customer_id.postal is not an array');
//        $this->assertIsString($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['delivery'][0]['id'], 'customer_id.delivery.0.id is not set');
//        $this->assertIsString($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal']['id'], 'customer_id.postal.id is not set');
//        $this->assertStringStartsWith('teststreet for ', $document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['postal']['street']);
        $this->assertSame($customerData['number'] . ' - ' . $customerData['name'],
            $document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]['fulltext']);
        $this->assertArrayHasKey('fulltext', $document[SMDOffer::FLD_RECIPIENT_ID]);
        $this->assertStringContainsString('delivery', $document[SMDOffer::FLD_RECIPIENT_ID]['fulltext']);

        $this->assertSame(1, Sales_Controller_Document_Offer::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(SMDOffer::class, [
                ['field' => 'customer_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'name', 'operator' => 'equals', 'value' => $customer->name],
                ]],
            ]))->count());

        $customerCopy = Sales_Controller_Document_Customer::getInstance()->get($document[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander = new Tinebase_Record_Expander(Sales_Model_Document_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
 /*               SMC::FLD_DEBITORS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        SMDN::FLD_DELIVERY => [],
                        SMDN::FLD_BILLING => [],
                    ],
                ],
                'postal'   => [],*/
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerCopy]));

        $this->assertNotSame($customer->getId(), $customerCopy->getId());
        $this->assertSame($customer->name, $customerCopy->name);
//        $this->assertSame(1, $customerCopy->{SMC::FLD_DEBITORS}->count());
//        $this->assertNotNull(($debitor = $customerCopy->{SMC::FLD_DEBITORS}->getFirstRecord()));
//        $this->assertSame(1, $debitor->{SMDN::FLD_BILLING}->count());
 //       $this->assertSame(1, $debitor->{SMDN::FLD_DELIVERY}->count());
/*
        $oldDebitor = $customer->{SMC::FLD_DEBITORS}->getFirstRecord();
        $this->assertNotSame($oldDebitor->getId(), $debitor->getId());
        $this->assertSame($oldDebitor->{SMDN::FLD_NUMBER}, $debitor->{SMDN::FLD_NUMBER});
        $this->assertNotSame($oldDebitor->delivery->getId(), $debitor->delivery->getId());
        $this->assertSame($oldDebitor->delivery->name, $debitor->delivery->name);
        $this->assertNotSame($oldDebitor->billing->getId(), $debitor->billing->getId());
        $this->assertSame($oldDebitor->billing->name, $debitor->billing->name);
        $this->assertNotSame($customer->postal->getId(), $customerCopy->postal->getId());
        $this->assertSame($customer->postal->name, $customerCopy->postal->name);

        $this->assertNotSame($document[SMDOffer::FLD_RECIPIENT_ID]['id'], $debitor->delivery->getFirstRecord()->getId());
        $this->assertNotSame($document[SMDOffer::FLD_RECIPIENT_ID]['id'], $oldDebitor->delivery->getFirstRecord()->getId());*/

        return $document;
    }

    public function testOfferDocumentUpdate()
    {
        $document = $this->testOfferDocumentCustomerCopy(true);

        $customer = $this->_createCustomer();
        $customerData = $customer->toArray();
        $document[SMDOffer::FLD_CUSTOMER_ID] = $customerData;
        $document[SMDOffer::FLD_DEBITOR_ID] = null;
        $document[SMDOffer::FLD_RECIPIENT_ID] = '';

        $updatedDocument = $this->_instance->saveDocument_Offer($document);

        $this->assertNotSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID]['id'],
            $document[SMDOffer::FLD_CUSTOMER_ID]['id']);
/*        $this->assertNotSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['id'],
            $document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['id']);
        $this->assertNotSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['delivery'][0]['id'],
            $document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['delivery'][0]['id']);
        $this->assertNotSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'][0]['id'],
            $document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'][0]['id']);*/
        $this->assertEmpty($updatedDocument[SMDOffer::FLD_RECIPIENT_ID]);

        $updated2Document = $updatedDocument;
        $updated2Document[SMDOffer::FLD_RECIPIENT_ID] = $customerData[SMC::FLD_DEBITORS][0]['billing'][0];
        //$updated2Document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'] = null;
        $updated2Document = $this->_instance->saveDocument_Offer($updated2Document);
       /* $this->assertSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'][0]['id'],
            $updated2Document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'][0]['id']);
        $this->assertNotSame($updatedDocument[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'][0]['id'],
            $updated2Document[SMDOffer::FLD_RECIPIENT_ID]['id']);*/
        $this->assertSame($customerData[SMC::FLD_DEBITORS][0]['billing'][0]['id'],
            $updated2Document[SMDOffer::FLD_RECIPIENT_ID]['original_id']);
        $this->assertNull($updated2Document[SMDOffer::FLD_RECIPIENT_ID]['customer_id']);

        /*$updated2Document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing'] = [];
        $updated2Document = $this->_instance->saveDocument_Offer($updated2Document);
        $this->assertEmpty($updated2Document[SMDOffer::FLD_CUSTOMER_ID][SMC::FLD_DEBITORS][0]['billing']);*/

        $document = Sales_Controller_Document_Offer::getInstance()->get($document['id']);
        $docExpander = new Tinebase_Record_Expander(SMDOffer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                SMDOffer::FLD_CUSTOMER_ID => []
            ]
        ]);
        $docExpander->expand(new Tinebase_Record_RecordSet(SMDOffer::class, [$document]));

        $deliveryAddress = $customer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord();
        $oldDeliveryAddress = clone $deliveryAddress;
        $deliveryAddress->name = 'other name';

        $documentUpdated = $this->_instance->saveDocument_Offer($document->toArray(true));

        $customer = $document->{SMDOffer::FLD_CUSTOMER_ID};
        $customerUpdated = Sales_Controller_Document_Customer::getInstance()->get($documentUpdated[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);

        $this->assertSame($customer->getId(), $customerUpdated->getId());
        /*$this->assertSame($customer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getId(), $customerUpdated->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getId());
        $this->assertSame($oldDeliveryAddress->getId(), $customerUpdated->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->getId());
        $this->assertNotSame($oldDeliveryAddress->name, $customerUpdated->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->name);
        $this->assertSame('other name', $customer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->name);
        $this->assertSame($customer->postal->getId(), $customerUpdated->postal->getId());*/

        $secondCustomer = $this->_createCustomer();
        $document = Sales_Controller_Document_Offer::getInstance()->get($documentUpdated['id']);
        $docExpander->expand(new Tinebase_Record_RecordSet(SMDOffer::class, [$document]));

/*        $document->{SMDOffer::FLD_CUSTOMER_ID}->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->name = 'shoo';
        $document->{SMDOffer::FLD_CUSTOMER_ID}->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->addRecord(new Sales_Model_Document_Address($secondCustomer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->toArray()));*/
        /*$document->{SMDOffer::FLD_CUSTOMER_ID}->postal = [
            'name' => 'new postal adr',
            'seq' => $document->{SMDOffer::FLD_CUSTOMER_ID}->postal->seq,
        ];

        $documentUpdated = $this->_instance->saveDocument_Offer($document->toArray(true));
        $customerUpdated = Sales_Controller_Document_Customer::getInstance()->get($documentUpdated[Sales_Model_Document_Abstract::FLD_CUSTOMER_ID]);
        $expander->expand(new Tinebase_Record_RecordSet(Sales_Model_Document_Customer::class, [$customerUpdated]));

        $this->assertSame(2, $customerUpdated->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->count());
        foreach ($customerUpdated->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery as $address) {
            if ('shoo' === $address->name) {
                $this->assertSame($oldDeliveryAddress->getId(), $address->getId());
            } else {
                $this->assertNotSame($oldDeliveryAddress->getId(), $address->getId());
                $this->assertNotSame($secondCustomer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->getId(), $address->getId());
                $this->assertSame($secondCustomer->{SMC::FLD_DEBITORS}->getFirstRecord()->delivery->getFirstRecord()->name, $address->name);
            }
        }
        $this->assertNotSame($customer->postal->getId(), $customerUpdated->postal->getId());
        $this->assertSame('new postal adr', $customerUpdated->postal->name);*/
    }

    public function testOfferDocumentPosition()
    {
        $customer = $this->_createCustomer();
        $subProduct = $this->_createProduct();
        $product = $this->_createProduct([
            Sales_Model_Product::FLD_SUBPRODUCTS => [(new Sales_Model_SubProductMapping([
                Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProduct,
                Sales_Model_SubProductMapping::FLD_SHORTCUT => 'lorem',
                Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
            ], true))->toArray()]
        ]);

        $document = new SMDOffer([
            SMDOffer::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOffer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray()
                ]
            ],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]);

        $this->_instance->saveDocument_Offer($document->toArray(true));
    }

    public function testOfferReversalTransition()
    {
        $customer = $this->_createCustomer();
        $product = $this->_createProduct();

        $document = new SMDOffer([
            SMDOffer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray(),
                    Sales_Model_DocumentPosition_Offer::FLD_SALES_TAX_RATE => 19,
                    Sales_Model_DocumentPosition_Offer::FLD_SALES_TAX => 100 * 19 / 100,
                    Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE => 100,
                ]
            ],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
            SMDOffer::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOffer::FLD_RECIPIENT_ID => $customer->postal->toArray(),
        ]);

        $savedDocument = $this->_instance->saveDocument_Offer($document->toArray(true));
        $this->assertSame(Sales_Config::DOCUMENT_REVERSAL_STATUS_NOT_REVERSED, $savedDocument[SMDOffer::FLD_REVERSAL_STATUS]);
        $savedDocument[SMDOffer::FLD_OFFER_STATUS] = SMDOffer::STATUS_DISPATCHED;
        $savedDocument = $this->_instance->saveDocument_Offer($savedDocument);
        $this->assertSame(Sales_Config::DOCUMENT_REVERSAL_STATUS_NOT_REVERSED, $savedDocument[SMDOffer::FLD_REVERSAL_STATUS]);

        /*Tinebase_Record_Expander_DataRequest::clearCache();

        /*$result = $this->_instance->createFollowupDocument((new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => SMDOffer::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $savedDocument,
                    Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL => true,
                ]),
            ],
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE =>
                SMDOffer::class,
        ]))->toArray());

        $updatedDocument = $this->_instance->getDocument_Offer($savedDocument['id']);
        $this->assertSame(Sales_Config::DOCUMENT_REVERSAL_STATUS_REVERSED, $updatedDocument[SMDOffer::FLD_REVERSAL_STATUS]);*/
    }

    public function testOfferToOrderToInvoiceTransition()
    {
        $customer = $this->_createCustomer();
        $subProduct = $this->_createProduct();
        $product = $this->_createProduct([
            Sales_Model_Product::FLD_SUBPRODUCTS => [(new Sales_Model_SubProductMapping([
                Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $subProduct,
                Sales_Model_SubProductMapping::FLD_SHORTCUT => 'lorem',
                Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
            ], true))->toArray()]
        ]);

        $document = new SMDOffer([
            SMDOffer::FLD_POSITIONS => [
                [
                    Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum',
                    Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $product->toArray(),
                    Sales_Model_DocumentPosition_Offer::FLD_TYPE => Sales_Model_DocumentPosition_Offer::POS_TYPE_PRODUCT,
                    Sales_Model_DocumentPosition_Offer::FLD_UNIT_PRICE => 100,
                    Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
                ]
            ],
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
            SMDOffer::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOffer::FLD_RECIPIENT_ID => $customer->postal->toArray(),
        ]);

        $savedDocument = $this->_instance->saveDocument_Offer($document->toArray(true));
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $savedDocument[SMDOffer::FLD_FOLLOWUP_ORDER_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $savedDocument[SMDOffer::FLD_FOLLOWUP_ORDER_BOOKED_STATUS]);
        $this->assertSame((int)$savedDocument[SMDOffer::FLD_POSITIONS][0][Sales_Model_DocumentPosition_Offer::FLD_NET_PRICE], (int)$document->{SMDOffer::FLD_POSITIONS}[0][Sales_Model_DocumentPosition_Offer::FLD_UNIT_PRICE]);
        $this->assertIsNumeric($savedDocument[SMDOffer::FLD_POSITIONS][0][Sales_Model_DocumentPosition_Offer::FLD_GROSS_PRICE]);
        $savedDocument[SMDOffer::FLD_POSITIONS][] = [
            Sales_Model_DocumentPosition_Offer::FLD_TITLE => 'ipsum sub',
            Sales_Model_DocumentPosition_Offer::FLD_PARENT_ID => $savedDocument[SMDOffer::FLD_POSITIONS][0]['id'],
            Sales_Model_DocumentPosition_Offer::FLD_PRODUCT_ID => $subProduct->toArray(),
            Sales_Model_DocumentPosition_Offer::FLD_TYPE => Sales_Model_DocumentPosition_Offer::POS_TYPE_PRODUCT,
            Sales_Model_DocumentPosition_Offer::FLD_UNIT_PRICE => 100,
            Sales_Model_DocumentPosition_Offer::FLD_QUANTITY => 1,
        ];
        $savedDocument[SMDOffer::FLD_OFFER_STATUS] = SMDOffer::STATUS_DISPATCHED;
        $savedDocument = $this->_instance->saveDocument_Offer($savedDocument);
        $this->assertNotSame($customer->getId(), $savedDocument[SMDOffer::FLD_CUSTOMER_ID]['id']);
        $this->assertSame($customer->getId(), $savedDocument[SMDOffer::FLD_CUSTOMER_ID]['original_id']);
        $this->assertCount(2, $savedDocument[SMDOffer::FLD_POSITIONS]);

        Tinebase_Record_Expander_DataRequest::clearCache();

        $result = $this->_instance->createFollowupDocument((new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => SMDOffer::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $savedDocument,
                ]),
            ],
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE =>
                SMDOrder::class,
        ]))->toArray());

        $this->assertNotSame($customer->getId(), $result[SMDOffer::FLD_CUSTOMER_ID]['id']);
        $this->assertNotSame($savedDocument[SMDOffer::FLD_CUSTOMER_ID]['id'],
            $result[SMDOffer::FLD_CUSTOMER_ID]['id']);
        $this->assertSame($customer->getId(), $result[SMDOffer::FLD_CUSTOMER_ID]['original_id']);
        $this->assertSame(SMDOrder::STATUS_RECEIVED, $result[SMDOrder::FLD_ORDER_STATUS]);

        $updatedOffer = $this->_instance->getDocument_Offer($savedDocument['id']);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOffer[SMDOffer::FLD_FOLLOWUP_ORDER_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOffer[SMDOffer::FLD_FOLLOWUP_ORDER_BOOKED_STATUS]);

        $result[SMDOrder::FLD_ORDER_STATUS] = SMDOrder::STATUS_ACCEPTED;
        $order = $this->_instance->saveDocument_Order($result);
        $updatedOffer = $this->_instance->getDocument_Offer($savedDocument['id']);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOffer[SMDOffer::FLD_FOLLOWUP_ORDER_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOffer[SMDOffer::FLD_FOLLOWUP_ORDER_BOOKED_STATUS]);

        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order[SMDOrder::FLD_FOLLOWUP_INVOICE_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order[SMDOrder::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $order[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $result = $this->_instance->createFollowupDocument((new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [
                new Sales_Model_Document_TransitionSource([
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => SMDOrder::class,
                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                ]),
            ],
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE =>
                Sales_Model_Document_Invoice::class,
        ]))->toArray());

        $updatedOrder = $this->_instance->getDocument_Order($order['id']);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOrder[SMDOrder::FLD_FOLLOWUP_INVOICE_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOrder[SMDOrder::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOrder[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOrder[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);

        // important to test the resolving in the next saveDocument_Invoice call!
        Tinebase_Record_Expander_DataRequest::clearCache();

        $result[Sales_Model_Document_Invoice::FLD_RECIPIENT_ID] = $customer->postal->toArray();
        $result[Sales_Model_Document_Invoice::FLD_INVOICE_STATUS] = Sales_Model_Document_Invoice::STATUS_BOOKED;
        $this->_instance->saveDocument_Invoice($result);

        $updatedOrder = $this->_instance->getDocument_Order($order['id']);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOrder[SMDOrder::FLD_FOLLOWUP_INVOICE_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED, $updatedOrder[SMDOrder::FLD_FOLLOWUP_INVOICE_BOOKED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOrder[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);
        $this->assertSame(Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE, $updatedOrder[SMDOrder::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS]);
    }

    public function testOrderDocument()
    {
        $offer = $this->testOfferDocumentCustomerCopy(true);

        $order = new SMDOrder([
            SMDOrder::FLD_CUSTOMER_ID => $offer[SMDOffer::FLD_CUSTOMER_ID],
            SMDOrder::FLD_ORDER_STATUS => SMDOrder::STATUS_RECEIVED,
            SMDOrder::FLD_PRECURSOR_DOCUMENTS => [
                $offer
            ]
        ]);
        $order = $this->_instance->saveDocument_Order($order->toArray());

        $this->assertEmpty($order[SMDOrder::FLD_PRECURSOR_DOCUMENTS]);
    }

    protected function getTrackingTestData()
    {
        $testData = [];
        $customer = $this->_createCustomer();

        $offer1 = Sales_Controller_Document_Offer::getInstance()->create(new SMDOffer([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]));
        $testData[$offer1->getId()] = $offer1;

        $offer2 = Sales_Controller_Document_Offer::getInstance()->create(new SMDOffer([
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOffer::FLD_OFFER_STATUS => SMDOffer::STATUS_DRAFT,
        ]));
        $testData[$offer2->getId()] = $offer2;

        $order = Sales_Controller_Document_Order::getInstance()->create(new SMDOrder([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => SMDOffer::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $offer1->getId(),
                    ]),
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => SMDOffer::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $offer2->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            SMDOrder::FLD_ORDER_STATUS => SMDOrder::STATUS_RECEIVED,
        ]));
        $testData[$order->getId()] = $order;

        $delivery1 = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => SMDOrder::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $testData[$delivery1->getId()] = $delivery1;

        $delivery2 = Sales_Controller_Document_Delivery::getInstance()->create(new Sales_Model_Document_Delivery([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => SMDOrder::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Delivery::FLD_DELIVERY_STATUS => Sales_Model_Document_Delivery::STATUS_CREATED,
        ]));
        $testData[$delivery2->getId()] = $delivery2;

        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new Sales_Model_Document_Invoice([
            Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS => new Tinebase_Record_RecordSet(
                Tinebase_Model_DynamicRecordWrapper::class, [
                    new Tinebase_Model_DynamicRecordWrapper([
                        Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME => SMDOrder::class,
                        Tinebase_Model_DynamicRecordWrapper::FLD_RECORD => $order->getId(),
                    ])
                ]
            ),
            Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => $customer->toArray(),
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS => Sales_Model_Document_Invoice::STATUS_PROFORMA,
        ]));
        $testData[$invoice->getId()] = $invoice;

        return $testData;
    }

    public function testTrackDocument()
    {
        $testData = $this->getTrackingTestData();
        $order = null;
        $offer = null;
        foreach ($testData as $document) {
            if ($document instanceof SMDOrder) {
                $order = $document;
            } elseif ($document instanceof SMDOffer) {
                $offer = $document;
            }
        }
        $documents = $this->_instance->trackDocument(SMDOrder::class, $order->getId());
        $this->assertSame(count($testData), count($documents));

        $data = $testData;
        foreach ($documents as $wrapper) {
            $id = $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]['id'];
            $this->assertArrayHasKey($id, $data);
            $this->assertSame(get_class($data[$id]), $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME]);
            unset($data[$id]);
        }
        $this->assertEmpty($data);

        $documents = $this->_instance->trackDocument(SMDOffer::class, $offer->getId());
        $this->assertSame(count($testData), count($documents));

        $data = $testData;
        foreach ($documents as $wrapper) {
            $id = $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]['id'];
            $this->assertArrayHasKey($id, $data);
            $this->assertSame(get_class($data[$id]), $wrapper[Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME]);
            unset($data[$id]);
        }
        $this->assertEmpty($data);
    }

    public function testDocumentPrecursorReadonly()
    {
        $testData = $this->getTrackingTestData();

        /**
         * @var string $id
         * @var Sales_Model_Document_Abstract $document
         */
        foreach ($testData as $id => $document) {
            if (!$document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS} ||
                    $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->count() < 1) {
                continue;
            }
            (new Tinebase_Record_Expander(get_class($document), $document::getConfiguration()->jsonExpander))
                ->expand(new Tinebase_Record_RecordSet(get_class($document), [$document]));
            $oldId = $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()
                ->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD};
            $document->{Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()
                ->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD} = Tinebase_Record_Abstract::generateUID();
            $data = $document->toArray();
            $data = $this->_instance->{'save' . $document::getConfiguration()->getModelName()}($data);
            $this->assertSame($oldId, $data[Sales_Model_Document_Abstract::FLD_PRECURSOR_DOCUMENTS][0]
                [Tinebase_Model_DynamicRecordWrapper::FLD_RECORD]);
        }
    }


    public function testExportInvoiceToDatev()
    {
        Sales_Config::getInstance()->set(Sales_Config::DATEV_RECIPIENT_EMAILS_INVOICE, [Tinebase_Core::getUser()->accountEmailAddress]);
        $testData = $this->getTrackingTestData();
        //insert attachment to invoice
        $invoice = null;
        foreach ($testData as $document) {
            if ($document instanceof Sales_Model_Document_Invoice) {
                $invoice = $document;
            }
        }
        $path1 = Tinebase_TempFile::getTempPath();
        file_put_contents($path1, 'testAtt1');
        $path2 = Tinebase_TempFile::getTempPath();
        file_put_contents($path2, 'testAtt2');
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice['id']);
        $invoice->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', [
            ['name' => 'testAtt1.txt', 'tempFile' => Tinebase_TempFile::getInstance()->createTempFile($path1)],
            ['name' => 'testAtt2.txt', 'tempFile' => Tinebase_TempFile::getInstance()->createTempFile($path2)],
        ], true);
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        //feed test invoice data
        Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($invoice);
        $attachments = $invoice->attachments;
        $invoiceData[$invoice['id']] = $attachments->id;
        $this->_instance->exportInvoicesToDatevEmail('Document_Invoice', $invoiceData);
        //assert acitionLog and decode data
        $actionLog = Tinebase_ControllerTest::assertActionLogEntry(Tinebase_Model_ActionLog::TYPE_DATEV_EMAIL, 1);
        $actionLog = $actionLog->getFirstRecord();
        $data = json_decode($actionLog->data, true);

        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice['id']);
        $this->assertEquals($invoice['last_datev_send_date'], $actionLog->datetime, 'invoice datev sent date should be the same as action log datetime');
        $this->assertStringContainsString('testAtt1.txt', $data['attachments'][0], 'attachement1 is invalid');
        $this->assertStringContainsString('testAtt2.txt', $data['attachments'][1], 'attachement1 is invalid');

    }
}
