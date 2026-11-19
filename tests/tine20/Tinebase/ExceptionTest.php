<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Exception
 */
class Tinebase_ExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testAppName()
    {
        $e = new Tinebase_Exception('test', 123);

        $this->assertEquals('Tinebase', $e->getAppName());
        $this->assertEquals(123, $e->getCode());
        $this->assertEquals('test', $e->getMessage());
    }
    
    /**
     * @see 0008794: Create Exception Handler Dialog with inputs
     *      https://forge.tine20.org/mantisbt/view.php?id=8794
     */
    public function testTitle()
    {
        $e = new Tinebase_Exception('test', 321);
        $this->assertEquals('Exception ({0})', $e->getTitle());
    }

    public function testIsDbTransactionRetryable()
    {
        $deadlock = new Zend_Db_Statement_Exception('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found'
            . ' when trying to get lock; try restarting transaction');
        $snapshotConflict = new Zend_Db_Statement_Exception('SQLSTATE[HY000]: General error: 1020 Record has'
            . ' changed since last read in table \'tine20_addressbook\'; try restarting transaction');
        $duplicate = new Zend_Db_Statement_Exception('SQLSTATE[23000]: Integrity constraint violation: 1062'
            . ' Duplicate entry');
        $noDbException = new Tinebase_Exception('Deadlock found');

        $this->assertTrue(Tinebase_Exception::isDbTransactionRetryable($deadlock));
        $this->assertTrue(Tinebase_Exception::isDbTransactionRetryable($snapshotConflict));
        $this->assertFalse(Tinebase_Exception::isDbTransactionRetryable($duplicate));
        $this->assertFalse(Tinebase_Exception::isDbTransactionRetryable($noDbException));
    }
}
