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

    //(new \Einvoicing\Writers\UblWriter)->export($ublInvoice);
    // TODO FIXME all usage of email: validate valid email format before using? as we do not enforce a valid email but xrechnung etc. probably enforce!
    // TODO FIXME check usage of all model properties, either they need to be mandatory and/or a empty/format check needs to be performed before using
    public function toEinvoice(?Sales_Model_Einvoice_PresetInterface $einvoiceConfig = null): \Einvoicing\Invoice
    {
        if (!($debitor = $this->{self::FLD_DEBITOR_ID}) instanceof Sales_Model_Document_Debitor) {
            throw new Tinebase_Exception_UnexpectedValue(self::FLD_DEBITOR_ID . ' not set or resolved');
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

        $ublInvoice = (new \Einvoicing\Invoice($einvoiceConfig->getPresetClassName()))
            ->setNumber($this->{self::FLD_DOCUMENT_NUMBER})
            ->setIssueDate($this->{self::FLD_DOCUMENT_DATE})

            // Abbrechnungszeitruam BT-73 (von) und BT-74 (bis) kann die lib nicht!

            ->setSeller((new \Einvoicing\Party())
                ->setName($division->{Sales_Model_Division::FLD_NAME})
                //    ->setTradingName('Metaways')
                ->setVatNumber($division->{Sales_Model_Division::FLD_VAT_NUMBER})
                ->setTaxRegistrationId($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID} ? new \Einvoicing\Identifier($division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID}, 'FC') : null) // BT-32 // 'FC' aus validen xrechnungen rausgesucht, ist nicht in codeliste, unklar wo das herkommt
                ->setElectronicAddress(new \Einvoicing\Identifier($division->{Sales_Model_Division::FLD_CONTACT_EMAIL}, 'EM'))
                //    ->setElectronicAddress(new Identifier('DE217232623', '9930')) // 9930 German VAT number // division->vat_number
//    ->setCompanyId(new Identifier('buchhaltung@metaways.de', 'EM')) // nicht klar was wir hier nehmen können/müssen
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
                // TODO FIXME ?! where from? ->setElectronicAddress(new \Einvoicing\Identifier('rechnung@erzbistum-hamburg.de', 'EM')) // BT-49* EM = electronic mail
//$buyer ->setElectronicAddress(new Identifier('992-90009-96', '0204')) // 0204 Leitweg-ID
//$buyer ->setElectronicAddress(new Identifier('DE217232623', '9930')) // 9930 German VAT number
                ->setName($billingAddress->{Sales_Model_Address::FLD_NAME} ?: $this->{self::FLD_CUSTOMER_ID}->name)
                ->setAddress([$billingAddress->{Sales_Model_Address::FLD_STREET}])
                ->setPostalCode($billingAddress->{Sales_Model_Address::FLD_POSTALCODE} ?: null)
                ->setCity($billingAddress->{Sales_Model_Address::FLD_LOCALITY} ?: null)
                ->setCountry($billingAddress->{Sales_Model_Address::FLD_COUNTRYNAME} ?: null)
            )

            ->setBuyerReference($this->{self::FLD_CUSTOMER_REFERENCE})
            ->setContractReference($this->{self::FLD_CONTRACT_ID} instanceof Sales_Model_Contract ? $this->{self::FLD_CONTRACT_ID}->number : null)

            // Projektkennung (BT-11) kann die lib nicht, dass haben die leute beim fork von kronos zugefügt
            //->addNote('bla bla'); // BT-22 FLD_DESCRIPTION (wirlich aufnehmen?)

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
            $ublInvoice->addLine((new \Einvoicing\InvoiceLine())
                ->setName($position->{Sales_Model_DocumentPosition_Invoice::FLD_TITLE})
                ->setPrice($position->{Sales_Model_DocumentPosition_Invoice::FLD_GROSS_PRICE})
                ->setVatRate($position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE})
                ->setQuantity($position->{Sales_Model_DocumentPosition_Invoice::FLD_QUANTITY})
            );
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
