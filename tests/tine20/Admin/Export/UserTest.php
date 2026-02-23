<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Admin_Export_UserTest extends TestCase
{
    public function testExportUser()
    {
        $fh = $this->_genericCsvExport([
            'app' => 'Admin',
            'definition' => dirname(dirname(dirname(dirname(__DIR__)))) . '/tine20/Admin/Export/definitions/admin_user_export_csv.xml',
        ], null);
        try {
            rewind($fh);

            $row = fgetcsv($fh, 0, "\t", '"', escape: '\\');
            print_r($row);
            static::assertTrue(is_array($row), 'could not read csv ');
            static::assertStringContainsString('accountLoginName', $row[0]);

            // TODO assert data (groups, ...)
//            $row = fgetcsv($fh, 0, "\t", '"');
//            print_r($row);
//            static::assertTrue(is_array($row), 'could not read csv: ');
//            static::assertEquals('Paul', $row[0]);
//            static::assertEquals('tmppaul@test.de', $row[1]);
        } finally {
            fclose($fh);
        }
    }
}
