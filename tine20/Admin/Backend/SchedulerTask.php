<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Controller
 *
 * @package     Admin
 * @subpackage  Scheduler
 */

class Admin_Backend_SchedulerTask extends Tinebase_Backend_Sql
{
    public function __construct() //$_options = array(), $_dbAdapter = NULL
    {
        parent::__construct([
            Tinebase_Backend_Sql::MODEL_NAME        => Admin_Model_SchedulerTask::class,
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Backend_Scheduler::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }

    /**
     * converts record into raw data for adapter
     *
     * @param Tinebase_Record_Interface $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record): array
    {
        $config = $_record->{Admin_Model_SchedulerTask::FLD_CONFIG};
        $callables = [];

        if (is_array($config)) {
            if (isset($config['callables'])) {
                $callables = $config['callables'];
            }
            //let Tinebase_Scheduler_Task construct the value
            $config = null;
        } else {
            if ($config instanceof Admin_Model_SchedulerTask_Abstract) {
                $config->{Admin_Model_SchedulerTask_Abstract::FLD_PARENT_ID} = $_record->getId();
                $callables = $config->getCallables();
                $config = $config->toArray();
            }
        }

        $raw = parent::_recordToRawData($_record);

        $raw[Admin_Model_SchedulerTask::FLD_CONFIG] = json_encode((new Tinebase_Scheduler_Task([
            'cron'          => $_record->{Admin_Model_SchedulerTask::FLD_CRON},
            'callables'     => $callables,
            'config'        => $config,
            'config_class'  => $_record->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS},
            'emails'        => $_record->{Admin_Model_SchedulerTask::FLD_EMAILS},
            'name'          => $_record->{Admin_Model_SchedulerTask::FLD_NAME},
        ]))->toArray());

        return $raw;
    }
}
