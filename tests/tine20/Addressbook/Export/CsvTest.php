<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */


/**
 * Addressbook Csv generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_CsvTest extends TestCase
{
    protected function _genericExportTest($_config)
    {
        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomerie',
            'n_given'           => 'Paul',
            'n_family'          => 'test',
            'email'             => 'tmpPaul@test.de',
            'tel_home'          => '1234'
        )));
        Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'adr_one_street'   => 'Montgomerie',
            'n_given'           => 'Adam',
            'n_family'          => 'test',
            'email'             => 'tmpAdam@test.de',
            'tel_home'          => '1234',
            'tel_cell'          => '12345',
            'org_name'          => 'Metaways',
        )));
        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'adr_one_street', 'operator' => 'contains', 'value' => 'Montgomerie')
        ));
        $_config['app'] = 'Addressbook';
        return $this->_genericCsvExport($_config, $filter);
    }

    public function testNewCsvExport()
    {
        $fh = $this->_genericExportTest([
            'definition' => __DIR__ . '/definitions/adb_csv_test.xml',
            'exportClass' => Tinebase_Export_CsvNew::class,
        ]);
        try {
            rewind($fh);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Adam', $row[0]);
            static::assertEquals('tmpadam@test.de', $row[1]);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv: ');
            static::assertEquals('Paul', $row[0]);
            static::assertEquals('tmppaul@test.de', $row[1]);
        } finally {
            fclose($fh);
        }
    }

    public function testNewCsvTwigExport()
    {
        $fh = $this->_genericExportTest([
            'definition' => __DIR__ . '/definitions/adb_csv_test_twig.xml',
            'exportClass' => Tinebase_Export_CsvNew::class,
        ]);
        try {
            rewind($fh);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Adam', $row[0]);
            static::assertEquals('tmpadam@test.de', $row[1]);

            $row = fgetcsv($fh, 0, "\t", '"');
            static::assertTrue(is_array($row), 'could not read csv: ');
            static::assertEquals('Paul', $row[0]);
            static::assertEquals('tmppaul@test.de', $row[1]);
            static::assertSame('0000001234', $row[2]);
        } finally {
            fclose($fh);
        }
    }

    public function test3cxExport()
    {
        $fh = $this->_genericExportTest([
            'definitionName' => 'adb_csv_3cx',
        ]);
        try {
            rewind($fh);
            $row = fgetcsv($fh, 0, ",");
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('FirstName', $row[0]);
            static::assertEquals('LastName', $row[1]);
            static::assertEquals('Company', $row[2]);
            static::assertEquals('Mobile', $row[3]);

            $row = fgetcsv($fh, 0, ",");
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertEquals('Adam', $row[0]);
            static::assertEquals('test', $row[1]);
            static::assertEquals('Metaways', $row[2]);
            static::assertEquals('\'+4912345', $row[3]);
            static::assertEquals('\'+491234', $row[5]);
            static::assertEquals('tmpadam@test.de', $row[9]);
        } finally {
            fclose($fh);
        }
    }
}
