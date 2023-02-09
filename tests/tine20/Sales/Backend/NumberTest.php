<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Sales_Backend_NumberTest
 */
class Sales_Backend_NumberTest extends TestCase
{
    
    /**
     * the number backend
     *
     * @var Sales_Backend_Number
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Sales Number Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->_backend = new Sales_Backend_Number();
        parent::setUp();
    }
    
    /**
     * get next number
     */
    public function testGetNextNumber()
    {
        $userId = Tinebase_Core::getUser()->getId();
        $number = $this->_backend->getNext('Sales_Model_Contract', $userId);
        
        $nextNumber = $this->_backend->getNext('Sales_Model_Contract', $userId);
        
        $this->assertEquals($number->number+1, $nextNumber->number);
        $this->assertEquals($number->model, $nextNumber->model);
        
        // reset or delete old number
        if ($number->number == 1) {
            $this->_backend->delete($number);
        } else {
            $number->number--;
            $this->_backend->update($number);
        }
    }
}
