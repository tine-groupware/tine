<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * sql backend for BatchJob
 */
class Tinebase_Backend_BatchJob extends Tinebase_Backend_Sql
{
    public function __construct()
    {
        parent::__construct([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_BatchJob::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_BatchJob::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
    }

    /**
     * @return array<string>
     */
    public function getBatchJobsToSpawn(): array
    {
        if (!static::$inUnittest && Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            throw new Tinebase_Exception(__METHOD__ . ' must not have open transactions');
        }

        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::ID,
                Tinebase_Model_BatchJob::FLD_NUM_PROC,
                Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT,
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' = ' . Tinebase_Model_BatchJob::STATUS_RUNNING);

        $candidates = [];
        $totalRunning = 0;
        foreach ($db->query($select)->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
            $totalRunning += $row[Tinebase_Model_BatchJob::FLD_NUM_PROC];
            if ($row[Tinebase_Model_BatchJob::FLD_NUM_PROC] < $row[Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT]) {
                $candidates[] = $row[Tinebase_Model_BatchJob::ID];
            }
        }

        $result = [];
        $maxConcurrency = Tinebase_Config::getInstance()->{Tinebase_Config::BATCH_JOB_MAX_CONCURRENCY};
        if ($candidates && $totalRunning < $maxConcurrency) {
            foreach ((array)array_rand($candidates, min($maxConcurrency - $totalRunning, count($candidates))) as $key) {
                $result[] = $candidates[$key];
            }
        }

        return $result;
    }

    public function spawnBatchJob(string $id): bool
    {
        if (!static::$inUnittest && Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            throw new Tinebase_Exception(__METHOD__ . ' must not have open transactions');
        }

        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                new Zend_Db_Expr('sum(' . Tinebase_Model_BatchJob::FLD_NUM_PROC . ')'),
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' IN (' . Tinebase_Model_BatchJob::STATUS_RUNNING . ', ' . Tinebase_Model_BatchJob::STATUS_PAUSED . ')');
        $procCount = $db->fetchOne($select);
        if ((int)Tinebase_Config::getInstance()->{Tinebase_Config::BATCH_JOB_MAX_CONCURRENCY} <= $procCount) {
            return false;
        }

        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::FLD_NUM_PROC,
                Tinebase_Model_BatchJob::FLD_RUNNING_PROC,
            ])
            ->where('`id` = ' . $db->quote($id) . ' AND '
                . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_MAX_CONCURRENT) . ' > '
                . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_NUM_PROC))
            ->forUpdate();

        if (false === ($batchJobData = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC))) {
            return false;
        }

        $runningProcs = json_decode($batchJobData[Tinebase_Model_BatchJob::FLD_RUNNING_PROC] ?: '[]', true);
        if (!is_array($runningProcs)) {
            $runningProcs = [];
        }

        if (null === ($batchStep = ($batchStepBackend = new Tinebase_Backend_BatchJobStep())->getStepToExecute($id))) {
            return false;
        }

        $lockId = $batchStep->getId() . '#' . $batchStep->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS};
        $runningProcs[$lockId] = time();
        if (false === Tinebase_Core::acquireMultiServerLock($lockId)) {
            return false;
        }

        // create history
        Tinebase_Controller_BatchJobHistory::getInstance()->getBackend()->create(new Tinebase_Model_BatchJobHistory([
            Tinebase_Model_BatchJobHistory::FLD_BATCH_JOB_STEP => $batchStep->getId(),
            Tinebase_Model_BatchJobHistory::FLD_DATA_ID => $batchStep->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS},
            Tinebase_Model_BatchJobHistory::FLD_TYPE => Tinebase_Model_BatchJobHistory::TYPE_STARTED,
            Tinebase_Model_BatchJobHistory::FLD_TS => Tinebase_DateTime::now(),
            Tinebase_Model_BatchJobHistory::FLD_MSG => json_encode($batchStep->{Tinebase_Model_BatchJobStep::FLD_IN_DATA}),
        ]));

        // update batch job
        $db->update($this->getPrefixedTableName(), [
            Tinebase_Model_BatchJob::FLD_RUNNING_PROC => json_encode($runningProcs),
            Tinebase_Model_BatchJob::FLD_NUM_PROC => count($runningProcs),
        ], '`id` = ' . $db->quote($id));

        $transaction->release();

        $outData = null;
        try {
            // run job
            $inData = new Tinebase_BatchJob_InOutData($batchStep->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS}, $batchStep->{Tinebase_Model_BatchJobStep::FLD_IN_DATA});
            /** @var Tinebase_Model_BatchJobCallable $callable */
            foreach ($batchStep->{Tinebase_Model_BatchJobStep::FLD_CALLABLES} as $callable) {
                $inData = $callable->doCall($inData);
                $inData = call_user_func_array($callable, $inData->getData());
                /** @var Tinebase_BatchJob_InOutData $inData */
            }
            $outData = $inData;
        } catch (Throwable $e) {
            Tinebase_Controller_BatchJobHistory::getInstance()->getBackend()->create(new Tinebase_Model_BatchJobHistory([
                Tinebase_Model_BatchJobHistory::FLD_BATCH_JOB_STEP => $batchStep->getId(),
                Tinebase_Model_BatchJobHistory::FLD_DATA_ID => $batchStep->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS},
                Tinebase_Model_BatchJobHistory::FLD_TYPE => Tinebase_Model_BatchJobHistory::TYPE_FAILED,
                Tinebase_Model_BatchJobHistory::FLD_TS => Tinebase_DateTime::now(),
                Tinebase_Model_BatchJobHistory::FLD_MSG => get_class($e) . ': ' . $e->getMessage(),
            ]));
        }

        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::FLD_NUM_PROC,
                Tinebase_Model_BatchJob::FLD_RUNNING_PROC,
                Tinebase_Model_BatchJob::FLD_TICKS,
                Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS,
            ])
            ->where('`id` = ' . $db->quote($id))
            ->forUpdate();

        if ($batchJobData = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC)) {

            if (null !== $outData) {
                Tinebase_Controller_BatchJobHistory::getInstance()->getBackend()->create(new Tinebase_Model_BatchJobHistory([
                    Tinebase_Model_BatchJobHistory::FLD_BATCH_JOB_STEP => $batchStep->getId(),
                    Tinebase_Model_BatchJobHistory::FLD_DATA_ID => $batchStep->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS},
                    Tinebase_Model_BatchJobHistory::FLD_TYPE => Tinebase_Model_BatchJobHistory::TYPE_SUCCEEDED,
                    Tinebase_Model_BatchJobHistory::FLD_TS => Tinebase_DateTime::now(),
                    Tinebase_Model_BatchJobHistory::FLD_MSG => json_encode($outData->toArray()),
                ]));

                // add outData to step children in_data/to_process
                $batchStepBackend->addInData($batchStep->getId(), $outData);
            }

            $runningProcs = json_decode($batchJobData[Tinebase_Model_BatchJob::FLD_RUNNING_PROC] ?: '[]', true);
            if ($runningProcs[$lockId] ?? false) {
                unset($runningProcs[$lockId]);
                if (0 === ($numProcs = count($runningProcs)) && !$batchStepBackend->hasWorkToDo($id)) {
                    $this->markDone($id);
                } else {
                    $db->update($this->getPrefixedTableName(), [
                        Tinebase_Model_BatchJob::FLD_RUNNING_PROC => json_encode($runningProcs),
                        Tinebase_Model_BatchJob::FLD_NUM_PROC => $numProcs,
                        Tinebase_Model_BatchJob::FLD_TICKS => min($batchJobData[Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS], $batchJobData[Tinebase_Model_BatchJob::FLD_TICKS] + (null === $outData ? $batchStep->{Tinebase_Model_BatchJobStep::FLD_TICKS} : 1)),
                    ], '`id` = ' . $db->quote($id));
                }
            }
        }
        $transaction->release();

        Tinebase_Core::releaseMultiServerLock($lockId);

        return true;
    }

    protected function markDone(string $id): void
    {
        ($db = $this->getAdapter())->update($this->getPrefixedTableName(), [
            Tinebase_Model_BatchJob::FLD_RUNNING_PROC => '[]',
            Tinebase_Model_BatchJob::FLD_NUM_PROC => 0,
            Tinebase_Model_BatchJob::FLD_STATUS => Tinebase_Model_BatchJob::STATUS_DONE,
            Tinebase_Model_BatchJob::FLD_TICKS => new Zend_Db_Expr(Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS),
        ], '`id` = ' . $db->quote($id));
    }

    public function getProgress(string $id): int
    {
        if (!static::$inUnittest && Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            throw new Tinebase_Exception(__METHOD__ . ' must not have open transactions');
        }

        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::FLD_TICKS,
                Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS,
            ])
            ->where('`id` = ' . $db->quote($id));
        $data = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC);

        return $data ? $data[Tinebase_Model_BatchJob::FLD_TICKS] / ($data[Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS] ?: 1) * 1000 : 0;
    }

    public function checkDone(): void
    {
        if (!static::$inUnittest && Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            throw new Tinebase_Exception(__METHOD__ . ' must not have open transactions');
        }

        $batchStepBackend = new Tinebase_Backend_BatchJobStep();
        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::ID,
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' = ' . Tinebase_Model_BatchJob::STATUS_RUNNING
                . ' AND ' . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_NUM_PROC) . ' = 0');

        foreach ($db->fetchAll($select, fetchMode: Zend_Db::FETCH_ASSOC) as $row) {
            $transaction = Tinebase_RAII::getTransactionManagerRAII();

            $select = ($db = $this->getAdapter())->select()
                ->from($this->getPrefixedTableName(), [
                    Tinebase_Model_BatchJob::FLD_NUM_PROC,
                ])
                ->where('`id` = ' . $db->quote($row['id']) . ' AND '
                    . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' = ' . Tinebase_Model_BatchJob::STATUS_RUNNING)
                ->forUpdate();

            if (($batchJobData = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC)) &&
                    0 === $batchJobData[Tinebase_Model_BatchJob::FLD_NUM_PROC] && !$batchStepBackend->hasWorkToDo($row['id'])) {
                $this->markDone($row['id']);
            }

            $transaction->release();
        }
    }

    public function checkForZombies(): void
    {
        if (!static::$inUnittest && Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            throw new Tinebase_Exception(__METHOD__ . ' must not have open transactions');
        }

        $batchStepBackend = new Tinebase_Backend_BatchJobStep();
        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJob::ID,
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' = ' . Tinebase_Model_BatchJob::STATUS_RUNNING);

        foreach ($db->fetchAll($select, fetchMode: Zend_Db::FETCH_ASSOC) as $row) {
            $transaction = Tinebase_RAII::getTransactionManagerRAII();

            $select = ($db = $this->getAdapter())->select()
                ->from($this->getPrefixedTableName(), [
                    Tinebase_Model_BatchJob::FLD_NUM_PROC,
                    Tinebase_Model_BatchJob::FLD_RUNNING_PROC,
                    Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS,
                    Tinebase_Model_BatchJob::FLD_TICKS,
                ])
                ->where('`id` = ' . $db->quote($row['id']) . ' AND '
                    . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_STATUS) . ' = ' . Tinebase_Model_BatchJob::STATUS_RUNNING
                    . ' AND ' . $db->quoteIdentifier(Tinebase_Model_BatchJob::FLD_NUM_PROC) . ' > 0')
                ->forUpdate();

            if ($batchJobData = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC)) {
                $runningProcs = json_decode($batchJobData[Tinebase_Model_BatchJob::FLD_RUNNING_PROC], true) ?: [];
                foreach ($runningProcs as $procId => $time) {
                    if (!Tinebase_Core::acquireMultiServerLock($procId)) {
                        continue;
                    }
                    unset($runningProcs[$procId]);
                    [$stepId, $dataId] = explode('#', $procId);

                    Tinebase_Controller_BatchJobHistory::getInstance()->getBackend()->create(new Tinebase_Model_BatchJobHistory([
                        Tinebase_Model_BatchJobHistory::FLD_BATCH_JOB_STEP => $stepId,
                        Tinebase_Model_BatchJobHistory::FLD_DATA_ID => $dataId,
                        Tinebase_Model_BatchJobHistory::FLD_TYPE => Tinebase_Model_BatchJobHistory::TYPE_FAILED,
                        Tinebase_Model_BatchJobHistory::FLD_TS => Tinebase_DateTime::now(),
                        Tinebase_Model_BatchJobHistory::FLD_MSG => 'Zombie found, terminated',
                    ]));

                    $batchStep = $batchStepBackend->get($stepId);
                    $batchJobData[Tinebase_Model_BatchJob::FLD_TICKS] += $batchStep->{Tinebase_Model_BatchJobStep::FLD_TICKS};

                    Tinebase_Core::releaseMultiServerLock($procId);
                }

                if ($batchJobData[Tinebase_Model_BatchJob::FLD_NUM_PROC] !== count($runningProcs)) {

                    $db->update($this->getPrefixedTableName(), [
                        Tinebase_Model_BatchJob::FLD_RUNNING_PROC => json_encode($runningProcs),
                        Tinebase_Model_BatchJob::FLD_NUM_PROC => count($runningProcs),
                        Tinebase_Model_BatchJob::FLD_TICKS => max(0, min($batchJobData[Tinebase_Model_BatchJob::FLD_EXPECTED_TICKS], $batchJobData[Tinebase_Model_BatchJob::FLD_TICKS])),
                    ], '`id` = ' . $db->quote($row['id']));
                }
            }

            $transaction->release();
        }
    }

    public static bool $inUnittest = false;
}