<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Einvoicing\InvoiceReference;
use Einvoicing\Payments\PaymentTerms;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\BaseTypesHandler;
use GoetasWebservices\Xsd\XsdToPhpRuntime\Jms\Handler\XmlSchemaDateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;

/**
 * Invoice Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Invoice extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Invoice';
    public const TABLE_NAME = 'sales_document_invoice';

    public const FLD_INVOICE_STATUS = 'invoice_status';
    public const FLD_DOCUMENT_PROFORMA_NUMBER = 'document_proforma_number';

    public const FLD_IS_SHARED = 'is_shared';

    public const FLD_LAST_DATEV_SEND_DATE = 'last_datev_send_date';
    public const FLD_INVOICE_PERIOD_START = 'invoice_period_start';
    public const FLD_INVOICE_PERIOD_END = 'invoice_period_end';

    /**
     * invoice status
     */
    public const STATUS_PROFORMA = 'PROFORMA';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_PAID = 'PAID';


    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Invoice'; // gettext('GENDER_Invoice')
        $_definition[self::RECORDS_NAME] = 'Invoices'; // ngettext('Invoice', 'Invoices', n)

        $_definition[self::VERSION] = 3;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ]*/
        ];

        // invoice recipient type
        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::CONFIG][self::TYPE] = Sales_Model_Document_Address::TYPE_BILLING;
        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::UI_CONFIG][self::TYPE] = Sales_Model_Document_Address::TYPE_BILLING;

        // invoice positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART;

        // invoice status
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_INVOICE_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_INVOICE_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ]
        ]);

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::NULLABLE] = true;
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE] =
            Sales_Controller_Document_Invoice::class . '::documentNumberConfigOverride';

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_DOCUMENT_PROFORMA_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Proforma Number', //_('Proforma Number')
                self::QUERY_FILTER              => true,
                self::SHY                       => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_DOCUMENT_PROFORMA_NUMBER,
                    Tinebase_Numberable_String::PREFIX     => 'PI-', // _('PI-')
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    Tinebase_Model_NumberableConfig::NO_AUTOCREATE => true,
                    Tinebase_Numberable::CONFIG_OVERRIDE   =>
                        Sales_Controller_Document_Invoice::class . '::documentProformaNumberConfigOverride',
                ],
            ],
            self::FLD_LAST_DATEV_SEND_DATE       => [
                self::LABEL                 => 'Last Datev send date', // _('Last Datev send date')
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
        ]);

        $_definition[self::FIELDS][self::FLD_IS_SHARED] = [
            self::TYPE                  => self::TYPE_BOOLEAN,
            self::LABEL                 => 'Shared Document', //_('Shared Document')
            self::DEFAULT_VAL           => false,
            self::SHY                   => true,
        ];

        $_definition[self::FIELDS][self::FLD_INVOICE_PERIOD_START] = [
            self::TYPE                  => self::TYPE_DATE,
            self::LABEL                 => 'Invoice Period Start', //_('SInvoice Period Start')
            self::NULLABLE              => true,
        ];
        $_definition[self::FIELDS][self::FLD_INVOICE_PERIOD_END] = [
            self::TYPE                  => self::TYPE_DATE,
            self::LABEL                 => 'Invoice Period End', //_('SInvoice Period End')
            self::NULLABLE              => true,
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static string $_statusField = self::FLD_INVOICE_STATUS;
    protected static string $_statusConfigKey = Sales_Config::DOCUMENT_INVOICE_STATUS;
    protected static string $_documentNumberPrefix = 'IN-'; // _('IN-')

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        parent::transitionFrom($transition);

        $sourceDoc = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->getFirstRecord();
        switch ($sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL}) {
            case Sales_Model_Document_Order::class:
                $this->{self::FLD_RECIPIENT_ID} = $sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                    ->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID};
            case Sales_Model_Document_Invoice::class:
                break;
            default:
                throw new Tinebase_Exception_SystemGeneric('transition from ' . $sourceDoc->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL} . ' to ' . static::class . ' not allowed');
        }

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->_checkProductPrecursorPositionsComplete();
        }

        $this->{self::FLD_IS_SHARED} = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->count() > 1;
    }

    public function toUbl(): string
    {
        if (!($debitor = $this->{self::FLD_DEBITOR_ID}) instanceof Sales_Model_Debitor) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_DEBITOR_ID . ' not set or resolved');
        }
        if (null !== $debitor->{Sales_Model_Debitor::FLD_EAS_ID} && !$debitor->{Sales_Model_Debitor::FLD_EAS_ID} instanceof Sales_Model_EDocument_EAS) {
            throw new Tinebase_Exception_UnexpectedValue('debitors eas not resolved');
        }
        if (! $this->{self::FLD_DOCUMENT_CATEGORY} instanceof Sales_Model_Document_Category) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_DOCUMENT_CATEGORY . ' not set or resolved');
        }
        if (!($division = $this->{self::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID}) instanceof Sales_Model_Division) {
            throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Debitor::FLD_DIVISION_ID . ' on category not set or resolved');
        }
        Tinebase_Record_Expander::expandRecord($division);
        if (!$division->{Sales_Model_Division::FLD_BANK_ACCOUNTS} instanceof Tinebase_Record_RecordSet || 0 === $division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() || !$division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->getFirstRecord()->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT} instanceof Tinebase_Model_BankAccount) {
            throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Division::FLD_BANK_ACCOUNTS . ' not set or resolved');
        }
        if (!($billingAddress = $this->{self::FLD_RECIPIENT_ID}) instanceof Sales_Model_Document_Address) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_RECIPIENT_ID . ' not set or resolved');
        }
        if (($buyerContact = $this->{self::FLD_CONTACT_ID}) && !$buyerContact instanceof Addressbook_Model_Contact) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_CONTACT_ID . ' set but not resolved');
        }

        $t = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, new Zend_Locale($this->{self::FLD_DOCUMENT_LANGUAGE}));

        $this->calculatePricesIncludingPositions();

        $cacheDir = rtrim(Tinebase_Core::getTempDir(), '/') . '/jms/ubl';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, recursive: true);
        }

        $serializer =
            JMS\Serializer\SerializerBuilder::create()
                ->setCacheDir($cacheDir)
                ->addMetadataDir(__DIR__ . '/../../../vendor/tine-groupware/ubl-common/src/jms', 'UBL21\Common')
                ->addMetadataDir(__DIR__ . '/../../../vendor/tine-groupware/ubl-invoice/src/jms', 'UBL21\Invoice')
                ->configureHandlers(function (HandlerRegistryInterface $h) {
                    $h->registerSubscribingHandler(new XmlSchemaDateHandler());
                    $h->registerSubscribingHandler(new BaseTypesHandler());
                })
                ->build();

        if ($this->{self::FLD_POSITIONS}->filter(Sales_Model_DocumentPosition_Abstract::FLD_REVERSAL, 1)->count() > 0) {
            $isStorno = true;
            if (!$this->{self::FLD_PRECURSOR_DOCUMENTS} instanceof Tinebase_Record_RecordSet || $this->{self::FLD_PRECURSOR_DOCUMENTS}->count() === 0) {
                throw new Tinebase_Exception_UnexpectedValue('precursor documents on storno/reversal not resolved or present');
            }
        } else {
            $isStorno = false;
        }

        $ublInvoice = (new UBL21\Invoice\Invoice())
            // BT-24: Specification identifier
            ->setCustomizationID(new \UBL21\Common\CommonBasicComponents\CustomizationID('urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0'))
            // BT-23: Business process type
            ->setProfileID(new \UBL21\Common\CommonBasicComponents\ProfileID('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0'))
            // BT-1: Invoice number
            ->setID(new \UBL21\Common\CommonBasicComponents\ID($this->{self::FLD_DOCUMENT_NUMBER}))
            // BT-2: Issue date
            ->setIssueDate($this->{self::FLD_DOCUMENT_DATE})
            // BG-14: Invoice period cac:InvoicePeriod' 'cbc:StartDate' 'cbc:EndDate',
            ->setInvoicePeriod($this->{self::FLD_INVOICE_PERIOD_START} && $this->{self::FLD_INVOICE_PERIOD_END} ?
                [(new \UBL21\Common\CommonAggregateComponents\InvoicePeriod())
                    ->setStartDate($this->{self::FLD_INVOICE_PERIOD_START})
                    ->setEndDate($this->{self::FLD_INVOICE_PERIOD_END})
                ] : []
            )

            // BT-3: Invoice type code
                /*
• 326 (Partial invoice)
• 380 (Commercial invoice)
• 384 (Corrected invoice)
• 389 (Self-billed invoice)
• 381 (Credit note)
• 875 (Partial construction invoice)
• 876 (Partial final construction invoice)
• 877 (Final construction invoice)
                 */
            ->setInvoiceTypeCode(new \UBL21\Common\CommonBasicComponents\InvoiceTypeCode($isStorno ? '384' : '380'))
            // BT-5: Invoice currency code
            ->setDocumentCurrencyCode(new \UBL21\Common\CommonBasicComponents\DocumentCurrencyCode('EUR'))
            ->setAccountingSupplierParty((new \UBL21\Common\CommonAggregateComponents\AccountingSupplierParty())
                ->setParty(($supplierParty = new \UBL21\Common\CommonAggregateComponents\Party())
                    ->setEndpointID($division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS} && $division->{Sales_Model_Division::FLD_EAS_ID} ?
                        (new \UBL21\Common\CommonBasicComponents\EndpointID($division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS}))->setSchemeID($division->{Sales_Model_Division::FLD_EAS_ID}->{Sales_Model_EDocument_EAS::FLD_CODE})
                        : null
                    )
                    ->setPartyLegalEntity([(new \UBL21\Common\CommonAggregateComponents\PartyLegalEntity())
                        ->setRegistrationName(new \UBL21\Common\CommonBasicComponents\RegistrationName($division->{Sales_Model_Division::FLD_NAME}))
                    ])
                    ->setPostalAddress((new \UBL21\Common\CommonAggregateComponents\PostalAddress())
                        //->setStreetName(new \UBL21\Common\CommonBasicComponents\StreetName($division->{Sales_Model_Division::FLD_ADDR_PREFIX1}))
                        ->setAddressLine(array_merge(
                            [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                                ->setLine(new \UBL21\Common\CommonBasicComponents\Line($division->{Sales_Model_Division::FLD_ADDR_PREFIX1}))],
                            $division->{Sales_Model_Division::FLD_ADDR_PREFIX2} ? [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                                ->setLine(new \UBL21\Common\CommonBasicComponents\Line($division->{Sales_Model_Division::FLD_ADDR_PREFIX2}))] : [],
                            $division->{Sales_Model_Division::FLD_ADDR_PREFIX3} ? [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                                ->setLine(new \UBL21\Common\CommonBasicComponents\Line($division->{Sales_Model_Division::FLD_ADDR_PREFIX3}))] : []
                        ))
                        ->setPostalZone(new \UBL21\Common\CommonBasicComponents\PostalZone($division->{Sales_Model_Division::FLD_ADDR_POSTAL}))
                        ->setCityName(new \UBL21\Common\CommonBasicComponents\CityName($division->{Sales_Model_Division::FLD_ADDR_LOCALITY}))
                        ->setCountry((new \UBL21\Common\CommonAggregateComponents\Country())
                            ->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode(strtoupper($division->{Sales_Model_Division::FLD_ADDR_COUNTRY})))
                        )
                    )
                    ->setContact((new \UBL21\Common\CommonAggregateComponents\Contact())
                        ->setName(new \UBL21\Common\CommonBasicComponents\Name($division->{Sales_Model_Division::FLD_CONTACT_NAME}))
                        ->setTelephone(new \UBL21\Common\CommonBasicComponents\Telephone($division->{Sales_Model_Division::FLD_CONTACT_PHONE}))
                        ->setElectronicMail(new \UBL21\Common\CommonBasicComponents\ElectronicMail($division->{Sales_Model_Division::FLD_CONTACT_EMAIL}))
                    )
                )
            )
            ->setAccountingCustomerParty(($customerParty = new \UBL21\Common\CommonAggregateComponents\AccountingCustomerParty())
                ->setParty((new \UBL21\Common\CommonAggregateComponents\Party())
                    ->setEndpointID($debitor->{Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS} && $debitor->{Sales_Model_Debitor::FLD_EAS_ID} ?
                        (new \UBL21\Common\CommonBasicComponents\EndpointID($debitor->{Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS}))->setSchemeID($debitor->{Sales_Model_Debitor::FLD_EAS_ID}->{Sales_Model_EDocument_EAS::FLD_CODE})
                        : null
                    )
                    ->setPartyLegalEntity([(new \UBL21\Common\CommonAggregateComponents\PartyLegalEntity())
                        ->setRegistrationName(new \UBL21\Common\CommonBasicComponents\RegistrationName($billingAddress->{Sales_Model_Address::FLD_NAME} ?: $this->{self::FLD_CUSTOMER_ID}->name))
                    ])
                    ->setPostalAddress((new \UBL21\Common\CommonAggregateComponents\PostalAddress())
                        ->setAddressLine($billingAddress->{Sales_Model_Address::FLD_STREET} ? [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                            ->setLine(new \UBL21\Common\CommonBasicComponents\Line($billingAddress->{Sales_Model_Address::FLD_STREET}))
                        ] : [])
                        ->setPostalZone($billingAddress->{Sales_Model_Address::FLD_POSTALCODE} ? new \UBL21\Common\CommonBasicComponents\PostalZone($billingAddress->{Sales_Model_Address::FLD_POSTALCODE}) : null)
                        ->setCityName($billingAddress->{Sales_Model_Address::FLD_LOCALITY} ? new \UBL21\Common\CommonBasicComponents\CityName($billingAddress->{Sales_Model_Address::FLD_LOCALITY}) : null)
                        ->setCountry($billingAddress->{Sales_Model_Address::FLD_COUNTRYNAME} ? (new \UBL21\Common\CommonAggregateComponents\Country())
                            ->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode(strtoupper($billingAddress->{Sales_Model_Address::FLD_COUNTRYNAME})))
                            : null
                        )
                    )
                )
            )
            ->setPaymentMeans([
                ($paymentMeans = new \UBL21\Common\CommonAggregateComponents\PaymentMeans())
                    ->setPaymentMeansCode(new \UBL21\Common\CommonBasicComponents\PaymentMeansCode('58'))  // BT-81 Zahlungsart 58 SEPA Überweisung 59 SEPA Einzug
                    ->setPaymentID([new \UBL21\Common\CommonBasicComponents\PaymentID($this->{self::FLD_DOCUMENT_NUMBER})]) // BT-83 Verwendungszweck
            ])
            ->setLegalMonetaryTotal((new \UBL21\Common\CommonAggregateComponents\LegalMonetaryTotal)
                ->setLineExtensionAmount((new \UBL21\Common\CommonBasicComponents\LineExtensionAmount($this->{self::FLD_POSITIONS_NET_SUM}))
                    ->setCurrencyID('EUR')
                )
                ->setTaxExclusiveAmount((new \UBL21\Common\CommonBasicComponents\TaxExclusiveAmount($this->{self::FLD_NET_SUM}))
                    ->setCurrencyID('EUR')
                )
                ->setTaxInclusiveAmount((new \UBL21\Common\CommonBasicComponents\TaxInclusiveAmount($this->{self::FLD_GROSS_SUM}))
                    ->setCurrencyID('EUR')
                )
                ->setPayableAmount((new \UBL21\Common\CommonBasicComponents\PayableAmount($this->{self::FLD_GROSS_SUM}))
                    ->setCurrencyID('EUR')
                )
            )
            ->addToTaxTotal(($taxTotal = new \UBL21\Common\CommonAggregateComponents\TaxTotal)
                ->setTaxAmount((new \UBL21\Common\CommonBasicComponents\TaxAmount($this->{self::FLD_SALES_TAX}))
                    ->setCurrencyID(('EUR'))
                )
            )
        ;
        /*
         * // BT-6: VAT accounting currency code 'cbc:TaxCurrencyCode'
         * // BT-7: Tax point date 'cbc:TaxPointDate'
         *
           // BT-17: Tender or lot reference 'cac:OriginatorDocumentReference' 'cbc:ID'
         * // BT-19: Buyer accounting reference 'cbc:AccountingCost's
         * // BT-22: Notes 'cbc:Note'
         *
         * 'cac:Delivery'
         * see \Einvoicing\Writers\UblWriter::addDeliveryNode
         * // BT-71: Delivery location identifier
         * // BT-72: Actual delivery date
         *
         * // BG-24: Attachments node \Einvoicing\Writers\UblWriter::addAttachmentNode
         *
         * // ??? 'cac:PayeeParty'
         */

        if (is_numeric($this->{self::FLD_PAYMENT_TERMS})) {
            // BT-9: Due date (for invoice profile)
            $paymentTermDays = (int)$this->{self::FLD_PAYMENT_TERMS};
            if (0 === $paymentTermDays) {
                $paymentTerms = $t->_('Payable immediately without deduction.');
            } else {
                $paymentTerms = str_replace('{days}', (string)$paymentTermDays, $t->_('Payable within {days} days without deduction.'));
            }
            $ublInvoice->setDueDate($this->{self::FLD_DOCUMENT_DATE}->getClone()->addDay($paymentTermDays))
                ->addToPaymentTerms((new \UBL21\Common\CommonAggregateComponents\PaymentTerms)
                    ->addToNote(new \UBL21\Common\CommonBasicComponents\Note($paymentTerms))
                );
        }

        // BT-10: Buyer reference
        if ($this->{self::FLD_BUYER_REFERENCE}) {
            $ublInvoice->setBuyerReference(new \UBL21\Common\CommonBasicComponents\BuyerReference($this->{self::FLD_BUYER_REFERENCE}));
        }
        // BT-11: Project reference 'cac:ProjectReference' 'cbc:ID'
        if ($this->{self::FLD_PROJECT_REFERENCE}) {
            $ublInvoice->setProjectReference([
                (new \UBL21\Common\CommonAggregateComponents\ProjectReference())
                    ->setID(new \UBL21\Common\CommonBasicComponents\ID($this->{self::FLD_CONTRACT_ID}->number))
            ]);
        }
        // BT-12: Contract reference
        if ($this->{self::FLD_CONTRACT_ID} instanceof Sales_Model_Contract) {
            $ublInvoice->setContractDocumentReference([
                (new \UBL21\Common\CommonAggregateComponents\ContractDocumentReference)
                    ->setID(new \UBL21\Common\CommonBasicComponents\ID($this->{self::FLD_CONTRACT_ID}->number))
            ]);
        }
        // BT-13: Purchase order reference
        if ($this->{self::FLD_PURCHASE_ORDER_REFERENCE}) {
            $ublInvoice->setOrderReference((new \UBL21\Common\CommonAggregateComponents\OrderReference)
                ->setID(new \UBL21\Common\CommonBasicComponents\ID($this->{self::FLD_PURCHASE_ORDER_REFERENCE}))
                // BT-14: Sales order reference
                //->setSalesOrderID()
            );
        }

        if ($buyerContact) {
            $customerParty->getParty()->setContact(($bContact = new \UBL21\Common\CommonAggregateComponents\Contact())
                ->setName(new \UBL21\Common\CommonBasicComponents\Name($buyerContact->n_fn)));
            if ($buyerContact->tel_work) {
                $bContact->setTelephone(new \UBL21\Common\CommonBasicComponents\Telephone($buyerContact->tel_work));
            }
            if ($buyerContact->email) {
                $bContact->setElectronicMail(new \UBL21\Common\CommonBasicComponents\ElectronicMail($buyerContact->email));
            }
        }

        if ($division->{Sales_Model_Division::FLD_VAT_NUMBER}) {
            $supplierParty
                ->addToPartyTaxScheme((new \UBL21\Common\CommonAggregateComponents\PartyTaxScheme())
                    ->setCompanyID(new \UBL21\Common\CommonBasicComponents\CompanyID($division->{Sales_Model_Division::FLD_VAT_NUMBER}))
                    ->setTaxScheme((new \UBL21\Common\CommonAggregateComponents\TaxScheme())
                        ->setID(new \UBL21\Common\CommonBasicComponents\ID('VAT'))
                    )
                );
        }
        // BT-32 // 'FC' aus validen xrechnungen rausgesucht, ist nicht in codeliste, unklar wo das herkommt
        if ($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID}) {
            $supplierParty
                ->addToPartyTaxScheme((new \UBL21\Common\CommonAggregateComponents\PartyTaxScheme())
                    ->setCompanyID(new \UBL21\Common\CommonBasicComponents\CompanyID($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID}))
                    ->setTaxScheme((new \UBL21\Common\CommonAggregateComponents\TaxScheme())
                        ->setID(new \UBL21\Common\CommonBasicComponents\ID('FC'))
                    )
                );
        }

        if ($isStorno) {
            $refDoc = $this->{self::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD};
            // BG-3: Preceding invoice reference
            $ublInvoice->addToBillingReference((new \UBL21\Common\CommonAggregateComponents\BillingReference)
                ->setInvoiceDocumentReference((new \UBL21\Common\CommonAggregateComponents\InvoiceDocumentReference)
                    ->setID(new \UBL21\Common\CommonBasicComponents\ID($refDoc->{self::FLD_DOCUMENT_NUMBER}))
                    ->setIssueDate($refDoc->{self::FLD_DOCUMENT_DATE})
                )
            );
        }

        $lineCounter = 0;
        $defaultRate = 19;
        /** @var Sales_Model_DocumentPosition_Invoice $position */
        foreach ($this->{self::FLD_POSITIONS} as $position) {
            if (Sales_Model_DocumentPosition_Invoice::POS_TYPE_PRODUCT !== $position->{Sales_Model_DocumentPosition_Invoice::FLD_TYPE}) {
                continue;
            }
            $defaultRate = $position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE};
            $ublInvoice->addToInvoiceLine((new \UBL21\Common\CommonAggregateComponents\InvoiceLine)
                ->setID(new \UBL21\Common\CommonBasicComponents\ID(++$lineCounter))
                ->setUUID($position->getId() ? new \UBL21\Common\CommonBasicComponents\UUID($position->getId()) : null)
                ->setInvoicedQuantity((new \UBL21\Common\CommonBasicComponents\InvoicedQuantity($position->{Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY}))
                    ->setUnitCode('C62')
                )
                ->setLineExtensionAmount((new \UBL21\Common\CommonBasicComponents\LineExtensionAmount($position->{Sales_Model_DocumentPosition_Invoice::FLD_NET_PRICE}))
                    ->setCurrencyID('EUR')
                )
                /*->addToTaxTotal((new \UBL21\Common\CommonAggregateComponents\TaxTotal)
                    ->setTaxAmount((new \UBL21\Common\CommonBasicComponents\TaxAmount($position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX}))
                        ->setCurrencyID('EUR')
                    )
                )*/
                ->setItem((new \UBL21\Common\CommonAggregateComponents\Item)
                    ->setName($position->{Sales_Model_DocumentPosition_Invoice::FLD_TITLE} ?
                        new \UBL21\Common\CommonBasicComponents\Name($position->{Sales_Model_DocumentPosition_Invoice::FLD_TITLE})
                        : null
                    )
                    ->addToClassifiedTaxCategory((new \UBL21\Common\CommonAggregateComponents\ClassifiedTaxCategory)
                        ->setID(new \UBL21\Common\CommonBasicComponents\ID('S'))
                        ->setPercent(new \UBL21\Common\CommonBasicComponents\Percent($position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE}))
                        ->setTaxScheme((new \UBL21\Common\CommonAggregateComponents\TaxScheme)
                            ->setID(new \UBL21\Common\CommonBasicComponents\ID('VAT'))
                        )
                    )
                )
                ->setPrice((new \UBL21\Common\CommonAggregateComponents\Price)
                    ->setPriceAmount((new \UBL21\Common\CommonBasicComponents\PriceAmount($position->{Sales_Model_DocumentPosition_Invoice::FLD_NET_PRICE}))
                        ->setCurrencyID('EUR')
                    )
                )
                ->setNote($position->{Sales_Model_DocumentPosition_Invoice::FLD_DESCRIPTION} ?
                    [new \UBL21\Common\CommonBasicComponents\Note($position->{Sales_Model_DocumentPosition_Invoice::FLD_DESCRIPTION})]
                    : null
                )
            );
        }

        $paymentMeansFirst = true;
        /** @var Tinebase_Model_BankAccount $bankAccount */
        foreach ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS} as $bankAccount) {
            $bankAccount = $bankAccount->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT};
            if (!$paymentMeansFirst) {
                $paymentMeans = clone $paymentMeans;
                $paymentMeans->setPaymentID([clone $paymentMeans->getPaymentID()[0]]);
                $paymentMeans->setPaymentMeansCode(clone $paymentMeans->getPaymentMeansCode());
            }
            $paymentMeansFirst = false;
            $paymentMeans->setPayeeFinancialAccount((new \UBL21\Common\CommonAggregateComponents\PayeeFinancialAccount)
                ->setID(new \UBL21\Common\CommonBasicComponents\ID($bankAccount->{Tinebase_Model_BankAccount::FLD_IBAN}))
                ->setName(new \UBL21\Common\CommonBasicComponents\Name($division->{Sales_Model_Division::FLD_NAME}))
                ->setFinancialInstitutionBranch((new \UBL21\Common\CommonAggregateComponents\FinancialInstitutionBranch)
                    ->setID(new \UBL21\Common\CommonBasicComponents\ID($bankAccount->{Tinebase_Model_BankAccount::FLD_BIC}))
                )
            );
        }

        // fix 0 Eur line issue -> no tax rates set
        if (empty($this->xprops(self::FLD_SALES_TAX_BY_RATE))) {
            $this->xprops(self::FLD_SALES_TAX_BY_RATE)[] = [
                self::NET_SUM => 0,
                self::TAX_SUM => 0,
                self::TAX_RATE => $defaultRate,
            ];
        }
        foreach ($this->xprops(self::FLD_SALES_TAX_BY_RATE) as $taxRate) {
            $taxTotal->addToTaxSubtotal((new \UBL21\Common\CommonAggregateComponents\TaxSubtotal)
                ->setTaxableAmount((new \UBL21\Common\CommonBasicComponents\TaxableAmount($taxRate[self::NET_SUM]))
                    ->setCurrencyID('EUR')
                )
                ->setTaxAmount((new \UBL21\Common\CommonBasicComponents\TaxAmount($taxRate[self::TAX_SUM]))
                    ->setCurrencyID('EUR')
                )
                ->setTaxCategory((new \UBL21\Common\CommonAggregateComponents\TaxCategory)
                    ->setID(new \UBL21\Common\CommonBasicComponents\ID('S'))
                    ->setPercent(new \UBL21\Common\CommonBasicComponents\Percent($taxRate[self::TAX_RATE]))
                    ->setTaxScheme((new \UBL21\Common\CommonAggregateComponents\TaxScheme)
                        ->setID(new \UBL21\Common\CommonBasicComponents\ID('VAT'))
                    )
                )
            );
        }

        return $serializer->serialize($ublInvoice, 'xml');
    }

    // TODO FIXME all usage of email: validate valid email format before using? as we do not enforce a valid email but xrechnung etc. probably enforce!
    // TODO FIXME check usage of all model properties, either they need to be mandatory and/or a empty/format check needs to be performed before using
    public function toEinvoice(?Sales_Model_Einvoice_PresetInterface $einvoiceConfig = null): Sales_EDocument_Einvoicing_Invoice
    {
        if (!($debitor = $this->{self::FLD_DEBITOR_ID}) instanceof Sales_Model_Document_Debitor) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_DEBITOR_ID . ' not set or resolved');
        }
        if (null !== $debitor->{Sales_Model_Debitor::FLD_EAS_ID} && !$debitor->{Sales_Model_Debitor::FLD_EAS_ID} instanceof Sales_Model_EDocument_EAS) {
            throw new Tinebase_Exception_UnexpectedValue('debitors eas not resolved');
        }
        if (! $this->{self::FLD_DOCUMENT_CATEGORY} instanceof Sales_Model_Document_Category) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_DOCUMENT_CATEGORY . ' not set or resolved');
        }
        if (!($division = $this->{self::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID}) instanceof Sales_Model_Division) {
            throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Debitor::FLD_DIVISION_ID . ' on category not set or resolved');
        }
        Tinebase_Record_Expander::expandRecord($division);
        if (!$division->{Sales_Model_Division::FLD_BANK_ACCOUNTS} instanceof Tinebase_Record_RecordSet || 0 === $division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() || !$division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->getFirstRecord()->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT} instanceof Tinebase_Model_BankAccount) {
            throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Division::FLD_BANK_ACCOUNTS . ' not set or resolved');
        }
        if (!($billingAddress = $this->{self::FLD_RECIPIENT_ID}) instanceof Sales_Model_Document_Address) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_RECIPIENT_ID . ' not set or resolved');
        }
        if (($buyerContact = $this->{self::FLD_CONTACT_ID}) && !$buyerContact instanceof Addressbook_Model_Contact) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_CONTACT_ID . ' set but not resolved');
        }
        /** @var Sales_Model_Einvoice_PresetInterface $einvoiceConfig */
        if (null === $einvoiceConfig && !($einvoiceConfig = $debitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG}) instanceof Sales_Model_Einvoice_PresetInterface) {
            throw new Tinebase_Exception_UnexpectedValue('debitor doesnt have einvoice config');
        }

        $t = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, new Zend_Locale($this->{self::FLD_DOCUMENT_LANGUAGE}));

        $ublInvoice = (new Sales_EDocument_Einvoicing_Invoice($einvoiceConfig->getPresetClassName()))
            ->setInvoicePeriodStart($this->{self::FLD_INVOICE_PERIOD_START})
            ->setInvoicePeriodEnd($this->{self::FLD_INVOICE_PERIOD_END})
            ->setNumber($this->{self::FLD_DOCUMENT_NUMBER})
            ->setIssueDate($this->{self::FLD_DOCUMENT_DATE})

            ->setBuyerReference($this->{self::FLD_BUYER_REFERENCE} ?: ($debitor->{Sales_Model_Debitor::FLD_BUYER_REFERENCE} ?: null))
            ->setContractReference($this->{self::FLD_CONTRACT_ID} instanceof Sales_Model_Contract ? $this->{self::FLD_CONTRACT_ID}->number : null)
            ->setProjectReference($this->{self::FLD_PROJECT_REFERENCE} ?: null)
            ->setPurchaseOrderReference($this->{self::FLD_PURCHASE_ORDER_REFERENCE} ?: null)

            ->setSeller((new \Einvoicing\Party())
                ->setName($division->{Sales_Model_Division::FLD_NAME})
                ->setVatNumber($division->{Sales_Model_Division::FLD_VAT_NUMBER})
                ->setTaxRegistrationId($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID} ? new \Einvoicing\Identifier($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID}, 'FC') : null) // BT-32 // 'FC' aus validen xrechnungen rausgesucht, ist nicht in codeliste, unklar wo das herkommt
                ->setElectronicAddress($division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS} && $division->{Sales_Model_Division::FLD_EAS_ID} ? new \Einvoicing\Identifier($division->{Sales_Model_Division::FLD_ELECTRONIC_ADDRESS}, $division->{Sales_Model_Division::FLD_EAS_ID}->{Sales_Model_EDocument_EAS::FLD_CODE}) : null)
                ->setAddress(array_merge(
                    [$division->{Sales_Model_Division::FLD_ADDR_PREFIX1}],
                    $division->{Sales_Model_Division::FLD_ADDR_PREFIX2} ? [$division->{Sales_Model_Division::FLD_ADDR_PREFIX2}] : [],
                    $division->{Sales_Model_Division::FLD_ADDR_PREFIX3} ? [$division->{Sales_Model_Division::FLD_ADDR_PREFIX3}] : []
                ))
                ->setPostalCode($division->{Sales_Model_Division::FLD_ADDR_POSTAL})
                ->setCity($division->{Sales_Model_Division::FLD_ADDR_LOCALITY})
                ->setCountry($division->{Sales_Model_Division::FLD_ADDR_COUNTRY})
                ->setContactName($division->{Sales_Model_Division::FLD_CONTACT_NAME})
                ->setContactPhone($division->{Sales_Model_Division::FLD_CONTACT_PHONE})
                ->setContactEmail($division->{Sales_Model_Division::FLD_CONTACT_EMAIL})
            )

            ->setBuyer(($buyer = new \Einvoicing\Party())
                ->setElectronicAddress($debitor->{Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS} && $debitor->{Sales_Model_Debitor::FLD_EAS_ID} ? new \Einvoicing\Identifier($debitor->{Sales_Model_Debitor::FLD_ELECTRONIC_ADDRESS}, $debitor->{Sales_Model_Debitor::FLD_EAS_ID}->{Sales_Model_EDocument_EAS::FLD_CODE}) : null)
                ->setName($billingAddress->{Sales_Model_Address::FLD_NAME} ?: $this->{self::FLD_CUSTOMER_ID}->name)
                ->setAddress([$billingAddress->{Sales_Model_Address::FLD_STREET}])
                ->setPostalCode($billingAddress->{Sales_Model_Address::FLD_POSTALCODE} ?: null)
                ->setCity($billingAddress->{Sales_Model_Address::FLD_LOCALITY} ?: null)
                ->setCountry($billingAddress->{Sales_Model_Address::FLD_COUNTRYNAME} ?: null)
            )

            ->addPayment(($payment = new \Einvoicing\Payments\Payment())
                ->setMeansCode('58') // BT-81 Zahlungsart 58 SEPA Überweisung 59 SEPA Einzug
                //->setMeansText('SEPA Überweisung') // BT-82
                ->setId($this->{self::FLD_DOCUMENT_NUMBER}) // BT-83 Verwendungszweck
            )
        ;

        if ($buyerContact) {
            $buyer
                ->setContactName($buyerContact->n_fn)
                ->setContactPhone($buyerContact->tel_work ?: null)
                ->setContactEmail($buyerContact->email ?: null);
        }

        // wenn storno
        /* BT-3
• 326 (Partial invoice)
• 380 (Commercial invoice)
• 384 (Corrected invoice)
• 389 (Self-billed invoice)
• 381 (Credit note)
• 875 (Partial construction invoice)
• 876 (Partial final construction invoice)
• 877 (Final construction invoice)
        */
        if ($this->{self::FLD_POSITIONS}->filter(Sales_Model_DocumentPosition_Abstract::FLD_REVERSAL, 1)->count() > 0) {
            if (!$this->{self::FLD_PRECURSOR_DOCUMENTS} instanceof Tinebase_Record_RecordSet || $this->{self::FLD_PRECURSOR_DOCUMENTS}->count() === 0) {
                throw new Tinebase_Exception_UnexpectedValue('precursor documents on storno/reversal not resolved or present');
            }
            $refDoc = $this->{self::FLD_PRECURSOR_DOCUMENTS}->getFirstRecord()->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD};
            $ublInvoice->setType(384); // BT-3 384 Credit note
            $ublInvoice->addPrecedingInvoiceReference(
                new InvoiceReference($refDoc->{self::FLD_DOCUMENT_NUMBER}, $refDoc->{self::FLD_DOCUMENT_DATE})
            );
        }

        /** @var Sales_Model_DocumentPosition_Invoice $position */
        foreach ($this->{self::FLD_POSITIONS} as $position) {
            if (Sales_Model_DocumentPosition_Invoice::POS_TYPE_PRODUCT !== $position->{Sales_Model_DocumentPosition_Invoice::FLD_TYPE}) {
                continue;
            }
            $ublInvoice->addLine(($line = new \Einvoicing\InvoiceLine())
                ->setPrice($position->{Sales_Model_DocumentPosition_Invoice::FLD_NET_PRICE}, $position->{Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY})
                ->setVatRate($position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE})
                ->setQuantity($position->{Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY})
            );
            if ($position->{Sales_Model_DocumentPosition_Invoice::FLD_TITLE}) {
                $line->setName($position->{Sales_Model_DocumentPosition_Invoice::FLD_TITLE});
            }
            if ($position->{Sales_Model_DocumentPosition_Invoice::FLD_DESCRIPTION}) {
                $line->setDescription($position->{Sales_Model_DocumentPosition_Invoice::FLD_DESCRIPTION});
            }
        }

        /** @var Tinebase_Model_BankAccount $bankAccount */
        foreach ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS} as $bankAccount) {
            $bankAccount = $bankAccount->{Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT};
            $payment->addTransfer((new \Einvoicing\Payments\Transfer())
                ->setAccountName($division->{Sales_Model_Division::FLD_NAME})
                ->setProvider($bankAccount->{Tinebase_Model_BankAccount::FLD_BIC})
                ->setAccountId($bankAccount->{Tinebase_Model_BankAccount::FLD_IBAN})
            );
        }

        if (is_numeric($this->{self::FLD_PAYMENT_TERMS})) {
            $paymentTermDays = (int)$this->{self::FLD_PAYMENT_TERMS};
            if (0 === $paymentTermDays) {
                $paymentTerms = $t->_('Payable immediately without deduction.');
            } else {
                $paymentTerms = str_replace('{days}', (string)$paymentTermDays, $t->_('Payable within {days} days without deduction.'));
            }
            $ublInvoice
                ->setDueDate($this->{self::FLD_DOCUMENT_DATE}->getClone()->addDay($paymentTermDays))
                ->setPaymentTerms((new PaymentTerms())->setNote($paymentTerms));
        }


        // @TODO attachments? (nice to have)
// stundenzettel, rechnungspdf, ...

// Es könnte auch Bezug auf die Bestellung genommen werden (BT-13, BT-14)

// Es könnte auch Bezug auf die Lieferung genommen werden (BT-72, ...)

        return $ublInvoice;
    }
}
