<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch dynamic config
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_DynamicConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_DynamicConfig';
    public const FLD_DISPATCH_CONFIG = 'dispatch_config';
    public const FLD_DISPATCH_TYPE = 'dispatch_type';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

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
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Empty::class => Sales_Model_EDocument_Dispatch_Manual::class,
                    Zend_Filter_Input::DEFAULT_VALUE => Sales_Model_EDocument_Dispatch_Manual::class,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ]],
                ]
            ],
            self::FLD_DISPATCH_CONFIG       => [
                self::LABEL                     => 'Electronic Document Transport Config', // _('Electronic Document Transport Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_DISPATCH_TYPE,
                    self::PERSISTENT                => true,
                ],
                self::INPUT_FILTERS         => [
                    Zend_Filter_Empty::class => [[]],
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [[]],
                    [Tinebase_Record_Validator_SubValidate::class, [Tinebase_Record_Validator_SubValidate::IGNORE_VALUE => []]],
                ],
            ],
        ],
    ];

    protected static $_configurationObject = null;
}