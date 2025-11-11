<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @subpackage  Test
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

use Firebase\JWT\JWT;

/**
 * Test class for GDPR_Controller_DataIntendedPurposeRecord
 */
class GDPR_Controller_DataIntendedPurposeRecordTest extends TestCase
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
        parent::setUp();
        if (empty(GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET})) {
            GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET} = 'test';
        }
        $this->_dataIntendedPurpose1 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'new purpose 1',
                ]],
            ]));
        $this->_dataIntendedPurpose2 = GDPR_Controller_DataIntendedPurpose::getInstance()->create(
            new GDPR_Model_DataIntendedPurpose([
                'name' => [[
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'new purpose 2',
                ]],
            ]));
    }

    public function testCreateByAdbContact($agreeDate = null, $withdrawDate = null)
    {
        if (!$agreeDate) {
            $agreeDate = Tinebase_DateTime::today()->subDay(1);
        }
        $contact = new Addressbook_Model_Contact([
            'n_given' => 'unittest',
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            'email_home' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate' => $agreeDate,
                    'agreeComment' => 'well, I talked the contact into it',
                    'withdrawDate'  => $withdrawDate,
                ], true),
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose2->getId(),
                    'agreeDate' => $agreeDate,
                    'agreeComment' => 'well, I talked the contact into that too',
                    'withdrawDate'  => $withdrawDate,
                ], true)
            ]
        ], true);

        /** @var Addressbook_Model_Contact $createdContact */
        $createdContact = Addressbook_Controller_Contact::getInstance()->create($contact);
        $paging = new Tinebase_Model_Pagination(['sort' => 'agreeDate']);
        $filter = new GDPR_Model_DataIntendedPurposeRecordFilter([
            ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
        ], '', [GDPR_Model_DataIntendedPurposeRecordFilter::OPTIONS_SHOW_WITHDRAWN => true]
        );
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->search($filter, $paging);
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        // after update with dependent record property === null -> no changes
        $createdContact->n_family = 'n_family';
        $updatedContact = Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->search($filter, $paging);
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        return $updatedContact;
    }

    public function testSearch()
    {
        $createdContact = $this->testCreateByAdbContact();
        $c2 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given' => 'unittest'.uniqid(),
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                new GDPR_Model_DataIntendedPurposeRecord([
                    'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
                    'agreeDate' => Tinebase_DateTime::now(),
                    'agreeComment' => 'well, I talked the contact into it',
                ], true),
            ]
        ], true));

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $this->_dataIntendedPurpose2->getId()],
                ]],
            ]
        ));

        $this->assertSame(1, $result->count());
        $this->assertSame($createdContact->getId(), $result->getFirstRecord()->getId());

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'definedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'not', 'value' => $this->_dataIntendedPurpose1->getId()],
                ]],
            ]
        ));

        $this->assertSame(1, $result->count());
        $this->assertSame($createdContact->getId(), $result->getFirstRecord()->getId());

        $result = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, 'operator' => 'notDefinedBy', 'value' => [
                    ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $this->_dataIntendedPurpose2->getId()],
                ]],
            ]
        ));

        $this->assertGreaterThan(1, $result->count());
        $ids = $result->getArrayOfIds();
        $this->assertNotContains($createdContact->getId(), $ids);
        $this->assertContains($c2->getId(), $ids);
    }

    public function testUpdate()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));

        $createdDipr->getFirstRecord()->withdrawComment = 'foo';
        $createdDipr->getLastRecord()->withdrawComment = 'foo';
        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} = $createdDipr;
        Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNull($dipr->withdrawDate, 'expect withdrawDate to be null');
            static::assertSame('foo', $dipr->withdrawComment, 'expect withdrawComment failed');
        }
    }

    public function testDirectDelete()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));

        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} =
            new Tinebase_Record_RecordSet($createdDipr->getRecordClassName(), [$createdDipr->getFirstRecord()]);

        Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
    }

    public function testContactDelete()
    {
        $createdContact = $this->testCreateByAdbContact();

        Addressbook_Controller_Contact::getInstance()->delete($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(0, $createdDipr->count(), 'expect to find 0 data intended purpose records for this contact');
    }

    public function testBlackList()
    {
        $createdContact = $this->testCreateByAdbContact();
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter(['record' => $createdContact->getId()]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNull($dipr->withdrawDate, 'expect withdrawDate to be null');
            static::assertEmpty($dipr->withdrawComment, 'expect withdrawComment to be empty');
        }

        $createdContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME} = true;
        $updatedContact = Addressbook_Controller_Contact::getInstance()->update($createdContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
                ['field' => 'withdrawDate', 'operator' => 'after', 'value' => '1970-01-01'],
            ]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNotNull($dipr->withdrawDate, 'expect withdrawDate to be not null');
            static::assertSame('Blacklist', $dipr->withdrawComment, 'expect withdrawComment failed');
        }


        // this should not work, we set the blacklist
        $createdDipr->getFirstRecord()->withdrawComment = 'foo';
        $updatedContact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME} = $createdDipr;
        Addressbook_Controller_Contact::getInstance()->update($updatedContact);

        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
                ['field' => 'withdrawDate', 'operator' => 'after', 'value' => '1970-01-01'],
            ]));
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');
        foreach ($createdDipr as $dipr) {
            static::assertNotNull($dipr->withdrawDate, 'expect withdrawDate to be not null');
            static::assertSame('Blacklist', $dipr->withdrawComment, 'expect withdrawComment failed');
        }
    }
    
    public function testCreateMultiple($paging = null)
    {
        $createdContact = $this->testCreateByAdbContact(Tinebase_DateTime::today()->subDay(1), Tinebase_DateTime::today()->addDay(1));
        $data = [
            'record'    => $createdContact->getId(),
            'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
            // agreeDate should later than withdrawDate
            'agreeDate' => Tinebase_DateTime::today()->addDay(2),
        ];
        GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($data, true));
        $filter = new GDPR_Model_DataIntendedPurposeRecordFilter([
            ['field' => 'record', 'operator' => 'equals', 'value' => $createdContact->getId()],
            ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $this->_dataIntendedPurpose1->getId()],
        ], '', [GDPR_Model_DataIntendedPurposeRecordFilter::OPTIONS_SHOW_WITHDRAWN => true]
        );
        $createdDipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->search($filter, $paging);
        static::assertSame(2, $createdDipr->count(), 'expect to find 2 data intended purpose records for this contact');

        return $createdDipr;
    }
    
    public function testCreateMultipleAgreeDateOverlap()
    {
        $createdContact = $this->testCreateByAdbContact(Tinebase_DateTime::today()->subDay(1), Tinebase_DateTime::today()->addDay(1));
        $data = [
            'record'    => $createdContact->getId(),
            'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
            'agreeDate' => Tinebase_DateTime::today()->subDay(1),
        ];

        try {
            $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($data, true));
            $this->fail('create multiple dips should fail when agreeDate overlap');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_SystemGeneric);
        }
    }

    public function testCreateMultipleEmptyWithdrawDate()
    {
        $createdContact = $this->testCreateByAdbContact();
        $data = [
            'record'    => $createdContact->getId(),
            'intendedPurpose' => $this->_dataIntendedPurpose1->getId(),
            'agreeDate' => Tinebase_DateTime::today()->addDay(2),
        ];
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($data, true));
            $this->fail('create multiple dips should fail when existing dip still have empty withdrawDate');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_SystemGeneric);
        }
    }
    
    public function testUpdateAgreeDateOverlap()
    {
        // dip records should be sorted by agreeDate
        $paging = new Tinebase_Model_Pagination(['sort' => 'agreeDate']);
        $createdDipr = $this->testCreateMultiple($paging);
        // changes in agreeDate need to be checked.
        try {
            $this->assertTrue($createdDipr[0]['agreeDate'] < $createdDipr[1]['agreeDate']);
            $createdDipr[1]['agreeDate'] = $createdDipr[0]['agreeDate'];
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);
            $this->fail('update dip agreeDate should fail when there is an overlap in existing dip');
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Tinebase_Exception_SystemGeneric);
        }
    }

    public function testUpdateWithdrawDateOverlap()
    {
        // dip records should be sorted by withdrawDate
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);
        //setting a new withdrawDate where none was set before does not need to be checked
        $this->assertNull($createdDipr[1]['withdrawDate'], 'second dip record should have empty withdrawDate');

        $createdDipr[0]['withdrawDate'] = Tinebase_DateTime::today()->addDay(3);
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[0]);
            $this->fail('update withdrawDate to overlap open ended second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }
    
    public function testUpdateWithdrawDateOverlap1()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['withdrawDate'] = Tinebase_DateTime::today()->addDay(3);
        $dip = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);
        
        try {
            // update withdraw before agree should not work
            $today = Tinebase_DateTime::today();
            $this->assertTrue($today < $dip['agreeDate'] && $dip['agreeDate'] < $dip['withdrawDate']);
            $dip['withdrawDate'] = $today;
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($dip);
            $this->fail('update withdrawDate earlier than agreeDate should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testUpdateWithdrawDateOverlap2()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['withdrawDate'] = Tinebase_DateTime::today()->addDay(3);
        GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);

        $createdDipr[0]['withdrawDate'] = Tinebase_DateTime::today()->addDay(2);
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[0]);
            $this->fail('update withdrawDate to overlap second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testUpdateWithdrawDateOverlap3()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['withdrawDate'] = Tinebase_DateTime::today()->addDay(3);
        GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);

        $createdDipr[0]['withdrawDate'] = Tinebase_DateTime::today()->addDay(4);
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[0]);
            $this->fail('update withdrawDate to overlap second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testUpdateWithdrawDateOverlap4()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['agreeDate'] = Tinebase_DateTime::today()->subDay(4);
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);
            $this->fail('update agreeDate to overlap second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testUpdateWithdrawDateOverlap5()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['agreeDate'] = Tinebase_DateTime::today()->subDay(1);
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);
            $this->fail('update agreeDate to overlap second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testUpdateWithdrawDateOverlap6()
    {
        $paging = new Tinebase_Model_Pagination(['sort' => 'withdrawDate', 'dir' => 'DESC']);
        $createdDipr = $this->testCreateMultiple($paging);

        $createdDipr[1]['agreeDate'] = Tinebase_DateTime::today();
        try {
            GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($createdDipr[1]);
            $this->fail('update agreeDate to overlap second intended purpose should not be possible and throw exception');
        } catch (Tinebase_Exception_SystemGeneric) {}
    }

    public function testPublicApiGetManageConsentByContactId()
    {
        $contact = new Addressbook_Model_Contact([
            'n_given' => Tinebase_Record_Abstract::generateUID() . 'unittest',
            'email' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
            'email_home' => Tinebase_Record_Abstract::generateUID() . '@unittest.de',
        ], true);

        /** @var Addressbook_Model_Contact $createdContact */
        $createdContact = Addressbook_Controller_Contact::getInstance()->create($contact);
        $response = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->publicApiGetManageConsentByContactId($createdContact->getId());
        $responseData = json_decode($response->getBody(), true);
        $dipCount = $responseData['allDataIntendedPurposes'];

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(GDPR_Model_DataIntendedPurpose::class, [
            ['field' => GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE, 'operator' => 'equals', 'value' => false]
        ]);
        $allDips = GDPR_Controller_DataIntendedPurpose::getInstance()->search($filter);
        $expander = new Tinebase_Record_Expander(GDPR_Model_DataIntendedPurpose::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                GDPR_Model_DataIntendedPurpose::FLD_NAME            => [],
                GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION     => [],
            ],
        ]);
        $expander->expand($allDips);
        $dataIntendedPurpose = $allDips->getFirstRecord();
        $dataIntendedPurpose[GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE] = true;
        GDPR_Controller_DataIntendedPurpose::getInstance()->update($dataIntendedPurpose);

        $response = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->publicApiGetManageConsentByContactId($createdContact->getId());
        $responseData = json_decode($response->getBody(), true);
        $dipCountNew = $responseData['allDataIntendedPurposes'];

        static::assertEquals(count($dipCount), count($dipCountNew) + 1, 'dip with self service enabled should not be shown');
    }

    public function testPublicApiPostRegisterForDataIntendedPurpose()
    {
        $account = TestServer::getInstance()->getTestEmailAccount();
        if (! $account) {
            self::markTestSkipped('test needs mail account');
        }
        $requestData = [
            'email' => $account->email,
        ];
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        if (! $request) {
            self::markTestSkipped('test needs REQUEST');
        }
        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->publicApiPostRegisterForDataIntendedPurpose();
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(['success' => true], $responseData, print_r($responseData, true));
    }

    public function testPublicApiGetRegisterFromToken()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(GDPR_Model_DataIntendedPurpose::class, [
            ['field' => GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE, 'operator' => 'equals', 'value' => false]
        ]);
        $dip = GDPR_Controller_DataIntendedPurpose::getInstance()->search($filter)->getFirstRecord();

        $key = GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET};
        $token = JWT::encode([
            'email' => 'test@mail.test',
            'issue_date' => Tinebase_DateTime::today(),
            'dipId' =>  $dip->getId(),
        ], $key, 'HS256');

        $response = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->publicApiGetRegisterFromToken($token);
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals('test@mail.test', $responseData['email']);
    }

    public function testPublicApiPostRegisterFromToken()
    {
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
        if (! $request) {
            self::markTestSkipped('test needs REQUEST');
        }

        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if (empty($imapConfig)) {
            static::markTestSkipped('no mail configuration');
        }

        // update message cache and check result
        static::resetMailer();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(GDPR_Model_DataIntendedPurpose::class, [
            ['field' => GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE, 'operator' => 'equals', 'value' => false]
        ]);
        $dip = GDPR_Controller_DataIntendedPurpose::getInstance()->search($filter)->getFirstRecord();

        $key = GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET};
        $token = JWT::encode([
            'email' => 'test@mail.test',
            'issue_date' => Tinebase_DateTime::today(),
            'dipId' =>  $dip->getId(),
        ], $key, 'HS256');
        // user can fill the registration form
        $requestData = [
            'email' => 'test@mail.test',
            'n_family' =>  'Kneschke',
            'n_given' =>  'Lars',
            'org_name' => 'aglio e olio',
        ];

        $request->getHeaders()->addHeader(new Zend\Http\Header\Authorization('basic ' . base64_encode(':testpwd')));
        $request->setContent(json_encode($requestData));

        $response = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->publicApiPostRegisterFromToken($token);
        $responseData = json_decode($response->getBody(), true);

        $containerId = GDPR_Config::getInstance()->{GDPR_Config::SUBSCRIPTION_CONTAINER_ID};
        $contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(
            array('field' => 'email', 'operator' => 'equals', 'value' => 'test@mail.test'),
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $containerId),
        )), null, true);

        $this->assertEquals(1, count($contacts));
        $this->assertArrayHasKey('current_contact', $responseData);
    }

    public function testRandomizeJWTSecret()
    {
        // reset the secret
        GDPR_Config::getInstance()->set(GDPR_Config::JWT_SECRET, '');

        // access it
        $key = GDPR_Config::getInstance()->get(GDPR_Config::JWT_SECRET);
        self::assertNotEmpty($key, 'key should be randomized');
    }
}
