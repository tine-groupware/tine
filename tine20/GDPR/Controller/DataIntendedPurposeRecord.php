<?php
/**
 * GDPR Data Intended Purpose Record Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;
use Firebase\JWT\JWT;

/**
 * GDPR Data Intended Purpose Record Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 */
class GDPR_Controller_DataIntendedPurposeRecord extends Tinebase_Controller_Record_Abstract
{
    protected static $_defaultModel = GDPR_Model_DataIntendedPurposeRecord::class;

    const ADB_CONTACT_CUSTOM_FIELD_NAME = 'GDPR_DataIntendedPurposeRecord';
    const ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME = 'GDPR_Blacklist';
    const ADB_CONTACT_EXPIRY_CUSTOM_FIELD_NAME = 'GDPR_DataExpiryDate';
    

    protected array $_templates = [];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     * @throws Tinebase_Exception_Backend_Database
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;

        $this->_applicationName = GDPR_Config::APP_NAME;
        $this->_modelName = GDPR_Model_DataIntendedPurposeRecord::class;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName' => $this->_modelName,
            'tableName' => 'gdpr_dataintendedpurposerecords',
            'modlogActive' => true
        ]);

        $this->_purgeRecords = false;
    }

    private function __clone()
    {
    }

    /**
     * @var self
     */
    private static $_instance = null;

    /**
     * @return self
     * @throws Tinebase_Exception_Backend_Database
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param GDPR_Model_DataIntendedPurposeRecord $_record
     * @return void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $this->checkAgreeWithdrawDates($_record);
    }

    /**
     * @param GDPR_Model_DataIntendedPurposeRecord $_record
     * @param GDPR_Model_DataIntendedPurposeRecord $_oldRecord
     * @return void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        // changes in agreeDate / withdrawDate need to be checked. setting a new withdrawDate where none was set before does not need to be checked
        if ($_record->agreeDate->compare($_oldRecord->agreeDate) !== 0 || ($_oldRecord->withdrawDate
                && (!$_record->withdrawDate || $_record->withdrawDate->compare($_oldRecord->withdrawDate) !== 0))) {
            $this->checkAgreeWithdrawDates($_record);
        }
    }

    protected function checkAgreeWithdrawDates(GDPR_Model_DataIntendedPurposeRecord $_record): void
    {
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);

        if ($_record->withdrawDate && $_record->withdrawDate < $_record->agreeDate) {
            throw new Tinebase_Exception_SystemGeneric($translation->_('agree date must not be after withdraw date'));
        }
        $filter = [
            [TMFA::FIELD => 'intendedPurpose', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_record->getIdFromProperty('intendedPurpose')],
            [TMFA::FIELD => 'record', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_record->getIdFromProperty('record')],
            [TMFA::FIELD => 'withdrawDate', TMFA::OPERATOR => 'after_or_equals', TMFA::VALUE => $_record->agreeDate],
        ];
        if ($_record->withdrawDate) {
            $filter[] = [TMFA::FIELD => 'agreeDate', TMFA::OPERATOR => 'before_or_equals', TMFA::VALUE => $_record->withdrawDate];
        }
        if (null !== $_record->getId()) {
            $filter[] = [TMFA::FIELD => TMCC::ID, TMFA::OPERATOR => 'not', TMFA::VALUE => $_record->getId()];
        }
        /** @phpstan-ignore-next-line */
        $results = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, $filter));
        if (count($results) > 0) {
            foreach ($results as $result) {
                if (empty($result->withdrawDate)) {
                    throw new Tinebase_Exception_SystemGeneric($translation->_('withdraw date must not be empty before create the new record'));
                }
            }
            throw new Tinebase_Exception_SystemGeneric($translation->_('agree date and withdraw date must not overlap'));
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    public static function adbContactBeforeUpdateHook(Addressbook_Model_Contact $_record, $_oldRecord)
    {
        // if the blacklist is set, don't allow updates on intended purposes
        if ($_record->{self::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME} &&
                $_oldRecord->{self::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME}) {
            $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME} = null;
        }

        // do not allow to deleted intended purposes
        if (isset($_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME})) {
            if ($_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME} instanceof Tinebase_Record_RecordSet ||
                    is_array($_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME})) {
                if (is_array($_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME})) {
                    $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME} = new Tinebase_Record_RecordSet(
                        GDPR_Model_DataIntendedPurposeRecord::class, $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME});
                }

                $ids = self::getInstance()->search(new GDPR_Model_DataIntendedPurposeRecordFilter(
                    ['record' => $_record->getId()]), null, false, true);

                if (count($diff = array_diff($ids, $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME}->getArrayOfIds())) > 0) {
                    // you can not remove an intended purpose from a contact, we just force them not to be deleted
                    $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME}->mergeById(self::getInstance()->getMultiple($diff));
                }

            } else {
                $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME} = null;
            }
        }

        // if the blacklist was activated "close" all intended purposes
        if ($_record->{self::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME} &&
                !$_oldRecord->{self::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME}) {
            $selfInstance = static::getInstance();

            // first update the dependent records... bit dirty, better do it on the adb controller...
            $selfInstance->_updateDependentRecords($_record, $_oldRecord, self::ADB_CONTACT_CUSTOM_FIELD_NAME,
                $_record::getConfiguration()->recordsFields[self::ADB_CONTACT_CUSTOM_FIELD_NAME]['config']);

            // avoid a second update later, just null it
            $_record->{self::ADB_CONTACT_CUSTOM_FIELD_NAME} = null;

            /** @var GDPR_Model_DataIntendedPurposeRecord $toUpdate */
            foreach ($selfInstance->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                        ['field' => 'record', 'operator' => 'equals', 'value' => $_record->getId()],
                        ['field' => 'withdrawDate', 'operator' => 'isnull', 'value' => true],
                    ])) as $toUpdate) {
                $toUpdate->withdrawDate = Tinebase_DateTime::now();
                $toUpdate->withdrawComment = 'Blacklist';

                $selfInstance->update($toUpdate);
            }
        }
    }

    /**
     * Delete All Contacts with an Expiry Date before now
     * 
     * @return bool
     */
    public function deleteExpiredData()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' delete expired Contact data...');

        $now = Tinebase_DateTime::now();
        $contactController = Addressbook_Controller_Contact::getInstance();
        $oldACL = $contactController->doContainerACLChecks(false);


        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, array(array(
            'field'    => GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_EXPIRY_CUSTOM_FIELD_NAME,
            'operator' => 'before',
            'value'    => $now
        )));

        $contactsToDelete =  $contactController->search($filter, null,false, true);
        $contactController->delete($contactsToDelete);

        $contactController->doContainerACLChecks($oldACL);

        return true;
    }


    /**
     * @param Felamimail_Model_Message $_message
     * @return null
     */
    public function prepareMassMailingMessage(Felamimail_Model_Message $_message, $twig)
    {
        if (!is_array($_message->to) || !isset($_message->to[0])) {
            throw new Tinebase_Exception_UnexpectedValue('bad message, no to[0] set');
        }
        // new recipient structure is array and should always have email field
        $contactId = $_message->to[0]['contact_record']['id'] ?? '';
        $twig->getEnvironment()->addGlobal('manageconstentlink', Tinebase_Core::getUrl() . '/GDPR/view/manageConsent/' . $contactId);
        return ;
    }

    public function publicApiMainScreen() {
        $locale = $this->_getLocale();
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=GDPR";
        $jsFiles[] = 'GDPR/js/ConsentClient/src/index.es6.js';
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles);
    }


    /**
     * public Api Get Manage Consent
     *
     * return all valid dataIntendedPurposes by default
     *
     * @param null $contactId
     */
    public function publicApiGetManageConsentByContactId($contactId = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $result = $this->_getDefaultGDPRData($contactId);
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($result));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    public function publicApiPostRegisterForDataIntendedPurpose($dipId = null)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            $dataIntendedPurpose = null;
            if ($dipId && !preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $dipId)) {
                try {
                    $dataIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->get($dipId);
                } catch (Exception $e) {
                }
            }

            $key = GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET};

            $token = JWT::encode([
                'email' => $request['email'],
                'issue_date' => 'the date user press',
                'dipId' => $dataIntendedPurpose ? $dipId : null,
                'n_given'   =>  $request['n_given'] ?? null,
                'n_family'   =>  $request['n_family'] ?? null,
                'org_name' => $request['org_name'] ?? null,
            ], $key, 'HS256');

            if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $request['email'])) {
                if ($contact = $this->_getGDPRContact($request)) {
                    $link = '/GDPR/view/manageConsent/' . $contact['id'];
                    $template = 'SendManageConsentLink';
                    // create dip before send the link to existing contact
                    if (!empty($dataIntendedPurpose))  {
                        $this->_createAcceptedDipr($dataIntendedPurpose->getId(), $contact);
                    }
                } else {
                    $template = 'SendRegistrationLink';
                    $link = '/GDPR/view/register/' . $token;
                    $contact = new Addressbook_Model_Contact($request);
                }
                $this->_sendMessageWithTemplate($template, [
                    'link' => Tinebase_Core::getUrl() . $link,
                    'contact' => $contact,
                    'dip'  =>  $dataIntendedPurpose ?? null
                ]);
            }

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    public function publicApiPostManageConsentByContactId($contactId = null) {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            $dipRecord = $this->_updateDipr($request, $contactId);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($this->_getDefaultGDPRData($contactId)));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetRegisterFromToken($token)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $result = $this->_decodeJWTData($token);
            $contact = $this->_getGDPRContact($result);
            $dipId = $result['dipId'] ?? null;
            $result = array_merge($result, $this->_getDefaultGDPRData($contact, $dipId));

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($result));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetRegisterForDataIntendedPurpose($dipId = null)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $result = [];
            if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $dipId)) {
                $result['email'] = $dipId;
            }

            $result = array_merge($result, $this->_getDefaultGDPRData(null, $dipId));
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($result));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    protected function _getDefaultGDPRData($contactId = null, $dipId = null, $templateContext = []): array
    {
        $result = [];
        $expander = new Tinebase_Record_Expander(GDPR_Model_DataIntendedPurpose::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                GDPR_Model_DataIntendedPurpose::FLD_NAME            => [],
                GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION     => [],
            ],
        ]);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(GDPR_Model_DataIntendedPurpose::class, [
            ['field' => GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE, 'operator' => 'equals', 'value' => false],
        ]);
        $allDataIntendedPurposes = GDPR_Controller_DataIntendedPurpose::getInstance()->search($filter);
        $expander->expand($allDataIntendedPurposes);

        // remove the dataIntendedPurposes where no dataIntendedPurposeRecord was created by the contact
        if ($dipId) {
            try {
                $dataIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->get($dipId);
                $templateContext['dip'] = $dataIntendedPurpose;
            } catch (Exception $e) {
                $result = array_merge($result, [
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $locale = $this->_getLocale();

        try {
            if ($contactId && $contact = Addressbook_Controller_Contact::getInstance()->get($contactId, null, true, false, false)) {
                $templateContext = array_merge($templateContext, ['contact' => $contact]);

                if (isset($contact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME])
                    && $contact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME] instanceof Tinebase_Record_RecordSet)
                {
                    $contactDips = $contact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME}->sort('agreeDate', 'DESC');
                    $dipIds = array_map(function ($dip) {
                        return $dip->getId();
                    }, $contactDips->intendedPurpose);

                    foreach ($allDataIntendedPurposes as $dataIntendedPurpose) {
                        if ($dataIntendedPurpose[GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_REGISTRATION]
                            && !in_array($dataIntendedPurpose['id'], $dipIds)
                        ) {
                            $allDataIntendedPurposes->removeRecord($dataIntendedPurpose);
                        }
                    }
                }

                $result = array_merge($result, [
                    'current_contact'   => $contact->toArray(),
                    'email' => $contact->email,
                    'container_id' => $contact->container_id ?? '',
                    'n_family' => $contact->n_family ?? '',
                    'n_given' => $contact->n_given ?? '',
                    'org_name' => $contact->org_name ?? '',
                ]);
            }
        } catch (Exception $e) {
            $result = array_merge($result, [
                'error'   => $e->getMessage(),
            ]);
        }

        try {
            if ($templateContext['contact']) {
                $locale = $this->_getLocale($templateContext['contact']->account_id);
            }

            $templates = $this->getViews($locale);
            $templateContext['browserLocale'] = $locale;

            foreach ($templates as $key => $template) {
                if (empty($this->_templates[$key])) {
                    foreach ($template->getBlockNames() as $block) {
                        $this->_templates[$key][$block] = $template->renderBlock($block, $templateContext);
                    }
                }
            }

            $result = array_merge($result, [
                'templates' => $this->_templates,
                'GDPR_default_lang'    => GDPR_Config::getInstance()->{GDPR_Config::LANGUAGES_AVAILABLE}->default,
                'allDataIntendedPurposes'   =>  $allDataIntendedPurposes->toArray(),
                'locale'           => [
                    'locale'   => $locale->toString(),
                    'language' => Zend_Locale::getTranslation($locale->getLanguage(), 'language', $locale),
                    'region'   => Zend_Locale::getTranslation($locale->getRegion(), 'country', $locale),
                ],
            ]);
        } catch (Exception $e) {
            $result = array_merge($result, [
                'error'   => $e->getMessage(),
            ]);
        }

        return $result;
    }

    protected function _getGDPRContact($request, $forceCreate = false)
    {
        if (empty($request['email'])) {
            return null;
        }

        $containerId = $this->_getDefaultGDPRContainerId();
        $contact = Addressbook_Controller_Contact::getInstance()->search(
            new Addressbook_Model_ContactFilter(array(
                array('field' => 'email', 'operator' => 'equals', 'value' => $request['email']),
                array('field' => 'container_id', 'operator' => 'equals', 'value' => $containerId),
            )),
            null,
            true
        )->getFirstRecord();

        if (empty($contact) && $forceCreate) {
            $contact = new Addressbook_Model_Contact([
                'email'         => $request['email'],
                'container_id'  => $containerId,
                'n_given'   =>  $request['n_given'] ?? '',
                'n_family'   =>  $request['n_family'] ?? '',
                'org_name' => $request['org_name'] ?? '',
            ]);
            $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        } else {
            if ($contact) {
                $isContactInfoChanged = false;
                foreach (['n_given', 'n_family', 'org_name'] as $key ) {
                    if (!empty($request[$key]) && $contact->$key !== $request[$key]) {
                        $isContactInfoChanged = true;
                        $contact->$key = $request[$key];
                    }
                }
                if ($isContactInfoChanged) {
                    $contact->n_fileas = '';
                    $contact = Addressbook_Controller_Contact::getInstance()->update($contact, false);
                }
            }
        }

        return $contact;
    }

    /*
   * should be able to update multiple dip status changes
   */
    public function publicApiPostRegisterFromToken($token)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            $result = $this->_decodeJWTData($token);
            $contact = $this->_getGDPRContact($request, true);
            $dipId = $result['dipId'] ?? null;

            if ($dipId && $dataIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->get($dipId)) {
                $this->_createAcceptedDipr($dataIntendedPurpose->getId(), $contact);
            }

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($this->_getDefaultGDPRData($contact->getId(), $dipId)));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    /**
     * send message with template
     *
     */
    protected function _sendMessageWithTemplate($templateFileName, $context = [])
    {
        $userId = $context['contact'] ? $context['contact']->account_id : null;
        $locale = $this->_getLocale($userId);

        $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(GDPR_Config::APP_NAME));
        $htmlTemplate = $twig->load(GDPR_Config::APP_NAME . '/views/emails/' . $templateFileName. '.html.twig');
        $textTemplate = $twig->load(GDPR_Config::APP_NAME . '/views/emails/' . $templateFileName. '.text.twig');

        $html = $htmlTemplate->render($context);
        $text = $textTemplate->render($context);
        $subject = $htmlTemplate->renderBlock('subject', $context);

        Tinebase_Notification::getInstance()->send(
            Tinebase_Core::getUser(),
            [$context['contact']],
            $subject,
            $text,
            $html
        );
    }

    protected function _decodeJWTData($token)
    {
        $decoded = false;
        try {
            $key = GDPR_Config::getInstance()->{GDPR_Config::JWT_SECRET};

            $tks = explode('.', $token);
            if (count($tks) !== 3) {
                throw new UnexpectedValueException('Wrong number of segments');
            }
            $headerRaw = JWT::urlsafeB64Decode($tks[0]);
            if (null === ($header = JWT::jsonDecode($headerRaw))) {
                throw new UnexpectedValueException('Invalid header encoding');
            }
            if (empty($header->alg)) {
                throw new UnexpectedValueException('Empty algorithm');
            }
            JWT::$leeway = 10;
            $decoded = json_decode(json_encode(JWT::decode($token, new Firebase\JWT\Key($key, $header->alg))), true);

            if (empty($decoded['email']) || empty($decoded['issue_date'])) {
                throw new UnexpectedValueException('Invalid token data');
            }
        } catch (Exception $e) {
        }
        return $decoded;
    }


    protected function _getDefaultGDPRContainerId()
    {
        try {
            $GDPRAddressbook = Tinebase_Container::getInstance()->getContainerByName(
                Addressbook_Model_Contact::class,
                'GDPR Contacts',
                Tinebase_Model_Container::TYPE_SHARED
            );
        } catch (Tinebase_Exception_NotFound $tenf) {
            // create new internal adb
            $GDPRAddressbook = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'              => 'GDPR Contacts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                'model'             => 'Addressbook_Model_Contact'
            )), null, true);
        }
        $containerId = $GDPRAddressbook->getId();
        GDPR_Config::getInstance()->set(GDPR_Config::SUBSCRIPTION_CONTAINER_ID, $GDPRAddressbook->getId());
        return $containerId;
    }

    protected function _updateDipr($diprData, $contactId, $agreeOnly = false)
    {
        if (empty($diprData['id'])) {
            //first time create
            $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
                ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                    ['field' => 'record', 'operator' => 'equals', 'value' => $contactId],
                    ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $diprData['intendedPurpose']['id']],
                ]))->getFirstRecord();
        } else {
            $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->get($diprData['id']);
        }
        if (!$dipr || !empty($dipr['withdrawDate'])) {
            $data = [
                'intendedPurpose' => $diprData['intendedPurpose'],
                'record' =>  $contactId,
                'agreeDate' => Tinebase_DateTime::now(),
                'agreeComment'  => $diprData['agreeComment'],
            ];
            $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($data));
        } else {
            // update or create new dip depends on date data
            if (!$agreeOnly) {
                $dipr['withdrawDate'] = Tinebase_DateTime::now();
                $dipr['withdrawComment'] = $diprData['withdrawComment'];
                $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($dipr);
            }
        }
        return $dipr;
    }

    protected function _createAcceptedDipr($dipId, $contact)
    {
        if ($contact->GDPR_Blacklist) {
            return null;
        }
        $contactId = $contact->getId();

        $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
            ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                ['field' => 'record', 'operator' => 'equals', 'value' => $contactId],
                ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $dipId],
            ]))->getFirstRecord();
        //first time create
        if (!$dipr || !empty($dipr['withdrawDate'])) {
            $diprData = new GDPR_Model_DataIntendedPurposeRecord([
                'record' => $contactId,
                'intendedPurpose' => $dipId,
                'agreeDate' => Tinebase_DateTime::now(),
                'agreeComment' => '',
            ]);
            $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create($diprData);
        }
        return $dipr;
    }

    protected function _getLocale($userId = null)
    {
        if ($userId && $userLocale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $userId))) {
            return $userLocale;
        }

        $defaultLocale = Tinebase_Core::getLocale();
        $array = array_keys($defaultLocale->getBrowser());
        $browserLocaleString = array_shift($array);
        return Tinebase_Translation::getLocale($browserLocaleString ?? Tinebase_Core::getLocale());
    }


    public static function getViews($locale = null)
    {
        $locale = $locale ?? new Zend_Locale();
        $templates = [];
        try {
            $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(GDPR_Config::APP_NAME));
            $tineRootPos = strlen(dirname(__DIR__, 2));
            $templatePath = dirname(__DIR__) . '/views/';
            $templateFiles = glob($templatePath . '*.twig');
            foreach ($templateFiles as $templateFile) {
                $file = basename($templateFile, '.twig');
                if (empty($templates[$file])) {
                    $template = $twig->load(substr($templateFile, $tineRootPos), $locale);
                    $templates[$file] = $template;
                }
            }
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        } finally {
            return $templates;
        }
    }
}
