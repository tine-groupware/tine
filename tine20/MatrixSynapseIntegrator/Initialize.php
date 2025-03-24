<?php
/**
 * Tine 2.0
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 *  MatrixSynapseIntegrator setup initialize class
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 */
class  MatrixSynapseIntegrator_Setup_Initialize extends Setup_Initialize
{
    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        MatrixSynapseIntegrator_Scheduler_Task::addExportDirectoryTask($scheduler);
    }
}
