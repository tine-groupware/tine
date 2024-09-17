<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for GDPR Json Frontends
 */
class GDPR_Frontend_JsonTest extends TestCase
{
    /** @var GDPR_Model_DataIntendedPurpose */
    protected $_dataIntendedPurpose1 = null;
    /** @var GDPR_Model_DataIntendedPurpose */
    protected $_dataIntendedPurpose2 = null;

    /**
     * set up tests
     */
    protected function setUp(): void
    {
        $this->_uit = new GDPR_Frontend_Json();

        parent::setUp();

        $this->_dataIntendedPurpose1 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' =>   [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'unittest1',
                ]],
            ], true));
        $this->_dataIntendedPurpose2 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' =>                 [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'unittest2',
                ]],
            ], true));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        GDPR_Config::getInstance()->clearMemoryCache();
        Addressbook_Model_Contact::resetConfiguration();
    }

    public function testCreateByAdbContact()
    {
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT);
        Addressbook_Model_Contact::resetConfiguration();

        $contact = new Addressbook_Model_Contact([
            'n_given'       => 'unittest',
            'email'         => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME => '',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose'           => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate'                 => Tinebase_DateTime::now(),
                    'agreeComment'              => 'well, I talked the contact into it',
                ], true)
            ],
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME => '',
        ], true);

        $adbJsonFE = new Addressbook_Frontend_Json();
        $createdContact = $adbJsonFE->saveContact($contact->toArray());

        static::assertTrue(isset($createdContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME]), 'resolving did not work');
        static::assertCount(1, $createdContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME], 'expect 1 intended purposes');
        static::assertTrue(is_array($createdContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][0]['intendedPurpose']),
            'expect resolved intended purposes');

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact['id']]));
        static::assertSame(1, $createdDipr->count(), 'expect to find 1 data intended purpose records for this contact');

        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord(Addressbook_Model_Contact::class,
            $createdContact['id'],'Sql', false);
        static::assertStringContainsString(GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
            $notes->getFirstRecord()->note, 'expect dataprovenance in notes');
        static::assertStringContainsString(GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME,
            $notes->getFirstRecord()->note, 'expect intendedPurpose in notes');


        $newDipr = new GDPR_Model_DataIntendedPurposeRecord([
            'intendedPurpose'           => $this->_dataIntendedPurpose2->getId(),
            'agreeDate'                 => Tinebase_DateTime::now(),
            'agreeComment'              => 'well, I talked the contact into that too',
        ], true);
        $updatedContact = $createdContact;
        $updatedContact['n_family'] = 'n_familyBlub';
        $updatedContact[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME] = '';
        $updatedContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][] =
            $newDipr->toArray();
        $updatedContact = $adbJsonFE->saveContact($updatedContact);

        static::assertTrue(isset($updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME]), 'resolving did not work');
        static::assertCount(2, $updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME], 'expect 2 intended purposes');
        static::assertTrue(is_array($updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][0]['intendedPurpose']),
            'expect resolved intended purposes');

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact['id']]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        $note = Tinebase_Notes::getInstance()->getNotesOfRecord(Addressbook_Model_Contact::class,
            $createdContact['id'],'Sql', false)->filter('note_type_id', Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED)->getFirstRecord();
        static::assertStringContainsString(GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME, $note->note,
            'expect dataprovenance in notes');
        static::assertStringContainsString(GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, $note->note,
            'expect intendedPurpose in notes');


        // after update with dependent record property === null -> no changes
        $updatedContact['n_family'] = 'n_family';
        $updatedContact[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME] = '';
        $updatedContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME] = null;
        $updatedContact = $adbJsonFE->saveContact($updatedContact);

        static::assertTrue(isset($updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME]), 'resolving did not work');
        static::assertCount(2, $updatedContact[
        GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME], 'expect 2 intended purposes');
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $updatedContact['id']]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');


        // test withdrawdate
        $now = Tinebase_DateTime::now()->toString('Y-m-d 00:00:00');
        $updatedContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][0]['withdrawDate']
            = $now;
        $updatedContact = $adbJsonFE->saveContact($updatedContact);
        $updatedContact = $adbJsonFE->getContact($updatedContact['id']);
        static::assertTrue(isset($updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME]), 'resolving did not work');
        static::assertCount(2, $updatedContact[
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME], 'expect 2 intended purposes');
        static::assertSame($now,
            $updatedContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][0]['withdrawDate']);

        unset($updatedContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME][1]);
        $adbJsonFE->saveContact($updatedContact);
        
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' =>  $updatedContact['id']]));
        static::assertSame(1, $createdDipr->count(), 'expect to find 1 data intended purpose records for this contact');

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' =>  $updatedContact['id']], '',
                [GDPR_Model_DataIntendedPurposeRecordFilter::OPTIONS_SHOW_WITHDRAWN => true]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        return $contact;
    }
    
    function testAdbFilter()
    {
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT);
        Addressbook_Model_Contact::resetConfiguration();

        $contact = new Addressbook_Model_Contact([
            'n_given'       => 'unittest',
            'email'         => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose'           => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate'                 => Tinebase_DateTime::now(),
                    'agreeComment'              => 'well, I talked the contact into it',
                ], true),
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose'           => $this->_dataIntendedPurpose2->getId(),
                    'agreeDate'                 => Tinebase_DateTime::now(),
                    'agreeComment'              => 'well, I talked the contact into that too',
                    'withdrawDate'              => Tinebase_DateTime::now(),
                ], true)
            ]
        ], true);

        $adbJsonFE = new Addressbook_Frontend_Json();
        $createdContact = $adbJsonFE->saveContact($contact->toArray());
        static::assertCount(2,
            $createdContact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME]);

        $filter = json_decode('[{"condition":"AND","filters":
        [{"field":"GDPR_DataIntendedPurposeRecord","operator":"AND","value":
        [{"field":":intendedPurpose","operator":"equals","value":"' . $this->_dataIntendedPurpose1->getId() . '"}]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);

        static::assertCount(1, $result['results']);
        static::assertSame($createdContact['id'], $result['results'][0]['id']);
        static::assertTrue(isset($result['filter'][0]['filters'][0]), 'expect proper filter in result');
        static::assertSame('GDPR_DataIntendedPurposeRecord', $result['filter'][0]['filters'][0]['field']);
        static::assertCount(1, $result['filter'][0]['filters'][0]['value']);
        static::assertTrue(is_array($result['filter'][0]['filters'][0]['value'][0]['value']));
        static::assertTrue(isset($result['filter'][0]['filters'][0]['value'][0]['value']['id']));
        static::assertSame($this->_dataIntendedPurpose1->getId(),
            $result['filter'][0]['filters'][0]['value'][0]['value']['id']);

        $filter = json_decode('[{"condition":"AND","filters":
        [{"field":"GDPR_DataIntendedPurposeRecord","operator":"AND","value":
        [{"field":":intendedPurpose","operator":"equals","value":"' . $this->_dataIntendedPurpose2->getId() . '"}]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);
        static::assertCount(0, $result['results']);


        $filter = json_decode('[{"condition":"AND","filters":
        [{"field":"GDPR_DataIntendedPurposeRecord","operator":"notDefinedBy:AND","value":
        [{"field":":intendedPurpose","operator":"equals","value":"' . $this->_dataIntendedPurpose2->getId() . '"}]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);

        static::assertGreaterThan(1, $result['results']);
        $ids = [];
        foreach ($result['results'] as $r) {
            $ids[$r['id']] = true;
        }
        static::assertTrue(isset($ids[$createdContact['id']]));


        $filter = json_decode('[{"condition":"AND","filters":
        [{"field":"GDPR_DataIntendedPurposeRecord","operator":"notDefinedBy:AND","value":
        [{"field":":intendedPurpose","operator":"equals","value":"' . $this->_dataIntendedPurpose1->getId() . '"}]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);

        static::assertGreaterThan(1, $result['results']);
        $ids = [];
        foreach ($result['results'] as $r) {
            $ids[$r['id']] = true;
        }
        static::assertFalse(isset($ids[$createdContact['id']]));


        $filter = json_decode('[{"condition":"AND","filters":
        [{"field": "GDPR_DataIntendedPurposeRecord","operator": "definedBy?condition=and&setOperator=oneOf",
        "value": [{"field": ":intendedPurpose","operator": "in",
                "value": [
                    {"id": "' . $this->_dataIntendedPurpose1->getId() . '", "name": "foo", "notes": "", "created_by": {
                        "accountId": "9a070b1f4a5ee1f1c2efb26f1f2c13ed07302ee3",
                        "accountLoginName": "tine20admin",
                        "accountDisplayName": "Admin, tine ®",
                        "accountFullName": "tine ® Admin",
                        "accountFirstName": "tine ®",
                        "accountLastName": "Admin",
                        "accountEmailAddress": "tine20admin@mail.test",
                        "contact_id": "e927142bc5497f53bb612e87d49b967e84b4828d",
                        "created_by": "65ecd668dadbb6ca5150c627dcda2021f52c4e33",
                        "creation_time": "2022-07-20 11:12:12",
                        "last_modified_by": "65ecd668dadbb6ca5150c627dcda2021f52c4e33",
                        "last_modified_time": "2022-07-20 11:12:12",
                        "is_deleted": "0",
                        "deleted_time": null,
                        "deleted_by": null,
                        "seq": "4",
                        "xprops": {
                          "emailUserIdImap": "ea4a06f1e5b1ef756670ade0187fa406d3907d86",
                          "emailUserIdSmtp": "ea4a06f1e5b1ef756670ade0187fa406d3907d86"
                        },
                        "notes": []
                      }, "deleted_by": null},
                    {"id": "' . $this->_dataIntendedPurpose2->getId() . '", "name": "bar"}
                ]
        }]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);
        static::assertCount(1, $result['results']);


        $filter = json_decode('[{"condition":"AND","filters":
        [{"field": "GDPR_DataIntendedPurposeRecord","operator": "definedBy?condition=and&setOperator=oneOf",
        "value": [
            {"field": "intendedPurpose","operator": "definedBy?condition=and&setOperator=oneOf",
                "value": [{"field": ":id","operator": "equals","value": "' . $this->_dataIntendedPurpose1->getId() . '"}]},
            {"field": "withdrawDate","operator": "equals","value": "2022-08-17 00:00:00"}
            ]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);
        static::assertCount(0, $result['results']);
    }

    function testAdbDecodedFilter()
    {
        GDPR_Config::getInstance()->set(GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY,
            GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT);
        Addressbook_Model_Contact::resetConfiguration();

        $contact = new Addressbook_Model_Contact([
            'n_given' => 'unittest',
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            'email_home' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into it',
                ], true),
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose2->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into that too',
                    'withdrawDate' => Tinebase_DateTime::now(),
                ], true)
            ]
        ], true);

        $adbJsonFE = new Addressbook_Frontend_Json();
        $adbJsonFE->saveContact($contact->toArray());

        $filter = json_decode('[{"condition":"AND","filters":
        [{"field": "GDPR_DataIntendedPurposeRecord","operator": "definedBy?condition=and&setOperator=oneOf",
        "value": [
            {"field": "intendedPurpose","operator": "equals",
                "value": "' . $this->_dataIntendedPurpose1->getId() . '"}
            ]}]}]', true);
        $result = $adbJsonFE->searchContacts($filter, []);
        static::assertCount(1, $result['results']);
        $decodedFilter = $result['filter'][0]['filters'][0]['value'][0]['value'];
        static::assertEquals('unittest1', $decodedFilter['name'][0]['text']);
    }

    public function testGetRecipientTokensByIntendedPurpose()
    {
        $createdContact = $this->testCreateByAdbContact(Tinebase_DateTime::now()->subDay(5));
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]))->getFirstRecord();
        $diprId = $createdDipr->intendedPurpose;

        $result = $this->_uit->getRecipientTokensByIntendedPurpose($diprId);
        $this->assertSame(1, count($result['results']));

        // expired DataIntendedPurposeRecord should not be returned
        $createdDipr->withdrawDate = Tinebase_DateTime::now()->subDay(1);
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr);
        $result = $this->_uit->getRecipientTokensByIntendedPurpose($diprId);
        $this->assertSame(0, count($result['results']));
    }
}
