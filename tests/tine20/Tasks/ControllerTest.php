<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Tinebase_Relations
 */
class Tasks_ControllerTest extends TestCase
{
    /**
     * @var array test Task 1 data
     */
    protected $_testTask1;

    /**
     * @var Tasks_Model_Task persistant (readout from db after persistant creation) test Task 1
     */
    protected $_persistantTestTask1;
    
    /**
     * application name of the controller to test
     *
     * @var string
     */
    protected $_appName = 'Tasks';
    
    /**
     * Name of the model(s) this controller handels
     *
     * @var array
     */
    protected $_modelNames = array('Tasks_Model_Task' => 'Task');

    protected Tasks_Controller_Task $_controller;

    protected $_minimalDatas = [];
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_controller = Tasks_Controller_Task::getInstance();
        $this->_minimalDatas = array('Task' => array(
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
        ));
        
        $this->_testTask1 = new Tasks_Model_Task(array(
            // tine record fields
            'container_id'         => NULL,
            'created_by'           => 6,
            'creation_time'        => Tinebase_DateTime::now(),
            'is_deleted'           => 0,
            'deleted_time'         => NULL,
            'deleted_by'           => NULL,
            // task only fields
            'percent'              => 70,
            'completed'            => NULL,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            // ical common fields
            //'class_id'             => 2,
            'description'          => str_pad('',1000,'.'),
            'geo'                  => 0.2345,
            'location'             => 'here and there',
            'organizer'            => Tinebase_Core::getUser()->getId(),
            'priority'             => 2,
            'status'               => 'NEEDS-ACTION',
            'summary'              => 'our first test task',
            'url'                  => 'http://www.testtask.com',
        ),true, false);
        $this->_testTask1->setConvertDates(true);
        
        $this->_persistantTestTask1 = $this->_controller->create($this->_testTask1);
    }

    /**
     * tests if completed gets deleted when status is open
     *
     */
    public function testCompletedNULL()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status = $this->_getStatus()->getId();
        $task->completed = Tinebase_DateTime::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertNull($pTask->completed);
        
        $this->_controller->delete($pTask->getId());
    }
    
    public function testCompletedViaStatus()
    {
        $task = new Tasks_Model_Task($this->_minimalDatas['Task']);
        $task->status = $this->_getStatus(false)->getId();
        //$task->completed = Tinebase_DateTime::now();
        
        $pTask = $this->_controller->create($task);
        $this->assertTrue($pTask->completed instanceof DateTime);
        
        $this->_controller->delete($pTask->getId());
    }
    
    /**
     * returns a status which is defined as open state
     *
     * @return Tasks_Model_Status
     */
    protected function _getStatus($_open=true)
    {
        foreach (Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records as $idx => $status) {
            if (! ($status->is_open xor $_open)) {
                return $status;
            }
        }
    }
    
    /**
     * test basic update function
     */
    public function testUpdateTask()
    {
        $nowTs = Tinebase_DateTime::now()->getTimestamp();
        $task = clone $this->_persistantTestTask1;
        
        $task->summary = 'Update of test task 1';
        $utask = $this->_controller->update($task);
        
        foreach ($task as $field => $value) {
            switch ($field) {
                case 'last_modified_time':
                    $this->assertGreaterThanOrEqual($nowTs, $utask->last_modified_time->getTimestamp(),'', 1);
                    break;
                case 'last_modified_by':
                    $this->assertEquals(Zend_Registry::get('currentAccount')->getId(), $utask->last_modified_by);
                    break;
                case 'notes':
                    break;
                default:
                    if ($value instanceof Tinebase_Record_Abstract || $value instanceof Tinebase_Record_RecordSet) {
                        $diff = $value->diff($utask->$field);
                        $this->assertTrue($diff->isEmpty(), "field $field not equal: " . print_r($diff->toArray(), TRUE));
                    } else {
                        $this->assertEquals($value, $utask->$field, "field $field not equal: " . print_r($value, TRUE));
                    }
            }
        }
        return $utask;
    }
    
    /**
     * testNonConcurrentUpdate
     */
    public function testNonConcurrentUpdate()
    {
        $utask = $this->testUpdateTask();
        
        $nonConflictTask = clone $utask;
        $nonConflictTask->summary = 'Second Update of test task 1';
        return $this->_controller->update($nonConflictTask);
    }
    
    /**
     * testConcurrencyResolveableSameValue
     */
    public function testConcurrencyResolveableSameValue()
    {
        $utask = $this->testUpdateTask();
        
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        
        return $this->_controller->update($resolvableConcurrencyTask);
    }
    
    /**
     * testConcurrencyResolveableOtherField
     */
    public function testConcurrencyResolveableOtherField()
    {
        $utask = $this->testUpdateTask();
        
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
        
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->description = 'other field';
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
    }

    /**
     * try to update the same task twice with the same values, should be resolved by concurrency handling
     * 
     * @see 0007108: inspect and solve concurrency conflicts when setting lead relations
     * @see 0000996: add changes in relations/linked objects to modlog/history
     */
    public function testConcurrencyDateTimeSameValues()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " 1. Update");
        $utask = $this->testUpdateTask();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " 2. Update");
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->due = $utask->due->addMonth(1);
        $resolvableConcurrencyTask->percent = 50;
        $resolvableConcurrencyTask->summary = 'Update of test task 1';
        $this->_controller->update($resolvableConcurrencyTask);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " 3. Update");
        $resolvableConcurrencyTask2 = clone $utask;
        $resolvableConcurrencyTask2->due = $resolvableConcurrencyTask->due;
        $resolvableConcurrencyTask2->percent = 50;
        $resolvableConcurrencyTask2->summary = 'Update of test task 1';
        
        try {
            $updatedTask = $this->_controller->update($resolvableConcurrencyTask2);
            $this->assertEquals($resolvableConcurrencyTask2->due, $updatedTask->due);
        } catch (Tinebase_Exception_ConcurrencyConflict $ttecc) {
            $this->fail($ttecc);
        }
    }
    
    public function testConcurrencyDateTimeResolveable()
    {
        $utask = $this->testUpdateTask();
        
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->due = $utask->due->addMonth(1);
        $this->_controller->update($resolvableConcurrencyTask);
        
        $resolvableConcurrencyTask2 = clone $utask;
        $resolvableConcurrencyTask2->summary = 'Update of test task 1';
        
        try {
            $updatedTask = $this->_controller->update($resolvableConcurrencyTask2);
            $this->assertEquals($resolvableConcurrencyTask->due, $updatedTask->due);
        } catch (Tinebase_Exception_ConcurrencyConflict $ttecc) {
            $this->fail($ttecc);
        }
    }
    
    /**
     * test if non resolvable concurrency problem gets detected
     *
     *@see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function testConcurrencyFail()
    {
        $utask = $this->testUpdateTask();
        
        $conflictTask = clone $utask;
        $conflictTask->seq = 0;
        $conflictTask->summary = 'Non resolvable conflict';
        $this->expectException('Tinebase_Exception_ConcurrencyConflict');
        $this->_controller->update($conflictTask);
    }

    /**
     * 2009-07-14 concurrency management on newly created records 
     */
    public function testConcurrencyFromCreatedTask()
    {
        $utask = $this->testUpdateTask();
        
        $ctask = clone $this->_persistantTestTask1;
        $ctask->description = 'testConcurrencyFromCreatedTask';
        
        $u2task = $this->_controller->update($ctask);
    }
    
    /**
     * testConcurrencyLinebreakMismatch
     * 
     * @see 0007140: normalize linebreaks in concurrency check
     */
    public function testConcurrencyLinebreakMismatch()
    {
        $utask = $this->_persistantTestTask1;
        $utask->description = 'description' . "\n";
        $utask = $this->_controller->update($utask);
        
        // change linebreak in db to \r\n
        $loggedMods = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications('Tasks', $utask->getId(),
            'Tasks_Model_Task', 'Sql', Tinebase_DateTime::now()->subMinute(5), $utask->last_modified_time);
        $this->assertEquals(2, count($loggedMods));
        $mod = $loggedMods[1];
        $diff = new Tinebase_Record_Diff(json_decode($mod->new_value, true));
        $this->assertTrue(isset($diff->diff['description']), 'description not part of diff');
        $diffArray = $diff->diff;
        $diffArray['description'] = 'description' . "\r\n";
        $diff->diff = $diffArray;
        $mod->new_value = json_encode($diff);
        
        $modlog = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'timemachine_modlog'));
        $modlog->update($mod->toArray(), Tinebase_Core::getDb()->quoteInto('id = ?', $mod->id));
        
        // this should still work as we normalize linebreaks in concurrency check
        $resolvableConcurrencyTask = clone $utask;
        $resolvableConcurrencyTask->last_modified_time = Tinebase_DateTime::now()->addHour(-1);
        $resolvableConcurrencyTask->description = 'description'. "\n";
        $task = $this->_controller->update($resolvableConcurrencyTask);
        
        $this->assertEquals('description'. "\n", $task->description);
    }

    public function testFilterNotDefinedBy()
    {
        $this->_controller->create(new Tasks_Model_Task([
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
            'due'           => Tinebase_DateTime::now(),
            'organizer'     => Tinebase_Core::getUser()->getId(),
            Tasks_Model_Task::FLD_DEPENDENS_ON => new Tinebase_Record_RecordSet(Tasks_Model_TaskDependency::class, [[
                Tasks_Model_TaskDependency::FLD_DEPENDS_ON => $this->_persistantTestTask1->getId(),
            ]], true),
        ]));

        $searchResult = $this->_controller->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => Tasks_Model_Task::FLD_DEPENDENS_ON, 'operator' => 'notDefinedBy', 'value' => [
                ['field' => Tasks_Model_TaskDependency::FLD_DEPENDS_ON, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'summary', 'operator' => 'equals', 'value' => $this->_persistantTestTask1->summary],
                ]],
            ]],
        ]));

        $this->assertSame(1, $searchResult->count());
        $this->assertSame($this->_persistantTestTask1->getId(), $searchResult->getFirstRecord()->getId());
    }

    public function testTasksDueFilter()
    {
        $this->_controller->create(new Tasks_Model_Task([
            'summary'       => 'minimal task by PHPUnit::Tasks_ControllerTest',
            'due'           => Tinebase_DateTime::now()->subDay(1),
            'organizer'     => Tinebase_Core::getUser()->getId(),
            Tasks_Model_Task::FLD_DEPENDENS_ON => new Tinebase_Record_RecordSet(Tasks_Model_TaskDependency::class, [[
                Tasks_Model_TaskDependency::FLD_DEPENDS_ON => $this->_persistantTestTask1->getId(),
            ]], true),
        ]));

        $searchResult = $this->_controller->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => 'tasksDue', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id],
        ]));

        // persistant *sic* test task has no attendees, so we as organizer are responsible for it
        $this->assertSame(1, $searchResult->count());
        $this->assertSame($this->_persistantTestTask1->getId(), $searchResult->getFirstRecord()->getId());

        $this->_persistantTestTask1 = $this->_controller->get($this->_persistantTestTask1->getId());
        $this->_persistantTestTask1->{Tasks_Model_Task::FLD_ATTENDEES} =
            new Tinebase_Record_RecordSet(Tasks_Model_Attendee::class, [[
                Tasks_Model_Attendee::FLD_USER_ID => $this->_personas['sclever']->contact_id,
            ]], true);
        $this->_persistantTestTask1 = $this->_controller->update($this->_persistantTestTask1);

        $searchResult = $this->_controller->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => 'tasksDue', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id],
        ]));
        $this->assertSame(0, $searchResult->count());

        $this->_persistantTestTask1->{Tasks_Model_Task::FLD_ATTENDEES}->getFirstRecord()
            ->{Tasks_Model_Attendee::FLD_STATUS} = 'DECLINED';
        $this->_persistantTestTask1 = $this->_controller->update($this->_persistantTestTask1);
        $this->assertSame('DECLINED', $this->_persistantTestTask1->{Tasks_Model_Task::FLD_ATTENDEES}->getFirstRecord()
            ->{Tasks_Model_Attendee::FLD_STATUS});

        $searchResult = $this->_controller->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => 'tasksDue', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->contact_id],
        ]));
        $this->assertSame(1, $searchResult->count());
        $this->assertSame($this->_persistantTestTask1->getId(), $searchResult->getFirstRecord()->getId());
    }
}
