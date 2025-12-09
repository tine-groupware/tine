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
 * sql backend for BatchJobStep
 */
class Tinebase_Backend_BatchJobStep extends Tinebase_Backend_Sql
{
    public function __construct()
    {
        parent::__construct([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_BatchJobStep::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_BatchJobStep::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
    }

    /**
     * we expect to be in a transaction that has an exclusive lock on batch_job
     */
    public function getStepToExecute(string $batchJobId): ?Tinebase_Model_BatchJobStep
    {
        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJobStep::ID,
                Tinebase_Model_BatchJobStep::FLD_CALLABLES,
                Tinebase_Model_BatchJobStep::FLD_TICKS,
                new Zend_Db_Expr('JSON_EXTRACT(' . Tinebase_Model_BatchJobStep::FLD_TO_PROCESS . ', "$[0]") AS ' . Tinebase_Model_BatchJobStep::FLD_TO_PROCESS),
                new Zend_Db_Expr('JSON_EXTRACT(' . Tinebase_Model_BatchJobStep::FLD_IN_DATA . ', CONCAT("$.", JSON_EXTRACT(' . Tinebase_Model_BatchJobStep::FLD_TO_PROCESS . ', "$[0]"))) AS ' . Tinebase_Model_BatchJobStep::FLD_IN_DATA),
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_BATCH_JOB_ID) . ' = ' . $db->quote($batchJobId)
                . ' AND JSON_LENGTH(' . $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_TO_PROCESS) . ', "$[0]") = 1'
            )->limit(1)->order(new Zend_Db_Expr('rand()'));

        if (false === ($step = $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC))) {
            return null;
        }

        $db->update($this->getPrefixedTableName(), [
            Tinebase_Model_BatchJobStep::FLD_TO_PROCESS => new Zend_Db_Expr('JSON_REMOVE(' . Tinebase_Model_BatchJobStep::FLD_TO_PROCESS . ', "$[0]")'),
        ], '`id` = ' . $db->quote($step['id']));

        $step[Tinebase_Model_BatchJobStep::FLD_IN_DATA] = json_decode($step[Tinebase_Model_BatchJobStep::FLD_IN_DATA], true);
        $step = new Tinebase_Model_BatchJobStep($step);
        $step->runConvertToRecord(); // json_decode to_process / in_data
        $step->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS} = substr($step->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS}, 1);
        return $step;
    }

    /**
     * we expect to be in a transaction that has an exclusive lock on batch_job
     */
    public function addInData(string $parentStepId, Tinebase_BatchJob_InOutData $inData): void
    {
        ($db = $this->getAdapter())->update($this->getPrefixedTableName(), [
            Tinebase_Model_BatchJobStep::FLD_TO_PROCESS => new Zend_Db_Expr('JSON_ARRAY_APPEND('
                . $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_TO_PROCESS) . ', \'$\', ' . $db->quote('_' . $inData->getId()) . ')'),
        ], $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_PARENT_ID) . ' = ' . $db->quote($parentStepId)
            . ' AND NOT JSON_CONTAINS_PATH(' . $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_IN_DATA) . ', "one", "$._' . $inData->getId() . '")');

        $db->update($this->getPrefixedTableName(), [
            Tinebase_Model_BatchJobStep::FLD_IN_DATA => new Zend_Db_Expr('JSON_INSERT(' . $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_IN_DATA) . ', "$._' . $inData->getId() . '", ' . $db->quote(json_encode($inData->getData())) . ')'),
        ], $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_PARENT_ID) . ' = ' . $db->quote($parentStepId));
    }

    /**
     * we expect to be in a transaction that has an exclusive lock on batch_job
     */
    public function hasWorkToDo(string $batchJobId): bool
    {
        $select = ($db = $this->getAdapter())->select()
            ->from($this->getPrefixedTableName(), [
                Tinebase_Model_BatchJobStep::ID,
            ])
            ->where($db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_BATCH_JOB_ID) . ' = ' . $db->quote($batchJobId)
                . ' AND JSON_LENGTH(' . $db->quoteIdentifier(Tinebase_Model_BatchJobStep::FLD_TO_PROCESS) . ', "$[0]") = 1'
            )->limit(1);

        return false !== $db->fetchRow($select, fetchMode: Zend_Db::FETCH_ASSOC);
    }
}