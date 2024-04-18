<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

// needed for bootstrap / autoloader
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!Tinebase_Model_Filter_FilterGroup::$beStrict) {
    throw new Exception('unittests need to set Tinebase_Model_Filter_FilterGroup::$beStrict');
}
/**
 * @package     Tinebase
 */
class AllTests
{
    public static function suite()
    {
        $node_total = isset($_ENV['NODE_TOTAL']) ? intval($_ENV['NODE_TOTAL']):1;
        $node_index = isset($_ENV['NODE_INDEX']) ? intval($_ENV['NODE_INDEX']):1;

        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 All Tests');

        $suites = array(
            'Tasks',
            'Tinebase',
            'Felamimail',
            'Addressbook',
            'Calendar',
            'Sales',
            'Crm',
            'ActiveSync',
            'Admin',
            'Courses',
            'Voipmanager',
            'Phone',
            'Timetracker',
            'Filemanager',
            'Projects',
            'HumanResources',
            'Inventory',
            'ExampleApplication',
            'SimpleFAQ',
            'CoreData',
            'Zend',
        );

        // this will not find ./library/OpenDocument/AllTests.php
        // ... but it had not been added previously neither. So nothing changed with regards to that
        foreach (new DirectoryIterator(__DIR__) as $dirIter) {
            if ($dirIter->isDir() && !$dirIter->isDot() &&
                is_file($dirIter->getPathname() . DIRECTORY_SEPARATOR . 'AllTests.php') &&
                'Scheduler' !== $dirIter->getFilename() &&
                !in_array($dirIter->getFilename(), $suites))
            {
                $suites[] = $dirIter->getFilename();
            }
        }

        foreach ($suites as $i => $name) {
            if ($i % $node_total === $node_index - 1) {
                $className = $name . '_AllTests';
                $suite->addTest($className::suite());
            }
        }

        return $suite;
    }
}
