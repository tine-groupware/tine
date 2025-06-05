<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch dynamic config
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @property Sales_Model_EDocument_Dispatch_Interface $dispatch_config
 */
class Sales_Model_EDocument_Dispatch_DynamicConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_DynamicConfig';
    public const FLD_DISPATCH_CONFIG = 'dispatch_config';
    public const FLD_DISPATCH_TYPE = 'dispatch_type';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Custom Config', // gettext('GENDER_Custom Config')
        self::RECORDS_NAME              => 'Custom Configs', // ngettext('Custom Config', 'Custom Configs', n)
        self::TITLE_PROPERTY            => self::FLD_DISPATCH_TYPE,
        self::IS_METADATA_MODEL_FOR     => self::FLD_DISPATCH_TYPE,

        self::FIELDS                    => [
            self::FLD_DISPATCH_TYPE         => [
                self::TYPE                      => self::TYPE_MODEL,
                self::LABEL                     => 'Electronic Document Transport Method', // _('Electronic Document Transport Method')
                self::DEFAULT_VAL               => Sales_Model_EDocument_Dispatch_Manual::class,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ],
                ],
                self::INPUT_FILTERS => [
                    Zend_Filter_Empty::class => Sales_Model_EDocument_Dispatch_Manual::class,
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::DEFAULT_VALUE => Sales_Model_EDocument_Dispatch_Manual::class,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    'includeAppName'                    => false,
                    'useRecordName'                     => true,
                ],
            ],
            self::FLD_DISPATCH_CONFIG       => [
                self::LABEL                     => 'Electronic Document Transport Config', // _('Electronic Document Transport Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_DISPATCH_TYPE,
                    self::PERSISTENT                => true,
                    self::SET_DEFAULT_INSTANCE      => true,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    [Tinebase_Record_Validator_SubValidate::class],
                ],
            ],
        ],
    ];

    protected static $_configurationObject = null;
}