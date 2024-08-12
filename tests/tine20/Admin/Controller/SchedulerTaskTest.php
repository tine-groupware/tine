<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * Test class for Admin_Controller_SchedulerTask
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Admin_Controller_SchedulerTaskTest extends TestCase
{
    public function testCreateSchedulerTask()
    {
        $task = new Admin_Model_SchedulerTask([
            Admin_Model_SchedulerTask::FLD_NAME => 'unittest import scheduled task',
            Admin_Model_SchedulerTask::FLD_CONFIG_CLASS => Admin_Model_SchedulerTask_Import::class,
            Admin_Model_SchedulerTask::FLD_CONFIG       => [
                Admin_Model_SchedulerTask_Import::FLD_PLUGIN_CLASS      => Calendar_Import_Ical::class,
                Admin_Model_SchedulerTask_Import::FLD_OPTIONS           => [
                    'container_id' => $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true)->getId(),
                    'url' => dirname(dirname(__DIR__)) . '/Calendar/Import/files/gotomeeting.ics',
                ],
            ],
            Admin_Model_SchedulerTask::FLD_CRON         => '* * * * *',
            Admin_Model_SchedulerTask::FLD_EMAILS       => Tinebase_Core::getUser()->accountEmailAddress,
        ]);
        $createdTask = Admin_Controller_SchedulerTask::getInstance()->create($task);

        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CRON}, $createdTask->{Admin_Model_SchedulerTask::FLD_CRON});
        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS}, $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS});
        $this->assertSame($createdTask->getId(), $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG}->{Admin_Model_SchedulerTask_Abstract::FLD_PARENT_ID});
        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_EMAILS}, $createdTask->{Admin_Model_SchedulerTask::FLD_EMAILS});

        $this->assertNull($createdTask->last_run);
        $this->assertEquals('0', $createdTask->failure_count);

        // run 5 times, there might be other tasks to do
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(Tinebase_Scheduler::getInstance()->run());
        }

        $runTask = Admin_Controller_SchedulerTask::getInstance()->get($createdTask->getId());
        $this->assertNotNull($runTask->last_run, print_r($runTask->toArray(), true));
        $this->assertEquals('0', $runTask->failure_count);
    }
    
    public function testSearchSchedulerTask()
    {
        $result = Admin_Controller_SchedulerTask::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Admin_Model_SchedulerTask::class, [
            ['field' => 'query', 'operator' => 'contains', 'value' => 'task']
        ]), new Tinebase_Model_Pagination(['sort' => 'name', 'dir' => 'desc']));
        $this->assertNotNull($result);
    }

    public function testUpdateSchedulerTask()
    {
        $scheduler = Tinebase_Core::getScheduler();
        $task = new Tinebase_Model_SchedulerTask([
            'name'      => 'test',
            'config'    => new Tinebase_Scheduler_Task([
                'cron'      => Tinebase_Scheduler_Task::TASK_TYPE_MINUTELY,
                'callables' => [
                    [
                        Tinebase_Scheduler_Task::CLASS_NAME     => Scheduler_Mock::class,
                        Tinebase_Scheduler_Task::METHOD_NAME    => 'run'
                    ], [
                        Tinebase_Scheduler_Task::CONTROLLER     => Tinebase_Scheduler::class,
                        Tinebase_Scheduler_Task::METHOD_NAME    => 'doContainerACLChecks',
                        Tinebase_Scheduler_Task::ARGS           => [true]
                    ]
                ]
            ]),
            'next_run'  => Tinebase_DateTime::now()->subDay(100)
        ]);
        $createdTask = $scheduler->create($task);
        $createdTask['next_run'] = Tinebase_DateTime::now();

        $this->expectException(Tinebase_Exception_AccessDenied::class);
        $updatedTask = Admin_Controller_SchedulerTask::getInstance()->update($createdTask);
    }
}
