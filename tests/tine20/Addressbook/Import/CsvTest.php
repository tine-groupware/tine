<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Addressbook_Import_Csv
 */
class Addressbook_Import_CsvTest extends ImportTestCase
{
    protected $_importerClassName = 'Addressbook_Import_Csv';
    protected $_modelName = 'Addressbook_Model_Contact';

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();

        // always resolve customfields
        Addressbook_Controller_Contact::getInstance()->resolveCustomfields(TRUE);

        // create test container
        $this->_testContainer = $this->_getTestContainer('Addressbook', 'Addressbook_Model_Contact');
        $this->_deleteImportFile = true;
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

        if ($this->_testContainer) {
            Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'addressbook', 'container_id = "' .
                $this->_testContainer->getId() . '"');
        }
        parent::tearDown();

        Addressbook_Controller_Contact::getInstance()->duplicateCheckFields(Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_DUP_FIELDS));
    }
    
    /**
     * test import duplicate data
     *
     * @return array
     */
    public function testImportDuplicates()
    {
        $internalContainer = Tinebase_Container::getInstance()->getContainerByName(Addressbook_Model_Contact::class, 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);
        $options = array(
            'container_id'  => $internalContainer->getId(),
        );
        $result = $this->_doImport($options, 'adb_tine_import_csv', new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $internalContainer->getId()),
        )));

        $this->assertGreaterThan(0, $result['duplicatecount'], 'no duplicates.');
        $this->assertTrue($result['exceptions'] instanceof Tinebase_Record_RecordSet);

        return $result;
    }
    
    /**
     * test import data
     */
    public function testImportSalutation()
    {
        $myContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $salutation = Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_SALUTATION)->records->getFirstRecord()->value;
        $myContact->salutation = $salutation;
        Addressbook_Controller_Contact::getInstance()->update($myContact);
        
        $result = $this->testImportDuplicates();
        
        $found = FALSE;
        foreach ($result['exceptions'] as $exception) {
            if ($exception['exception']['clientRecord']['email'] === Tinebase_Core::getUser()->accountEmailAddress) {
                $found = TRUE;
                $this->assertTrue(isset($exception['exception']['clientRecord']['salutation']), 'no salutation found: ' . print_r($exception['exception']['clientRecord'], TRUE));
                $this->assertEquals($salutation, $exception['exception']['clientRecord']['salutation']);
                break;
            }
        }
        
        $this->assertTrue($found,
            'did not find user ' . Tinebase_Core::getUser()->accountFullName . ' in import exceptions: '
            . print_r($result['exceptions']->toArray(), true));
    }

    /**
     * test import umlaut
     * 
     * @see 0006936: detect import file encoding
     */
    public function testImportUmlaut()
    {
        $myContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $myContact->org_name = 'Übel leckerer Äppler';
        Addressbook_Controller_Contact::getInstance()->update($myContact);
        
        $result = $this->testImportDuplicates();
        
        $found = FALSE;
        foreach ($result['exceptions'] as $exception) {
            $record = $exception['exception']['clientRecord'];
            if ($record['email'] === Tinebase_Core::getUser()->accountEmailAddress) {
                $found = TRUE;
                $this->assertEquals($myContact->org_name, $record['org_name']);
            }
        }
        
        $this->assertTrue($found);
    }
    
    /**
     * import google contacts
     */
    public function testImportGoogleContacts()
    {
        $this->_filename = dirname(__FILE__) . '/files/google_contacts.csv';
        $this->_deleteImportFile = FALSE;

        $options = array(
            'container_id'  => $this->_testContainer->getId(),
            'dryrun' => true,
        );
        $result = $this->_doImport($options, 'adb_google_import_csv');
        
        $this->assertEquals(5, $result['totalcount']);
        $this->assertEquals('Niedersachsen Ring 22', $result['results'][4]->adr_one_street);
        $this->assertEquals('abc@here.de', $result['results'][3]->email);
        $this->assertEquals('+49227913452', $result['results'][0]->tel_work);
    }
    
    /**
     * test import of a customfield
     */
    public function testImportCustomField()
    {
        $this->_createCustomField();
        
        // create/get new import/export definition with customfield
        $filename = dirname(__FILE__) . '/files/adb_google_import_csv_test.xml';
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        $this->_filename = dirname(__FILE__) . '/files/google_contacts.csv';
        $this->_deleteImportFile = FALSE;
        $options = array(
            'container_id'  => $this->_testContainer->getId(),
        );

        $result = $this->_doImport($options, $definition);
        $this->assertEquals(5, $result['totalcount']);
        
        $contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testContainer->getId()),
            array('field' => 'n_given', 'operator' => 'equals', 'value' => 'Ando'),
        )));
        $this->assertEquals(1, count($contacts));
        $ando = $contacts->getFirstRecord();
        $this->assertEquals(array('YomiName' => 'yomi'), $ando->customfields);
    }
    
    /**
     * testExportAndImportWithCustomField
     * 
     * @see 0006230: add customfields to csv export
     */
    public function testImportExportWithCustomFieldTypeString()
    {
        $this->_customFieldImportExportHelper();
    }

    public function testImportExportWithCustomFieldTypeKeyfield()
    {
        $definition = Tinebase_Helper::jsonDecode('{"uiconfig":{"order":null,"group":null,"tab":"TAB","key":null},'
            . '"label":"CF ok","type":"keyField","required":false,"keyFieldConfig":'
            . '{"value":{"records":[{"id":"abgelaufen","value":"abgelaufen"},{"id":"Eintrag vorhanden - nicht ok",'
            . '"value":"Eintrag vorhanden - nicht ok"},{"id":"kein Eintrag vorhanden - ok","value":"kein Eintrag vorhanden - ok"}]}}}');
        $this->_customFieldImportExportHelper([
            'definition' => $definition,
        ], 'kein Eintrag vorhanden - ok');
    }

    public function testImportExportWithCustomFieldTypeDate()
    {
        $definition = Tinebase_Helper::jsonDecode('{"uiconfig":{"order":null,"group":null,"tab":"TAB",'
            . '"key":null},"label":"CF ausgestellt am:","type":"date","required":false}');
        $this->_customFieldImportExportHelper([
            'definition' => $definition,
        ], '2023-04-04',
            // client expects TIME
            '2023-04-04 00:00:00');
    }

    /**
     * @param $cfConfig
     * @param string $cfTestValue
     * @param string|null $expectedValue
     * @return void
     * @throws Addressbook_Exception_AccessDenied
     * @throws Addressbook_Exception_NotFound
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _customFieldImportExportHelper($cfConfig = 'YomiName',
                                                      string $cfTestValue = 'testing',
                                                      ?string $expectedValue = null)
    {
        $customField = $this->_createCustomField($cfConfig);
        $this->assertTrue($customField instanceof Tinebase_Model_CustomField_Config);
        $ownContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $cfValue = array($customField->name => $cfTestValue);
        $ownContact->customfields = $cfValue;
        Addressbook_Controller_Contact::getInstance()->update($ownContact);

        $options = array(
            'container_id'  => $this->_testContainer->getId(),
        );
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        $definition->plugin_options = preg_replace('/<\/mapping>/',
            '<field>
                <source>' . $customField->name . '</source>
                <destination>'. $customField->name . '</destination>
            </field></mapping>', $definition->plugin_options);
        $result = $this->_doImport($options, $definition, new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $ownContact->getId()),
        )));
        $this->assertGreaterThan(0, $result['duplicatecount'], 'no duplicates.');
        $this->assertTrue($result['exceptions'] instanceof Tinebase_Record_RecordSet);

        $exceptionArray = $result['exceptions']->toArray();
        $this->assertTrue(isset($exceptionArray[0]['exception']['clientRecord']['customfields']),
            'could not find customfields in client record: ' . print_r($exceptionArray[0]['exception']['clientRecord'], TRUE));
        $this->assertEquals($expectedValue ?? $cfTestValue, $exceptionArray[0]['exception']['clientRecord']['customfields'][$customField->name],
            'could not find cf value in client record: ' . print_r($exceptionArray[0]['exception']['clientRecord'], TRUE));
    }

    /**
     * testExportAndImportWithBooleanCustomField 0 => 1
     *
     * @see 0011096: Can not import Custom Field "Boolean"
     *
     * @group nogitlabci
     * gitlabci:  Failed asserting that '0' matches expected 1.
     */
    public function testExportAndImportWithBooleanCustomField1($from = 0, $to = 1)
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_family' => 'testcontact',
            'container_id' => $this->_testContainer->getId(),
        ]));
        $cfName = 'booleanTester';
        $customField = $this->_createCustomField($cfName, 'Addressbook_Model_Contact', 'boolean');
        $cfValue = array($customField->name => $from);
        $contact->customfields = $cfValue;
        Addressbook_Controller_Contact::getInstance()->update($contact);

        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('adb_tine_import_csv');
        $definition->plugin_options = preg_replace('/<\/mapping>/',
            '<field>
                <source>' . $cfName . '</source>
                <destination>' . $cfName . '</destination>
            </field></mapping>', $definition->plugin_options);

        $csvReplacements = array('from' => ',"' . $from . '",', 'to' => ',"' . $to . '",');
        $result = $this->_doImport(array(
            'duplicateResolveStrategy' => 'mergeMine'
        ), $definition, new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $contact->getId()),
        )), [], $csvReplacements);

        $this->assertEquals(1, count($result['results']), 'should import/update contact: '
            . print_r($result['results']->toArray(), true));
        $importedRecord = $result['results']->getFirstRecord();
        $this->assertEquals($to, $importedRecord->customfields[$cfName], print_r($importedRecord->toArray(), true));
    }

    /**
     * testExportAndImportWithBooleanCustomField 1 => 0
     *
     * @see 0011096: Can not import Custom Field "Boolean"
     */
    public function testExportAndImportWithBooleanCustomField2()
    {
        $this->testExportAndImportWithBooleanCustomField1(/* from = */ 1, /* to = */ 0);
    }

    /**
     * testImportWithUmlautsWin1252
     * 
     * @see 0006534: import of contacts with umlaut as first char fails
     */
    public function testImportWithUmlautsWin1252()
    {
        $options = array(
            'container_id'  => $this->_testContainer->getId(),
        );

        $definition = $this->_getDefinitionFromFile('adb_import_csv_win1252.xml');
        
        $this->_filename = dirname(__FILE__) . '/files/importtest_win1252.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport($options, $definition);

        $this->assertEquals(4, $result['totalcount']);
        $this->assertEquals('Üglü, ÖzdemirÖ', $result['results'][2]->n_fileas, 'Umlauts were not imported correctly: ' . print_r($result['results'][2]->toArray(), TRUE));
    }
    
    /**
     * returns import definition from file
     * 
     * @param string $filename
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getDefinitionFromFile($filename, $path = null)
    {
        $filename = ($path ? $path : dirname(__FILE__) . '/files/') . $filename;
        $applicationId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile($filename, $applicationId);
        
        return $definition;
    }

    /**
     * testImportDuplicateResolve
     * 
     * @see 0009316: add duplicate resolving to cli import
     *
     * @param bool $withCustomfields
     * @return array
     */
    public function testImportDuplicateResolve($withCustomfields = false)
    {
        $options = array(
            'container_id'  => $this->_testContainer->getId(),
        );

        $definition = $this->_getDefinitionFromFile('adb_import_csv_duplicate.xml');
        
        $this->_filename = dirname(__FILE__) . ($withCustomfields ? '/files/import_duplicate_1_cf.csv' : '/files/import_duplicate_1.csv');
        $this->_deleteImportFile = FALSE;
        
        $this->_doImport($options, $definition);

        $this->_filename = dirname(__FILE__) .($withCustomfields ? '/files/import_duplicate_2_cf.csv' : '/files/import_duplicate_2.csv');
        
        $result = $this->_doImport(array(), $definition);
        
        $this->assertEquals(2, $result['updatecount'], 'should have updated 2 contacts');
        $this->assertEquals(0, $result['totalcount'], 'should have imported 0 records: ' . print_r($result['results']->toArray(), true));
        $this->assertEquals(0, $result['failcount']);
        $this->assertEquals('joerg@home.com', $result['results'][0]->email_home, 'duplicates resolving did not work: ' . print_r($result['results']->toArray(), true));
        $this->assertEquals('Jörg', $result['results'][0]->n_given, 'wrong encoding: ' . print_r($result['results']->toArray(), true));

        return $result;
    }

    /**
     * testImportDuplicateResolveCustomfields
     */
    public function testImportDuplicateResolveCustomfields()
    {
        $this->_createCustomField('customfield1');
        $this->_createCustomField('customfield2');
        // empty values: should not trigger record updates
        $this->_createCustomField('customfield3');

        $result = $this->testImportDuplicateResolve(/* $withCustomfields */ true);

        // check customfields in result
        $joerg = $result['results'][0]->toArray();
        $this->assertTrue(isset($joerg['customfields']), 'cfs missing: ' .  print_r($joerg, true));
        $this->assertFalse(isset($joerg['customfields']['customfield1']), print_r($joerg, true));
        $this->assertEquals('cf2-2', $joerg['customfields']['customfield2']);
    }

    /**
     * testImportLxOffice
     */
    public function testImportLxOffice()
    {
        $options = array(
            'container_id'  => $this->_testContainer->getId(),
        );
        
        // add duplicate field "customernumber"
        Addressbook_Controller_Contact::getInstance()->duplicateCheckFields(array(
            array('email'),
            array('customernumber')
        ));
        
        $this->_createCustomField('customernumber');
        
        $definition = $this->_getDefinitionFromFile('adb_lxoffice_import_csv.xml',
            dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/tine20/Addressbook/Import/definitions/');
        
        $this->_filename = dirname(__FILE__) . '/files/importtest_lxoffice1.csv';
        $this->_deleteImportFile = FALSE;
        
        $result = $this->_doImport($options, $definition);

        $this->assertEquals(3, $result['totalcount'], print_r($result['results']->toArray(), true));
        
        $contacts = $result['results'];
        $berger = $contacts->getFirstRecord();
        $this->assertEquals(array('customernumber' => '73029'), $berger->customfields, print_r($berger->toArray(), true));
        
        $this->_filename = dirname(__FILE__) . '/files/importtest_lxoffice2.csv';
        
        $result = $this->_doImport($options, $definition);
        
        self::assertGreaterThanOrEqual(5, count($result['results']));
        // NOTE: this assertion is strange because the results vary between 1 and 2
        self::assertGreaterThanOrEqual(1, $result['updatecount'], 'should have updated 1 or more contacts / results: '
            . print_r($result['results']->toArray(), true));
        // NOTE: this assertion is strange because the results vary between 3 and 4
        self::assertTrue((3 === $result['totalcount'] || 4 === $result['totalcount']), 'should have added 3 or 4 contacts');
        self::assertEquals('Straßbough', $result['results'][1]['adr_one_locality'],
                'should have changed the locality of contact #2: ' . print_r($result['results'][1]->toArray(), true));
        $n_family = $result['results'][3]['n_family'];
        self::assertTrue('Gartencenter Röhr & Vater' === $n_family
            || 'Dr. Schutheiss' === $n_family,
            print_r($result['results']->toArray(), true));
    }

    public function testSplitField()
    {
        $definition = $this->_getDefinitionFromFile('adb_import_csv_split.xml');

        $this->_filename = dirname(__FILE__) . '/files/import_split.csv';
        $this->_deleteImportFile = FALSE;

        $result = $this->_doImport(array('dryrun' => true), $definition);

        $this->assertTrue(1 === $result['totalcount'] || 1 === $result['updatecount'], print_r($result, true));
        $importedRecord = $result['results']->getFirstRecord();

        $this->assertEquals('21222', $importedRecord->adr_one_postalcode, print_r($importedRecord->toArray(), true));
        if (1 === $result['updatecount']) {
            $this->assertEquals('Köln', $importedRecord->adr_one_locality, print_r($importedRecord->toArray(), true));
        } else {
            $this->assertEquals('Käln', $importedRecord->adr_one_locality, print_r($importedRecord->toArray(), true));
        }
    }

    /**
     * @see 0011354: keep both records if duplicates are within current import file
     */
    public function testImportDuplicateInImport()
    {
        $definition = $this->_getDefinitionFromFile('adb_import_csv_split.xml');

        $this->_filename = dirname(__FILE__) . '/files/import_split_duplicate.csv';
        $this->_deletePersonalContacts = TRUE;
        $this->_deleteImportFile = false;

        $result = $this->_doImport(array('dryrun' => false), $definition);

        $this->assertEquals(2, $result['totalcount'], print_r($result, true));
        $this->assertEquals(2, count(array_unique($result['results']->getArrayOfIds())));
    }

    public function testImportOutlook2013()
    {
        $definition = $this->_getDefinitionFromFile('../../../../../tine20/Addressbook/Import/definitions/adb_outlook_import_csv.xml');

        $this->_deleteImportFile = false;
        $this->_filename = dirname(__FILE__) . '/files/importtest_outlook2013.csv';

        $result = $this->_doImport(array('dryrun' => true), $definition);
        $this->assertEquals('c.baumann@unittest.de', $result['results'][0]->email);
    }

    public function testAppendField()
    {
        $contact = $this->_doImportSingleContact('adb_import_csv_append.xml', 'append.csv');
        self::assertEquals('0190 800', $contact->tel_work, print_r($contact->toArray(), true));
    }

    public function testMapUndefinedFields()
    {
        $contact = $this->_doImportSingleContact('adb_import_csv_mapundefined.xml', 'mapundefined.csv');

        $translation = Tinebase_Translation::getTranslation('Tinebase');

        $valueIfEmpty = $translation->_("N/A");
        $expected = sprintf($translation->_("The following fields weren't imported: %s"), "\n");
        $expected .= "und_so_weiter : irgendwas \nleer : " . $valueIfEmpty . " \n";
        self::assertEquals($expected, $contact->note, print_r($contact->toArray(), true));
    }

    public function testMapUndefinedFieldsIgnoreEmpty()
    {
        $contact = $this->_doImportSingleContact('adb_import_csv_mapundefinedempty.xml', 'mapundefined.csv');
        $expected = "und_so_weiter : irgendwas \n";
        self::assertEquals($expected, $contact->note, print_r($contact->toArray(), true));
    }

    protected function _doImportSingleContact($xml, $csv)
    {
        $definition = $this->_getDefinitionFromFile($xml);
        $this->_filename = dirname(__FILE__) . '/files/' . $csv;
        $this->_deleteImportFile = false;
        $result = $this->_doImport(array('dryrun' => true), $definition);
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        return $result['results']->getFirstRecord();
    }
}
