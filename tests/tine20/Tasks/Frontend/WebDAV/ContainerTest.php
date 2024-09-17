<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tasks_Frontend_WebDAV_Container
 */
class Tasks_Frontend_WebDAV_ContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Tasks WebDAV Container Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->objects['initialContainer'] = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => Tasks_Model_Task::class,
        )));
        
        Tinebase_Container::getInstance()->addGrants($this->objects['initialContainer'], Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, Tinebase_Core::getUser()->accountPrimaryGroup, array(Tinebase_Model_Grants::GRANT_READ));
        
        // must be defined for Calendar/Frontend/WebDAV/Event.php
        $_SERVER['REQUEST_URI'] = 'foobar';
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetName()
    {
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->name, $result);
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetACL()
    {
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getACL();
        
        //var_dump($result);
        
        $this->assertEquals(6, count($result));
    }
    
    /**
     * assert that name of folder is container name
     */
    public function testGetIdAsName()
    {
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer'], true);
        
        $result = $container->getName();
        
        $this->assertEquals($this->objects['initialContainer']->getId(), $result);
    }
    
    /**
     * test getProperties
     */
    public function testGetProperties()
    {
        $this->testCreateFile();
        
        $requestedProperties = array(
            '{http://calendarserver.org/ns/}getctag',
            '{DAV:}resource-id'
        );
        
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $result = $container->getProperties($requestedProperties);
        
        $this->assertTrue(! empty($result['{http://calendarserver.org/ns/}getctag']));
        $this->assertEquals($result['{DAV:}resource-id'], 'urn:uuid:' . $this->objects['initialContainer']->getId());
    }
    
    /**
     * test updateProperties of calendar folder
     */
    public function testUpdateProperties()
    {
        $this->testCreateFile();
        
        $mutations = array(
            '{http://apple.com/ns/ical/}calendar-color'      => '#123456FF',
            '{DAV:}displayname'                              => 'testUpdateProperties',
            '{http://calendarserver.org/ns/}invalidProperty' => null
        );
        
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $container->propPatch($propPath = new \Sabre\DAV\PropPatch($mutations));
        $this->assertTrue($propPath->commit());
        
        $updatedContainer = Tinebase_Container::getInstance()->get($this->objects['initialContainer']);

        $this->assertEquals($updatedContainer->color, substr($mutations['{http://apple.com/ns/ical/}calendar-color'], 0, 7));
        $this->assertEquals($updatedContainer->name,  $mutations['{DAV:}displayname']);
    }
    
    /**
     * test getCreateFile
     * 
     * @return Tasks_Frontend_WebDAV_Task
     */
    public function testCreateFile()
    {
        $GLOBALS['_SERVER']['HTTP_USER_AGENT'] = 'FooBar User Agent';
        
        $vcalendarStream = $this->_getVCalendar(dirname(__FILE__) . '/../../Import/files/lightning.ics');
        
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $id = Tinebase_Record_Abstract::generateUID();
        
        $etag   = $container->createFile("$id.ics", $vcalendarStream);
        $event  = $container->getChild("$id.ics");
        $record = $event->getRecord();
        
        $this->assertTrue($event instanceof Tasks_Frontend_WebDAV_Task);
        $this->assertEquals($id, $record->getId(), 'ID mismatch');
        
        return $event;
    }
    
    /**
     * test getChildren
     * 
     */
    public function testGetChildren()
    {
        $event = $this->testCreateFile()->getRecord();
        
        #// reschedule to match period filter
        #$event->dtstart = Tinebase_DateTime::now();
        #$event->dtend = Tinebase_DateTime::now()->addMinute(30);
        #Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        $container = new Tasks_Frontend_WebDAV_Container($this->objects['initialContainer']);
        
        $children = $container->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertTrue($children[0] instanceof Tasks_Frontend_WebDAV_Task);
    }
    
    /**
     * return vcalendar as string and replace organizers email address with emailaddess of current user
     * 
     * @param string $_filename  file to open
     * @return string
     */
    protected function _getVCalendar($_filename)
    {
        $vcalendar = file_get_contents($_filename);
        
        $vcalendar = preg_replace('/l.kneschke@metaway\n s.de/', Tinebase_Core::getUser()->accountEmailAddress, $vcalendar);
        
        return $vcalendar;
    }
}
