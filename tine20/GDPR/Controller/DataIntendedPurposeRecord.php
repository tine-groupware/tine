<?php
/**
 * GDPR Data Intended Purpose Record Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

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
    
    /**
     * @var array contains cached contacts during prepareMassMailingMessage
     */
    protected $_cachedContacts = [];

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
        $locale = Tinebase_Core::getLocale();
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=GDPR";
        $jsFiles[] = 'GDPR/js/ConsentClient/src/index.es6.js';
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles);
    }


    public function publicApiGetManageConsent($contactId = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        $response = new \Laminas\Diactoros\Response();
        $result = Tinebase_Core::getCoreRegistryData();

        try {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(GDPR_Model_DataIntendedPurpose::class, [
                ['field' => GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE, 'operator' => 'equals', 'value' => false]
            ]);
            $allDataIntendedPurposes = GDPR_Controller_DataIntendedPurpose::getInstance()->search($filter);

            $expander = new Tinebase_Record_Expander(GDPR_Model_DataIntendedPurpose::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    GDPR_Model_DataIntendedPurpose::FLD_NAME            => [],
                    GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION     => [],
                ],
            ]);
            $expander->expand($allDataIntendedPurposes);

            // we want to return all valid dataIntendedPurposes by default
            $result = array_merge($result, [
                'manageConsentPageExplainText'  => GDPR_Config::getInstance()->{GDPR_Config::MANAGE_CONSENT_PAGE_EXPLAIN_TEXT} ?? '',
                'GDPR_default_lang'    => GDPR_Config::getInstance()->{GDPR_Config::LANGUAGES_AVAILABLE}->default,
                'allDataIntendedPurposes'   =>  $allDataIntendedPurposes->toArray(),
            ]);

            // remove the dataIntendedPurposes where no dataIntendedPurposeRecord was created by the contact
            if ($contactId && $contact = Addressbook_Controller_Contact::getInstance()->get($contactId, null, true, false, false)) {
                if (
                    isset($contact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME])
                    && $contact[GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME] instanceof Tinebase_Record_RecordSet
                ) {
                    $contactDips = $contact->{GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME}->sort('agreeDate', 'DESC');
                    $dipIds = array_map(function ($dip) {
                        return $dip->getId();
                    }, $contactDips->intendedPurpose);
                    foreach ($allDataIntendedPurposes as $dataIntendedPurpose) {
                        if (
                            $dataIntendedPurpose->{GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_REGISTRATION}
                            && !in_array($dataIntendedPurpose->getId(), $dipIds)
                        ) {
                            $allDataIntendedPurposes->removeRecord($dataIntendedPurpose);
                        }
                    }
                }
                $user = Tinebase_Core::getUser();
                $result = array_merge($result, [
                    'current_contact'   => $contact?->toArray(),
                    'isCurrentUser'     => $user['accountId'] === $contact['account_id'],
                    'allDataIntendedPurposes'   =>  $allDataIntendedPurposes->toArray(),
                ]);
            }
        } catch (Exception $e) {
            $result = array_merge($result, [
                'error_message'  =>  $e->getMessage()
            ]);
        } finally {
            $assertAclUsage();
            $response->getBody()->write(json_encode($result));
        }

        return $response;
    }

    public function publicApiSearchManageConsent($email = null) {
        $assertAclUsage = $this->assertPublicUsage();
        //todo: if the user click the link but the link does not belongs to the user , then we should check the be and and return correct link to the use
        try {
            $response = new \Laminas\Diactoros\Response();
            $result = '';
            if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $email)) {
                $contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(
                    array('field' => 'email', 'operator' => 'equals', 'value' => $email),
                )), null, true);

                if (sizeof($contacts) === 0) {
                    $response = new \Laminas\Diactoros\Response('php://memory', 404);
                    $result = 'no system user contact relate to this email';
                }
                
                foreach ($contacts as $contact){
                    // new recipient structure is array and should always have email field
                    if ($contact && !$contact->GDPR_Blacklist) {
                        $emailTo = $contact->email;
                        // add manage consent link after search all contacts from the recipients
                        $translation = Tinebase_Translation::getTranslation('GDPR');
                        $locale = Tinebase_Translation::getLocale($translation->getLocale());
                        $twig = new Tinebase_Twig($locale, $translation);
                        $linkConfig = GDPR_Config::getInstance()->{GDPR_Config::MANAGE_CONSENT_EMAIL_TEMPLATE};
                        $template = $twig->getEnvironment()->createTemplate($linkConfig[$locale->getLanguage()]);
                        $message = new Felamimail_Model_Message([
                            'account_id'    => Tinebase_Core::getUser()->getId(),
                            'subject'       => $translation->_('Consent management for') .  $emailTo,
                            'to'            => $emailTo,
                            'body'          => $template->render([
                                'manageconstentlink' => Tinebase_Core::getUrl() . '/GDPR/view/manageConsent/' . $contact['id']
                            ]),
                        ]);
                        Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
                    }
                }
            } else {
                $response = new \Laminas\Diactoros\Response('php://memory', 404);
                $result = empty($email) ? 'email should not be empty' : 'email is not valid';
            }
            $response->getBody()->write(json_encode($result));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }

        return $response;
    }

    public function publicApiPostManageConsent($contactId) {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            if (empty($request['id'])) {
                //first time create
                $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()
                    ->search(new GDPR_Model_DataIntendedPurposeRecordFilter([
                        ['field' => 'record', 'operator' => 'equals', 'value' => $contactId],
                        ['field' => 'intendedPurpose', 'operator' => 'equals', 'value' => $request['intendedPurpose']['id']],
                    ]))->getFirstRecord();
            } else {
                $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->get($request['id']);
            }
            if (!$dipr) {
                $request['record'] = $contactId;
                $request['agreeDate'] = Tinebase_DateTime::now();
                $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($request));
            } else {
                // update or create new dip depends on date data
                if (empty($dipr['withdrawDate'])) {
                    $dipr['withdrawDate'] = Tinebase_DateTime::now();
                    $dipr['withdrawComment'] = $request['withdrawComment'];
                    $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->update($dipr);
                } else {
                    $data = [
                        'intendedPurpose' => $request['intendedPurpose'],
                        'record' =>  $request['record'],
                        'agreeDate' => Tinebase_DateTime::now(),
                        'agreeComment'  => $request['agreeComment'],
                    ];
                    $dipr = GDPR_Controller_DataIntendedPurposeRecord::getInstance()->create(new GDPR_Model_DataIntendedPurposeRecord($data));
                }
            }
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($dipr->toArray()));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($terv->getMessage()));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }
}
