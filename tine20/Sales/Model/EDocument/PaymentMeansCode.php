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

use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Model_EDocument_PaymentMeansCode extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_PaymentMeansCode';
    public const TABLE_NAME = 'edocument_payment_means_code';

    public const FLD_NAME = 'name';
    public const FLD_CODE = 'code';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_CONFIG_CLASS = 'config_class';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 2,
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Payment Means' , // ngettext('Payment Means', 'Payment Means', n)
        self::RECORDS_NAME                  => 'Payment Means', // gettext('GENDER_Payment Means')
        self::TITLE_PROPERTY                => self::FLD_NAME,
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,
        self::HAS_DELETED_TIME_UNIQUE       => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NAME                  => [
                    self::COLUMNS                   => [self::FLD_NAME, self::FLD_DELETED_TIME]
                ],
                self::FLD_CODE                  => [
                    self::COLUMNS                   => [self::FLD_CODE, self::FLD_DELETED_TIME]
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_CODE                      => [
                self::LABEL                         => 'Code', // _('Code')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_NAME                      => [
                self::LABEL                         => 'Name', // _('Name')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_TEXT,
                self::QUERY_FILTER                  => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_CONFIG_CLASS              => [
                self::LABEL                         => 'Config Class', // _('Config Class')
                self::TYPE                          => self::TYPE_MODEL,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Sales_Model_EDocument_PMC_NoConfig::class,
                        Sales_Model_EDocument_PMC_PayeeFinancialAccount::class,
                    ],
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Empty::class            => Sales_Model_EDocument_PMC_NoConfig::class,
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
}