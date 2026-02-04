<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use UBL21\Invoice\Invoice;

class Sales_Model_PurchasePaymentMeans extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'PurchasePaymentMeans';

    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_CONFIG = 'config';
    public const FLD_PAYMENT_MEANS_CODE = 'payment_means_code';
    public const FLD_PAYMENT_MEANS_TEXT = 'payment_means_text';
    public const FLD_REMITTANCE_INFORMATION = 'remittance_information';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::IS_METADATA_MODEL_FOR         => self::FLD_PAYMENT_MEANS_CODE,

        self::RECORD_NAME                   => 'Payment Means', // gettext('GENDER_Payment Means')
        self::RECORDS_NAME                  => 'Payment Means', // ngettext('Payment Means', 'Payment Means', n)

        self::TITLE_PROPERTY                => '{{ payment_means_code.name }}',

        self::FIELDS                        => [
            self::FLD_PAYMENT_MEANS_CODE             => [
                self::TYPE                          => self::TYPE_RECORD,
                self::LABEL                         => 'Payment Means', // _('Payment Means')
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_EDocument_PaymentMeansCode::MODEL_NAME_PART,
                ],
                self::INPUT_FILTERS                 => [
                    Tinebase_Record_Filter_RecordId::class,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [ // TODO FIXME do we need this?!
                    'copyOnSelectProps'          => [
                        Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS,
                    ],
                ]
            ],
            self::FLD_PAYMENT_MEANS_TEXT        => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_REMITTANCE_INFORMATION    => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_CONFIG_CLASS              => [
                self::TYPE                          => self::TYPE_MODEL,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Sales_Model_EDocument_PMC_PurchaseCreditTransfer::class,
                        Sales_Model_EDocument_PMC_PurchaseDirectDebit::class,
                        Sales_Model_EDocument_PMC_PurchasePaymentCard::class,
                    ],
                ],
                self::UI_CONFIG                      => [
                    self::DISABLED                      => true,
                ]
            ],
            self::FLD_CONFIG                    => [
                self::LABEL                         => 'Config', // _('Config')
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_CONFIG_CLASS,
                    self::PERSISTENT                    => true,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function setFromArray(array &$_data)
    {
        if (!isset($_data[self::ID])) {
            $_data[self::ID] = Tinebase_Record_Abstract::generateUID();
        }
        parent::setFromArray($_data);
    }

    public static function fromXrXML(SimpleXMLElement $element): Tinebase_Record_RecordSet
    {
        $result = new Tinebase_Record_RecordSet(static::class);

        if ('' === ($pmc = (string)$element->Payment_means_type_code)) { // 1
            return $result;
        }
        if (null === ($pmc = Sales_Controller_EDocument_PaymentMeansCode::getInstance()->getByCode($pmc))) {
            $e = new Tinebase_Exception('payment means code '. (string)$element->Payment_means_type_code . ' not found');
            $e->setLogToSentry(false);
            $e->setLogLevelMethod('warn');
            Tinebase_Exception::log($e);
            return $result;
        }
        if ('' === ($ri = (string)$element->Remittance_information)) { // 0..1
            $ri = null;
        }
        if ('' === ($pmt = (string)$element->Payment_means_text)) { // 0..1
            $pmt = null;
        }

        $purchasePaymentMeans = null;
        // 0..* CREDIT_TRANSFER
        foreach ($element->CREDIT_TRANSFER as $creditTransfer) {
            $purchasePaymentMeans = new static([
                self::FLD_PAYMENT_MEANS_CODE => $pmc,
                self::FLD_REMITTANCE_INFORMATION => $ri,
                self::FLD_PAYMENT_MEANS_TEXT => $pmt,
                self::FLD_CONFIG_CLASS => Sales_Model_EDocument_PMC_PurchaseCreditTransfer::class,
                self::FLD_CONFIG => ($pct = new Sales_Model_EDocument_PMC_PurchaseCreditTransfer([
                    Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_ACCOUNT_IDENTIFIER => (string) $creditTransfer->Payment_account_identifier, // 1
                ])),
            ]);
            if ('' !== (string)$creditTransfer->Payment_account_name) { // 0..1
                $pct->{Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_ACCOUNT_NAME} = $creditTransfer->Payment_account_name;
            }
            if ('' !== (string)$creditTransfer->Payment_service_provider_identifier) { // 0..1
                $pct->{Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_SERVICE_PROVIDER_IDENTIFIER} = $creditTransfer->Payment_service_provider_identifier;
            }
            $result->addRecord($purchasePaymentMeans);
        }
        // 0..* PAYMENT_CARD_INFORMATION
        foreach ($element->PAYMENT_CARD_INFORMATION as $paymentCardInfo) {
            $purchasePaymentMeans = new static([
                self::FLD_PAYMENT_MEANS_CODE => $pmc,
                self::FLD_REMITTANCE_INFORMATION => $ri,
                self::FLD_PAYMENT_MEANS_TEXT => $pmt,
                self::FLD_CONFIG_CLASS => Sales_Model_EDocument_PMC_PurchasePaymentCard::class,
                self::FLD_CONFIG => ($ppc = new Sales_Model_EDocument_PMC_PurchasePaymentCard([
                    Sales_Model_EDocument_PMC_PurchasePaymentCard::FLD_PRIMARY_ACCOUNT_NUMBER => (string) $paymentCardInfo->Payment_card_primary_account_number, // 1
                ])),
            ]);
            if ('' !== (string)$paymentCardInfo->Payment_card_holder_name) { // 0..1
                $ppc->{Sales_Model_EDocument_PMC_PurchasePaymentCard::FLD_CARD_HOLDER_NAME} = $paymentCardInfo->Payment_card_holder_name;
            }
            $result->addRecord($purchasePaymentMeans);
        }
        // 0..* DIRECT_DEBIT
        foreach ($element->DIRECT_DEBIT as $directDebit) {
            $purchasePaymentMeans = new static([
                self::FLD_PAYMENT_MEANS_CODE => $pmc,
                self::FLD_REMITTANCE_INFORMATION => $ri,
                self::FLD_PAYMENT_MEANS_TEXT => $pmt,
                self::FLD_CONFIG_CLASS => Sales_Model_EDocument_PMC_PurchaseDirectDebit::class,
                self::FLD_CONFIG => ($pdd = new Sales_Model_EDocument_PMC_PurchaseDirectDebit()),
            ]);
            if ('' !== (string)$directDebit->Bank_assigned_creditor_identifier) { // 0..1
                $pdd->{Sales_Model_EDocument_PMC_PurchaseDirectDebit::FLD_CREDITOR_IDENTIFIER} = $directDebit->Bank_assigned_creditor_identifier;
            }
            if ('' !== (string)$directDebit->Debited_account_identifier) { // 0..1
                $pdd->{Sales_Model_EDocument_PMC_PurchaseDirectDebit::FLD_IBAN} = $directDebit->Debited_account_identifier;
            }
            $result->addRecord($purchasePaymentMeans);
        }

        if (null === $purchasePaymentMeans) {
            $result->addRecord(new static([
                self::FLD_PAYMENT_MEANS_CODE => $pmc,
                self::FLD_REMITTANCE_INFORMATION => $ri,
                self::FLD_PAYMENT_MEANS_TEXT => $pmt,
            ]));
        }

        return $result;
    }
}