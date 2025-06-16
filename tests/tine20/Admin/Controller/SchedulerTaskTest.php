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
        $name = 'unittest import scheduled task';
        $task = new Admin_Model_SchedulerTask([
            Admin_Model_SchedulerTask::FLD_NAME => $name,
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

        // disable the other tasks temporarily to make sure only "our" task is run
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Tinebase_Model_SchedulerTask::class, [
            ['field' => Tinebase_Model_SchedulerTask::FLD_NAME, 'operator' => 'not', 'value' => $name]
        ]);
        Tinebase_Scheduler::getInstance()->updateMultiple($filter, [
            Tinebase_Model_SchedulerTask::FLD_ACTIVE => false,
        ]);
        $this->assertTrue(Tinebase_Scheduler::getInstance()->run());

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

    public function testUpdateSchedulerTaskWithoutTaskConfigs()
    {
        $task = new Admin_Model_SchedulerTask([
            Admin_Model_SchedulerTask::FLD_NAME => 'unittest import scheduled task',
            Admin_Model_SchedulerTask::FLD_CONFIG_CLASS => '',
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
        $createdTask['next_run'] = Tinebase_DateTime::now();
        $updatedTask = Admin_Controller_SchedulerTask::getInstance()->update($createdTask);
        $this->assertNotNull($updatedTask);
    }

    public function testUpdateSystemSchedulerTask()
    {
        $task = new Admin_Model_SchedulerTask([
            Admin_Model_SchedulerTask::FLD_NAME => 'unittest import scheduled task',
            Admin_Model_SchedulerTask::FLD_CONFIG_CLASS => '',
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

        $db = Tinebase_Core::getDb();
        $db->query('UPDATE ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'scheduler_task') . ' SET ' . $db->quoteIdentifier('is_system') . ' = "1" WHERE ' . $db->quoteIdentifier('id') . ' = \'' . $createdTask->getId() .'\'')->closeCursor();
        $createdTask = Admin_Controller_SchedulerTask::getInstance()->get($createdTask->getId());

        $now = Tinebase_DateTime::now();
        $createdTask[Admin_Model_SchedulerTask::FLD_NEXT_RUN] = $now;
        $createdTask[Admin_Model_SchedulerTask::FLD_CRON] = '23 4 28 * *';
        $createdTask[Admin_Model_SchedulerTask::FLD_EMAILS] = 'test@mail.test';
        $createdTask[Admin_Model_SchedulerTask::FLD_ACTIVE] = 0;

        $updatedTask = Admin_Controller_SchedulerTask::getInstance()->update($createdTask);

        $this->assertEquals($now, $updatedTask[Admin_Model_SchedulerTask::FLD_NEXT_RUN]);
        $this->assertEquals('23 4 28 * *', $updatedTask[Admin_Model_SchedulerTask::FLD_CRON]);
        $this->assertEquals('test@mail.test', $updatedTask[Admin_Model_SchedulerTask::FLD_EMAILS]);
        $this->assertEquals(0, $updatedTask[Admin_Model_SchedulerTask::FLD_ACTIVE]);
    }
}
