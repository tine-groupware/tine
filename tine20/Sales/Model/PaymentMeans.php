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

use UBL21\Invoice\Invoice;

class Sales_Model_PaymentMeans extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'PaymentMeans';

    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_CONFIG = 'config';
    public const FLD_PAYMENT_MEANS_CODE = 'payment_means_code';
    public const FLD_DEFAULT = 'default';

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
                self::UI_CONFIG                     => [
                    'copyOnSelectProps'          => [
                        Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS,
                    ],
                ]
            ],
            self::FLD_DEFAULT                   => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::LABEL                         => 'Default', // _('Default')
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => false,
                ],
            ],
            self::FLD_CONFIG_CLASS              => [
                self::TYPE                          => self::TYPE_MODEL,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Sales_Model_EDocument_PMC_NoConfig::class,
                        Sales_Model_EDocument_PMC_PayeeFinancialAccount::class,
                    ],
                ],
                self::INPUT_FILTERS                 => [
                    Zend_Filter_Empty::class            => Sales_Model_EDocument_PMC_NoConfig::class,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::DEFAULT_VALUE    => Sales_Model_EDocument_PMC_NoConfig::class,
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
                    self::SET_DEFAULT_INSTANCE          => true,
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

    public function toUblInvoice(Invoice $ublInvoice, Sales_Model_Document_Invoice $invoice): void
    {
        $this->{self::FLD_CONFIG}->toUblInvoice($ublInvoice, $invoice, $this->{self::FLD_PAYMENT_MEANS_CODE});
    }

    public function setFromArray(array &$_data)
    {
        if (!isset($_data[self::ID])) {
            $_data[self::ID] = Tinebase_Record_Abstract::generateUID();
        }
        parent::setFromArray($_data);
    }
}