<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Tinebase initialization
 * 
 * @package     Sales
 */
class Sales_Setup_Initialize extends Setup_Initialize
{
    protected function _initializeEDocumentXRechnungElement(): void
    {
        self::initializeEDocumentXRechnungElement();
    }

    public static function initializeEDocumentXRechnungElement(): void
    {
        $doc = new DOMDocument();
        $doc->load(__DIR__ . '/files/xrechnung-semantic-model.xsd');
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');
        $ctrl = Sales_Controller_EDocument_XRechnungElement::getInstance();

        $fun = null;
        $fun = function(?Sales_Model_EDocument_XRechnungElement $parent, $elementDefs, array $stack) use($ctrl, $xpath, &$fun): void{
            /** @var DOMNode $elementDef */
            foreach($elementDefs as $elementDef) {
                $xrElement = $ctrl->create(new Sales_Model_EDocument_XRechnungElement([
                    Sales_Model_EDocument_XRechnungElement::FLD_PARENT_ID => $parent?->getId(),
                    Sales_Model_EDocument_XRechnungElement::FLD_TYPE => $type = substr($xpath->evaluate('string(./@type)', $elementDef), 3),
                    Sales_Model_EDocument_XRechnungElement::FLD_NAME => $xpath->evaluate('string(./@name)', $elementDef),
                    Sales_Model_EDocument_XRechnungElement::FLD_BT_NUMBER => ($btNum = $xpath->evaluate('string(./xs:annotation/xs:appinfo/text()[1])', $elementDef)),
                    Sales_Model_EDocument_XRechnungElement::FLD_DESCRIPTION => $xpath->evaluate('string(./xs:annotation/xs:documentation/text()[1])', $elementDef),
                    Sales_Model_EDocument_XRechnungElement::FLD_IS_OVERRIDEABLE => in_array($btNum, [
                        'BT-3', // Invoice_type_code
                        'BT-7', // Value_added_tax_point_date
                        'BT-8', // Value_added_tax_point_date_code
                        'BT-10', //.Buyer_reference
                        'BT-11', // document_reference
                        'BT-12', // document_reference
                        'BT-13', // Purchase_order_reference
                        'BT-14', // Sales_order_reference
                        'BT-15', // Receiving_advice_reference
                        'BT-16', // Despatch_advice_reference
                        'BT-18', // Invoiced_object_identifier
                        'BT-19', // Buyer_accounting_reference
                        'BT-20', // Payment_terms
                        'BT-25', // document_reference
                        'BT-26', // Preceding_Invoice_issue_date
                        'BT-27', // Seller_name
                        'BT-28', // Seller_trading_name
                        'BT-33', // Seller_additional_legal_information
                        'BT-35', // Seller_address_line_1
                        'BT-36', // Seller_address_line_2
                        'BT-37', // Seller_city
                        'BT-38', // Seller_post_code
                        'BT-39', // Seller_country_subdivision
                        'BT-40', // Seller_country_code
                        'BT-41', // Seller_contact_point
                        'BT-42', // Seller_contact_telephone_number
                        'BT-43', // Seller_contact_email_address
                        'BT-44', // Buyer_name
                        'BT-45', // Buyer_trading_name
                        'BT-46', // Buyer_identifier
                        'BT-50', // Buyer_address_line_1
                        'BT-51', // Buyer_address_line_2
                        'BT-52', // Buyer_city
                        'BT-53', // Buyer_post_code
                        'BT-54', // Buyer_country_subdivision
                        'BT-55', // Buyer_country_code
                        'BT-56', // Buyer_contact_point
                        'BT-57', // Buyer_contact_telephone_number
                        'BT-58', // Buyer_contact_email_address
                        'BT-59', // Payee_name
                        'BT-60', // Payee_identifier
                        'BT-64', // Tax_representative_address_line_1
                        'BT-65', // Tax_representative_address_line_2
                        'BT-66', // Tax_representative_city
                        'BT-67', // Tax_representative_post_code
                        'BT-68', // Tax_representative_country_subdivision
                        'BT-69', // Tax_representative_country_code
                        'BT-70', // Deliver_to_party_name
                        'BT-71', // Deliver_to_location_identifier
                        'BT-72', // Actual_delivery_date
                        'BT-75', // Deliver_to_address_line_1
                        'BT-76', // Deliver_to_address_line_2
                        'BT-77', // Deliver_to_city
                        'BT-78', // Deliver_to_post_code
                        'BT-79', // Deliver_to_country_subdivision
                        'BT-80', // Deliver_to_country_code
                        'BT-81', // Payment_means_type_code
                        'BT-82', // Payment_means_text
                        'BT-87', // Payment_card_primary_account_number
                        'BT-88', // Payment_card_holder_name
                        'BT-89', // Mandate_reference_identifier
                        'BT-90', // Bank_assigned_creditor_identifier
                        'BT-91', // Debited_account_identifier
                        'BT-113', // Paid_amount
                        'BT-115', // Amount_due_for_payment
                        'BT-162', // Seller_address_line_3
                        'BT-163', // Buyer_address_line_3
                        'BT-164', // Tax_representative_address_line_3
                        'BT-165', // Deliver_to_address_line_3
                    ]),
                ]));
                if (in_array($type, $stack)) continue;
                $tmpStack = $stack;
                $tmpStack[] = $type;
                $fun($xrElement, $xpath->evaluate('/xs:schema/xs:complexType[@name = \'' . $type . '\']/xs:sequence/xs:element'), $tmpStack);
            }
        };

        $fun(null, $xpath->evaluate('/xs:schema/xs:element/xs:complexType/xs:sequence/xs:element'), []);
    }

    /**
    * init favorites
    */
    protected function _initializeFavorites() {
        // Products
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'model'             => 'Sales_Model_ProductFilter',
        );
        
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Products", // _('My Products')
                'description'       => "Products created by me", // _('Products created by me')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Contracts", // _('My Contracts')
                'description'       => "Contracts created by me", // _('Contracts created by myself')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Customers
        $commonValues['model'] = 'Sales_Model_CustomerFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Customers", // _('All Customers')
                'description' => "All customer records", // _('All customer records')
                'filters'     => array(
                ),
            ))
        ));
        
        // Offers
        $commonValues['model'] = 'Sales_Model_OfferFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Offers", // _('All Offers')
                'description' => "All offer records", // _('All offer records')
                'filters'     => array(
                ),
            ))
        ));
        
        static::createDefaultFavoritesForSub20();

        static::createDefaultFavoritesForSub22();

        static::createDefaultFavoritesForSub24();

        static::createDefaultFavoritesForContracts();

        static::createDefaultFavoritesDocPurchaseInvoice();

        static::createDocumentInvoiceFavorites();

        static::createDocumentOfferAndOrderFavorites();
    }

    public static function createDocumentOfferAndOrderFavorites(): void
    {
        $commonValues = [
            'account_id'        => null,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId(),
        ];

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Offer favorites
        $commonValues['model'] = Sales_Model_Document_Offer::class;

        // All Offers
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'All Offers', // _('All Offers')
                'description' => 'All Offers',
                'filters'     => [],
            ])
        ));

        // Draft Offers
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Draft Offers', // _('Draft Offers')
                'description' => 'Draft Offers',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Offer::FLD_OFFER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Offer::STATUS_DRAFT],
                ],
            ])
        ));

        // To be dispatched Offers (Manual Dispatch)
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'To be dispatched Offers', // _('To be dispatched Offers')
                'description' => 'To be dispatched Offers',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Offer::FLD_OFFER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH],
                ],
            ])
        ));

        // Dispatched Offers
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Dispatched Offers', // _('Dispatched Offers')
                'description' => 'Dispatched Offers',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Offer::FLD_OFFER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Offer::STATUS_DISPATCHED],
                ],
            ])
        ));

        // Ordered Offers
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Ordered Offers', // _('Ordered Offers')
                'description' => 'Ordered Offers',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Offer::FLD_OFFER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Offer::STATUS_ORDERED],
                ],
            ])
        ));

        // Refused Offers
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Refused Offers', // _('Refused Offers')
                'description' => 'Refused Offers',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Offer::FLD_OFFER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Offer::STATUS_REJECTED],
                ],
            ])
        ));

        // Order favorites
        $commonValues['model'] = Sales_Model_Document_Order::class;

        // All Orders
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'All Orders', // _('All Orders')
                'description' => 'All Orders',
                'filters'     => [],
            ])
        ));

        // Received Orders
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Received Orders', // _('Received Orders')
                'description' => 'Received Orders',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Order::STATUS_RECEIVED],
                ],
            ])
        ));

        // Accepted Orders
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Accepted Orders', // _('Accepted Orders')
                'description' => 'Accepted Orders',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Order::STATUS_ACCEPTED],
                ],
            ])
        ));

        // To be dispatched Orders (Manual Dispatch)
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'To be dispatched Orders', // _('To be dispatched Orders')
                'description' => 'To be dispatched Orders',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH],
                ],
            ])
        ));

        // Dispatched Orders
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Dispatched Orders', // _('Dispatched Orders')
                'description' => 'Dispatched Orders',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Order::STATUS_DISPATCHED],
                ],
            ])
        ));

        // Done Orders
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'        => 'Done Orders', // _('Done Orders')
                'description' => 'Done Orders',
                'filters'     => [
                    [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Order::STATUS_COMPLETED],
                ],
            ])
        ));
    }

    public static function createDocumentInvoiceFavorites(): void
    {
        $commonValues = [
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId(),
            'model'             => Sales_Model_Document_Invoice::class,
        ];

        $pfe = Tinebase_PersistentFilter::getInstance();

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'All Invoices', // _('All Invoices')
                'description'       => 'All Invoices', // _('All Invoices')
                'filters'           => [],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Invoices current month', // _('Invoices current month')
                'description'       => 'Invoices current month', // _('Invoices current month')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE, TMFA::OPERATOR => 'within', TMFA::VALUE => Tinebase_Model_Filter_Date::MONTH_THIS],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Invoices not booked', // _('Invoices not booked')
                'description'       => 'Invoices not booked', // _('Invoices not booked')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Invoice::STATUS_PROFORMA],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Invoices manual dispatch', // _('Invoices manual dispatch')
                'description'       => 'Invoices manual dispatch', // _('Invoices manual dispatch')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Invoices unpaid', // _('Invoices unpaid')
                'description'       => 'Invoices unpaid', // _('Invoices unpaid')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_Invoice::STATUS_DISPATCHED],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Invoices reversed', // _('Invoices reversed')
                'description'       => 'Invoices reversed', // _('Invoices reversed')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_REVERSED_STATUS, TMFA::OPERATOR => 'in', TMFA::VALUE => [Sales_Config::DOCUMENT_REVERSED_STATUS_REVERSED, Sales_Config::DOCUMENT_REVERSED_STATUS_PARTIALLY_REVERSED]],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Reversal Invoices', // _('Reversal Invoices')
                'description'       => 'Reversal Invoices', // _('Reversal Invoices')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_REVERSAL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => true],
                ],
            ])
        ));
    }

    public static function createDefaultFavoritesDocPurchaseInvoice(): void
    {
        $commonValues = [
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId(),
            'model'             => Sales_Model_Document_PurchaseInvoice::class,
        ];

        $pfe = Tinebase_PersistentFilter::getInstance();

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'All Purchase Invoices', // _('All Purchase Invoices')
                'description'       => 'All Purchase Invoices', // _('All Purchase Invoices')
                'filters'           => [],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Unpaid Purchase Invoices', // _('Unpaid Purchase Invoices')
                'description'       => 'All Purchase Invoices that have not yet been paid', // _('All Purchase Invoices that have not yet been paid')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OPERATOR_NOT, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::STATUS_PAID],
                    [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OPERATOR_NOT, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::STATUS_COMPLETED],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Unapproved Purchase Invoices', // _('Unapproved Purchase Invoices')
                'description'       => 'All Purchase Invoices that have not yet been approved', // _('All Purchase Invoices that have not yet been approved')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED],
                ],
            ])
        ));
    }

    protected function _initializeEDocumentEAS(): void
    {
        self::initializeEDocumentEAS();
        self::initializeEDocumentPaymentMeansCode();
        self::initializeEDocumentVATEX();
    }

    public static function initializeEDocumentEAS(): void
    {
        $easData = json_decode(file_get_contents(__DIR__ . '/EAS_5.json'), true);
        foreach ($easData['daten'] as $eas) {
            Sales_Controller_EDocument_EAS::getInstance()->create(new Sales_Model_EDocument_EAS([
                Sales_Model_EDocument_EAS::FLD_NAME => $eas[1],
                Sales_Model_EDocument_EAS::FLD_CODE => $eas[0],
                Sales_Model_EDocument_EAS::FLD_REMARK => $eas[2],
            ]));
        }
    }

    public static function initializeEDocumentVATEX(): void
    {
        static::_importVatExData(json_decode(file_get_contents(__DIR__ . '/VATEX_1.json'), true), 'en');
        static::_importVatExData(json_decode(file_get_contents(__DIR__ . '/VATEX_1_DE.json'), true), 'de');
    }

    protected static function _importVatExData(array $vatexData, string $lang): void
    {
        $vatexCtrl = Sales_Controller_EDocument_VATEX::getInstance();
        foreach ($vatexData['daten'] as $row) {
            if (null === ($vatex = $vatexCtrl->getByCode($row[0]))) {
                $vatexCtrl->create(new Sales_Model_EDocument_VATEX([
                    Sales_Model_EDocument_VATEX::FLD_CODE => $row[0],
                    Sales_Model_EDocument_VATEX::FLD_NAME => [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[1],
                    ]],
                    Sales_Model_EDocument_VATEX::FLD_DESCRIPTION => [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[2],
                    ]],
                    Sales_Model_EDocument_VATEX::FLD_REMARK => array_merge([], $row[3] ? [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[3],
                    ]] : []),
                ]));
            } else {
                Tinebase_Record_Expander::expandRecord($vatex);
                foreach ([
                             Sales_Model_EDocument_VATEX::FLD_NAME => 1,
                             Sales_Model_EDocument_VATEX::FLD_DESCRIPTION => 2,
                             Sales_Model_EDocument_VATEX::FLD_REMARK => 3,
                         ] as $property => $offset) {
                    if (null === ($text = $vatex->{$property}->find(Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE, $lang))) {
                        $vatex->{$property}->addRecord(new Sales_Model_EDocument_VATEXLocalization([
                            Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                            Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[$offset],
                        ], true));
                    } else {
                        $text->{Sales_Model_EDocument_VATEXLocalization::FLD_TEXT} = $row[$offset];
                    }
                }
                $vatexCtrl->update($vatex);
            }
        }
    }

    public static function initializeEDocumentPaymentMeansCode(): void
    {
        $paymentMeansData = json_decode(file_get_contents(__DIR__ . '/UNTDID_4461_3.json'), true);
        foreach ($paymentMeansData['daten'] as $row) {
            $paymentMeans = new Sales_Model_EDocument_PaymentMeansCode([
                Sales_Model_EDocument_PaymentMeansCode::FLD_CODE => $row[0],
                Sales_Model_EDocument_PaymentMeansCode::FLD_NAME => $row[1],
                Sales_Model_EDocument_PaymentMeansCode::FLD_DESCRIPTION => $row[2],
            ]);
            switch ($row[0]) {
                case '58': // SEPA credit transfer
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_PayeeFinancialAccount::class;
                    break;
                case '59': // SEPA direct debit
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_PaymentMandate::class;
                    break;
                default:
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_NoConfig::class;
                    break;
            }
            $paymentMeans = Sales_Controller_EDocument_PaymentMeansCode::getInstance()->create($paymentMeans);
            if ('58' === $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CODE}) { // SEPA credit transfer
                Sales_Config::getInstance()->{Sales_Config::DEBITOR_DEFAULT_PAYMENT_MEANS} = $paymentMeans->getId();
            }
        }
    }

    public static function initializeBoilerPlates()
    {
        $importer = Tinebase_Import_Csv_Generic::createFromDefinition(
            Tinebase_ImportExportDefinition::getInstance()->getFromFile(dirname(__DIR__) . '/Import/definitions/sales_import_boilerplate_csv.xml', Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME))
        );
        $importer->importFile(__DIR__ . '/files/boilerplate.csv');
    }

    protected function _initializeBoilerPlates()
    {
        static::initializeBoilerPlates();
    }

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Sales_Scheduler_Task::addUpdateProductLifespanTask($scheduler);
        Sales_Scheduler_Task::addCreateAutoInvoicesDailyTask($scheduler);
        Sales_Scheduler_Task::addCreateAutoInvoicesMonthlyTask($scheduler);
        Sales_Scheduler_Task::addEMailDispatchResponseMinutelyTask($scheduler);
    }

    protected function _initializeTbSystemCFEvaluationDimension(): void
    {
        self::createTbSystemCFEvaluationDimension();
    }

    public static function createTbSystemCFEvaluationDimension(): void
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $appId = Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => 'divisions',
            'application_id' => $appId,
            'model' => Tinebase_Model_EvaluationDimensionItem::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Limit to Sales Divisions', // _('Limit to Sales Divisions')
                    TMCC::TYPE              => TMCC::TYPE_RECORDS,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => Sales_Config::APP_NAME,
                        TMCC::MODEL_NAME        => Sales_Model_DivisionEvalDimensionItem::MODEL_NAME_PART,
                        TMCC::REF_ID_FIELD      => Sales_Model_DivisionEvalDimensionItem::FLD_EVAL_DIMENSION_ITEM_ID,
                        TMCC::DEPENDENT_RECORDS => true,
                    ],
                    TMCC::UI_CONFIG         => [
                        'searchComboConfig'     => [
                            'useEditPlugin'         => false,
                        ],
                    ]
                ],
            ]
        ], true));

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => 'divisions',
            'application_id' => $appId,
            'model' => Tinebase_Model_EvaluationDimension::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [Sales_Controller_Division::class, 'evalDimModelConfigHook'],
                ],
            ],
        ], true));
    }

    protected function _initializeCostCenterCostBearer()
    {
        self::initializeCostCenterCostBearer();
    }

    public static function initializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Sales_Model_Invoice::class,
            Sales_Model_Product::class,
            Sales_Model_Contract::class,
            Sales_Model_PurchaseInvoice::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_Document_PurchaseInvoice::class,
            Sales_Model_Document_Credit::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_PurchaseInvoice::class,
            Sales_Model_DocumentPosition_Credit::class,
        ]);
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
            Sales_Model_Product::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_Document_PurchaseInvoice::class,
            Sales_Model_Document_Credit::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_PurchaseInvoice::class,
            Sales_Model_DocumentPosition_Credit::class,
        ]);
    }

    /**
     * add more contract favorites
     */
    public static function createDefaultFavoritesForContracts()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Inactive Contracts", // _('Inactive Contracts')
                'description' => "Contracts that have already been terminated", // _('Contracts that have already been terminated')
                'filters' => [
                    ['field' => 'end_date', 'operator' => 'before', 'value' => Tinebase_Model_Filter_Date::DAY_THIS]
                ],
            ))
        ));
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Active Contracts", // _('Active Contracts')
                'description' => "Contracts that are still running", // _('Contracts that are still running')
                'filters' => [
                        ['field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST],
                ],
            ))
        ));
    }

    /**
     * creates default favorited for version 8.22 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub22()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_SupplierFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Suppliers",        // _('All Suppliers')
                'description' => "All supplier records", // _('All supplier records')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.24 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub24()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_PurchaseInvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Purchase Invoices", // _('All Purchase Invoices')
                'description' => "All purchase invoices", // _('All purchase invoices')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.20 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub20()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Products
        $commonValues['model'] = 'Sales_Model_ProductFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Products", // _('All Products')
                'description' => "All product records", // _('All product records')
                'filters' => array(),
            ))
        ));

        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Contracts", // _('All Contracts')
                'description' => "All contract records", // _('All contract records')
                'filters' => array(),
            ))
        ));

        // Invoices
        $commonValues['model'] = 'Sales_Model_InvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Invoices", // _('All Invoices')
                'description' => "All invoice records", // _('All invoice records')
                'filters' => array(),
            ))
        ));

        // OrderConfirmations
        $commonValues['model'] = 'Sales_Model_OrderConfirmationFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Order Confirmations", // _('All Order Confirmations')
                'description' => "All order confirmation records", // _('All order confirmation records')
                'filters' => array(),
            ))
        ));
    }

    protected function _initializeDefaultDivision(): void
    {
        $division = static::createDefaultDivision();
        static::createDefaultCategory($division);
    }

    public static function createDefaultDivision(): Sales_Model_Division
    {
        $t = Tinebase_Translation::getTranslation('Sales');
        $division = Sales_Controller_Division::getInstance()->create(new Sales_Model_Division([
            Sales_Model_Division::FLD_TITLE => $t->_('Default Division'),
            Sales_Model_Division::FLD_NAME => $t->_('Fill with name'),
            Sales_Model_Division::FLD_ADDR_PREFIX1 => $t->_('Fill with address'),
            Sales_Model_Division::FLD_ADDR_POSTAL => $t->_('Fill with postal'),
            Sales_Model_Division::FLD_ADDR_LOCALITY => $t->_('Fill with locality'),
            Sales_Model_Division::FLD_ADDR_COUNTRY => $t->_('Fill with country'),
            Sales_Model_Division::FLD_CONTACT_NAME => $t->_('Fill with contact name'),
            Sales_Model_Division::FLD_CONTACT_EMAIL => $t->_('Fill with contact email'),
            Sales_Model_Division::FLD_CONTACT_PHONE => $t->_('Fill with contact phone'),
            Sales_Model_Division::FLD_VAT_NUMBER => $t->_('Fill with vat number'),
        ]));
        Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION} = $division->getId();
        Tinebase_Container::getInstance()->setGrants($division->container_id,
            new Tinebase_Record_RecordSet(Sales_Model_DivisionGrants::class, [
                new Sales_Model_DivisionGrants([
                    'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Sales_Model_DivisionGrants::GRANT_ADMIN => true,
                ])
            ]));

        return $division;
    }

    public static function createDefaultCategory(Sales_Model_Division $division): Sales_Model_Document_Category
    {
        $t = Tinebase_Translation::getTranslation('Sales');
        $category = Sales_Controller_Document_Category::getInstance()->create(new Sales_Model_Document_Category([
            Sales_Model_Document_Category::FLD_NAME => $t->_('Standard'),
            Sales_Model_Document_Category::FLD_DIVISION_ID => $division->getId(),
        ]));
        Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT} = $category->getId();

        return $category;

    }
}
