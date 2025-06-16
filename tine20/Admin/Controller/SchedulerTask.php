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

class Admin_Controller_SchedulerTask extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_backend = new Admin_Backend_SchedulerTask();
        $this->_applicationName = Admin_Config::APP_NAME;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_modelName = Admin_Model_SchedulerTask::class;
    }

    public function runCustomScheduledTask(string $id): bool
    {
        /** @var Admin_Model_SchedulerTask $task */
        $task = $this->get($id);

        return $task->{Admin_Model_SchedulerTask::FLD_CONFIG}->run();
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        /** @var Admin_Model_SchedulerTask $_record */
        $_record->{Admin_Model_SchedulerTask::FLD_IS_SYSTEM} = 0;
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if ($_oldRecord->{Admin_Model_SchedulerTask::FLD_IS_SYSTEM}) {
            // only allow to update some values for system accounts
            $allowedFields = array(
                Tinebase_Model_SchedulerTask::FLD_NAME,
                Tinebase_Model_SchedulerTask::FLD_ACTIVE,
                Tinebase_Model_SchedulerTask::FLD_NEXT_RUN,
                Tinebase_Model_SchedulerTask::FLD_DISABLE_AUTO_SHUFFLE,
                Admin_Model_SchedulerTask::FLD_EMAILS,
                Admin_Model_SchedulerTask::FLD_CRON,
                'last_modified_time',
                'last_modified_by',
                'seq'
            );
            $diff = $_record->diff($_oldRecord)->diff;
            foreach ($diff as $key => $value) {
                if (! in_array($key, $allowedFields)) {
                    throw new Tinebase_Exception_AccessDenied('can not update admin system task field : ' . $key);
                }
            }
        }
        if ($_record->{Admin_Model_SchedulerTask::FLD_IS_SYSTEM} && !$_oldRecord->{Admin_Model_SchedulerTask::FLD_IS_SYSTEM}) {
            throw new Tinebase_Exception_AccessDenied('can not make a task a system task');
        }

        if ($_record->{Admin_Model_SchedulerTask::FLD_CRON} !== $_oldRecord->{Admin_Model_SchedulerTask::FLD_CRON}) {
            $_record->{Admin_Model_SchedulerTask::FLD_DISABLE_AUTO_SHUFFLE} = true;
        }
    }

    protected function _inspectDelete(array $_ids): array
    {
        $_ids = parent::_inspectDelete($_ids);

        /** @var Admin_Model_SchedulerTask $record */
        foreach ($this->getMultiple($_ids) as $record) {
            if ($record->{Admin_Model_SchedulerTask::FLD_IS_SYSTEM}) {
                throw new Tinebase_Exception_AccessDenied('can not delete system tasks');
            }
        }

        return $_ids;
    }
}
