<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SMS Config Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property Tinebase_Sms_AdapterInterface $adapter_config
 */
class Tinebase_Model_Sms_SendConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Sms_SendConfig';

    public const FLD_ADAPTER_CLASS = 'adapter_class';
    public const FLD_ADAPTER_CONFIG = 'adapter_config';
    public const FLD_MESSAGE = 'message';
    public const FLD_RECIPIENT_NUMBER = 'recipient_number';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_ADAPTER_CLASS             => [
                self::TYPE                          => self::TYPE_MODEL,
                self::LABEL                         => 'SMS Adapter Type', //_('SMS Adapter Type')
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Tinebase_Model_Sms_GenericHttpAdapter::class,
                    ],
                ],
            ],
            self::FLD_ADAPTER_CONFIG            => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::LABEL                         => 'SMS Adapter Config', // _('SMS Adapter Config')
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_ADAPTER_CLASS,
                    self::PERSISTENT                    => true,
                ],
            ],
            self::FLD_MESSAGE                   => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_RECIPIENT_NUMBER          => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}