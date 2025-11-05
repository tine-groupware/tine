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
 * BatchJob History Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_BatchJobHistory extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'BatchJobHistory';
    public const TABLE_NAME = 'batch_job_history';

    public const TYPE_STARTED = 'started';
    public const TYPE_SUCCEEDED = 'succeeded';
    public const TYPE_FAILED = 'failed';

    public const FLD_TYPE = 'type';
    public const FLD_TS = 'ts';
    public const FLD_MSG = 'msg';
    public const FLD_BATCH_JOB_STEP = 'batch_job_step';
    public const FLD_DATA_ID = 'data_id';

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
        self::IS_DEPENDENT              => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_BATCH_JOB_STEP             => [
                    self::COLUMNS                   => [self::FLD_BATCH_JOB_STEP],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_BATCH_JOB_STEP        => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BatchJobStep::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
            ],
            self::FLD_TYPE                  => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_TS                    => [
                self::TYPE                      => self::TYPE_DATETIME,
            ],
            self::FLD_DATA_ID               => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_MSG                   => [
                self::TYPE                      => self::TYPE_JSON,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function notifyBroadcastHub(): bool
    {
        return false;
    }
}
