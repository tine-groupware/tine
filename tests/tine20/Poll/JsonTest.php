<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * Test class for Poll_JsonTest
 */
class Poll_JsonTest extends TestCase
{
    public function testGetRegistry()
    {
        $json = new Tinebase_Frontend_Json_Generic('POll');
        $data = $json->getRegistryData();
        self::assertEquals(array(), $data, 'Poll has no custom registry');
    }

    /**
     * TODO activate test
     */
    public function _testExampleAppApi()
    {
        $this->_testSimpleRecordApi(
            'Poll',
            /* $nameField  */ 'name',
            /* $descriptionField */ 'description',
            /* $delete */ false
        );
    }
}
