<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use UBL21\Common\CommonAggregateComponents\PayerFinancialAccount;
use UBL21\Common\CommonAggregateComponents\PaymentMandate;
use UBL21\Common\CommonAggregateComponents\PaymentMeans;
use UBL21\Common\CommonBasicComponents\ID as UBL_ID;
use UBL21\Invoice\Invoice;

class Sales_Model_EDocument_PMC_PaymentMandate extends Sales_Model_EDocument_PMC_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_PaymentMandate';

    public const FLD_MANDATE_ID = 'mandate_id';
    public const FLD_PAYER_IBAN = 'payer_iban';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TITLE_PROPERTY] = self::FLD_MANDATE_ID;
        $_definition[self::RECORD_NAME] = 'Payment Mandate'; // gettext('GENDER_Payment Mandate')
        $_definition[self::RECORDS_NAME] = 'Payment Mandatea'; // ngettext('Payment Mandate', 'Payment Mandates', n)

        $_definition[self::FIELDS][self::FLD_MANDATE_ID] = [
            self::LABEL         => 'Mandate reference identifier',
            self::TYPE          => self::TYPE_STRING,
            self::VALIDATORS    => [
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
            ],
        ];
        $_definition[self::FIELDS][self::FLD_PAYER_IBAN] = [
            self::LABEL         => 'Debited account identifier (e.g. IBAN)',
            self::TYPE          => self::TYPE_STRING,
            self::VALIDATORS    => [
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
            ],
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function toUblInvoice(Invoice $ublInvoice, Sales_Model_Document_Invoice $invoice, Sales_Model_EDocument_PaymentMeansCode $pmc): void
    {
        parent::toUblInvoice($ublInvoice, $invoice, $pmc);

        $division = $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_CATEGORY}->{Sales_Model_Document_Category::FLD_DIVISION_ID};
        if (empty($division->{Sales_Model_Division::FLD_SEPA_CREDITOR_ID})) {
            throw new Tinebase_Exception_UnexpectedValue('Division has no SEPA Creditor Identification');
        }
    }

    public function toUblPaymentMeans(PaymentMeans $paymentMeans, Sales_Model_EDocument_PaymentMeansCode $pmc): void
    {
        parent::toUblPaymentMeans($paymentMeans, $pmc);
        $paymentMeans->setPaymentMandate((new PaymentMandate)
            ->setID(new UBL_ID($this->{self::FLD_MANDATE_ID}))
            ->setPayerFinancialAccount((new PayerFinancialAccount())
                ->setID(new UBL_ID($this->{self::FLD_PAYER_IBAN}))
            )
        );
    }
}