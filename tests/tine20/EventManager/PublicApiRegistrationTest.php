<?php
/**
 * Tine 2.0 - https://www.tine20.org
 *
 * @package     EventManagers
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Tonia Wulff <t.wulff@metaways.de>
 *
 */

use Firebase\JWT\JWT;

/**
 * Tests for the public registration endpoints
 *
 * NOTE: _processRegistration() runs in its own transaction and calls rollBack() on failure.
 * Tinebase_TransactionManager::rollBack() rolls back ALL open transactions - including the
 * unittest transaction. Tests that expect a failure inside _processRegistration therefore
 * call _commitTestTransaction() first and clean up their data in tearDown().
 */
class EventManager_PublicApiRegistrationTest extends TestCase
{
    protected const JWT_TEST_SECRET = 'phpunit-eventmanager-jwt-secret-0123456789abcdef';

    protected $_originalRequest = null;
    protected $_originalUser = null;
    protected array $_pendingConsentIds = [];

    protected ?Tinebase_Model_Container $_contactEventContainer = null;
    protected bool $_committed = false;
    protected array $_originalConfig = [];
    protected array $_cleanupEventIds = [];
    protected array $_cleanupContactIds = [];
    protected array $_cleanupEmails = [];

    protected function setUp(): void
    {
        if (!Tinebase_Application::getInstance()->isInstalled('EventManager')) {
            self::markTestSkipped('App is not installed');
        }
        parent::setUp();

        $this->_originalUser = Tinebase_Core::getUser();
        $this->_originalRequest = Tinebase_Core::isRegistered(Tinebase_Core::REQUEST)
            ? Tinebase_Core::get(Tinebase_Core::REQUEST)
            : null;

        $config = EventManager_Config::getInstance();
        if (!$config->{EventManager_Config::JWT_SECRET}) {
            $this->_setConfig(EventManager_Config::JWT_SECRET, self::JWT_TEST_SECRET);
        }
        $this->_contactEventContainer = EventManager_Setup_Initialize::getContactEventContainer();
    }

    protected function tearDown(): void
    {
        Tinebase_Core::setUser($this->_originalUser);
        Tinebase_Core::set(Tinebase_Core::REQUEST, $this->_originalRequest);

        foreach ($this->_pendingConsentIds as $pendingId) {
            Tinebase_Core::getCache()->remove('EventManagerParentConsent_' . $pendingId);
        }

        if ($this->_committed) {
            $this->_cleanupCommittedData();
        }

        parent::tearDown();
    }

    /************ publicApiPostRegistration *************/

    /**
     * mirrors the production request: guardian registers a minor (without own email) on behalf,
     * communication only via registrant
     */
    public function testPublicApiPostRegistrationOnBehalfOfMinor()
    {
        [$event, $checkbox, $textInput] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');

        [$status, $data] = $this->_postRegistration($event->getId(), [
            'token'          => $this->_getAccountToken($guardianEmail),
            'eventId'        => $event->getId(),
            'contactDetails' => [
                'n_given'            => 'Kid',
                'n_family'           => 'Phpunit',
                'bday'               => $this->_getBday(8),
                'adr_one_street'     => 'Teststr. 1',
                'adr_one_postalcode' => '12345',
                'adr_one_locality'   => 'Hamburg',
                'registration_id'    => '',
            ],
            'replies' => [
                $checkbox->getId()  => 'true',
                $textInput->getId() => '28.4.2024',
            ],
            'registrantDetails' => [
                'n_given'            => 'Parent',
                'n_middle'           => 'M.',
                'n_family'           => 'Phpunit',
                'email'              => $guardianEmail,
                'tel_cell'           => '0171 1234567',
                'adr_one_street'     => 'Teststr. 1',
                'adr_one_postalcode' => '12345',
                'adr_one_locality'   => 'Hamburg',
            ],
            'isAlreadyRegistered'     => false,
            'isOnBehalf'              => true,
            'isLegalGuardian'         => true,
            'communicationPreference' => '2',
        ]);

        self::assertSame(200, $status, print_r($data, true));
        self::assertNotEmpty($data['id'] ?? null, print_r($data, true));

        $registration = EventManager_Controller_Registration::getInstance()->get($data['id']);
        self::assertTrue((bool) $registration->{EventManager_Model_Registration::FLD_HAS_REGISTRANT});
        self::assertTrue((bool) $registration->{EventManager_Model_Registration::FLD_REGISTRANT_IS_LEGAL_GUARDIAN});
        self::assertSame('2', $this->_getId(
            $registration->{EventManager_Model_Registration::FLD_COMMUNICATION_PREFERENCE}
        ));
        self::assertSame($event->getId(), (string) $registration->getIdFromProperty(
            EventManager_Model_Registration::FLD_EVENT_ID
        ));

        $participant = $this->_getRegisterContact($data['id'], 'participant');
        $registrant = $this->_getRegisterContact($data['id'], 'registrant');
        self::assertSame('Kid', $participant->n_given);
        self::assertEmpty($participant->email);
        self::assertSame('Parent', $registrant->n_given);
        self::assertSame($guardianEmail, strtolower(trim((string) $registrant->email)));

        // the guardian (verified email) gets an addressbook contact
        $guardianContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($guardianEmail);
        self::assertNotNull($guardianContact, 'addressbook contact for registrant was not created');
        self::assertSame('Parent', $guardianContact->n_given);
        self::assertSame($this->_contactEventContainer->getId(), $this->_getId($guardianContact->container_id));
        self::assertSame($guardianContact->getId(), $registrant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID});

        // the minor without email becomes a dependant contact of the guardian
        $kidContact = $this->_assertDependantContact($guardianContact->getId(), $participant);
        self::assertSame('Kid', $kidContact->n_given);
        self::assertSame('Phpunit', $kidContact->n_family);
        self::assertEmpty($kidContact->email);
        self::assertSame($this->_contactEventContainer->getId(), $this->_getId($kidContact->container_id));

        // booked options: checkbox + text input
        self::assertCount(2, $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS});
        $textBooked = null;
        foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bo) {
            if ($this->_getId($bo->{EventManager_Model_BookedOption::FLD_OPTION}) === $textInput->getId()) {
                $textBooked = $bo;
                break;
            }
        }
        self::assertNotNull($textBooked);
        self::assertSame('28.4.2024', $textBooked->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}->response);

        // checkbox places got booked
        $this->_assertCheckboxBookedPlaces($checkbox, 1);
    }

    /**
     * same as above, but executed as the anonymous user like in production
     */
    public function testPublicApiPostRegistrationAsAnonymousUser()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'true']);

        Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));
        try {
            [$status, $data] = $this->_postRegistration($event->getId(), $body);
        } finally {
            Tinebase_Core::setUser($this->_originalUser);
        }

        self::assertSame(200, $status, print_r($data, true));
        self::assertNotEmpty($data['id'] ?? null);
        $this->_assertCheckboxBookedPlaces($checkbox, 1);
        $guardianContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($guardianEmail);
        self::assertNotNull($guardianContact);

        // dependant contact + relation are also created without any grants
        $this->_assertDependantContact(
            $guardianContact->getId(),
            $this->_getRegisterContact($data['id'], 'participant')
        );
    }

    /**
     * the same child registered by the same guardian for another event reuses the dependant contact
     * (matched by first + last name, case insensitive), a sibling gets its own contact
     */
    public function testPublicApiPostRegistrationOnBehalfReusesDependantContact()
    {
        [$event1] = $this->_createEventWithOptions();
        [$event2] = $this->_createEventWithOptions();
        [$event3] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');

        [$status, $data1] = $this->_postRegistration(
            $event1->getId(),
            $this->_getOnBehalfBody($event1->getId(), $guardianEmail, [])
        );
        self::assertSame(200, $status, print_r($data1, true));

        $body = $this->_getOnBehalfBody($event2->getId(), $guardianEmail, []);
        $body['contactDetails']['n_given'] = ' kid ';
        $body['contactDetails']['n_family'] = 'PHPUNIT';
        $body['contactDetails']['adr_one_locality'] = 'Bremen';
        [$status, $data2] = $this->_postRegistration($event2->getId(), $body);
        self::assertSame(200, $status, print_r($data2, true));

        $guardianContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($guardianEmail);
        $kid1 = $this->_assertDependantContact(
            $guardianContact->getId(),
            $this->_getRegisterContact($data1['id'], 'participant')
        );
        $kid2 = $this->_assertDependantContact(
            $guardianContact->getId(),
            $this->_getRegisterContact($data2['id'], 'participant')
        );
        self::assertSame($kid1->getId(), $kid2->getId(), 'dependant contact must be reused');
        self::assertCount(1, $this->_getDependantRelations($guardianContact->getId()));
        // the verified guardian manages the dependant: non-empty fields are taken over
        self::assertSame('Bremen', $kid2->adr_one_locality);

        // a sibling with another first name gets its own contact
        $body = $this->_getOnBehalfBody($event3->getId(), $guardianEmail, []);
        $body['contactDetails']['n_given'] = 'Sibling';
        [$status, $data3] = $this->_postRegistration($event3->getId(), $body);
        self::assertSame(200, $status, print_r($data3, true));

        $sibling = $this->_assertDependantContact(
            $guardianContact->getId(),
            $this->_getRegisterContact($data3['id'], 'participant')
        );
        self::assertNotSame($kid1->getId(), $sibling->getId());
        self::assertSame('Sibling', $sibling->n_given);
        self::assertCount(2, $this->_getDependantRelations($guardianContact->getId()));
    }

    /**
     * dependants of different guardians with the same name must not be mixed up
     */
    public function testPublicApiPostRegistrationOnBehalfDependantIsPerGuardian()
    {
        [$event] = $this->_createEventWithOptions();
        $guardianA = $this->_getUniqueEmail('guardian.a');
        $guardianB = $this->_getUniqueEmail('guardian.b');

        [$status, $dataA] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianA, [])
        );
        self::assertSame(200, $status, print_r($dataA, true));
        [$status, $dataB] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianB, [])
        );
        self::assertSame(200, $status, print_r($dataB, true));

        $cc = Addressbook_Controller_Contact::getInstance();
        $kidA = $this->_assertDependantContact(
            $cc->getContactByEmail($guardianA)->getId(),
            $this->_getRegisterContact($dataA['id'], 'participant')
        );
        $kidB = $this->_assertDependantContact(
            $cc->getContactByEmail($guardianB)->getId(),
            $this->_getRegisterContact($dataB['id'], 'participant')
        );
        self::assertNotSame($kidA->getId(), $kidB->getId());
    }

    /**
     * now that dependants have an original_id, the duplicate check also applies to them
     */
    public function testPublicApiPostRegistrationOnBehalfSameDependantTwiceFails()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, []);

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(200, $status, print_r($data, true));

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(422, $status, print_r($data, true));
        self::assertSame(
            $this->_translate('It is not possible to add the same participant multiple times'),
            $data['error']
        );

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});
    }

    /**
     * without first and last name there is nothing to match - no contact is created
     */
    public function testPublicApiPostRegistrationOnBehalfWithoutNameCreatesNoDependant()
    {
        [$event] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, []);
        $body['contactDetails']['n_given'] = '';
        $body['contactDetails']['n_family'] = '';

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        if ($status !== 200) {
            self::markTestSkipped('registration without participant name is rejected: ' . print_r($data, true));
        }

        $guardianContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($guardianEmail);
        self::assertCount(0, $this->_getDependantRelations($guardianContact->getId()));
        self::assertEmpty(
            $this->_getRegisterContact($data['id'], 'participant')->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID}
        );
    }

    public function testPublicApiPostRegistrationSelfRegistrationUsesTokenEmail()
    {
        [$event] = $this->_createEventWithOptions();
        $email = $this->_getUniqueEmail('adult');

        [$status, $data] = $this->_postRegistration($event->getId(), $this->_getSelfRegistrationBody(
            $event->getId(),
            $email,
            ['email' => 'someone.else@example.org'] // must be overwritten by token email
        ));

        self::assertSame(200, $status, print_r($data, true));

        $registration = EventManager_Controller_Registration::getInstance()->get($data['id']);
        self::assertFalse((bool) $registration->{EventManager_Model_Registration::FLD_HAS_REGISTRANT});
        self::assertSame('1', $this->_getId(
            $registration->{EventManager_Model_Registration::FLD_COMMUNICATION_PREFERENCE}
        ));

        $participant = $this->_getRegisterContact($data['id'], 'participant');
        $registrant = $this->_getRegisterContact($data['id'], 'registrant');
        self::assertSame($email, strtolower(trim((string) $participant->email)));
        self::assertSame($email, strtolower(trim((string) $registrant->email)));

        // participant and registrant are linked to the same (new) addressbook contact
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email);
        self::assertNotNull($contact);
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail('someone.else@example.org'));
        self::assertSame($contact->getId(), $participant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID});
        self::assertSame($contact->getId(), $registrant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID});
    }

    /**
     * an existing addressbook contact with the verified email is reused and its name gets updated
     */
    public function testPublicApiPostRegistrationSelfRegistrationUpdatesExistingAddressbookContact()
    {
        [$event] = $this->_createEventWithOptions();
        $email = $this->_getUniqueEmail('existing');

        $existing = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_given'  => 'Old',
            'n_family' => 'Name',
            'email'    => $email,
        ]));

        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getSelfRegistrationBody($event->getId(), $email)
        );
        self::assertSame(200, $status, print_r($data, true));

        $participant = $this->_getRegisterContact($data['id'], 'participant');
        self::assertSame($existing->getId(), $participant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID});

        $updated = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email);
        self::assertSame($existing->getId(), $updated->getId(), 'no second contact must be created');
        self::assertSame('Adult', $updated->n_given);
        self::assertSame('Phpunit', $updated->n_family);
        self::assertSame('Phpunit, Adult', $updated->n_fileas);
    }

    public function testPublicApiPostRegistrationUpdateExistingRegistration()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');

        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'true'])
        );
        self::assertSame(200, $status, print_r($data, true));
        $registrationId = $data['id'];
        $this->_assertCheckboxBookedPlaces($checkbox, 1);

        // second post with the registration id -> update, checkbox unbooked, new locality
        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, []);
        $body['contactDetails']['registration_id'] = $registrationId;
        $body['contactDetails']['adr_one_locality'] = 'Bremen';
        $body['registrantDetails']['registration_id'] = $registrationId;
        $body['isAlreadyRegistered'] = true;

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(200, $status, print_r($data, true));
        self::assertSame($registrationId, $data['id']);

        $participant = $this->_getRegisterContact($registrationId, 'participant');
        self::assertSame('Bremen', $participant->adr_one_locality);
        $this->_assertCheckboxBookedPlaces($checkbox, 0);

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});
    }

    public function testPublicApiPostRegistrationUpdateWithForeignTokenIsDenied()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');

        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [])
        );
        self::assertSame(200, $status, print_r($data, true));
        $registrationId = $data['id'];

        $attackerEmail = $this->_getUniqueEmail('attacker');
        $body = $this->_getOnBehalfBody($event->getId(), $attackerEmail, []);
        $body['contactDetails']['registration_id'] = $registrationId;
        $body['contactDetails']['n_given'] = 'Hijacked';

        [$status] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(403, $status);
        self::assertSame('Kid', $this->_getRegisterContact($registrationId, 'participant')->n_given);
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail($attackerEmail));
    }

    public function testPublicApiPostRegistrationDuplicateSelfRegistrationFails()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $email = $this->_getUniqueEmail('adult');
        $body = $this->_getSelfRegistrationBody($event->getId(), $email);

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(200, $status, print_r($data, true));

        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(422, $status, print_r($data, true));
        self::assertSame(
            $this->_translate('It is not possible to add the same participant multiple times'),
            $data['error']
        );

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});
    }

    public function testPublicApiPostRegistrationMinorOnBehalfWithoutGuardianFails()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, []);
        $body['isLegalGuardian'] = false;

        [$status, $data] = $this->_postRegistration($event->getId(), $body);

        self::assertSame(422, $status, print_r($data, true));
        self::assertSame($this->_translate('Minors must be registered by a legal guardian!'), $data['error']);
        $this->_assertNoRegistrations($event);
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail($guardianEmail));
    }

    public function testPublicApiPostRegistrationMinorSelfRegistrationWithoutConsentFails()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $email = $this->_getUniqueEmail('kid');

        [$status, $data] = $this->_postRegistration($event->getId(), [
            'token'          => $this->_getAccountToken($email),
            'contactDetails' => [
                'n_given'  => 'Kid',
                'n_family' => 'Phpunit',
                'bday'     => $this->_getBday(10),
                'email'    => $email,
            ],
            'replies'           => [],
            'registrantDetails' => [],
            'isOnBehalf'        => false,
            'isLegalGuardian'   => false,
        ]);

        self::assertSame(422, $status, print_r($data, true));
        self::assertSame(sprintf(
            $this->_translate('Parent or guardian consent is required for participants under %d.'),
            (int) EventManager_Config::getInstance()->get(EventManager_Config::GUARDIAN_REQUIRED_AGE)
        ), $data['error']);
        $this->_assertNoRegistrations($event);
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail($email));
    }

    public function testPublicApiPostRegistrationWithoutValidTokenIsDenied()
    {
        // fails in _getEmailFromToken() before the registration transaction is started
        [$event] = $this->_createEventWithOptions();

        foreach (['', 'not-a-jwt', JWT::encode(['email' => 'x@example.org'], 'wrong-secret-0123456789abcdef', 'HS256')] as $token) {
            $body = $this->_getOnBehalfBody($event->getId(), 'x@example.org', []);
            $body['token'] = $token;
            [$status] = $this->_postRegistration($event->getId(), $body);
            self::assertSame(403, $status, 'token: ' . $token);
        }
        $this->_assertNoRegistrations($event);
    }

    /************ parent consent *************/

    public function testPublicApiPostParentConsentRequest()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();

        [$status, $data] = $this->_callPublicApi(
            '/EventManager/registration/parentConsent/' . $event->getId(),
            [
                'eventId'        => $event->getId(),
                'parentEmail'    => $this->_getUniqueEmail('parent'),
                'contactDetails' => $this->_getMinorContactDetails(),
                'replies'        => [$checkbox->getId() => 'true'],
            ],
            fn() => EventManager_Controller_Registration::getInstance()
                ->publicApiPostParentConsentRequest($event->getId())
        );

        self::assertSame(200, $status, print_r($data, true));
        self::assertTrue($data['success'] ?? false);
        // nothing is registered before the guardian confirmed
        $this->_assertNoRegistrations($event);
    }

    public function testPublicApiPostParentConsentRequestRejectsInvalidInput()
    {
        [$event] = $this->_createEventWithOptions();
        $call = fn() => EventManager_Controller_Registration::getInstance()
            ->publicApiPostParentConsentRequest($event->getId());
        $uri = '/EventManager/registration/parentConsent/' . $event->getId();

        // invalid parent email
        [$status] = $this->_callPublicApi($uri, [
            'parentEmail'    => 'no-email',
            'contactDetails' => $this->_getMinorContactDetails(),
        ], $call);
        self::assertSame(400, $status);

        // adult participant -> no consent needed
        $adult = $this->_getMinorContactDetails();
        $adult['bday'] = $this->_getBday(30);
        [$status] = $this->_callPublicApi($uri, [
            'parentEmail'    => $this->_getUniqueEmail('parent'),
            'contactDetails' => $adult,
        ], $call);
        self::assertSame(400, $status);
    }

    public function testPublicApiPostParentConsentConfirm()
    {
        [$event, $checkbox, $textInput] = $this->_createEventWithOptions();
        $parentEmail = $this->_getUniqueEmail('parent');
        $replies = [$checkbox->getId() => 'true', $textInput->getId() => 'Hamburg'];
        [$token, $pendingId] = $this->_createPendingParentConsent(
            $event->getId(),
            $parentEmail,
            $this->_getMinorContactDetails(),
            $replies
        );

        // body as sent by the frontend in the guardian consent flow
        [$status, $data] = $this->_postParentConsentConfirm($token, [
            'token'          => $this->_getAccountToken($parentEmail),
            'eventId'        => $event->getId(),
            'contactDetails' => $this->_getMinorContactDetails() + ['registration_id' => ''],
            'registrantDetails' => [
                'n_given'          => 'Parent',
                'n_family'         => 'Phpunit',
                'email'            => 'manipulated@example.org', // must be replaced by the token's parent email
                'adr_one_locality' => 'Hamburg',
            ],
            'replies'                 => $replies,
            'isAlreadyRegistered'     => false,
            'isOnBehalf'              => true,
            'isLegalGuardian'         => true,
            'communicationPreference' => '2',
        ]);

        self::assertSame(200, $status, print_r($data, true));
        self::assertNotEmpty($data['id'] ?? null);

        $registration = EventManager_Controller_Registration::getInstance()->get($data['id']);
        self::assertTrue((bool) $registration->{EventManager_Model_Registration::FLD_HAS_REGISTRANT});
        self::assertTrue((bool) $registration->{EventManager_Model_Registration::FLD_REGISTRANT_IS_LEGAL_GUARDIAN});
        self::assertCount(2, $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS});

        $registrant = $this->_getRegisterContact($data['id'], 'registrant');
        self::assertSame($parentEmail, strtolower(trim((string) $registrant->email)));
        self::assertSame('Kid', $this->_getRegisterContact($data['id'], 'participant')->n_given);
        $this->_assertCheckboxBookedPlaces($checkbox, 1);

        // guardian is linked to an addressbook contact with the verified email, the manipulated one is ignored
        $parentContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($parentEmail);
        self::assertNotNull($parentContact);
        self::assertSame($parentContact->getId(), $registrant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID});
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail('manipulated@example.org'));

        // pending consent is consumed -> link can't be used twice (fails before the registration transaction)
        self::assertFalse(Tinebase_Core::getCache()->load('EventManagerParentConsent_' . $pendingId));
        [$status, $data] = $this->_postParentConsentConfirm($token, [
            'contactDetails'  => $this->_getMinorContactDetails(),
            'replies'         => [],
            'isLegalGuardian' => true,
        ]);
        self::assertSame(422, $status);
        self::assertSame($this->_translate('This confirmation link is invalid or has expired.'), $data['error']);
    }

    public function testPublicApiPostParentConsentConfirmWithoutGuardianFlagFails()
    {
        $this->_commitTestTransaction();

        [$event] = $this->_createEventWithOptions();
        $parentEmail = $this->_getUniqueEmail('parent');
        [$token, $pendingId] = $this->_createPendingParentConsent(
            $event->getId(),
            $parentEmail,
            $this->_getMinorContactDetails()
        );

        [$status, $data] = $this->_postParentConsentConfirm($token, [
            'contactDetails'    => $this->_getMinorContactDetails(),
            'registrantDetails' => ['n_given' => 'Parent', 'n_family' => 'Phpunit'],
            'replies'           => [],
            'isLegalGuardian'   => false,
        ]);

        self::assertSame(422, $status, print_r($data, true));
        self::assertSame($this->_translate('Minors must be registered by a legal guardian!'), $data['error']);
        $this->_assertNoRegistrations($event);
        self::assertNull(Addressbook_Controller_Contact::getInstance()->getContactByEmail($parentEmail));
        // consent still pending, guardian can retry
        self::assertNotFalse(Tinebase_Core::getCache()->load('EventManagerParentConsent_' . $pendingId));
    }

    public function testPublicApiPostParentConsentConfirmWithInvalidToken()
    {
        // fails in _loadPendingParentConsent() before the registration transaction is started
        [$event] = $this->_createEventWithOptions();
        $key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET};

        $tokens = [
            'garbage'         => 'not-a-jwt',
            'wrong signature' => JWT::encode([
                'eventId' => $event->getId(), 'parentEmail' => 'p@example.org',
                'pendingId' => 'x', 'exp' => time() + 60,
            ], 'wrong-secret-0123456789abcdef', 'HS256'),
            'expired'         => JWT::encode([
                'eventId' => $event->getId(), 'parentEmail' => 'p@example.org',
                'pendingId' => 'x', 'exp' => time() - 60,
            ], $key, 'HS256'),
            'unknown pending' => JWT::encode([
                'eventId' => $event->getId(), 'parentEmail' => 'p@example.org',
                'pendingId' => Tinebase_Record_Abstract::generateUID(), 'exp' => time() + 60,
            ], $key, 'HS256'),
            'account token'   => $this->_getAccountToken('p@example.org'),
        ];

        foreach ($tokens as $label => $token) {
            [$status, $data] = $this->_postParentConsentConfirm($token, [
                'contactDetails'  => $this->_getMinorContactDetails(),
                'replies'         => [],
                'isLegalGuardian' => true,
            ]);
            self::assertSame(422, $status, $label . ': ' . print_r($data, true));
        }
        $this->_assertNoRegistrations($event);
    }

    /************ publicApiPostDeregistration *************/

    public function testPublicApiPostDeregistration()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'true'])
        );
        self::assertSame(200, $status, print_r($data, true));
        $this->_assertCheckboxBookedPlaces($checkbox, 1);

        $token = $this->_getAccountToken($guardianEmail);
        [$status, $result] = $this->_postDeregistration($event->getId(), $token, $data['id']);
        self::assertSame(200, $status, print_r($result, true));
        self::assertTrue($result['success'] ?? false);

        $registration = EventManager_Controller_Registration::getInstance()->get($data['id']);
        self::assertSame('3', $this->_getId($registration->{EventManager_Model_Registration::FLD_STATUS}));
        $this->_assertCheckboxBookedPlaces($checkbox, 0);

        // deregistering an already deregistered registration changes nothing
        [$status] = $this->_postDeregistration($event->getId(), $token, $data['id']);
        self::assertSame(200, $status);
        $this->_assertCheckboxBookedPlaces($checkbox, 0);
    }

    /**
     * production case: logged out user cancels a registration with an uploaded file.
     * the anonymous user has no delete grant in the filemanager - the files must be moved anyway
     */
    public function testPublicApiPostDeregistrationAsAnonymousUserMovesFiles()
    {
        [$event] = $this->_createEventWithOptions();
        $fileOption = $this->_createFileOption($event->getId());
        $guardianEmail = $this->_getUniqueEmail('guardian');
        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [])
        );
        self::assertSame(200, $status, print_r($data, true));

        $nodeId = $this->_attachUploadedFile($data['id'], $fileOption);
        $pathBefore = $this->_getNodePath($nodeId);
        $participantDir = dirname($pathBefore);

        Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));
        try {
            [$status, $result] = $this->_postDeregistration(
                $event->getId(),
                $this->_getAccountToken($guardianEmail),
                $data['id']
            );
        } finally {
            Tinebase_Core::setUser($this->_originalUser);
        }

        self::assertSame(200, $status, print_r($result, true));
        self::assertSame(
            dirname($participantDir) . '/' . $this->_getDeregisteredName() . '/'
            . basename($participantDir) . '/' . basename($pathBefore),
            $this->_getNodePath($nodeId)
        );
    }

    public function testPublicApiPostDeregistrationIsDenied()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        [$otherEvent] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'true'])
        );
        self::assertSame(200, $status, print_r($data, true));
        $registrationId = $data['id'];
        $token = $this->_getAccountToken($guardianEmail);

        $cases = [
            'foreign token' => [$event->getId(), $this->_getAccountToken($this->_getUniqueEmail('attacker')), $registrationId, 403],
            'invalid token' => [$event->getId(), 'not-a-jwt', $registrationId, 403],
            'other event'   => [$otherEvent->getId(), $token, $registrationId, 403],
            'missing id'    => [$event->getId(), $token, null, 400],
            'unknown id'    => [$event->getId(), $token, Tinebase_Record_Abstract::generateUID(), 404],
        ];
        foreach ($cases as $label => [$eventId, $caseToken, $caseRegistrationId, $expectedStatus]) {
            [$status, $result] = $this->_postDeregistration($eventId, $caseToken, $caseRegistrationId);
            self::assertSame($expectedStatus, $status, $label . ': ' . print_r($result, true));
        }

        $registration = EventManager_Controller_Registration::getInstance()->get($registrationId);
        self::assertNotSame('3', $this->_getId($registration->{EventManager_Model_Registration::FLD_STATUS}));
        $this->_assertCheckboxBookedPlaces($checkbox, 1);
    }

    /**
     * registering again with the id of a deregistered registration reactivates it
     * (status back to the default, places booked again, no second registration)
     */
    public function testPublicApiPostRegistrationAfterDeregistrationReactivates()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        $guardianEmail = $this->_getUniqueEmail('guardian');
        $replies = [$checkbox->getId() => 'true'];
        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, $replies)
        );
        self::assertSame(200, $status, print_r($data, true));
        $registrationId = $data['id'];

        [$status] = $this->_postDeregistration($event->getId(), $this->_getAccountToken($guardianEmail), $registrationId);
        self::assertSame(200, $status);
        $this->_assertCheckboxBookedPlaces($checkbox, 0);

        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, $replies);
        $body['contactDetails']['registration_id'] = $registrationId;
        $body['registrantDetails']['registration_id'] = $registrationId;
        $body['isAlreadyRegistered'] = true;
        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(200, $status, print_r($data, true));
        self::assertSame($registrationId, $data['id']);

        $registration = EventManager_Controller_Registration::getInstance()->get($registrationId);
        $defaultStatus = EventManager_Controller_Registration::getInstance()->getDefaultRegistrationKeyFields()['status'];
        self::assertSame(
            $this->_getId($defaultStatus),
            $this->_getId($registration->{EventManager_Model_Registration::FLD_STATUS})
        );
        $this->_assertCheckboxBookedPlaces($checkbox, 1);

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});
    }

    /**
     * the form only sends checkbox/text replies - an update must not drop already uploaded files
     */
    public function testPublicApiPostRegistrationUpdateKeepsUploadedFiles()
    {
        [$event, $checkbox] = $this->_createEventWithOptions();
        $fileOption = $this->_createFileOption($event->getId());
        $guardianEmail = $this->_getUniqueEmail('guardian');
        [$status, $data] = $this->_postRegistration(
            $event->getId(),
            $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'true'])
        );
        self::assertSame(200, $status, print_r($data, true));
        $registrationId = $data['id'];

        $nodeId = $this->_attachUploadedFile($registrationId, $fileOption);
        $pathBefore = $this->_getNodePath($nodeId);

        $body = $this->_getOnBehalfBody($event->getId(), $guardianEmail, [$checkbox->getId() => 'false']);
        $body['contactDetails']['registration_id'] = $registrationId;
        $body['registrantDetails']['registration_id'] = $registrationId;
        $body['isAlreadyRegistered'] = true;
        [$status, $data] = $this->_postRegistration($event->getId(), $body);
        self::assertSame(200, $status, print_r($data, true));

        self::assertSame($nodeId, $this->_getUploadedNodeId($registrationId), 'uploaded file was dropped');
        self::assertSame($pathBefore, $this->_getNodePath($nodeId));
        $this->_assertCheckboxBookedPlaces($checkbox, 0);
    }

    /************ transaction / cleanup helpers *************/

    /**
     * commits the unittest transaction, because a failing _processRegistration() rolls back
     * everything that is open - data created afterwards is removed in tearDown()
     */
    protected function _commitTestTransaction(): void
    {
        $this->_testNeedsTransaction();
        $this->_committed = true;
    }

    protected function _setConfig(string $name, $value): void
    {
        $config = EventManager_Config::getInstance();
        if (!array_key_exists($name, $this->_originalConfig)) {
            $this->_originalConfig[$name] = $config->get($name);
        }
        $config->set($name, $value);
    }

    protected function _cleanupCommittedData(): void
    {
        $registrationController = EventManager_Controller_Registration::getInstance();
        foreach ($this->_cleanupEventIds as $eventId) {
            try {
                $registrationIds = $registrationController->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(EventManager_Model_Registration::class, [
                        ['field' => EventManager_Model_Registration::FLD_EVENT_ID, 'operator' => 'equals', 'value' => $eventId],
                    ]),
                    null,
                    false,
                    true
                );
                if (!empty($registrationIds)) {
                    $registrationController->delete($registrationIds);
                }
                EventManager_Controller_Event::getInstance()->delete([$eventId]);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        $contactIds = $this->_cleanupContactIds;
        foreach (array_unique($this->_cleanupEmails) as $email) {
            if ($contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email)) {
                $contactIds[] = $contact->getId();
                // dependants (minors without email) can't be found by email
                foreach ($this->_getDependantRelations($contact->getId()) as $relation) {
                    $contactIds[] = $relation->related_id;
                }
            }
        }
        if (!empty($contactIds)) {
            $cc = Addressbook_Controller_Contact::getInstance();
            $oldAcl = $cc->doContainerACLChecks(false);
            try {
                $cc->delete(array_unique($contactIds));
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            } finally {
                $cc->doContainerACLChecks($oldAcl);
            }
        }

        foreach ($this->_originalConfig as $name => $value) {
            EventManager_Config::getInstance()->set($name, $value);
        }
    }

    /************ request helpers *************/

    /**
     * simulates the http request the FastRoute middleware hands to the controller
     *
     * @return array [statusCode, decodedBody]
     */
    protected function _callPublicApi(string $uri, array $body, callable $call): array
    {
        $json = json_encode($body);
        $request = Tinebase_Http_Request::fromString(
            "POST $uri HTTP/1.1\r\n"
            . "Host: localhost\r\n"
            . "Content-Type: application/json\r\n"
            . "Accept: application/json\r\n"
            . 'Content-Length: ' . strlen($json) . "\r\n"
            . "\r\n"
            . $json
        );
        Tinebase_Core::set(Tinebase_Core::REQUEST, $request);

        /** @var \Laminas\Diactoros\Response $response */
        $response = $call();
        $response->getBody()->rewind();

        return [$response->getStatusCode(), json_decode($response->getBody()->getContents(), true)];
    }

    protected function _postRegistration(string $eventId, array $body): array
    {
        return $this->_callPublicApi(
            '/EventManager/register/' . $eventId,
            $body,
            fn() => EventManager_Controller_Registration::getInstance()->publicApiPostRegistration($eventId)
        );
    }

    protected function _postParentConsentConfirm(string $token, array $body): array
    {
        return $this->_callPublicApi(
            '/EventManager/registration/parentConsent/confirm/' . $token,
            $body,
            fn() => EventManager_Controller_Registration::getInstance()->publicApiPostParentConsentConfirm($token)
        );
    }

    /**
     * token as it is put into the double-opt-in link (/EventManager/view/event/{id}/registration/{token})
     */
    protected function _getAccountToken(string $email): string
    {
        return JWT::encode(
            ['email' => $email],
            EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET},
            'HS256'
        );
    }

    /**
     * creates the pending consent exactly like publicApiPostParentConsentRequest does,
     * so we get hold of the token without parsing the notification mail
     *
     * @return array [token, pendingId]
     */
    protected function _createPendingParentConsent(
        string $eventId,
        string $parentEmail,
        array $contactDetails,
        array $replies = []
    ): array {
        $pendingId = Tinebase_Record_Abstract::generateUID();
        $ttl = 600;
        $this->_pendingConsentIds[] = $pendingId;

        Tinebase_Core::getCache()->save(
            json_encode([
                'eventId'             => $eventId,
                'contactDetails'      => $contactDetails,
                'replies'             => $replies,
                'registrantDetails'   => [],
                'isAlreadyRegistered' => false,
                'isOnBehalf'          => false,
                'isLegalGuardian'     => false,
            ]),
            'EventManagerParentConsent_' . $pendingId,
            ['eventmanager', 'parentConsent'],
            $ttl
        );

        $token = JWT::encode([
            'eventId'     => $eventId,
            'parentEmail' => $parentEmail,
            'pendingId'   => $pendingId,
            'exp'         => time() + $ttl,
        ], EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}, 'HS256');

        return [$token, $pendingId];
    }

    protected function _getOnBehalfBody(string $eventId, string $registrantEmail, array $replies): array
    {
        return [
            'token'             => $this->_getAccountToken($registrantEmail),
            'eventId'           => $eventId,
            'contactDetails'    => $this->_getMinorContactDetails() + ['registration_id' => ''],
            'replies'           => $replies,
            'registrantDetails' => [
                'n_given'          => 'Parent',
                'n_family'         => 'Phpunit',
                'email'            => $registrantEmail,
                'adr_one_locality' => 'Hamburg',
            ],
            'isAlreadyRegistered'     => false,
            'isOnBehalf'              => true,
            'isLegalGuardian'         => true,
            'communicationPreference' => '2',
        ];
    }

    protected function _getSelfRegistrationBody(string $eventId, string $email, array $contactOverrides = []): array
    {
        return [
            'token'          => $this->_getAccountToken($email),
            'eventId'        => $eventId,
            'contactDetails' => array_merge([
                'n_given'          => 'Adult',
                'n_family'         => 'Phpunit',
                'bday'             => $this->_getBday(30),
                'email'            => $email,
                'adr_one_locality' => 'Hamburg',
                'registration_id'  => '',
            ], $contactOverrides),
            'replies'                 => [],
            'registrantDetails'       => [],
            'isAlreadyRegistered'     => false,
            'isOnBehalf'              => false,
            'isLegalGuardian'         => false,
            'communicationPreference' => '1',
        ];
    }

    protected function _getMinorContactDetails(): array
    {
        return [
            'n_given'            => 'Kid',
            'n_family'           => 'Phpunit',
            'bday'               => $this->_getBday(8),
            'adr_one_street'     => 'Teststr. 1',
            'adr_one_postalcode' => '20095',
            'adr_one_locality'   => 'Hamburg',
        ];
    }

    protected function _getBday(int $age): string
    {
        return (new DateTime('today'))->modify("-$age years")->modify('-1 day')->format('Y-m-d');
    }

    protected function _getUniqueEmail(string $prefix): string
    {
        $email = strtolower($prefix . '.' . Tinebase_Record_Abstract::generateUID(8) . '@phpunit.example.org');
        $this->_cleanupEmails[] = $email;
        return $email;
    }

    protected function _translate(string $msg): string
    {
        return Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME)->_($msg);
    }

    protected function _postDeregistration(string $eventId, string $token, ?string $registrationId): array
    {
        return $this->_callPublicApi(
            '/EventManager/deregistration/' . $eventId . '/' . $token . '/' . $registrationId,
            [],
            fn() => EventManager_Controller_Registration::getInstance()
                ->publicApiPostDeregistration($eventId, $token, $registrationId)
        );
    }

    /************ file helpers *************/

    protected function _createFileOption(string $eventId): EventManager_Model_Option
    {
        $tempFile = $this->_getTempFile();
        return EventManager_Controller_Option::getInstance()->create($this->_getOption(
            $eventId,
            'phpunit file option',
            new EventManager_Model_FileOption([
                'node_id'   => $tempFile->getId(),
                'file_name' => $tempFile->name,
                'file_type' => $tempFile->type,
                'file_size' => $tempFile->size,
            ]),
            EventManager_Model_FileOption::class
        ));
    }

    /**
     * adds an uploaded file to the registration like publicApiPostFileToFileManager does
     * (that endpoint needs a real http upload, is_uploaded_file() can't be faked in tests)
     *
     * @return string node id of the stored file
     */
    protected function _attachUploadedFile(string $registrationId, EventManager_Model_Option $fileOption): string
    {
        $controller = EventManager_Controller_Registration::getInstance();
        $registration = $controller->get($registrationId);
        Tinebase_Record_Expander::expandRecord($registration);

        $tempFile = $this->_getTempFile();
        $bookedOptions = $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}
            ?: new Tinebase_Record_RecordSet(EventManager_Model_BookedOption::class, []);
        $bookedOptions->addRecord(new EventManager_Model_BookedOption([
            'event_id'               => $registration->getIdFromProperty(EventManager_Model_Registration::FLD_EVENT_ID),
            'option'                 => $fileOption->getId(),
            'selection_config'       => new EventManager_Model_Selections_File([
                'node_id'   => $tempFile->getId(),
                'file_name' => $tempFile->name,
                'file_type' => $tempFile->type,
                'file_size' => $tempFile->size,
            ], true),
            'selection_config_class' => EventManager_Model_Selections_File::class,
        ], true));
        $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = $bookedOptions;
        $controller->update($registration);

        return $this->_getUploadedNodeId($registrationId);
    }

    protected function _getUploadedNodeId(string $registrationId): string
    {
        $registration = EventManager_Controller_Registration::getInstance()->get($registrationId);
        Tinebase_Record_Expander::expandRecord($registration);
        foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?: [] as $bookedOption) {
            if (
                $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                === EventManager_Model_Selections_File::class
            ) {
                $config = $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};
                $nodeId = is_array($config) ? ($config['node_id'] ?? null) : $config->node_id;
                self::assertNotEmpty($nodeId);
                return (string) $nodeId;
            }
        }
        self::fail('registration ' . $registrationId . ' has no uploaded file');
    }

    protected function _getNodePath(string $nodeId): string
    {
        return Tinebase_FileSystem::getInstance()->getPathOfNode($nodeId, true);
    }

    protected function _getDeregisteredName(): string
    {
        return $this->_translate('Deregistered');
    }

    /************ assertion helpers *************/

    protected function _getRegisterContact(string $registrationId, string $type): EventManager_Model_Register_Contact
    {
        $contact = EventManager_Controller_Register_Contact::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(EventManager_Model_Register_Contact::class, [
                ['field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_ID, 'operator' => 'equals', 'value' => $registrationId],
                ['field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE, 'operator' => 'equals', 'value' => $type],
            ])
        )->getFirstRecord();
        self::assertNotNull($contact, "no $type found for registration $registrationId");
        return $contact;
    }

    protected function _getId($value): ?string
    {
        if ($value instanceof Tinebase_Record_Interface) {
            return (string) $value->getId();
        }
        if (is_array($value)) {
            return isset($value['id']) ? (string) $value['id'] : null;
        }
        return $value === null ? null : (string) $value;
    }

    protected function _getDependantRelations(string $guardianContactId): Tinebase_Record_RecordSet
    {
        return Tinebase_Relations::getInstance()->getRelations(
            Addressbook_Model_Contact::class,
            Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            $guardianContactId,
            null,
            [EventManager_Controller_Registration::RELATION_TYPE_DEPENDANT],
            true
        );
    }

    /**
     * asserts the participant is linked to a contact that is a dependant (child) of the guardian contact
     */
    protected function _assertDependantContact(
        string $guardianContactId,
        EventManager_Model_Register_Contact $participant
    ): Addressbook_Model_Contact {
        $dependantId = $participant->{EventManager_Model_Register_Contact::FLD_ORIGINAL_ID};
        self::assertNotEmpty($dependantId, 'participant without email is not linked to a dependant contact');
        self::assertNotSame($guardianContactId, $dependantId, 'participant must not be linked to the guardian');

        $relation = $this->_getDependantRelations($guardianContactId)->filter('related_id', $dependantId)
            ->getFirstRecord();
        self::assertNotNull($relation, 'no dependant relation from guardian to participant contact');
        self::assertSame(Addressbook_Model_Contact::class, $relation->related_model);
        self::assertSame(Tinebase_Model_Relation::DEGREE_CHILD, $relation->related_degree);

        $cc = Addressbook_Controller_Contact::getInstance();
        $oldAcl = $cc->doContainerACLChecks(false);
        try {
            return $cc->get($dependantId);
        } finally {
            $cc->doContainerACLChecks($oldAcl);
        }
    }

    protected function _assertCheckboxBookedPlaces(EventManager_Model_Option $option, int $expectedBooked): void
    {
        $config = EventManager_Controller_Option::getInstance()->get($option->getId())
            ->{EventManager_Model_Option::FLD_OPTION_CONFIG};
        self::assertEquals($expectedBooked, (int) $config->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES});
        self::assertEquals(10 - $expectedBooked, (int) $config->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES});
    }

    protected function _assertNoRegistrations(EventManager_Model_Event $event): void
    {
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(0, $event->{EventManager_Model_Event::FLD_REGISTRATIONS} ?: []);
    }

    /************ fixture helpers (same as EventManager_ControllerTest) *************/

    /**
     * @return array [event, checkboxOption, textInputOption]
     */
    protected function _createEventWithOptions(): array
    {
        $this->_createSharedEventCalendar();
        $event = EventManager_Controller_Event::getInstance()->create($this->_getEvent());
        $this->_cleanupEventIds[] = $event->getId();

        $checkbox = EventManager_Controller_Option::getInstance()->create($this->_getOption(
            $event->getId(),
            'phpunit checkbox option',
            new EventManager_Model_CheckboxOption([
                'price'            => 0,
                'total_places'     => 10,
                'booked_places'    => 0,
                'available_places' => 10,
                'description'      => 'description checkbox phpunit',
            ]),
            EventManager_Model_CheckboxOption::class
        ));

        $textInput = EventManager_Controller_Option::getInstance()->create($this->_getOption(
            $event->getId(),
            'phpunit text input option',
            new EventManager_Model_TextInputOption([
                'max_characters' => 100,
                'multiple_lines' => false,
                'only_numbers'   => false,
            ], true),
            EventManager_Model_TextInputOption::class
        ));

        return [$event, $checkbox, $textInput];
    }

    protected function _getEvent(): EventManager_Model_Event
    {
        $location = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_family'           => 'test location',
            'adr_one_street'     => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality'   => 'Test City',
        ]));
        $this->_cleanupContactIds[] = $location->getId();

        $config = EventManager_Config::getInstance();
        $containerId = EventManager_Setup_Initialize::_getOrCreateSharedEventContainer(
            $config->get(EventManager_Config::EVENT_SHARED_CONTAINER_NAME),
            EventManager_Model_Event::class,
            EventManager_Config::APP_NAME
        )->getId();

        return new EventManager_Model_Event([
            'container_id'     => $containerId,
            'name'             => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT     => 'phpunit public api event '
                    . Tinebase_Record_Abstract::generateUID(8),
            ]],
            'start'            => Tinebase_DateTime::now()->addMonth(1),
            'end'              => Tinebase_DateTime::now()->addMonth(1)->addDay(2),
            'location'         => $location,
            'type'             => $config->get(EventManager_Config::EVENT_TYPE)->records->getById('1'),
            'status'           => $config->get(EventManager_Config::EVENT_STATUS)->records->getById('1'),
            'fee'              => 0,
            'total_places'     => 50,
            'booked_places'    => 0,
            'available_places' => 50,
            'double_opt_in'    => false,
            'options'          => [],
            'registrations'    => [],
            'appointments'     => [],
            'description'      => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT     => 'description phpunit public api event',
            ]],
            'is_live'          => true,
        ], true);
    }

    protected function _getOption(
        string $eventId,
        string $name,
        Tinebase_Record_Interface $optionConfig,
        string $optionConfigClass
    ): EventManager_Model_Option {
        $config = EventManager_Config::getInstance();
        return new EventManager_Model_Option([
            'event_id'            => $eventId,
            'name_option'         => $name,
            'option_config'       => $optionConfig,
            'option_config_class' => $optionConfigClass,
            'display'             => $config->get(EventManager_Config::DISPLAY_TYPE)->records->getById('1'),
            'option_required'     => $config->get(EventManager_Config::OPTION_REQUIRED_TYPE)->records->getById('2'),
            'group'               => '',
            'level'               => $config->get(EventManager_Config::OPTION_LEVEL)->records->getById('1'),
            'sorting'             => 1,
            'option_rule'         => [],
            'rule_type'           => $config->get(EventManager_Config::RULE_TYPE)->records->getById('1'),
        ], true);
    }

    protected function _createSharedEventCalendar(): void
    {
        $calendarName = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CALENDAR_NAME);
        try {
            Tinebase_Container::getInstance()->getContainerByName(
                Calendar_Model_Event::class,
                $calendarName,
                Tinebase_Model_Container::TYPE_SHARED,
            );
        } catch (Tinebase_Exception_NotFound $e) {
            Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container([
                'name'           => $calendarName,
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'       => Tinebase_Core::getUser(),
                'backend'        => 'Sql',
                'application_id' => Tinebase_Application::getInstance()
                    ->getApplicationByName(Calendar_Config::APP_NAME)->getId(),
                'model'          => Calendar_Model_Event::class,
            ]));
        }
    }
}
