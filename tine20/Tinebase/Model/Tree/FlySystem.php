<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Model_Tree_FlySystem extends Tinebase_Record_NewAbstract
{
    public const ADAPTER_LOCAL = 'League\Flysystem\Local\LocalFilesystemAdapter';

    public const FLD_ADAPTER = 'adapter';
    public const FLD_ADAPTER_CONFIG = 'adapter_config';
    public const FLD_NAME = 'name';

    public const MODEL_NAME_PART = 'Tree_FlySystem';
    public const TABLE_NAME = 'tree_flysystem';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 1,
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_NAME      => [
                    self::COLUMNS       => [self::FLD_NAME],
                ],
            ],
        ],

        self::FIELDS        => [
            self::FLD_NAME      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ADAPTER   => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                    ['InArray', [
                        self::ADAPTER_LOCAL,
                    ]],
                ],
            ],
            self::FLD_ADAPTER_CONFIG => [
                self::TYPE          => self::TYPE_JSON,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
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