<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
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
        (new Sales_Frontend_Cli)->createBoilerplatesIfEmpty();

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


        // for reproducibility, as suites may have the same estimated runtime, sort alphabetically first
        sort($suites, SORT_STRING);
        foreach ($suites as &$name) {
            $name = $name . '_AllTests';
        }

        if ($node_total > 1) {
            // sort by runtime, highest to lowest
            $sortedSuites = [];
            foreach ($suites as $className) {
                if (method_exists($className, 'estimatedRunTime')) {
                    $time = $className::estimatedRunTime();
                } else {
                    $time = 0;
                }
                $sortedSuites[$time][] = $className;
            }
            krsort($sortedSuites, SORT_NUMERIC);

            $suites = array_merge(...$sortedSuites);
        }

        foreach ($suites as $i => $className) {
            if ($i % $node_total === $node_index - 1) {;
                $suite->addTest($className::suite());
            }
        }

        return $suite;
    }
}
