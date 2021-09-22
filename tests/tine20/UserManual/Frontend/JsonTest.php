<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for UserManual_Frontend_Json
 */
class UserManual_Frontend_JsonTest extends TestCase
{
    /**
     * instance of test class
     *
     * @var Tinebase_Frontend_Json_Generic
     */
    protected $_uit;

    /**
     * lazy init of uit
     *
     * @return Tinebase_Frontend_Json_Generic
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $this->_uit = new Tinebase_Frontend_Json_Generic('UserManual');
        }

        return $this->_uit;
    }

    /**
     * testManualPageApi
     */
    public function testManualPageApi()
    {
        $this->_testSimpleRecordApi('ManualPage', 'title', 'file', false, [
            'content' => '<html></html>',
        ]);
    }

    /**
     * testSearchPage
     */
    public function testSearchPage()
    {
        $this->testManualPageApi();

        $filter = array();
        $result = $this->_getUit()->searchManualPages($filter, array('limit' => 50));

        self::assertTrue($result['totalcount'] > 0);
        self::assertTrue(! isset($result['results'][0]['content']), 'should not return html');
    }
}
