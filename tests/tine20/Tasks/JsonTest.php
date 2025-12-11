<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tasks_JsonTest
 */
class Tasks_JsonTest extends TestCase
{
    /**
     * Backend
     *
     * @var Tasks_Frontend_Json
     */
    protected $_backend;

    /**
     * smtp config array
     * 
     * @var array
     */
    protected $_smtpConfig = [];

    /**
     * smtp config changed
     * 
     * @var array
     */
    protected $_smtpConfigChanged = FALSE;

    /**
     * smtp transport
     * 
     * @var Zend_Mail_Transport_Abstract
     */
    protected $_smtpTransport = NULL;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->_backend = new Tasks_Frontend_Json();
        $this->_smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        $this->_smtpTransport = Tinebase_Smtp::getDefaultTransport();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        if ($this->_smtpConfigChanged) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $this->_smtpConfig);
            Tinebase_Smtp::setDefaultTransport($this->_smtpTransport);
        }

        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, false);

        parent::tearDown();
    }

    /**
     * test creation of a task
     *
     */
    public function testCreateTask()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask($task->toArray());

        $this->assertEquals($task['summary'], $returned['summary']);
        $this->assertNotNull($returned['id']);

        // test getTask($contextId) as well
        $returnedGet = $this->_backend->getTask($returned['id']);
        $this->assertEquals($task['summary'], $returnedGet['summary']);

        $returnedGet = $this->_backend->getTask($returned['id'], '0', '');
        $this->assertEquals($task['summary'], $returnedGet['summary']);

        $this->_backend->deleteTasks(array($returned['id']));
    }

    public function testAttendeeAcl()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask($task->toArray());

        Tinebase_Core::setUser($this->_personas['sclever']);
        try {
            $this->_backend->getTask($returned['id']);
            $this->fail('acl should prevent sclever from accessing this task');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        Tinebase_Core::setUser($this->_originalTestUser);
        $returned['attendees'] = [
            (new Tasks_Model_Attendee([
                Tasks_Model_Attendee::FLD_USER_ID => $this->_personas['sclever']->contact_id,
            ], true))->toArray(),
        ];
        $returned['alarms'] = [
            (new Tinebase_Model_Alarm([
                //Tinebase_Model_Alarm::FLD_MODEL => Tasks_Model_Task::class,
                Tinebase_Model_Alarm::FLD_ALARM_TIME => Tinebase_DateTime::now(),
            ], true))->toArray(),
        ];
        $returned = $this->_backend->saveTask($returned);
        $this->assertCount(1, $returned['alarms']);

        Tinebase_Core::setUser($this->_personas['sclever']);
        try {
            $returned = $this->_backend->getTask($returned['id']);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->fail('sclever should have access as attendee');
        }
        $this->assertNull($returned['alarms'][0][Tinebase_Model_Alarm::FLD_SKIP]);
        $this->assertNull($returned['alarms'][0][Tinebase_Model_Alarm::FLD_SNOOZE_TIME]);
        $this->assertNull($returned['alarms'][0][Tinebase_Model_Alarm::FLD_ACK_TIME]);

        // we should be able to update our attendee status, attendee alarms, add notes and attachments
        foreach ($returned[Tasks_Model_Task::FLD_ATTENDEES] as &$attendee) {
            if ($attendee[Tasks_Model_Attendee::FLD_USER_ID]['id'] === $this->_personas['sclever']->contact_id) {
                $attendee[Tasks_Model_Attendee::FLD_STATUS] = Tasks_Model_Attendee::STATUS_TENTATIVE;
                $attendee['alarms'] = [
                    (new Tinebase_Model_Alarm([
                        //Tinebase_Model_Alarm::FLD_MODEL => Tasks_Model_Attendee::class,
                        Tinebase_Model_Alarm::FLD_ALARM_TIME => Tinebase_DateTime::now(),
                    ], true))->toArray(),
                ];
                break;
            }
        }
        unset($attendee);
        $returned['notes'] = [
            (new Tinebase_Model_Note(['note' => 'a note from sclever'], true))->toArray(),
        ];
        $returned['alarms'][0][Tinebase_Model_Alarm::FLD_SKIP] = true;
        $returned['alarms'][0][Tinebase_Model_Alarm::FLD_SNOOZE_TIME] = $ts = Tinebase_DateTime::now()->toString();
        $returned['alarms'][0][Tinebase_Model_Alarm::FLD_ACK_TIME] = $ts;

        $tempPath = Tinebase_TempFile::getTempPath();
        $tempFileId = Tinebase_TempFile::getInstance()->createTempFile($tempPath)->getId();
        file_put_contents($tempPath, 'someData');
        $raii = new Tinebase_RAII(fn () => unlink($tempPath));
        $returned['attachments'] = [
            ['tempFile' => $tempFileId],
        ];

        $returned = $this->_backend->saveTask($returned);
        $found = false;
        foreach ($returned[Tasks_Model_Task::FLD_ATTENDEES] as $attendee) {
            if ($attendee[Tasks_Model_Attendee::FLD_USER_ID]['id'] === $this->_personas['sclever']->contact_id) {
                $this->assertSame(Tasks_Model_Attendee::STATUS_TENTATIVE, $attendee[Tasks_Model_Attendee::FLD_STATUS]);
                $this->assertCount(1, $attendee['alarms']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertCount(1, $returned['notes']);
        $this->assertCount(1, $returned['attachments']);
        $this->assertTrue($returned['alarms'][0][Tinebase_Model_Alarm::FLD_SKIP]);
        $this->assertSame($ts, $returned['alarms'][0][Tinebase_Model_Alarm::FLD_SNOOZE_TIME]);
        $this->assertSame($ts, $returned['alarms'][0][Tinebase_Model_Alarm::FLD_ACK_TIME]);

        unset($raii);
    }

    public function testCreateDependentTask()
    {
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask($task->toArray());

        $this->assertSame([], $returned[Tasks_Model_Task::FLD_DEPENDENT_TASKS]);
        $this->assertSame([], $returned[Tasks_Model_Task::FLD_DEPENDENS_ON]);

        $depTask = $this->_getTask();
        $depTask->{Tasks_Model_Task::FLD_DEPENDENS_ON} = [
            (new Tasks_Model_TaskDependency([
                Tasks_Model_TaskDependency::FLD_DEPENDS_ON => $returned['id'],
            ], true))->toArray(),
        ];
        $savedDepTask = $this->_backend->saveTask($depTask->toArray());
        $this->assertSame([], $savedDepTask[Tasks_Model_Task::FLD_DEPENDENT_TASKS]);
        $this->assertCount(1, $savedDepTask[Tasks_Model_Task::FLD_DEPENDENS_ON]);

        Tinebase_Record_Expander_DataRequest::clearCache();
        $returned = $this->_backend->getTask($returned['id']);
        $this->assertCount(1, $returned[Tasks_Model_Task::FLD_DEPENDENT_TASKS]);
        $this->assertSame([], $returned[Tasks_Model_Task::FLD_DEPENDENS_ON]);

        $thirdTask = $this->_backend->saveTask($this->_getTask()->toArray());
        $returned[Tasks_Model_Task::FLD_DEPENDENT_TASKS][] =
            (new Tasks_Model_TaskDependency([
                Tasks_Model_TaskDependency::FLD_TASK_ID => $thirdTask['id'],
            ], true))->toArray();
        $returned = $this->_backend->saveTask($returned);

        $this->assertCount(2, $returned[Tasks_Model_Task::FLD_DEPENDENT_TASKS]);
        $this->assertSame([], $returned[Tasks_Model_Task::FLD_DEPENDENS_ON]);

        $thirdTask = $this->_backend->getTask($thirdTask['id']);
        $this->assertCount(0, $thirdTask[Tasks_Model_Task::FLD_DEPENDENT_TASKS]);
        $this->assertCount(1, $thirdTask[Tasks_Model_Task::FLD_DEPENDENS_ON]);
    }

    /**
     * test create task with alarm
     *
     */
    public function testCreateTaskWithAlarmTime()
    {
        $task = $this->_getTaskWithAlarm(array(
            'alarm_time'        => Tinebase_DateTime::now(),
            'minutes_before'    => 'custom',
        ));

        $persistentTaskData = $this->_backend->saveTask($task->toArray());

        $this->_checkAlarm($persistentTaskData);
    }

    public function testCreateTaskWithDefaultAlarm()
    {
        $task = $this->_getTaskWithAlarm(array(
            'alarm_time'        => Tinebase_DateTime::now(),
            'minutes_before'    => '15',
            'sent_status' => null
        ));
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $this->_checkAlarm($persistentTaskData);
    }

    /**
     * test create task with alarm
     */
    public function testCreateTaskWithAlarm()
    {
        $task = $this->_getTaskWithAlarm();

        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);

        $this->_checkAlarm($loadedTaskData);
        $this->_sendAlarm();

        // check alarm status
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedTaskData['alarms'][0]['sent_status']);

        // try to save task without due (alarm should be removed)
        unset($task->due);
        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $this->assertTrue(isset($persistentTaskData['alarms']));
        $this->assertEquals(0, count($persistentTaskData['alarms']));
    }

    /**
     * test update task with alarm
     * reshedule alarm for new due
     */
    public function testUpdateTaskWithAlarm()
    {
        $task = $this->_getTaskWithAlarm();

        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);

        $this->_checkAlarm($loadedTaskData);
        $this->_sendAlarm();

        // check alarm status
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_SUCCESS, $loadedTaskData['alarms'][0]['sent_status']);
        $this->assertEquals($loadedTaskData['due'], $loadedTaskData['alarms'][0]['alarm_time']);

        // try to save task with new due (alarm should be moved according to new due and Task should be reactivated)
        $due = new DateTime($loadedTaskData['due']);
        $due->add(new DateInterval('P1D'));
        $loadedTaskData['due'] = $due->format('Y-m-d H:i:s');
        $persistentTaskData = $this->_backend->saveTask($loadedTaskData);

        // check alarm status
        $this->assertTrue(isset($persistentTaskData['alarms']));
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $persistentTaskData['alarms'][0]['sent_status']);
        $this->assertEquals($persistentTaskData['due'], $persistentTaskData['alarms'][0]['alarm_time']);
    }

    /**
     * send alarm via scheduler
     */
    protected function _sendAlarm()
    {
        $scheduler = Tinebase_Core::getScheduler();
        /** @var Tinebase_Model_SchedulerTask $task */
        $task = $scheduler->getBackend()->getByProperty('Tinebase_Alarm', 'name');
        $task->config->run();
    }

    /**
     * create scheduler task
     * 
     * @return Tinebase_Scheduler_Task
     */
    protected function _createTask()
    {
        $request = new Zend_Controller_Request_Http();
        $request->setControllerName('Tinebase_Alarm');
        $request->setActionName('sendPendingAlarms');

        $task = new Tinebase_Scheduler_Task();
        $task->setMonths("Jan-Dec");
        $task->setWeekdays("Sun-Sat");
        $task->setDays("1-31");
        $task->setHours("0-23");
        $task->setMinutes("0/1");
        $task->setRequest($request);
        return $task;
    }

    /**
     * check alarm of task
     * 
     * @param array $_taskData
     */
    protected function _checkAlarm($_taskData)
    {
        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($_taskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $_taskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $_taskData['alarms'][0]['sent_status']);
        $this->assertTrue((isset($_taskData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $_taskData['alarms'][0])), 'minutes_before is missing');
    }

    /**
     * test create task with automatic alarm
     *
     */
    public function testCreateTaskWithAutomaticAlarm()
    {
        $task = $this->_getTask();

        // set config for automatic alarms
        Tasks_Config::getInstance()->set(
            Tinebase_Config::AUTOMATICALARM,
            array(
                2*24*60,    // 2 days before
                //0           // 0 minutes before
            )
        );

        $persistentTaskData = $this->_backend->saveTask($task->toArray());
        $loadedTaskData = $this->_backend->getTask($persistentTaskData['id']);

        // check if alarms are created / returned
        $this->assertGreaterThan(0, count($loadedTaskData['alarms']));
        $this->assertEquals('Tasks_Model_Task', $loadedTaskData['alarms'][0]['model']);
        $this->assertEquals(Tinebase_Model_Alarm::STATUS_PENDING, $loadedTaskData['alarms'][0]['sent_status']);
        $this->assertTrue((isset($loadedTaskData['alarms'][0]['minutes_before']) || array_key_exists('minutes_before', $loadedTaskData['alarms'][0])), 'minutes_before is missing');
        $this->assertEquals(2*24*60, $loadedTaskData['alarms'][0]['minutes_before']);

       // reset automatic alarms config
        Tasks_Config::getInstance()->set(
            Tinebase_Config::AUTOMATICALARM,
            array()
        );
        $this->_backend->deleteTasks($persistentTaskData['id']);
    }

    /**
     * test update of a task
     *
     */
    public function testUpdateTask()
    {
        $task = $this->_getTask();

        $returned = $this->_backend->saveTask($task->toArray());
        $returned['summary'] = 'new summary';

        $updated = $this->_backend->saveTask($returned);
        $this->assertEquals($returned['summary'], $updated['summary']);
        $this->assertNotNull($updated['id']);

        $this->_backend->deleteTasks(array($returned['id']));
    }

    public function testSearchFilterRemoveImplicit()
    {
        $task = $this->_getTask();
        $task->container_id = $this->_getTestContainer(Tasks_Config::APP_NAME, Tasks_Model_Task::class, true);
        $task = $this->_backend->saveTask($task->toArray());

        // search tasks
        $tasks = $this->_backend->searchTasks([[
            "condition" => "OR",
            "filters" => [[
                "condition" => "AND",
                "filters" => [[
                    "field" => "status",
                    "operator" => "notin",
                    "value" => ["COMPLETED", "CANCELLED"],
                ], [
                    "field" => "container_id",
                    "operator" => "in",
                    "value" => [$task['container_id']],
                ]],
            ],[
            "field" => "query",
            "operator" => "contains",
            "value" => null,
        ]]]], []);

        $this->assertCount(1, $tasks['filter'] ?? []);
        $this->assertSame('OR', $tasks['filter'][0]['condition'] ?? 'not set');
    }

    /**
     * try to search for tasks
     *
     */
    public function testSearchTasks()    
    {
        Tasks_Controller_Task::destroyInstance();

        // create task
        $task = $this->_getTask();
        $task = $this->_backend->saveTask($task->toArray());

        // search tasks
        $tasks = $this->_backend->searchTasks($filter = $this->_getFilter(), $this->_getPaging());

        // check
        $count = $tasks['totalcount'];
        $this->assertGreaterThan(0, $count);
        $filter[0]['operator'] = 'equals';
        $filter[0]['value'] = ['path' => '/'];
        $this->assertSame($filter, $tasks['filter'] ?? null, print_r($tasks['filter'], true));

        $tasks = $this->_backend->searchTasks($filter = [
            ['field' => Tasks_Model_Task::FLD_DEPENDENS_ON, 'operator' => 'definedBy', 'value' => [
                ['field' => Tasks_Model_TaskDependency::FLD_DEPENDS_ON, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'summary', 'operator' => 'equals', 'value' => 'shalala'],
                ]],
            ]],
        ], $this->_getPaging());
        $this->assertSame($filter, $tasks['filter']);

        // delete task
        $this->_backend->deleteTasks(array($task['id']));

        // search and check again
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        $this->assertEquals($count - 1, $tasks['totalcount']);
    }

    /**
     * test create default container
     *
     */
    public function testDefaultContainer()
    {
        $application = 'Tasks';
        $task = $this->_getTask();
        $returned = $this->_backend->saveTask($task->toArray());

        $test_container = $this->_backend->getDefaultContainer();
        $this->assertEquals($returned['container_id']['type'], 'personal');

        $application_id_1 = $test_container['application_id'];
        $application_id_2 = Tinebase_Application::getInstance()->getApplicationByName($application)->toArray();
        $application_id_2 = $application_id_2['id'];

        $this->assertEquals($application_id_1, $application_id_2);

        $this->_backend->deleteTasks(array($returned['id']));
    }

    /**
     * test delete organizer of task (and then search task and retrieve single task) 
     * 
     */
    public function testDeleteOrganizer()
    {
        $organizer = $this->_createUser();
        $organizerId = $organizer->getId();

        $task = $this->_getTask();

        $task->organizer = $organizer;
        $returned = $this->_backend->saveTask($task->toArray());
        $taskId = $returned['id'];

        // check search tasks- organizer exists
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');
        $this->assertEquals($tasks['results'][0]['organizer']['accountId'], $organizerId);

        // check get single task - organizer exists
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($task['organizer']['accountId'], $organizerId);

        Tinebase_User::getInstance()->deleteUser($organizerId);

        // test seach search tasks - organizer is deleted
        $tasks = $this->_backend->searchTasks($this->_getFilter(), $this->_getPaging());
        $this->assertEquals(1, $tasks['totalcount'], 'more (or less) than one tasks found');

        $organizerArray = $tasks['results'][0]['organizer'];
        $this->assertTrue(is_array($organizerArray), 'organizer not resolved: ' . print_r($tasks['results'][0], TRUE));
        $expectedDisplayName = $organizer->accountDisplayName;
        $this->assertEquals($expectedDisplayName, $organizerArray['accountDisplayName']);

        // test get single task - organizer is deleted
        $task = $this->_backend->getTask($taskId);
        $this->assertEquals($expectedDisplayName, $task['organizer']['accountDisplayName']);
    }

    /**
     * Create and save dummy user object
     * 
     * @return Tinebase_Model_FullUser
     */
    protected function _createUser()
    {
        try {
            $user = Tinebase_User::getInstance()->getUserByLoginName('creator');
        } catch (Tinebase_Exception_NotFound $tenf) {
            $user = new Tinebase_Model_FullUser(array(
                'accountLoginName'      => 'creator',
                'accountStatus'         => 'enabled',
                'accountExpires'        => NULL,
                'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->id,
                'accountLastName'       => 'Tine 2.0',
                'accountFirstName'      => 'Creator',
                'accountEmailAddress'   => 'phpunit@' . TestServer::getPrimaryMailDomain(),
            ));
            $user = Tinebase_User::getInstance()->addUser($user);
        }

        return $user;
    }

    /**
     * get task record
     *
     * @return Tasks_Model_Task
     * 
     * @todo add task to objects
     */
    protected function _getTask()
    {
        return new Tasks_Model_Task(array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
            'due'           => new Tinebase_DateTime("now", Tinebase_Core::getUserTimezone()),
            'organizer'     => Tinebase_Core::getUser()->getId(),
        ));
    }

    /**
     * get task record
     *
     * @param $_alarmData alarm settings
     * @return Tasks_Model_Task
     */
    protected function _getTaskWithAlarm($_alarmData = NULL)
    {
        $task = new Tasks_Model_Task(array(
            'summary'       => 'minimal task with alarm by PHPUnit::Tasks_ControllerTest',
            'due'           => new Tinebase_DateTime()
        ));
        $alarmData = ($_alarmData !== NULL) ? $_alarmData : array(
            'minutes_before'    => 0
        );
        $task->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array($alarmData), TRUE);
        return $task;
    }

    /**
     * get filter for task search
     *
     * @return Tasks_Model_Task
     */
    protected function _getFilter()
    {
        // define filter
        return array(
            array('field' => 'container_id', 'operator' => 'specialNode', 'value' => 'all'),
            array('field' => 'summary'     , 'operator' => 'contains',    'value' => 'minimal task by PHPUnit'),
            array('field' => 'due'         , 'operator' => 'within',      'value' => 'dayThis'),
        );
    }

    /**
     * get default paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        // define paging
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'summary',
            'dir' => 'ASC',
        );
    }

    /**
     * test advanced search
     *
     * @see 0011492: activate advanced search (search in lead relations)
     *
    public function testAdvancedSearch()
    {
        // create task with lead relation
        $crmTests = new Crm_JsonTest();
        $crmTests->saveLead();

        // activate advanced search
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, true);

        // search in lead
        $result = $this->_backend->searchTasks(array(array(
            'field' => 'query', 'operator' => 'contains', 'value' => 'PHPUnit LEAD'
        )), array());
        $this->assertEquals(1, $result['totalcount']);
    }*/
}
