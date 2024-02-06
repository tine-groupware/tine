<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * 
 * @package     Tasks
 */
class Tasks_Model_TaskFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * set up tests
     *
     */
    public function setUp(): void
{
        $user = Tinebase_Core::getUser();
        $container = Tinebase_Container::getInstance()->getPersonalContainer($user, Tasks_Model_Task::class, $user, Tinebase_Model_Grants::GRANT_ADMIN);
        $container_id = $container[0]->getId();
        $backend = Tasks_Controller_Task::getInstance()->getBackend();
        
        $testTask1 = new Tasks_Model_Task(array(
            // Tine 2.0 record fields
            'uid'                  => Tinebase_Record_Abstract::generateUID(),
            'container_id'         => $container_id,
            'created_by'           => 6,
            'creation_time'        => '2009-03-31 17:35:00',
            'is_deleted'           => 0,
            'deleted_time'         => NULL,
            'deleted_by'           => NULL,
            // task only fields
            'percent'              => 70,
            'completed'            => NULL,
            'due'                  => '2009-04-30 17:35:00',
            // ical common fields
            //'class_id'             => 2,
            'description'          => "Test Task",
            'geo'                  => 0.2345,
            'location'             => 'here and there',
            'organizer'            => Tinebase_Core::getUser()->getId(),
            'priority'             => 2,
            'status'               => 'NEEDS-ACTION',
            'summary'              => 'our first test task',
            'url'                  => 'http://www.testtask.com',
        ));
        
        $backend->create($testTask1);
        
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All my tasks",
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array( array('condition' => 'OR', 'filters' => array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
             )))
        )));
    }
    
    /**
     * tear down tests
     *
     */
    public function tearDown(): void
{
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $pfe->deleteByProperty('All my tasks', 'description');
        $backend = Tasks_Controller_Task::getInstance()->getBackend();
        $backend->deleteByProperty("Test Task", "description");
    }
    
    /**
     * Search by container filter
     */
    public function testSearchByContainerFilter() 
    {
        $backend = Tasks_Controller_Task::getInstance()->getBackend();
        $filter = new Tasks_Model_TaskFilter(array(
             array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT)
        ));
        $tasks = $backend->search($filter);
        $this->assertTrue(count($tasks) > 0);
    }
    
    /**
     * Search by organizer filter
     */
    public function testSearchByUserFilter() 
    {
        $backend = Tasks_Controller_Task::getInstance()->getBackend();
        $filter = new Tasks_Model_TaskFilter(array(
             array('field' => 'organizer', 'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT)
        ));
        $tasks = $backend->search($filter);
        $this->assertTrue(count($tasks) > 0);
    }
    
}
