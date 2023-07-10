<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Courses
 */
class Courses_CliTest extends TestCase
{
    /**
     * Backend
     *
     * @var Courses_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * test course
     * 
     * @var array
     */
    protected $_course = NULL;

    /**
     * filtered internet group
     * 
     * @var Tinebase_Model_Group::
     */
    protected $_internetFilteredGroup = NULL;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // enable courses app
        Tinebase_Application::getInstance()->setApplicationStatus(array(
            Tinebase_Application::getInstance()->getApplicationByName('Courses')->getId()
        ), Tinebase_Application::ENABLED);
        
        $this->_cli = new Courses_Frontend_Cli();

        Courses_Config::getInstance()->clearCache();
        
        try {
            $internetGroup = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group(array(
                'name'   => 'internetOn'
            )));
        } catch (Exception $e) {
            $internetGroup = Tinebase_Group::getInstance()->getGroupByName('internetOn');
        }
        Courses_Config::getInstance()->set(Courses_Config::INTERNET_ACCESS_GROUP_ON, $internetGroup->getId());
        
        try {
            $this->_internetFilteredGroup = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group(array(
                'name'   => 'internetFiltered'
            )));
        } catch (Exception $e) {
            $this->_internetFilteredGroup = Tinebase_Group::getInstance()->getGroupByName('internetFiltered');
        }
        Courses_Config::getInstance()->set(Courses_Config::INTERNET_ACCESS_GROUP_FILTERED, $this->_internetFilteredGroup->getId());
        
        $department = Tinebase_Department::getInstance()->create(new Tinebase_Model_Department(array(
            'name'  => Tinebase_Record_Abstract::generateUID()
        )));
        $json = new Courses_Frontend_Json();
        $this->_course = $json->saveCourse(array(
            'name'          => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
            'type'          => $department->getId(),
            'internet'      => 'OFF',
            'members'       => [
                ['id' => Tinebase_Core::getUser()->getId()],
            ],
        ));
    }
    
    /**
     * testResetCoursesInternetAccess
     * 
     * @see 0006370: add cli function for setting all courses to filtered internet
     * @see 0006872: cli function for internet filter does not update memberships
     */
    public function testResetCoursesInternetAccess()
    {
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
            
        $this->assertEquals('OFF', $this->_course['internet']);
        
        ob_start();
        $result = $this->_cli->resetCoursesInternetAccess();
        $out = ob_get_clean();
        
        $this->assertEquals(0, $result, $out);
        $updatedCourse = Courses_Controller_Course::getInstance()->get($this->_course['id']);
        $this->assertEquals('FILTERED', $updatedCourse->internet);
        $this->assertEquals("Updated 1 Course(s)\n", $out);
        
        $groupMembers = Tinebase_Group::getInstance()->getGroupMembers($updatedCourse->group_id);
        $this->assertTrue(count($groupMembers) > 0);
        
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($groupMembers[0]);
        $this->assertTrue(in_array($this->_internetFilteredGroup->getId(), $groupMemberships),
            'filtered internet group not found in group memberships: ' . print_r($groupMemberships, TRUE));
    }
}
