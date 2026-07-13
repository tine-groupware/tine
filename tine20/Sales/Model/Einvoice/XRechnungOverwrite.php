<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use UBL21\Common\CommonAggregateComponents\PayeeParty;

/**
 * Einvoice XRechnung Overwrite config
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Einvoice_XRechnungOverwrite extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Einvoice_XRechnungOverwrite';

    public const ACTION_DELETE = 'delete';
    public const ACTION_DYNAMIC = 'dynamic';
    public const ACTION_STATIC = 'static';

    public const FLD_VALUE = 'value';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_ACTION = 'action';
    public const FLD_XRECHNUNG_ELEMENT = 'xrechnung_element';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'XRechnung Overwrite', // ngettext('XRechnung Overwrite', 'XRechnung Overwrites', n)
        self::RECORDS_NAME                  => 'XRechnung Overwrites', // gettext('GENDER_XRechnung Overwrite')
        self::TITLE_PROPERTY                 => '[{{ keyField("Sales", "xRechnungOverwriteAction", action) }}] {{ renderTitle(xrechnung_element, "Sales_Model_EDocument_XRechnungElement") }}{% if action != "delete" %} => {{ value }}{% endif %}',

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_XRECHNUNG_ELEMENT   => [],
            ],
        ],
        self::FIELDS                    => [
            self::FLD_XRECHNUNG_ELEMENT     => [
                self::LABEL                     => 'XRechnung Element', // _('XRechnung Element')
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_XRechnungElement::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                 => [
                    'additionalFilters'             => [[
                        Tinebase_Model_Filter_Abstract::FIELD       => Sales_Model_EDocument_XRechnungElement::FLD_IS_OVERRIDEABLE,
                        Tinebase_Model_Filter_Abstract::OPERATOR    => Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
                        Tinebase_Model_Filter_Abstract::VALUE       => true
                    ]]

                ],
            ],
            self::FLD_ACTION                => [
                self::LABEL                     => 'Action', // _('Action')
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::NAME                      => Sales_Config::XRECHNUNG_OVERWRITE_ACTION,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        self::ACTION_DELETE,
                        self::ACTION_DYNAMIC,
                        self::ACTION_STATIC,
                    ]],
                ],
            ],
            self::FLD_VALUE                 => [
                self::LABEL                     => 'Value', // _('Value')
                self::TYPE                      => self::TYPE_STRING,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                      => 'Description', // _('Description')
                self::TYPE                       => self::TYPE_TEXT,
            ],
        ],
    ];

    public function executeOverwrite(Sales_Model_Document_Invoice $record, UBL21\Invoice\Invoice $ublInvoice): void
    {
        $value = null;
        if ($this->{self::FLD_ACTION} === self::ACTION_DYNAMIC) {
            $template = $this->{self::FLD_VALUE};
            $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(), [
                Tinebase_Twig::TWIG_LOADER =>
                    new Tinebase_Twig_CallBackLoader($tmplKey = __METHOD__ . $record->getId(), time() - 1, function () use ($template) {
                        return $template;
                    })
            ]);
            $value = $twig->load($tmplKey)->render([
                'record' => $record,
            ]);
        } elseif ($this->{self::FLD_ACTION} === self::ACTION_STATIC) {
            $value = $this->{self::FLD_VALUE};
        }

        switch ($this->{self::FLD_XRECHNUNG_ELEMENT}->{Sales_Model_EDocument_XRechnungElement::FLD_BT_NUMBER}) {
            case 'BT-3':
                $ublInvoice->setInvoiceTypeCode($this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new \UBL21\Common\CommonBasicComponents\InvoiceTypeCode($value));
                break;
            case 'BT-7':
                $ublInvoice->setTaxPointDate($this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new DateTime($value));
                break;
            case 'BT-8':
                $period = $ublInvoice->getInvoicePeriod()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $period) {
                        $period->setDescriptionCode();
                    }
                    break;
                }
                if (null === $period) {
                    $period = new \UBL21\Common\CommonAggregateComponents\InvoicePeriod();
                    $ublInvoice->setInvoicePeriod([$period]);
                }
                $period->setDescriptionCode([new \UBL21\Common\CommonBasicComponents\DescriptionCode($value)]);
                break;
            case 'BT-10':
                $ublInvoice->setBuyerReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new \UBL21\Common\CommonBasicComponents\BuyerReference($value));
                break;
            case 'BT-11':
                $ublInvoice->setProjectReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [(new \UBL21\Common\CommonAggregateComponents\ProjectReference())->setID(new \UBL21\Common\CommonBasicComponents\ID($value))]);
                break;
            case 'BT-12':
                $ublInvoice->setContractDocumentReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [(new \UBL21\Common\CommonAggregateComponents\ContractDocumentReference())->setID(new \UBL21\Common\CommonBasicComponents\ID($value))]);
                break;
            case 'BT-13':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null === $ublInvoice->getOrderReference()) {
                        break;
                    }
                    if (null === $ublInvoice->getOrderReference()->getSalesOrderID()) {
                        $ublInvoice->setOrderReference();
                    } else {
                        $ublInvoice->getOrderReference()->setID(new \UBL21\Common\CommonBasicComponents\ID('NA'));
                    }
                    break;
                }
                if (null === $ublInvoice->getOrderReference()) {
                    $ublInvoice->setOrderReference(new \UBL21\Common\CommonAggregateComponents\OrderReference);
                }
                $ublInvoice->getOrderReference()->setID(new \UBL21\Common\CommonBasicComponents\ID($value));
                break;
            case 'BT-14':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null === $ublInvoice->getOrderReference()?->getSalesOrderID()) {
                        break;
                    }
                    if ($ublInvoice->getOrderReference()->getID()->value() === 'NA') {
                        $ublInvoice->setOrderReference();
                    } else {
                        $ublInvoice->getOrderReference()->setSalesOrderID();
                    }
                    break;
                }
                if (null === $ublInvoice->getOrderReference()) {
                    $ublInvoice->setOrderReference((new \UBL21\Common\CommonAggregateComponents\OrderReference)
                        ->setID(new \UBL21\Common\CommonBasicComponents\ID('NA')));
                }
                $ublInvoice->getOrderReference()->setSalesOrderID(new \UBL21\Common\CommonBasicComponents\SalesOrderID($value));
                break;
            case 'BT-15':
                $ublInvoice->setReceiptDocumentReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [(new \UBL21\Common\CommonAggregateComponents\ReceiptDocumentReference())->setID(new \UBL21\Common\CommonBasicComponents\ID($value))]);
                break;
            case 'BT-16':
                $ublInvoice->setDespatchDocumentReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [(new \UBL21\Common\CommonAggregateComponents\DespatchDocumentReference())->setID(new \UBL21\Common\CommonBasicComponents\ID($value))]);
                break;
            case 'BT-18':
                $ublInvoice->setAdditionalDocumentReference($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [(new \UBL21\Common\CommonAggregateComponents\AdditionalDocumentReference())->setID(new \UBL21\Common\CommonBasicComponents\ID($value))]);
                break;
            case 'BT-19':
                $ublInvoice->setAccountingCost($this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new \UBL21\Common\CommonBasicComponents\AccountingCost($value));
                break;
            case 'BT-20':
                $paymentTerm = $ublInvoice->getPaymentTerms()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $paymentTerm) {
                        $paymentTerm->setNote([]);
                    }
                    break;
                }
                if (null === $paymentTerm) {
                    $paymentTerm = new \UBL21\Common\CommonAggregateComponents\PaymentTerms();
                    $ublInvoice->setPaymentTerms([$paymentTerm]);
                }
                $paymentTerm->setNote([new \UBL21\Common\CommonBasicComponents\Note($value)]);
                break;
            case 'BT-25':
                $billingReference = $ublInvoice->getBillingReference()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $billingReference?->setInvoiceDocumentReference();
                    break;
                }
                if (null === $billingReference) {
                    $billingReference = new \UBL21\Common\CommonAggregateComponents\BillingReference();
                    $ublInvoice->setBillingReference([$billingReference]);
                }
                if (null === ($invoiceDocRef = $billingReference->getInvoiceDocumentReference())) {
                    $invoiceDocRef = new \UBL21\Common\CommonAggregateComponents\InvoiceDocumentReference();
                    $billingReference->setInvoiceDocumentReference($invoiceDocRef);
                }
                $invoiceDocRef->setID(new \UBL21\Common\CommonBasicComponents\ID($value));
                break;
            case 'BT-26':
                $billingReference = $ublInvoice->getBillingReference()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $billingReference?->getInvoiceDocumentReference()?->setIssueDate();
                    break;
                }
                if (null === $billingReference) {
                    $billingReference = new \UBL21\Common\CommonAggregateComponents\BillingReference();
                    $ublInvoice->setBillingReference([$billingReference]);
                }
                if (null === ($invoiceDocRef = $billingReference->getInvoiceDocumentReference())) {
                    $invoiceDocRef = new \UBL21\Common\CommonAggregateComponents\InvoiceDocumentReference();
                    $billingReference->setInvoiceDocumentReference($invoiceDocRef);
                }
                $invoiceDocRef->setIssueDate(new DateTime($value));
                break;
            case 'BT-27':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPartyLegalEntity()[0]->setRegistrationName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new \UBL21\Common\CommonBasicComponents\RegistrationName($value));
                break;
            case 'BT-28':
                $ublInvoice->getAccountingSupplierParty()->getParty()->setPartyName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                        [(new \UBL21\Common\CommonAggregateComponents\PartyName)->setName(new \UBL21\Common\CommonBasicComponents\Name($value))]
                );
                break;
            case 'BT-33':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPartyLegalEntity()[0]->setCompanyLegalForm(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\CompanyLegalForm($value));
                break;
            case 'BT-35':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setStreetName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\StreetName($value));
                break;
            case 'BT-36':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setAdditionalStreetName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\AdditionalStreetName($value));
                break;
            case 'BT-37':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setCityName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\CityName($value));
                break;
            case 'BT-38':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setPostalZone(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\PostalZone($value));
                break;
            case 'BT-39':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setCountrySubentity(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new UBL21\Common\CommonBasicComponents\CountrySubentity($value));
                break;
            case 'BT-40':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setCountry(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        (new \UBL21\Common\CommonAggregateComponents\Country())->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode($value)));
                break;
            case 'BT-41':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getContact()->setName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\Name($value));
                break;
            case 'BT-42':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getContact()->setTelephone(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\Telephone($value));
                break;
            case 'BT-43':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getContact()->setElectronicMail(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\ElectronicMail($value));
                break;
            case 'BT-44':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPartyLegalEntity()[0]->setRegistrationName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\RegistrationName($value));
                break;
            case 'BT-45':
                $ublInvoice->getAccountingCustomerParty()->getParty()->setPartyName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                        [(new \UBL21\Common\CommonAggregateComponents\PartyName)->setName(new \UBL21\Common\CommonBasicComponents\Name($value))]
                );
                break;
            case 'BT-46':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    ($ublInvoice->getAccountingCustomerParty()->getParty()->getPartyIdentification()[0] ?? null)?->setID(null);
                    break;
                }
                if (null === ($partyIdentification = $ublInvoice->getAccountingCustomerParty()->getParty()->getPartyIdentification()[0] ?? null)) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()->setPartyIdentification([$partyIdentification = new \UBL21\Common\CommonAggregateComponents\PartyIdentification()]);
                }
                $partyIdentification->setID(new \UBL21\Common\CommonBasicComponents\ID($value));
                break;
            case 'BT-50':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setStreetName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\StreetName($value));
                break;
            case 'BT-51':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setAdditionalStreetName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\AdditionalStreetName($value));
                break;
            case 'BT-52':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setCityName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\CityName($value));
                break;
            case 'BT-53':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setPostalZone(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new \UBL21\Common\CommonBasicComponents\PostalZone($value));
                break;
            case 'BT-54':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setCountrySubentity(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        new UBL21\Common\CommonBasicComponents\CountrySubentity($value));
                break;
            case 'BT-55':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setCountry(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                        (new \UBL21\Common\CommonAggregateComponents\Country())->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode($value)));
                break;
            case 'BT-56':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()?->getContact()?->setName();
                    break;
                }
                if (null === ($contact = $ublInvoice->getAccountingCustomerParty()->getParty()->getContact())) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()->setContact($contact = new \UBL21\Common\CommonAggregateComponents\Contact);
                }
                $contact->setName(new \UBL21\Common\CommonBasicComponents\Name($value));
                break;
            case 'BT-57':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()?->getContact()?->setTelephone();
                    break;
                }
                if (null === ($contact = $ublInvoice->getAccountingCustomerParty()->getParty()->getContact())) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()->setContact($contact = new \UBL21\Common\CommonAggregateComponents\Contact);
                }
                $contact->setTelephone(new \UBL21\Common\CommonBasicComponents\Telephone($value));
                break;
            case 'BT-58':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()?->getContact()?->setElectronicMail();
                    break;
                }
                if (null === ($contact = $ublInvoice->getAccountingCustomerParty()->getParty()->getContact())) {
                    $ublInvoice->getAccountingCustomerParty()->getParty()->setContact($contact = new \UBL21\Common\CommonAggregateComponents\Contact);
                }
                $contact->setElectronicMail(new \UBL21\Common\CommonBasicComponents\ElectronicMail($value));
                break;
            case 'BT-59':
                $payeeParty = $ublInvoice->getPayeeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $payeeParty) {
                        $payeeParty->setPartyName([]);
                    }
                    break;
                }
                if (null === $payeeParty) {
                    $ublInvoice->setPayeeParty($payeeParty = new \UBL21\Common\CommonAggregateComponents\PayeeParty());
                }
                $payeeParty->setPartyName([(new UBL21\Common\CommonAggregateComponents\PartyName)->setName(new \UBL21\Common\CommonBasicComponents\Name($value))]);
                break;
            case 'BT-60':
                foreach ($ids = ($ublInvoice->getPayeeParty()?->getPartyIdentification() ?? []) as $key => $id) {
                    if ('SEPA' !== $id->getID()->getSchemeID()) {
                        unset($ids[$key]);
                    }
                }
                $payeeParty = $ublInvoice->getPayeeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $payeeParty) {
                        $payeeParty->setPartyIdentification($ids);
                    }
                    break;
                }
                if (null === $payeeParty) {
                    $ublInvoice->setPayeeParty($payeeParty = new \UBL21\Common\CommonAggregateComponents\PayeeParty());
                }
                $ids[] = (new \UBL21\Common\CommonAggregateComponents\PartyIdentification)->setID(new \UBL21\Common\CommonBasicComponents\ID($value));
                $payeeParty->setPartyIdentification($ids);
                break;
            case 'BT-64':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setStreetName();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setStreetName(new \UBL21\Common\CommonBasicComponents\StreetName($value));
                break;
            case 'BT-65':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setAdditionalStreetName();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setAdditionalStreetName(new \UBL21\Common\CommonBasicComponents\AdditionalStreetName($value));
                break;
            case 'BT-66':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setCityName();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setCityName(new \UBL21\Common\CommonBasicComponents\CityName($value));
                break;
            case 'BT-67':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setPostalZone();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setPostalZone(new \UBL21\Common\CommonBasicComponents\PostalZone($value));
                break;
            case 'BT-68':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setCountrySubentity();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setCountrySubentity(new \UBL21\Common\CommonBasicComponents\CountrySubentity($value));
                break;
            case 'BT-69':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $taxParty?->getPostalAddress()) {
                        $taxParty->getPostalAddress()->setCountry();
                    }
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setCountry((new \UBL21\Common\CommonAggregateComponents\Country)->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode($value)));
                break;
            case 'BT-70':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryParty()) {
                        $delivery->setDeliveryParty();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryParty = $delivery->getDeliveryParty())) {
                    $delivery->setDeliveryParty($deliveryParty = new \UBL21\Common\CommonAggregateComponents\DeliveryParty());
                }
                if (null === ($deliveryPartyName = ($deliveryParty->getPartyName()[0] ?? null))) {
                    $deliveryParty->setPartyName([$deliveryPartyName = new \UBL21\Common\CommonAggregateComponents\PartyName()]);
                }
                $deliveryPartyName->setName(new \UBL21\Common\CommonBasicComponents\Name($value));
                break;
            case 'BT-71':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getID()) {
                        $delivery->setDeliveryLocation()->setID();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                $deliveryLocation->setID(new \UBL21\Common\CommonBasicComponents\ID($value));
                break;
            case 'BT-72':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getActualDeliveryDate()) {
                        $delivery->setActualDeliveryDate();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                $delivery->setActualDeliveryDate(new DateTime($value));
                break;
            case 'BT-75':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setStreetName();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setStreetName(new \UBL21\Common\CommonBasicComponents\StreetName($value));
                break;
            case 'BT-76':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setAdditionalStreetName();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setAdditionalStreetName(new \UBL21\Common\CommonBasicComponents\AdditionalStreetName($value));
                break;
            case 'BT-77':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setCityName();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setCityName(new \UBL21\Common\CommonBasicComponents\CityName($value));
                break;
            case 'BT-78':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setPostalZone();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setPostalZone(new \UBL21\Common\CommonBasicComponents\PostalZone($value));
                break;
            case 'BT-79':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setCountrySubentity();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setCountrySubentity(new \UBL21\Common\CommonBasicComponents\CountrySubentity($value));
                break;
            case 'BT-80':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $delivery?->getDeliveryLocation()?->getAddress()) {
                        $delivery->getDeliveryLocation()->getAddress()->setCountry();
                    }
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setCountry((new \UBL21\Common\CommonAggregateComponents\Country)->setIdentificationCode(new \UBL21\Common\CommonBasicComponents\IdentificationCode($value)));
                break;
            case 'BT-81':
                ($ublInvoice->getPaymentMeans()[0])->setPaymentMeansCode(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null :
                    new \UBL21\Common\CommonBasicComponents\PaymentMeansCode($value));
                break;
            case 'BT-82':
                ($ublInvoice->getPaymentMeans()[0])->getPaymentMeansCode()->setName(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null : $value);
                break;
            case 'BT-83':
                ($ublInvoice->getPaymentMeans()[0])->setPaymentID($this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                    [new \UBL21\Common\CommonBasicComponents\PaymentID($value)]);
                break;
            case 'BT-87':
                $cardAccount = $ublInvoice->getPaymentMeans()[0]->getCardAccount();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $cardAccount) {
                        $ublInvoice->getPaymentMeans()[0]->setCardAccount();
                    }
                    break;
                }
                if (null === $cardAccount) {
                    $ublInvoice->getPaymentMeans()[0]->setCardAccount($cardAccount = new \UBL21\Common\CommonAggregateComponents\CardAccount());
                    $cardAccount->setNetworkID(new \UBL21\Common\CommonBasicComponents\NetworkID('xxx'));
                }
                $cardAccount->setPrimaryAccountNumberID(new \UBL21\Common\CommonBasicComponents\PrimaryAccountNumberID($value));
                break;
            case 'BT-88':
                $cardAccount = $ublInvoice->getPaymentMeans()[0]->getCardAccount();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $cardAccount) {
                        $ublInvoice->getPaymentMeans()[0]->setCardAccount();
                    }
                    break;
                }
                if (null === $cardAccount) {
                    $ublInvoice->getPaymentMeans()[0]->setCardAccount($cardAccount = new \UBL21\Common\CommonAggregateComponents\CardAccount());
                    $cardAccount->setNetworkID(new \UBL21\Common\CommonBasicComponents\NetworkID('xxx'));
                }
                $cardAccount->setHolderName(new \UBL21\Common\CommonBasicComponents\HolderName($value));
                break;
            case 'BT-89':
                $mandate = $ublInvoice->getPaymentMeans()[0]->getPaymentMandate();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    if (null !== $mandate) {
                        $ublInvoice->getPaymentMeans()[0]->setPaymentMandate();
                    }
                    break;
                }
                if (null === $mandate) {
                    $ublInvoice->getPaymentMeans()[0]->setPaymentMandate($mandate = new UBL21\Common\CommonAggregateComponents\PaymentMandate());
                }
                $mandate->setId(new \UBL21\Common\CommonBasicComponents\ID($value));
                break;
            case 'BT-90':
                if (null !== $ublInvoice->getPayeeParty()) {
                    foreach ($ids = ($ublInvoice->getPayeeParty()->getPartyIdentification() ?? []) as $key => $id) {
                        if ('SEPA' === $id->getID()->getSchemeID()) {
                            unset($ids[$key]);
                        }
                    }
                    if ($this->{self::FLD_ACTION} !== self::ACTION_DELETE) {
                        $ids[] = (new UBL21\Common\CommonAggregateComponents\PartyIdentification())
                            ->setID((new UBL21\Common\CommonBasicComponents\ID($value))->setSchemeID('SEPA'));
                    }
                    $ublInvoice->getPayeeParty()?->setPartyIdentification($ids);
                }
                foreach ($ids = $ublInvoice->getAccountingSupplierParty()->getParty()->getPartyIdentification() as $key => $id) {
                    if ('SEPA' === $id->getID()->getSchemeID()) {
                        unset($ids[$key]);
                    }
                }
                if ($this->{self::FLD_ACTION} !== self::ACTION_DELETE) {
                    $ids[] = (new UBL21\Common\CommonAggregateComponents\PartyIdentification())
                        ->setID((new UBL21\Common\CommonBasicComponents\ID($value))->setSchemeID('SEPA'));
                }
                $ublInvoice->getAccountingSupplierParty()->getParty()->setPartyIdentification($ids);
                break;
            case 'BT-91':
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    ($ublInvoice->getPaymentMeans()[0] ?? null)?->getPaymentMandate()?->setPayerFinancialAccount();
                    break;
                }
                $paymentMeans = $ublInvoice->getPaymentMeans()[0];
                if (null === ($paymentMandate = $paymentMeans->getPaymentMandate())) {
                    $paymentMeans->setPaymentMandate($paymentMandate = new UBL21\Common\CommonAggregateComponents\PaymentMandate());
                }
                $paymentMandate->setPayerFinancialAccount((new UBL21\Common\CommonAggregateComponents\PayerFinancialAccount())->setId(new UBL21\Common\CommonBasicComponents\ID($value)));
                break;
            case 'BT-113':
                $ublInvoice->getLegalMonetaryTotal()->setPrepaidAmount(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null : (new UBL21\Common\CommonBasicComponents\PrepaidAmount($value))->setCurrencyID('EUR')
                );
                break;
            case 'BT-115':
                $ublInvoice->getLegalMonetaryTotal()->setPayableAmount(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? null : (new UBL21\Common\CommonBasicComponents\PayableAmount($value))->setCurrencyID('EUR')
                );
                break;
            case 'BT-162':
                $ublInvoice->getAccountingSupplierParty()->getParty()->getPostalAddress()->setAddressLine(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                        [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                            ->setLine(new \UBL21\Common\CommonBasicComponents\Line($value))]);
                break;
            case 'BT-163':
                $ublInvoice->getAccountingCustomerParty()->getParty()->getPostalAddress()->setAddressLine(
                    $this->{self::FLD_ACTION} === self::ACTION_DELETE ? [] :
                        [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                            ->setLine(new \UBL21\Common\CommonBasicComponents\Line($value))]);
                break;
            case 'BT-164':
                $taxParty = $ublInvoice->getTaxRepresentativeParty();
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $taxParty?->getPostalAddress()?->setAddressLine([]);
                    break;
                }
                if (null === $taxParty) {
                    $ublInvoice->setTaxRepresentativeParty($taxParty = new \UBL21\Common\CommonAggregateComponents\TaxRepresentativeParty());
                }
                if (null === ($postalAddress = $taxParty->getPostalAddress())) {
                    $taxParty->setPostalAddress($postalAddress = new \UBL21\Common\CommonAggregateComponents\PostalAddress());
                }
                $postalAddress->setAddressLine(
                    [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                        ->setLine(new \UBL21\Common\CommonBasicComponents\Line($value))]);
                break;
            case 'BT-165':
                $delivery = $ublInvoice->getDelivery()[0] ?? null;
                if ($this->{self::FLD_ACTION} === self::ACTION_DELETE) {
                    $delivery?->getDeliveryLocation()?->getAddress()?->setAddressLine([]);
                    break;
                }
                if (null === $delivery) {
                    $ublInvoice->setDelivery([$delivery = new \UBL21\Common\CommonAggregateComponents\Delivery()]);
                }
                if (null === ($deliveryLocation = $delivery->getDeliveryLocation())) {
                    $delivery->setDeliveryLocation($deliveryLocation = new \UBL21\Common\CommonAggregateComponents\DeliveryLocation());
                }
                if (null === ($address = $deliveryLocation->getAddress())) {
                    $deliveryLocation->setAddress($address = new \UBL21\Common\CommonAggregateComponents\Address());
                }
                $address->setAddressLine(
                    [(new \UBL21\Common\CommonAggregateComponents\AddressLine())
                        ->setLine(new \UBL21\Common\CommonBasicComponents\Line($value))]);
                break;
        }
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}