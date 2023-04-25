<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Inventory
 */
class Inventory_Import_CsvTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Inventory_Import_Csv instance
     */
    protected $_instance = NULL;
    
    /**
     * @var string $_filename
     */
    protected $_filename = NULL;
    
    /**
     * @var boolean
     */
    protected $_deleteImportFile = TRUE;
    
    protected $_deletePersonalInventoryItems = FALSE;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Inventory Csv Import Tests');
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
        Inventory_Controller_InventoryItem::getInstance()->resolveCustomfields(TRUE);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        // cleanup
        if (file_exists($this->_filename) && $this->_deleteImportFile) {
            unlink($this->_filename);
        }
        
        if ($this->_deletePersonalInventoryItems) {
            Inventory_Controller_InventoryItem::getInstance()->deleteByFilter(new Inventory_Model_InventoryItemFilter(array(array(
                'field' => 'container_id', 'operator' => 'equals', 'value' => Inventory_Controller_InventoryItem::getInstance()->getDefaultInventory()->getId()
            ))));
        }
    }
    
    /**
     * test import of a csv
     */
    public function testImportOfCSVWithHook ()
    {
        $this->markTestSkipped('FIXME: repair this test - it fails on nightly build about 80% of the time');

        $filename = dirname(__FILE__) . '/files/inv_tine_import_csv.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(__FILE__) . '/files/inv_tine_import.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array(), $definition);
        $this->_deletePersonalInventoryItems = TRUE;
        
        // There are two test entries, so check for 3 imported entries because one is scripted in the postMappingHook :)
        $this->assertEquals(3, $result['totalcount'], 'import exceptions: ' . print_r($result['exceptions']->toArray(), true));
        
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $translatedString = sprintf($translation->_("The following fields weren't imported: %s"), "\n");
        
        $this->assertEquals($result['results'][0]['name'], 'Tine 2.0 für Einsteiger');
        $this->assertEquals($result['results'][0]['added_date']->setTimezone('Europe/Berlin')->toString(), '2013-01-11 00:00:00');
        $this->assertEquals($result['results'][0]['inventory_id'], '12345');
        $this->assertStringContainsString($translatedString, $result['results'][0]['description']);
        
        $this->assertEquals($result['results'][1]['name'], 'Tine 2.0 für Tolle Leute - second mapping set');
        $this->assertEquals($result['results'][1]['added_date']->setTimezone('Europe/Berlin')->toString(), '2012-01-11 00:00:00');
        $this->assertEquals($result['results'][1]['inventory_id'], '1333431646');
        
        $this->assertEquals($result['results'][2]['name'], 'Tine 2.0 für Profis');
        $this->assertEquals($result['results'][2]['added_date']->setTimezone('Europe/Berlin')->toString(), '2012-01-11 00:00:00');
        $this->assertEquals($result['results'][2]['inventory_id'], '1333431667');
        $this->assertStringContainsString($translatedString, $result['results'][2]['description']);
    }
    
    /**
     * Tests if import works without the _postMappingHook
     */
    public function testImportOfCSVWithoutHook ()
    {
        $filename = dirname(__FILE__) . '/files/inv_tine_import_csv_nohook.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(__FILE__) . '/files/inv_tine_import.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array(), $definition);
        $this->_deletePersonalInventoryItems = TRUE;
        
        // There are two test entries, so check for 2 imported entries
        $this->assertEquals(2, $result['totalcount']);
        
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $translatedString = sprintf($translation->_("The following fields weren't imported: %s"), "\n");
        
        $this->assertEquals($result['results'][0]['name'], 'Tine 2.0 für Einsteiger');
        $this->assertEquals($result['results'][0]['added_date']->setTimezone('Europe/Berlin')->toString(), '2013-01-11 00:00:00');
        $this->assertNotEquals($result['results'][0]['inventory_id'], '');
        $this->assertStringContainsString($translatedString, $result['results'][0]['description']);
        
        $this->assertEquals($result['results'][1]['name'], 'Tine 2.0 für Profis');
        $this->assertEquals($result['results'][1]['added_date']->setTimezone('Europe/Berlin')->toString(), '2012-01-11 00:00:00');
        $this->assertEquals($result['results'][1]['inventory_id'], '1333431667');
    }
    
     /**
     * Test if different Datetime formats are correctly imported, if datetime_pattern is set
     */
    public function testImportOfDatetimeFormats ()
    {
        $filename = dirname(__FILE__) . '/files/inv_tine_import_csv_nohook.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(__FILE__) . '/files/inv_tine_import_datetimes.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array(), $definition);
        $this->_deletePersonalInventoryItems = TRUE;
        
        $this->assertEquals('2013-12-31 00:00:00', $result['results'][0]['added_date']->setTimezone('Europe/Berlin')->toString(), 'Datetime and datetime_pattern should match');
        $this->assertEquals('2013-12-31 00:00:00', $result['results'][0]['removed_date']->setTimezone('Europe/Berlin')->toString(), 'Datetime and datetime_pattern should match');
        $this->assertNull($result['results'][1]['added_date'], 'Datetime and datetime_pattern do not match,  therefore should return null');
        $this->assertNull( $result['results'][1]['added_date'], 'Datetime and datetime_pattern do not match,  therefore should return null');
    }
    
    /**
     * Tests if import of the example file works
     */
    public function testImportOfExampleFile ()
    {
        $filename = dirname(__FILE__) . '/files/inv_tine_import_csv_nohook.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tine20/Inventory/Import/examples/inv_tine_import.csv'; 
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport(array(), $definition);
        $this->_deletePersonalInventoryItems = TRUE;
        
        $translation = Tinebase_Translation::getTranslation('Tinebase');
        $translatedString = sprintf($translation->_("The following fields weren't imported: %s"), "\n");
        
        $this->assertEquals($result['results'][0]['name'], 'Tine 2.0 für Einsteiger');
        $this->assertEquals($result['results'][0]['added_date']->setTimezone('Europe/Berlin')->toString(), '2014-08-27 00:00:00');
        $this->assertEquals($result['results'][0]['inventory_id'], '133331666');
        $this->assertStringContainsString($translatedString, $result['results'][0]['description']);
    }
    
    /**
     * import helper
     *
     * @param array $_options
     * @param string|Tinebase_Model_ImportExportDefinition $_definition
     * @param Inventory_Model_InventoryItemFilter $_exportFilter
     * @return array
     */
    protected function _doImport(array $_options, $_definition, Inventory_Model_InventoryItemFilter $_exportFilter = NULL)
    {
        $definition = ($_definition instanceof Tinebase_Model_ImportExportDefinition) ? $_definition : Tinebase_ImportExportDefinition::getInstance()->getByName($_definition);
        $this->_instance = Inventory_Import_Csv::createFromDefinition($definition, $_options);
        
        // export first
        if ($_exportFilter !== NULL) {
            $exporter = new Inventory_Export_Csv($_exportFilter, Inventory_Controller_InventoryItem::getInstance());
            $this->_filename = $exporter->generate();
        }
        
        // then import
        $result = $this->_instance->importFile($this->_filename);
        
        return $result;
    }
}

