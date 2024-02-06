<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Tinebase_TransactionManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $_testTableName = null;
    
    /**
     * @var Tinebase_TransactionManager
     */
    protected $_instance = NULL;
    
    protected static $_tableXML = '
        <table>
            <name>transactiontest</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>Column1</name>
                    <type>text</type>
                    <length>64</length>
                </field>
                <field>
                    <name>contact_id</name>
                    <type>text</type>
                    <length>64</length>
                </field>
            </declaration>
        </table>
    ';
    
    /**
     * setup test
     */
    protected function setUp(): void
    {
        $setupBackend = Setup_Backend_Factory::factory();
        $setupBackend->dropTable('transactiontest');
        $setupBackend->createTable(new Setup_Backend_Schema_Table_Xml(self::$_tableXML));

        $this->_testTableName = SQL_TABLE_PREFIX . 'transactiontest';
        $this->_instance      = Tinebase_TransactionManager::getInstance();
    }

    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass(): void
    {
        $setupBackend = Setup_Backend_Factory::factory();
        $setupBackend->dropTable('transactiontest');
    }
    
    /**
     * test getInstance()
     */
    public function testGetInstance()
    {
        $instance = Tinebase_TransactionManager::getInstance();
        $this->assertTrue($instance instanceof Tinebase_TransactionManager, 'Could not get an instance of Tinebase_TransactionManager');
    }
    
    /**
     * tests transaction ids
     */
    public function testUniqueTransactionIds()
    {
        $db = Zend_Registry::get('dbAdapter');
        
        $transactionId01 = $this->_instance->startTransaction($db);
        $this->assertEquals('string', gettype($transactionId01), 'TransactionId is not a string');
        $this->assertGreaterThan(10, strlen($transactionId01) , 'TransactionId is weak');
        
        $transactionId02 = $this->_instance->startTransaction($db);
        $this->assertNotEquals($transactionId01, $transactionId02, 'TransactionId is not unique');
        
        $this->_instance->rollBack();
    }
    
    /**
     * test transaction exception on non transactionable
     */
    public function testNonTransactionable()
    {
        $date = Tinebase_DateTime::now();
        $this->expectException('Tinebase_Exception_UnexpectedValue');
        $this->_instance->startTransaction($date);
    }
    
    /**
     * test one single transaction
     */
    public function testOneDbSingleTransaction()
    {
        $db = Zend_Registry::get('dbAdapter');

        $transactionId = $this->_instance->startTransaction($db);
        $db->insert($this->_testTableName, array(
            'Column1' => $transactionId
        ));
        $this->_instance->commitTransaction($transactionId);
        
        $columns = $db->fetchAll("SELECT * FROM " . $this->_testTableName . " WHERE " . $db->quoteInto($db->quoteIdentifier('Column1') . ' = ?', $transactionId) . ";");
        $this->assertEquals(1, count($columns), 'Transaction failed');
        $this->assertEquals($transactionId, $columns[0]['Column1'], 'Transaction was not executed properly');
    }

    /**
     * 
     */
    public function testOneDbRollback()
    {
        $db = Zend_Registry::get('dbAdapter');

        $transactionId = $this->_instance->startTransaction($db);
        $db->insert($this->_testTableName, array(
            'Column1' => $transactionId
        ));
        $this->_instance->rollBack();
        
        $columns = $db->fetchAll("SELECT * FROM " . $this->_testTableName . " WHERE " . $db->quoteInto($db->quoteIdentifier('Column1') . ' = ?', $transactionId) . ";");
        $this->assertSame(0, count($columns));
    }
    
    /**
     * tests if nested transactions will be rolled back, if the outer fails
     */
    public function testNestedTransactions()
    {
        $c1 = Sales_Controller_Contract::getInstance();
        $c2 = Tinebase_Controller_EvaluationDimensionItem::getInstance();
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
            ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
        ]), null, new Tinebase_Record_Expander(Tinebase_Model_EvaluationDimension::class, Tinebase_Model_EvaluationDimension::getConfiguration()->jsonExpander))->getFirstRecord();

        $tm = Tinebase_TransactionManager::getInstance();
        
        $tm->startTransaction(Tinebase_Core::getDb());

        try {
            // create cost center
            $costCenter = $c2->create(new Tinebase_Model_EvaluationDimensionItem(
                array('name' => 'unittest123', 'number' => 123, Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID => $cc->getId())
            ));

            // exception should be thrown (title needed)
            $c1->create(new Sales_Model_Contract(array()));
            
        } catch (Exception $e) {
            $tm->rollBack();
        }
        
        $this->expectException('Tinebase_Exception_NotFound');
        
        // try to get the created cost center
        $c2->get($costCenter->getId());
    }
}
