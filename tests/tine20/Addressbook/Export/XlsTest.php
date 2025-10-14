<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Addressbook Xls generation class tests
 *
 * @package     Addressbook
 * @subpackage  Export
 */
class Addressbook_Export_XlsTest extends TestCase
{
    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Exception
     */
    public function testXlsxExport()
    {
        Tinebase_Core::setupUserLocale('en');
        $doc = $this->_doXlsExport();

        // depending on the number of contact fields, the range might have to be increased some more
        $arrayData = $doc->getActiveSheet()->rangeToArray('A1:CH4');

        $positionIndex = array_search('Salutation', $arrayData[2], true);
        static::assertNotEquals(false, $positionIndex, 'can\'t find Salutation in: ' . print_r($arrayData[2], true));
        static::assertEquals('Mr', $arrayData[3][$positionIndex], $positionIndex . ' ' . print_r($arrayData[3], true));

        $positionIndex = array_search('Tags', $arrayData[2], true);
        static::assertNotSame(false, $positionIndex, 'could not find "Tags" column');
        static::assertStringContainsString('tag1', $arrayData[3][$positionIndex]);
        static::assertStringContainsString('tag2', $arrayData[3][$positionIndex]);
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function testXlsxExportLocalised()
    {
        Tinebase_Core::setupUserLocale('de');
        $doc = $this->_doXlsExport();

        $arrayData = $doc->getActiveSheet()->rangeToArray('A1:CA4');

        $positionIndex = array_search('Anrede', $arrayData[2], true);
        static::assertNotEquals(false, $positionIndex, 'can\'t find Anrede in: ' . print_r($arrayData[2], true));
        static::assertEquals('Herr', $arrayData[3][$positionIndex], $positionIndex . ' ' . print_r($arrayData[3], true));
    }

    public function testCustomFields100PlusRows()
    {
        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId(),
            'model' => Addressbook_Model_Contact::class,
            'name' => 'unittest_non_system_cf',
            'definition' => ['type' => 'string'],
        ]));

        for ($i = 0; $i < 110; ++$i) {
            Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
                'n_given' => 'Test Contact Name ' . $i,
                'n_family' => 'Test Name ' . $i,
                'unittest_non_system_cf' => 'nscf',
            ]));
        }
        $filter = new Addressbook_Model_ContactFilter([
            ['field' => 'n_given', 'operator' => 'startswith', 'value' => 'Test Contact Name'],
        ]);

        $doc = $this->_doXlsExport(filter: $filter);

        $arrayData = $doc->getActiveSheet()->rangeToArray('A1:DD120');
        $this->assertCount(1, array_keys($arrayData[2], 'unittest_non_system_cf', true));
        $this->assertNotSame(false, $positionIndex = array_search('unittest_non_system_cf', $arrayData[2], true));
        $this->assertSame('nscf', $arrayData[3][$positionIndex]);
        $this->assertSame('nscf', $arrayData[109][$positionIndex]);
    }
}
