<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * NumberableConfig Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_NumberableConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'NumberableConfig';
    public const TABLE_NAME = 'numberable_config';

    public const FLD_MODEL = 'model';
    public const FLD_PROPERTY = 'property';
    public const FLD_BUCKET_KEY = 'bucket_key';
    public const FLD_ADDITIONAL_KEY = 'additional_key';
    public const FLD_PREFIX = 'prefix';
    public const FLD_ZEROFILL = 'zerofill';
    public const FLD_START = 'start';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 1,
        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE         => true,

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
        ],

        self::FIELDS                => [
            self::FLD_MODEL             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 150,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_PROPERTY          => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 100,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ADDITIONAL_KEY    => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::DEFAULT_VAL           => '',
            ],
            self::FLD_BUCKET_KEY    => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::DEFAULT_VAL           => '',
                // TODO Conny -> FE disbaled, hidden etc.
                self::DISABLED              => true,
            ],
            self::FLD_START             => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::DEFAULT_VAL           => 1,
            ],
            self::FLD_ZEROFILL          => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::DEFAULT_VAL           => 0,
            ],
            self::FLD_PREFIX            => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 100,
                self::DEFAULT_VAL           => '',
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
