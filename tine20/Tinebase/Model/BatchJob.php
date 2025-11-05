<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * BatchJob Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_BatchJob extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'BatchJob';
    public const TABLE_NAME = 'batch_job';

    public const STATUS_RUNNING = 0;
    public const STATUS_PAUSED = 1;
    public const STATUS_DONE = 2;


    public const FLD_TITLE = 'title';
    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_STATUS = 'status';
    public const FLD_MAX_CONCURRENT = 'max_concurrent';
    public const FLD_NUM_PROC = 'num_proc';
    public const FLD_RUNNING_PROC = 'running_proc';
    public const FLD_STEPS = 'steps';
    public const FLD_EXPECTED_TICKS = 'expected_ticks';
    public const FLD_TICKS = 'ticks';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => false,
        
        self::EXPOSE_JSON_API           => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_STATUS                => [
                    self::COLUMNS                   => [self::FLD_STATUS],
                ],
            ],
        ],

        // DO NOT SET JSON EXPANDER!

        self::FIELDS                    => [
            self::FLD_STATUS                => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => self::STATUS_RUNNING,
            ],
            self::FLD_MAX_CONCURRENT        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 1,
            ],
            self::FLD_NUM_PROC              => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_EXPECTED_TICKS        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_TICKS                 => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_RUNNING_PROC          => [
                self::TYPE                      => self::TYPE_NATIVE_JSON,
                self::DEFAULT_VAL               => '{}',
            ],
            self::FLD_TITLE                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_ACCOUNT_ID            => [
                self::TYPE                      => self::TYPE_USER,
                self::LENGTH                    => 40,
            ],
            self::FLD_STEPS             => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BatchJobStep::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => Tinebase_Model_BatchJobStep::FLD_BATCH_JOB_ID,
                    self::DEPENDENT_RECORDS         => true,
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
