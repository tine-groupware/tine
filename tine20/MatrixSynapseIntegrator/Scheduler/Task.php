<?php
/**
 * Tine 2.0
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Scheduler
 */
class MatrixSynapseIntegrator_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    /**
     * add exportDirectory task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addExportDirectoryTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('MatrixSynapseIntegrator_Controller_Directory::exportDirectory')) {
            return;
        }

        $task = self::_getPreparedTask('MatrixSynapseIntegrator_Controller_Directory::exportDirectory', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'MatrixSynapseIntegrator_Controller_Directory',
            self::METHOD_NAME   => 'exportDirectory',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task MatrixSynapseIntegrator_Controller_Directory::exportDirectory in scheduler.');
    }

    /**
     * remove exportDirectory Data task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeExportDirectoryTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('MatrixSynapseIntegrator_Controller_Directory::exportDirectory');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task MatrixSynapseIntegrator_Controller_Directory::exportDirectory from scheduler.');
    }
}
