<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Bookmarks
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Timo Scholz <t.scholz@metaways.de>
 */



/**
 * Test class for Bookmarks_ImportTest
 */
class Bookmarks_ImportTest extends ImportTestCase
{


    public function testCliImport()
    {
        $countBeforeImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));
        $this->_deleteImportFile = false;
        $this->_filename = __DIR__ . '/../../lib/Bookmarks/Import/examples/bookmarks.html';
        $cli = new Bookmarks_Frontend_Cli();
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array(
            'definition=bookmarks_import_html',
            $this->_filename
        ));

        ob_start();
        $cli->import($opts);
        $out = ob_get_clean();

        $countAfterImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));

        $this->assertStringContainsString('Imported 12 records', $out);
        $this->assertEquals($countBeforeImport + 12, $countAfterImport);

    }

    public function testCliImportDryrun()
    {
        $countBeforeImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));
        $this->_deleteImportFile = false;
        $this->_filename = __DIR__ . '/../../lib/Bookmarks/Import/examples/bookmarks.html';
        $cli = new Bookmarks_Frontend_Cli();
        $opts = new Zend_Console_Getopt('abp:');
       
        $opts->setArguments(array(
            'definition=bookmarks_import_html',
            'dryrun=1',
            $this->_filename
        ));
        
        ob_start();
        $cli->import($opts);
        $out = ob_get_clean();

        $countAfterImport = Bookmarks_Controller_Bookmark::getInstance()->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Bookmarks_Model_Bookmark::class));

        $this->assertStringContainsString('Imported 12 records', $out);
        $this->assertEquals($countBeforeImport, $countAfterImport);


    }


}
