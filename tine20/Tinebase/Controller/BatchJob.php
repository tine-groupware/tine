<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * controller for BatchJob
 *
 * @package     Tinebase
 * @subpackage  Controller
 *
 * @property Tinebase_Backend_BatchJob  $_backend
 *
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_BatchJob>
 */
class Tinebase_Controller_BatchJob extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_BatchJob::class;
        $this->_backend = new Tinebase_Backend_BatchJob();
        // default => $this->_purgeRecords = true;
        $this->_omitModLog = true;
        $this->_handleDependentRecords = false;
        $this->_doContainerACLChecks = false;
    }

    public function getProgress(string $id): array
    {
        return $this->_backend->getProgress($id);
    }

    public function clearOldBatchJobs(): bool
    {
        $this->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_BatchJob::class, [
            [TMFA::FIELD => Tinebase_Model_BatchJob::FLD_LAST_STATUS_UPDATE, TMFA::OPERATOR => 'before', TMFA::VALUE => Tinebase_DateTime::now()->subMonth(3)],
        ]));
        return true;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_handleDependentRecords = true;

        parent::_inspectBeforeCreate($_record);

        if (!$_record->{Tinebase_Model_BatchJob::FLD_ACCOUNT_ID}) {
            $_record->{Tinebase_Model_BatchJob::FLD_ACCOUNT_ID} = Tinebase_Core::getUser()->getId();
        }

        if (!in_array($_record->{Tinebase_Model_BatchJob::FLD_STATUS}, [null, Tinebase_Model_BatchJob::STATUS_RUNNING, Tinebase_Model_BatchJob::STATUS_PAUSED], true)) {
            throw new Tinebase_Exception_Record_Validation(Tinebase_Model_BatchJob::FLD_STATUS . ' must not be set or be running or be paused');
        }

        if (null !== $_record->{Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT} && $_record->{Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT} < 1) {
            throw new Tinebase_Exception_Record_Validation(Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT . ' must not be set or be bigger than 0');
        }

        unset($_record->{Tinebase_Model_BatchJob::FLD_NUM_PROC});
        unset($_record->{Tinebase_Model_BatchJob::FLD_TICKS_SUCCEEDED});
        unset($_record->{Tinebase_Model_BatchJob::FLD_TICKS_FAILED});
        unset($_record->{Tinebase_Model_BatchJob::FLD_RUNNING_PROC});

        $expectedTicks = 0;
        $countDepth = function(Tinebase_Model_BatchJobStep $step) use(&$countDepth) {
            $count = 1;
            foreach ($step->{Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS} ?? [] as $nextStep) {
                $count += $countDepth($nextStep);
            }
            return $count;
        };
        foreach ($_record->{Tinebase_Model_BatchJob::FLD_STEPS} as $step) {
            if (!is_array($step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA}) || empty($step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA})) {
                throw new Tinebase_Exception_Record_Validation('batch step without in data found');
            }
            $expectedTicks += count($step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA}) * $countDepth($step);
        }
        $_record->{Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS} = $expectedTicks;

        if ($this->unitTestMode) {
            Tinebase_TransactionManager::getInstance()->registerOnCommitCallback(fn() => Tinebase_Controller_BatchJob::getInstance()->spawnBatchJobs());
        } else {
            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(fn() => Tinebase_Controller_BatchJob::getInstance()->spawnBatchJobs());
        }
    }

    /**
     * @param Tinebase_Model_BatchJob $_record
     * @param Tinebase_Model_BatchJob $_oldRecord
     * @return void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $fields = $_record::getConfiguration()->getFields();
        unset($fields[Tinebase_Model_BatchJob::FLD_STATUS]);
        unset($fields[Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT]);
        $fields = array_keys($fields);
        foreach ($fields as $field) {
            $_record->{$field} = $_oldRecord->{$field};
        }

        if ($_record->{Tinebase_Model_BatchJob::FLD_STATUS} !== $_oldRecord->{Tinebase_Model_BatchJob::FLD_STATUS} &&
                Tinebase_Model_BatchJob::STATUS_CANCELLED === $_oldRecord->{Tinebase_Model_BatchJob::FLD_STATUS}) {
            throw new Tinebase_Exception_Record_Validation(Tinebase_Model_BatchJob::FLD_STATUS . ' can\'t be changed once canceled');
        }
        if ($_record->{Tinebase_Model_BatchJob::FLD_STATUS} !== $_oldRecord->{Tinebase_Model_BatchJob::FLD_STATUS} &&
                !in_array($_record->{Tinebase_Model_BatchJob::FLD_STATUS}, [Tinebase_Model_BatchJob::STATUS_RUNNING, Tinebase_Model_BatchJob::STATUS_PAUSED, Tinebase_Model_BatchJob::STATUS_CANCELLED], true)) {
            throw new Tinebase_Exception_Record_Validation(Tinebase_Model_BatchJob::FLD_STATUS . ' must be running, paused or canceled');
        }
        if ((int)$_record->{Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT} !== $_oldRecord->{Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT} &&
                (int)$_record->{Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT} < 1) {
            throw new Tinebase_Exception_Record_Validation(Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT . ' must be bigger than 0');
        }
    }

    public function batchJobMaintenance(): bool
    {
        $aq = Tinebase_ActionQueue::getInstance();
        if (!$this->unitTestMode && !$aq->hasAsyncBackend()) {
            return true;
        }

        if (!Tinebase_Core::acquireMultiServerLock(__METHOD__)) {
            return true;
        }

        $this->spawnBatchJobs();

        $this->_backend->checkForZombies();
        $this->_backend->checkDone();

        Tinebase_Core::releaseMultiServerLock(__METHOD__);

        return true;
    }

    public function spawnBatchJobs(): void
    {
        $aq = Tinebase_ActionQueue::getInstance();
        if (!$this->unitTestMode && !$aq->hasAsyncBackend()) {
            return;
        }
        foreach ($this->_backend->getBatchJobsToSpawn() as $batchJobId) {
            $aq->send(['action' => self::class . '.spawnBatchJob', 'params' => [$batchJobId]]);
        }
    }

    public function spawnBatchJob(string $batchJobId): void
    {
        $this->_backend->spawnBatchJob($batchJobId);

        foreach ($this->_backend->getBatchJobsToSpawn() as $batchJobId) {
            Tinebase_ActionQueue::getInstance()->send(['action' => self::class . '.spawnBatchJob', 'params' => [$batchJobId]]);
            return;
        }
    }

    public function setUnitTestMode(bool $val): bool
    {
        $oldValue = $this->unitTestMode;
        $this->unitTestMode = $val;
        Tinebase_Backend_BatchJob::$inUnittest = $val;
        return $oldValue;
    }

    protected bool $unitTestMode = false;
}
