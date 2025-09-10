<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * Test class for Admin_Controller_SchedulerTask
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Admin_Controller_SchedulerTaskTest extends TestCase
{
    public function testCreateSchedulerTask()
    {
        $createdTask = $this->_getTestTask();

        // run 5 times, there might be other tasks to do
        // TODO maybe we should disable the other tasks temporarily to make sure only "our" task is run
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

    public function testUpdateSchedulerTaskWithoutTaskConfigs()
    {
        $createdTask = $this->_getTestTask();
        $createdTask['next_run'] = Tinebase_DateTime::now();
        $updatedTask = Admin_Controller_SchedulerTask::getInstance()->update($createdTask);
        $this->assertNotNull($updatedTask);
    }

    protected function _getTestTask(array $data = []): Admin_Model_SchedulerTask
    {
        $task = new Admin_Model_SchedulerTask(array_merge([
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
            Admin_Model_SchedulerTask::FLD_APPLICATION_ID   =>  Tinebase_Application::getInstance()->getApplicationByName(Calendar_Config::APP_NAME)->getId()
        ], $data));
        /** @var Admin_Model_SchedulerTask $createdTask */
        $createdTask = Admin_Controller_SchedulerTask::getInstance()->create($task);

        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CRON}, $createdTask->{Admin_Model_SchedulerTask::FLD_CRON});
        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS}, $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS});
        $this->assertSame($createdTask->getId(), $createdTask->{Admin_Model_SchedulerTask::FLD_CONFIG}->{Admin_Model_SchedulerTask_Abstract::FLD_PARENT_ID});
        $this->assertSame($task->{Admin_Model_SchedulerTask::FLD_EMAILS}, $createdTask->{Admin_Model_SchedulerTask::FLD_EMAILS});

        $this->assertNull($createdTask->last_run);
        $this->assertEquals('0', $createdTask->failure_count);

        return $createdTask;
    }

    public function testTaskNotification()
    {
        $recipient = $this->_getPersona('sclever');

        self::flushMailer();

        try {
            $scheduler = Tinebase_Core::getScheduler();
            $task = $scheduler->getBackend()->getByProperty('Tinebase_Alarm', 'name');
            $adminTask = Admin_Controller_SchedulerTask::getInstance()->get($task->getId());
            $adminTask->{Admin_Model_SchedulerTask::FLD_CONFIG_CLASS} = Admin_Model_SchedulerTask_Import::class;
            $config = $adminTask->{Admin_Model_SchedulerTask::FLD_CONFIG};
            $config['emails'] = $recipient->accountEmailAddress;
            $adminTask->{Admin_Model_SchedulerTask::FLD_CONFIG} = $config;

            $adminTask = $scheduler->update($adminTask);
            $adminTask->config->run();
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Something strange happened and the async jobs did not complete ... maybe the test system is not configured correctly for this: ' . $e);
            static::fail($e->getMessage());
        }

        $messages = self::getMessages();
        $mailsForPersona = array();
        $personaEmail = $this->_getPersona(trim('sclever'))->accountEmailAddress;

        foreach ($messages as $message) {
            if (Tinebase_Helper::array_value(0, $message->getRecipients()) == $personaEmail) {
                array_push($mailsForPersona, $message);
            }
        }
        $bodyPart = $mailsForPersona[0]->getBodyText(FALSE);
        $s = fopen('php://temp','r+');
        fputs($s, $bodyPart->getContent());
        rewind($s);
        $bodyPartStream = new Zend_Mime_Part($s);
        $bodyPartStream->encoding = $bodyPart->encoding;
        $bodyText = $bodyPartStream->getDecodedContent();
        $this->assertStringContainsString('0 alarms sent (limit: 100).', $bodyText);
        $this->assertStringNotContainsString('Tinebase_Alarm::sendPendingAlarms::157', $bodyText, 'regex should remove method and line in notification');
        $this->assertStringNotContainsString('NOTICE', $bodyText, 'custom formatter should not include notice');
    }
}
