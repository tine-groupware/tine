<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EventManagers
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 *
 */

/**
 * Test class for EventManagers_Json
 */
class EventManager_ControllerTest extends TestCase
{
    /**
     * set up tests
     */
    protected function setUp(): void
    {
        if (!Tinebase_Application::getInstance()->isInstalled('EventManager')) {
            self::markTestSkipped('App is not installed');
        }
        parent::setUp();
    }

    /**
     * try to add an event
     *
     */
    public function testAddEvent()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        self::assertEquals('phpunit event', $event['name'][0][EventManager_Model_EventLocalization::FLD_TEXT]);
    }

    /**
     * try to add an option to an event
     */
    public function testAddOptionToEvent()
    {
        $option = $this->_getOption();
        $createdOption = EventManager_Controller_Option::getInstance()->create($option);
        self::assertEquals($option->event_id, $createdOption->event_id);
    }

    /************ protected helper funcs *************/

    /**
     * get event
     *
     * @param $name
     * @return EventManager_Model_Event
     */
    protected function _getEvent($name = 'phpunit event'): EventManager_Model_Event
    {
        return new EventManager_Model_Event([
            'name'         => [[
                'text' => $name,
                'language' => 'en',
                'type' => 'name',
            ]]
        ], true);
    }
    /**
     * get option
     *
     * @return EventManager_Model_Option
     */
    protected function _getOption(): EventManager_Model_Option
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        return new EventManager_Model_Option([
            'eventId'       => $event['id'],
            'name'          => [[
                EventManager_Model_OptionLocalization::FLD_LANGUAGE => 'en',
                EventManager_Model_OptionLocalization::FLD_TEXT => 'PHPUnit test event',
            ]],
            'description'   => [[
                EventManager_Model_OptionLocalization::FLD_LANGUAGE => 'en',
                EventManager_Model_OptionLocalization::FLD_TEXT => 'test option description',
            ]],
        ]);
    }
}
