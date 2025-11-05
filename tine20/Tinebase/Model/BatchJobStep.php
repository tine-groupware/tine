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
 * BatchJobStep Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_BatchJobStep extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'BatchJobStep';
    public const TABLE_NAME = 'batch_job_step';

    public const FLD_TITLE = 'title';
    public const FLD_PARENT_ID = 'parent_id';
    public const FLD_BATCH_JOB_ID = 'batch_job_id';

    public const FLD_NEXT_STEPS = 'next_steps';
    public const FLD_IN_DATA = 'in_data';
    public const FLD_TO_PROCESS = 'to_process';
    public const FLD_CALLABLES = 'callables';
    public const FLD_HISTORY = 'history';
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
        self::IS_DEPENDENT              => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_PARENT_ID             => [
                    self::COLUMNS                   => [self::FLD_PARENT_ID],
                ],
                self::FLD_BATCH_JOB_ID               => [
                    self::COLUMNS                   => [self::FLD_BATCH_JOB_ID],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_BATCH_JOB_ID          => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BatchJob::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
            ],
            self::FLD_PARENT_ID             => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => self::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
            ],
            self::FLD_TICKS                 => [
                self::TYPE                      => self::TYPE_INTEGER,
            ],
            self::FLD_IN_DATA               => [
                self::TYPE                      => self::TYPE_NATIVE_JSON,
                self::DEFAULT_VAL               => '{}',
            ],
            self::FLD_TO_PROCESS            => [
                self::TYPE                      => self::TYPE_NATIVE_JSON,
                self::DEFAULT_VAL               => '[]',
            ],
            self::FLD_TITLE                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_CALLABLES             => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BatchJobCallable::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
            ],
            self::FLD_NEXT_STEPS            => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => self::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => self::FLD_PARENT_ID,
                    self::DEPENDENT_RECORDS         => true,
                ],
            ],
            self::FLD_HISTORY               => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_BatchJobHistory::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => Tinebase_Model_BatchJobHistory::FLD_BATCH_JOB_STEP,
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
    protected static $_configurationObject = NULL;

    public function notifyBroadcastHub(): bool
    {
        return false;
    }

    public function calcTickValue(): int
    {
        $ticks = 1;

        /** @var self $step */
        foreach ($this->{self::FLD_NEXT_STEPS} ?? [] as $step) {
            $ticks += $step->calcTickValue();
        }

        return $ticks;
    }
}
