<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for Tinebase_Model_Pagination
 *
 * @package     Tinebase
 */
class Tinebase_Model_PaginationTest extends TestCase
{
    protected function _getOrderSql(Tinebase_Model_Pagination $pagination): string
    {
        $select = Tinebase_Core::getDb()->select()->from(['addressbook' => SQL_TABLE_PREFIX . 'addressbook']);
        $pagination->appendSort($select);
        return (string)$select;
    }

    public function testSortSqlInjection()
    {
        $sql = $this->_getOrderSql(new Tinebase_Model_Pagination([
            'sort' => 'IF(/*foreignSortCol*/(SELECT 1 FROM ' . SQL_TABLE_PREFIX . 'credential_cache LIMIT 1)=1,SLEEP(5),0)',
            'dir' => 'ASC',
        ]));
        $this->assertStringNotContainsString('SLEEP', $sql);
        $this->assertStringNotContainsString('credential_cache', $sql);
    }

    public function testDirSqlInjection()
    {
        $sql = $this->_getOrderSql(new Tinebase_Model_Pagination([
            'sort' => 'n_family',
            'dir' => ', IF((SELECT 1)=1,SLEEP(5),0) ASC',
        ]));
        $this->assertStringNotContainsString('SLEEP', $sql);
        $this->assertStringContainsString('n_family', $sql);

        $sql = $this->_getOrderSql(new Tinebase_Model_Pagination([
            'sort' => ['n_family', 'n_given'],
            'dir' => ['desc', ', SLEEP(5) ASC'],
        ]));
        $this->assertStringNotContainsString('SLEEP', $sql);
        $this->assertStringContainsString('DESC', $sql);
    }

    public function testSearchContactsSortInjection()
    {
        $json = new Addressbook_Frontend_Json();
        $start = microtime(true);
        $result = $json->searchContacts([], [
            'sort' => ['IF(/*foreignSortCol*/(SELECT 1)=1,SLEEP(5),0)', 'n_family'],
            'dir' => ['ASC', ', IF((SELECT 1)=1,SLEEP(5),0) ASC'],
            'limit' => 1,
        ]);
        $this->assertLessThan(5, microtime(true) - $start, 'sql injection via paging possible');
        $this->assertArrayHasKey('results', $result);
    }

    public function testValidSort()
    {
        $sql = $this->_getOrderSql(new Tinebase_Model_Pagination([
            'sort' => ['addressbook.n_family', 'n_given'],
            'dir' => ['DESC', 'ASC'],
        ]));
        $this->assertStringContainsString('`addressbook`.`n_family` DESC', $sql);
        $this->assertStringContainsString('`addressbook`.`n_given` ASC', $sql);
    }
}
