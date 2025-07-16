<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

use Addressbook_Model_ContactProperties_Definition as AMCPD;
use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 *
 * @property Addressbook_Backend_Sql $_backend protected member, you don't have access to that
 */
class Addressbook_Controller_Contact extends Tinebase_Controller_Record_Abstract implements Tinebase_User_Plugin_SqlInterface
{
    const CONTEXT_ALLOW_CREATE_USER = 'context_allow_create_user';
    const CONTEXT_NO_ACCOUNT_UPDATE = 'context_no_account_update';
    const CONTEXT_NO_SYNC_PHOTO = 'context_no_sync_photo';
    const CONTEXT_NO_SYNC_CONTACT_DATA = 'context_no_sync_contact_data';

    /**
     * set geo data for contacts
     * 
     * @var boolean
     */
    protected $_setGeoDataForContacts = FALSE;

    protected $_addressFields = [];

    /**
     * configured syncBackends
     *
     * @var array|null
     */
    protected $_syncBackends = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Addressbook';
        $this->_modelName = 'Addressbook_Model_Contact';
        $this->_backend = new Addressbook_Backend_Sql();
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
        $this->_updateMultipleValidateEachRecord = TRUE;
        $this->_duplicateCheckFields = Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_DUP_FIELDS, array(
            array('n_given', 'n_family', 'org_name'),
            array('email'),
        ));
        
        // fields used for private and company address
        $this->_addressFields = array('locality', 'postalcode', 'street', 'countryname');
        
        $this->_setGeoDataForContacts = Tinebase_Config::getInstance()->get(Tinebase_Config::USE_NOMINATIM_SERVICE);
        if (! $this->_setGeoDataForContacts) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Geolocation service disabled with config option.');
        }
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_Contact
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Contact
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Contact();
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        $contact = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted, $_aclProtect);

        if ($_id) {
            $listController = Addressbook_Controller_List::getInstance();
            $groups = $listController->getMemberships($_id);
            // we remove the list members, so we can do the search on the backend, its way more efficient
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_List::class, [
                ['field' => 'id', 'operator' => 'in', 'value' => $groups]
            ]);
            $listController->checkFilterACL($filter);
            $listController->addDefaultFilter($filter);
            $contact->groups = $listController->getBackend()->search($filter);
            $contact->groups->members = null;
        }

        Tinebase_CustomField::getInstance()->resolveRecordCustomFields($contact);
        return $contact;
    }

    /**
     * gets binary contactImage
     *
     * @param int $_contactId
     * @return string
     */
    public function getImage($_contactId) {
        // ensure user has rights to see image
        $this->get($_contactId);
        
        $image = $this->_backend->getImage($_contactId);
        return $image;
    }
    
    /**
     * returns the default addressbook
     * 
     * @return Tinebase_Model_Container
     */
    public function getDefaultAddressbook()
    {
        return Tinebase_Container::getInstance()->getDefaultContainer($this->_modelName, NULL, Addressbook_Preference::DEFAULTADDRESSBOOK);
    }
    
    /**
    * you can define default filters here
    *
    * @param Tinebase_Model_Filter_FilterGroup $_filter
    */
    protected function _addDefaultFilter(?\Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        // look into subfilters
        // TODO only allow admins to see hidden/disabled users?
        if (! $_filter->isFilterSet('showDisabled', /* recursive */ true)) {
            $disabledFilter = $_filter->createFilter('showDisabled', 'equals', FALSE);
            $disabledFilter->setIsImplicit(TRUE);
            $_filter->addFilter($disabledFilter);
        }
    }

    /**
     * fetch one contact identified by $_userId
     *
     * @param string|Tinebase_Model_User $_userId
     * @param boolean $_ignoreACL don't check acl grants
     * @return Addressbook_Model_Contact
     * @throws Addressbook_Exception_AccessDenied
     * @throws Addressbook_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     * @todo this is almost always called with ignoreACL = TRUE because contacts can be hidden from addressbook.
     *       is this the way we want that?
     */
    public function getContactByUserId($_userId, $_ignoreACL = FALSE)
    {
        if (empty($_userId)) {
            throw new Tinebase_Exception_InvalidArgument('Empty user id');
        }

        $userId = $_userId instanceof Tinebase_Model_User ? $_userId->getId() : $_userId;
        $contact = $this->_backend->getByUserId($userId);
        
        if ($_ignoreACL === FALSE) {
            if (empty($contact->container_id)) {
                throw new Addressbook_Exception_NotFound('Contact is hidden from addressbook (container id is empty).');
            }
            if (! Tinebase_Core::getUser()->hasGrant($contact->container_id, Tinebase_Model_Grants::GRANT_READ)) {
                throw new Addressbook_Exception_AccessDenied('Read access to contact denied.');
            }
        }
        
        if ($this->_resolveCustomFields && $contact->has('customfields')) {
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($contact);
        }
        
        return $contact;
    }

    /**
    * can be called to activate/deactivate if geodata should be set for contacts (ignoring the config setting)
    *
    * @param  boolean $setTo (optional)
    * @return boolean
    */
    public function setGeoDataForContacts($setTo = NULL)
    {
        return $this->_setBooleanMemberVar('_setGeoDataForContacts', $setTo);
    }
    
    /**
     * gets profile portion of the requested user
     * 
     * @param string $_userId
     * @return Addressbook_Model_Contact 
     */
    public function getUserProfile($_userId)
    {
        Tinebase_UserProfile::getInstance()->checkRight($_userId);
        
        $contact = $this->getContactByUserId($_userId, TRUE);
        $userProfile = Tinebase_UserProfile::getInstance()->doProfileCleanup($contact);
        
        return $userProfile;
    }

    /**
     * update multiple records in an iteration
     * @see Tinebase_Record_Iterator / self::updateMultiple()
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_data
     *
     *    Overwrites Tinebase_Controller_Record_Abstract::processUpdateMultipleIteration: jpegphoto is set to null, so no deletion of photos on multipleUpdate happens
     *    @TODO: Can be removed when "0000284: modlog of contact images / move images to vfs" is resolved. 
     * 
     */
    public function processUpdateMultipleIteration($_records, $_data)
    {
        if (count($_records) === 0) {
            return;
        }

        foreach ($_records as $currentRecord) {
            $oldRecordArray = $currentRecord->toArray();
            $data = array_merge($oldRecordArray, $_data);

            if ($this->_newRelations || $this->_removeRelations) {
                $data['relations'] = $this->_iterateRelations($currentRecord);
            }

            try {

                $record = new $this->_modelName($data);
                $record->__set('jpegphoto', NULL);
                $updatedRecord = $this->update($record, FALSE);

                $this->_updateMultipleResult['results']->addRecord($updatedRecord);
                $this->_updateMultipleResult['totalcount'] ++;

            } catch (Tinebase_Exception_Record_Validation $e) {

                $this->_updateMultipleResult['exceptions']->addRecord(new Tinebase_Model_UpdateMultipleException(array(
                    'id'         => $currentRecord->getId(),
                    'exception'  => $e,
                        'record'     => $currentRecord,
                        'code'       => $e->getCode(),
                        'message'    => $e->getMessage()
                )));
                $this->_updateMultipleResult['failcount'] ++;
            }
        }
    }
    
    /**
     * update profile portion of given contact
     * 
     * @param  Addressbook_Model_Contact $_userProfile
     * @return Addressbook_Model_Contact
     * 
     * @todo think about adding $_ignoreACL to generic update() to simplify this
     */
    public function updateUserProfile($_userProfile)
    {
        Tinebase_UserProfile::getInstance()->checkRight($_userProfile->account_id);
        
        $doContainerACLChecks = $this->doContainerACLChecks(FALSE);
        
        $contact = $this->getContactByUserId($_userProfile->account_id, true);
        
        // we need to unset the jpegphoto because update() expects the image data and we only have a boolean value here
        unset($contact->jpegphoto);
        
        $userProfile = Tinebase_UserProfile::getInstance()->mergeProfileInfo($contact, $_userProfile);

        /** @var Addressbook_Model_Contact $contact */
        $contact = $this->update($userProfile, FALSE);
        
        $userProfile = Tinebase_UserProfile::getInstance()->doProfileCleanup($contact);

        $this->doContainerACLChecks($doContainerACLChecks);
        
        return $userProfile;
    }

    /**
     * set relations / tags / alarms
     *
     * @param   Tinebase_Record_Interface $updatedRecord the just updated record
     * @param   Tinebase_Record_Interface $record the update record
     * @param   Tinebase_Record_Interface $currentRecord the original record if one exists
     * @param   boolean $returnUpdatedRelatedData
     * @param   boolean $isCreate
     * @return  Tinebase_Record_Interface
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _setRelatedData(Tinebase_Record_Interface $updatedRecord, Tinebase_Record_Interface $record, ?\Tinebase_Record_Interface $currentRecord = null, $returnUpdatedRelatedData = false, $isCreate = false)
    {
        if (is_array($groupsDiff = $record->groups_diff) && !empty($groupsDiff)) {
            $groupsDiff = new Tinebase_Record_RecordSetDiff($groupsDiff, true);
            if (is_array($groupsDiff->added)) {
                $groupsDiff->added = new Tinebase_Record_RecordSet(Addressbook_Model_List::class, $groupsDiff->added, true);
            }
            if (is_array($groupsDiff->removed)) {
                $groupsDiff->removed = new Tinebase_Record_RecordSet(Addressbook_Model_List::class, $groupsDiff->removed, true);
            }
        } elseif (!$groupsDiff instanceof Tinebase_Record_RecordSetDiff) {
            return parent::_setRelatedData($updatedRecord, $record, $currentRecord, $returnUpdatedRelatedData, $isCreate);
        }

        if ($groupsDiff->added instanceof Tinebase_Record_RecordSet) {
            $toAdd = $groupsDiff->added->getArrayOfIds();
        } else {
            $toAdd = [];
        }

        if ($groupsDiff->removed instanceof Tinebase_Record_RecordSet) {
            $toDelete = $groupsDiff->removed->getArrayOfIds();
        } else {
            $toDelete = [];
        }

        foreach ($toAdd as $groupId) {
            Addressbook_Controller_List::getInstance()->addListMember($groupId, $updatedRecord->getId());
        }
        foreach ($toDelete as $groupId) {
            Addressbook_Controller_List::getInstance()->removeListMember($groupId, $updatedRecord->getId());
        }
        $updatedRecord->groups = Addressbook_Controller_List::getInstance()->getMemberships($updatedRecord);

        return parent::_setRelatedData($updatedRecord, $record, $currentRecord, $returnUpdatedRelatedData, $isCreate);
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Addressbook_Model_Contact $updatedRecord   the just updated record
     * @param   Addressbook_Model_Contact $record          the update record
     * @param   Addressbook_Model_Contact $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->_updateMailinglistsOnEmailChange($updatedRecord, $currentRecord);

        if (isset($record->account_id) && !isset($updatedRecord->account_id)) {
            $updatedRecord->account_id = $record->account_id;
        }

        if ($updatedRecord->type === Addressbook_Model_Contact::CONTACTTYPE_USER) {
            if (!is_array($this->_requestContext) || !isset($this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE]) ||
                !$this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE]) {
                try {
                    Tinebase_User::getInstance()->updateContact($updatedRecord);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Don\'t update contact record: ' . $tenf->getMessage());
                }
            }
        }

        // assertion
        if ($updatedRecord->syncBackendIds !== $currentRecord->syncBackendIds) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' updatedRecord and currentRecord have different syncBackendIds values, must never happen. "'
                . $updatedRecord->syncBackendIds .'", "' . $currentRecord->syncBackendIds . '"');
        }

        $oldRecordBackendIds = $currentRecord->syncBackendIds;
        if (is_string($oldRecordBackendIds)) {
            $oldRecordBackendIds = explode(',', $currentRecord->syncBackendIds);
        } else {
            $oldRecordBackendIds = array();
        }

        $updateSyncBackendIds = false;

        //get sync backends
        foreach($this->getSyncBackends() as $backendId => $backendArray) {
            if (isset($backendArray['filter'])) {
                $oldACL = $this->doContainerACLChecks(false);

                $filter = new Addressbook_Model_ContactFilter($backendArray['filter']);
                $filter->addFilter(new Addressbook_Model_ContactIdFilter(
                    array('field' => $updatedRecord->getIdProperty(), 'operator' => 'equals', 'value' => $updatedRecord->getId())
                ));

                if ($this->searchCount($filter) !== 1) {

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' record did not match filter of syncBackend "' . $backendId . '"');

                    // record is stored in that backend, so we remove it from there
                    if (in_array($backendId, $oldRecordBackendIds)) {

                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' deleting record from syncBackend "' . $backendId . '"');

                        try {
                            $backendArray['instance']->delete($updatedRecord);

                            $updatedRecord->syncBackendIds = trim(preg_replace('/(^|,)' . $backendId . '($|,)/', ',', $updatedRecord->syncBackendIds), ',');

                            $updateSyncBackendIds = true;
                        } catch (Exception $e) {
                            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not delete record from sync backend "' .
                            $backendId . '": ' . $e->getMessage());
                            Tinebase_Exception::log($e, false);
                        }
                    }

                    $this->doContainerACLChecks($oldACL);

                    continue;
                }
                $this->doContainerACLChecks($oldACL);
            }

            // if record is in this syncbackend, update it
            if (in_array($backendId, $oldRecordBackendIds)) {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' update record in syncBackend "' . $backendId . '"');

                try {
                    $backendArray['instance']->update($updatedRecord);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not update record in sync backend "' .
                        $backendId . '": ' . $e->getMessage());
                    Tinebase_Exception::log($e, false);
                }

            // else create it
            } else {

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' create record in syncBackend "' . $backendId . '"');

                try {
                    $backendArray['instance']->create($updatedRecord);

                    $updatedRecord->syncBackendIds = (empty($updatedRecord->syncBackendIds)?'':$updatedRecord->syncBackendIds . ',') . $backendId;

                    $updateSyncBackendIds = true;
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not create record in sync backend "' .
                        $backendId . '": ' . $e->getMessage());
                    Tinebase_Exception::log($e, false);
                }
            }
        }

        if (true === $updateSyncBackendIds) {
            $this->_backend->updateSyncBackendIds($updatedRecord->getId(), $updatedRecord->syncBackendIds);
        }

        if (! $this->_disabledEvents) {
            $event = new Addressbook_Event_InspectContactAfterUpdate();
            $event->updatedContact = $updatedRecord;
            $event->record = $currentRecord;
            Tinebase_Event::fireEvent($event);
        }
    }

    protected function _updateMailinglistsOnEmailChange($updatedRecord, $currentRecord)
    {
        if (! Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            return;
        }

        if (($updatedRecord->email !== $currentRecord->email || (empty($updatedRecord->email) &&
                    $updatedRecord->email_home !== $currentRecord->email_home)) &&
            count($listIds = Addressbook_Controller_List::getInstance()->getMemberships($updatedRecord)) > 0) {

            $oldListAclCheck = Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);
            $raii = new Tinebase_RAII(function() use($oldListAclCheck) {
                Addressbook_Controller_List::getInstance()->doContainerACLChecks($oldListAclCheck);
            });

            $lists = Addressbook_Controller_List::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_List::class, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $listIds],
                    ['field' => 'xprops', 'operator' => 'contains', 'value' => Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST],
                ]));
            foreach ($lists->filter(function($list) {
                return $list->xprops[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST];}) as $list) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function($list) {
                    try {
                        Felamimail_Sieve_AdbList::setScriptForList($list);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                    }
                }, [$list]);
            }

            //for unused variable check
            unset($raii);
        }
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataCreate($_createdRecord, $_record)
    {
        // assertion
        if (! empty($_createdRecord->syncBackendIds)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' $_createdRecord->syncBackendIds is not empty, must never happen. "' . $_createdRecord->syncBackendIds . '"');
        }
        if (isset($_record->account_id) && !isset($_createdRecord->account_id)) {
            $_createdRecord->account_id = $_record->account_id;
        }

        $updateSyncBackendIds = false;

        //get sync backends
        foreach ($this->getSyncBackends() as $backendId => $backendArray) {
            if (isset($backendArray['filter'])) {
                $oldACL = $this->doContainerACLChecks(false);

                $filter = new Addressbook_Model_ContactFilter($backendArray['filter']);
                $filter->addFilter(new Addressbook_Model_ContactIdFilter(
                    array('field' => $_createdRecord->getIdProperty(), 'operator' => 'equals', 'value' => $_createdRecord->getId())
                ));

                if ($this->searchCount($filter) !== 1) {

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' record did not match filter of syncBackend "' . $backendId . '"');

                    $this->doContainerACLChecks($oldACL);
                    continue;
                }
                $this->doContainerACLChecks($oldACL);
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' create record in syncBackend "' . $backendId . '"');

            try {
                $backendArray['instance']->create($_createdRecord);

                $_createdRecord->syncBackendIds = (empty($_createdRecord->syncBackendIds)?'':$_createdRecord->syncBackendIds . ',') . $backendId;

                $updateSyncBackendIds = true;
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not create record in sync backend "' .
                    $backendId . '": ' . $e->getMessage());
                Tinebase_Exception::log($e, false);
            }
        }

        if (true === $updateSyncBackendIds) {
            $this->_backend->updateSyncBackendIds($_createdRecord->getId(), $_createdRecord->syncBackendIds);
        }

        if (! $this->_disabledEvents) {
            $event = new Addressbook_Event_CreateContact();
            $event->createdContact = $_createdRecord;
            $event->record = $_record;
            Tinebase_Event::fireEvent($event);
        }
    }

    public function resetSyncBackends()
    {
        $this->_syncBackends = null;
    }

    public function getSyncBackends()
    {
        if ($this->_syncBackends !== null) {
            return $this->_syncBackends;
        }

        $this->_syncBackends = Addressbook_Config::getInstance()->get(Addressbook_Config::SYNC_BACKENDS);
        foreach($this->_syncBackends as $name => &$val) {
            if (!isset($val['class'])) {
                throw new Tinebase_Exception_UnexpectedValue('bad addressbook syncbackend configuration: "' . $name . '" missing class');
            }
            if (isset($val['options'])) {
                $val['instance'] = new $val['class']($val['options']);
            } else {
                $val['instance'] = new $val['class']();
            }
        }

        return $this->_syncBackends;
    }

    /**
     * @param $mails
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function doMailsBelongToAccount($emails) {
        $contactFilters = [];
        $queryFilters = array_map(function($email) {return ['field' => 'email_query', 'operator' => 'contains', 'value' => $email];}, $emails);
        $contactFilters[] = [
            'condition' => 'OR',
            'filters' => $queryFilters
        ];
        $contactFilters[] = [
            'condition' => 'AND',
            'filters' => [
                ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_USER],
            ]
        ];
        $contactFilter = new Addressbook_Model_ContactFilter($contactFilters);
        $contacts = Addressbook_Controller_Contact::getInstance()->search($contactFilter);
        $usermails = array_filter(array_merge($contacts->email, $contacts->email_home));
        return array_diff($emails, $usermails);
    }

    /**
     * delete one record
     * - don't delete if it belongs to an user account
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        /** @var Addressbook_Model_Contact $_record */
        if (($this->_doContainerACLChecks || $this->_doRightChecks) && !empty($_record->account_id)) {
            $translation = Tinebase_Translation::getTranslation('Addressbook');
            throw new Addressbook_Exception_AccessDenied($translation->_('It is not allowed to delete a contact linked to an user account!'));
        }

        /** @var Addressbook_Model_Contact $_record */
        if ($_record->type === 'email_account') {
            $translation = Tinebase_Translation::getTranslation('Addressbook');
            throw new Addressbook_Exception_AccessDenied($translation->_('It is not allowed to delete email account type contact!'));
        }

        if (! $this->_disabledEvents) {
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent(new Addressbook_Event_BeforeDeleteContact(array(
                'observable' => $_record
            )));
            $event = new Addressbook_Event_DeleteContact();
            $event->record = $_record;
            Tinebase_Event::fireEvent($event);
        }

        $recordBackendIds = $_record->syncBackendIds;

        parent::_deleteRecord($_record);

        // delete in syncBackendIds
        if (is_string($recordBackendIds)) {

            $recordBackends = explode(',', $recordBackendIds);
            //get sync backends
            foreach ($this->getSyncBackends() as $backendId => $backendArray) {
                if (in_array($backendId, $recordBackends)) {
                    try {
                        $backendArray['instance']->delete($_record);
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not delete record from sync backend "' .
                            $backendId . '": ' . $e->getMessage());
                        Tinebase_Exception::log($e, false);
                    }
                }
            }
        }
    }

    /**
     * inspect creation of one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_InvalidArgument
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        /** @var Addressbook_Model_Contact $_record */
        $this->_setGeoData($_record);

        $this->_checkAndSetShortName($_record);
        $this->_resolvePreferredEmailAddress($_record);
        
        if (isset($_record->type) &&  $_record->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            if (!is_array($this->_requestContext) || !isset($this->_requestContext[self::CONTEXT_ALLOW_CREATE_USER]) ||
                !$this->_requestContext[self::CONTEXT_ALLOW_CREATE_USER]) {
                throw new Addressbook_Exception_InvalidArgument('can not add contact of type user');
            }
        }

        // syncBackendIds is read only property!
        unset($_record->syncBackendIds);
    }

    /**
     * inspect update of one record
     *
     * @param   Tinebase_Record_Interface $_record the update record
     * @param   Tinebase_Record_Interface $_oldRecord the current persistent record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        /** @var Addressbook_Model_Contact $_record */
        /** @var Addressbook_Model_Contact $_oldRecord */

        if ($this->_doUpdateGeoData($_record, $_oldRecord)) {
            $this->_setGeoData($_record);
        }

        $this->_checkAndSetShortName($_record, $_oldRecord);

        if (isset($_oldRecord->type) && $_oldRecord->type == Addressbook_Model_Contact::CONTACTTYPE_USER && empty($_record->type)) {
            $_record->type = Addressbook_Model_Contact::CONTACTTYPE_USER;
        }

        if (! empty($_record->account_id) || $_record->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {

            if ($this->doContainerACLChecks()) {
                // first check if something changed that requires special rights
                $changeAccount = false;
                foreach (Addressbook_Model_Contact::getManageAccountFields() as $field) {
                    if ($_record->{$field} != $_oldRecord->{$field}) {
                        $changeAccount = true;
                        break;
                    }
                }

                // if so, check rights
                if ($changeAccount) {
                    if (!Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
                        throw new Tinebase_Exception_AccessDenied('No permission to change account properties.');
                    }
                }
            }
        }

        $this->_resolvePreferredEmailAddress($_record);

        // syncBackendIds is read only property!
        unset($_record->syncBackendIds);
    }

    protected function _resolvePreferredEmailAddress(Addressbook_Model_Contact $_record)
    {
        if (empty($_record->{$_record->preferred_email})) {
            $emailFields = array_keys(Addressbook_Model_Contact::getEmailFields());
            foreach ($emailFields as $emailField) {
                if (!empty($_record->{$emailField})) {
                    $_record->preferred_email = $emailField;
                    break;
                }
            }
        }
    }

    /**
     * Set Short Name if no Short Name is set or the Short Name Already exists
     *
     * @param Addressbook_Model_Contact $_record
     * @param Addressbook_Model_Contact|null $_oldRecord
     * @return void
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkAndSetShortName(Addressbook_Model_Contact $_record, ?Addressbook_Model_Contact $_oldRecord = null)
    {
        if (Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_SHORT_NAME)
            && $this->_duplicateCheck
        ) {
            if (!$_record->n_short) {
                $this->_setShortName($_record);
            } elseif ($_oldRecord && $_record->n_short != $_oldRecord->n_short
                && ! $this->findUnusedShortName([$_record->n_short])
            ) {
                $this->_setShortName($_record);
                $this->_throwShortNameException($_record->n_short);
            }
        }
    }

    /**
     * @param $shortname
     * @throws Tinebase_Exception_SystemGeneric
     *
     * TODO make translation work
     */
    protected function _throwShortNameException($shortname)
    {
        $translation = Tinebase_Translation::getTranslation('Addressbook');
        throw new Tinebase_Exception_SystemGeneric(str_replace(
            '{0}',
            $shortname,
            $translation->_('This Short Name already exists. How about {0}?')
        ));
    }

    /**
     * do update of geo data only if one of address field changed
     *
     * @param $record
     * @param $oldRecord
     * @return bool
     */
    protected function _doUpdateGeoData($record, $oldRecord)
    {
        if (! $this->_setGeoDataForContacts || isset($oldRecord['xprops'][Addressbook_Model_Contact::XPROP_NO_GEODATA_UPDATE])) {
            return false;
        }

        $addressDataChanged = false;
        $addrOneEmpty = true;
        $addrTwoEmpty = true;
        foreach ($this->_addressFields as $field) {
            if (
                ($record->{'adr_one_' . $field} != $oldRecord->{'adr_one_' . $field}) ||
                ($record->{'adr_two_' . $field} != $oldRecord->{'adr_two_' . $field})
            ) {
                $addressDataChanged = true;
                break;
            }
            if ($addrOneEmpty && ! empty($record->{'adr_one_' . $field})) {
                $addrOneEmpty = false;
            }
            if ($addrTwoEmpty && ! empty($record->{'adr_two_' . $field})) {
                $addrTwoEmpty = false;
            }
        }
        if ($addressDataChanged) {
            return true;
        }
        if (! $addrOneEmpty && empty($record->adr_one_lat)) {
            return true;
        }
        if (! $addrTwoEmpty && empty($record->adr_one_lat)) {
            return true;
        }

        return false;
    }

    protected function _setGeoDataForAddressRecord(string $_address, Addressbook_Model_Contact $_record, bool $_omitPostal = false): void
    {
        if (!$_record->{$_address} instanceof Addressbook_Model_ContactProperties_Address) {
            return;
        }
        if (empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LOCALITY}) &&
            ($_omitPostal || empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_POSTALCODE})) &&
            empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_STREET}) &&
            empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_COUNTRYNAME})
        ) {
            $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LON} = null;
            $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LAT} = null;
            return;
        }

        $nominatim = $this->_getNominatimService();

        if (! empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LOCALITY})) {
            $nominatim->setVillage($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LOCALITY});
        }

        if (!$_omitPostal && ! empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_POSTALCODE})) {
            $nominatim->setPostcode($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_POSTALCODE});
        }

        if (! empty($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_STREET})) {
            $nominatim->setStreet($_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_STREET});
        }

        if (! empty($countryname = $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_COUNTRYNAME})) {
            try {
                $country = Zend_Locale::getTranslation($countryname, 'Country', $countryname);
                $nominatim->setCountry($country);
            } catch (Zend_Locale_Exception $zle) {
                // country not found
            }
        }

        try {
            $places = $nominatim->search();

            if (count($places) > 0) {
                $place = $places->current();
                $this->_applyNominatimPlaceToRecord($_address, $_record, $place);

            } else {
                if (!$_omitPostal) {
                    $this->_setGeoDataForAddress($_address, $_record, true);
                    return;
                }

                $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LON} = null;
                $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LAT} = null;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());

            // the address has changed, the old values for lon/lat can not be valid anymore
            $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LON} = null;
            $_record->{$_address}->{Addressbook_Model_ContactProperties_Address::FLD_LAT} = null;
        }
    }

    /**
     * set geodata for given address of record
     * 
     * @param string                     $_address (addressbook prefix - adr_one_ or adr_two_)
     * @param Addressbook_Model_Contact $_record
     * @param array $_ommitFields do not submit these fields to nominatim
     * @return void
     */
    protected function _setGeoDataForAddress($_address, Addressbook_Model_Contact $_record, $_ommitFields = array())
    {
        if (
            empty($_record->{$_address . 'locality'}) && 
            empty($_record->{$_address . 'postalcode'}) && 
            empty($_record->{$_address . 'street'}) && 
            empty($_record->{$_address . 'countryname'})
        ) {
            $_record->{$_address . 'lon'} = NULL;
            $_record->{$_address . 'lat'} = NULL;
            
            return;
        }

        $nominatim = $this->_getNominatimService();

        if (! empty($_record->{$_address . 'locality'})) {
            $nominatim->setVillage($_record->{$_address . 'locality'});
        }
        
        if (! empty($_record->{$_address . 'postalcode'}) && ! in_array($_address . 'postalcode', $_ommitFields)) {
            $nominatim->setPostcode($_record->{$_address . 'postalcode'});
        }
        
        if (! empty($_record->{$_address . 'street'})) {
            $nominatim->setStreet($_record->{$_address . 'street'});
        }
        
        if (! empty($countryname = $_record->{$_address . 'countryname'})) {
            try {
                $country = Zend_Locale::getTranslation($countryname, 'Country', $countryname);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ($_address == 'adr_one_' ? ' Company address' : ' Private address') . ' country ' . $country);
                $nominatim->setCountry($country);

            } catch (Zend_Locale_Exception $zle) {
                // country not found
            }
        }
        
        try {
            $places = $nominatim->search();
            
            if (count($places) > 0) {
                $place = $places->current();
                $this->_applyNominatimPlaceToRecord($_address, $_record, $place);
                
            } else {
                if (! in_array($_address . 'postalcode', $_ommitFields)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                        ($_address == 'adr_one_' ? ' Company address' : ' Private address') . ' could not find place - try it again without postalcode.');
                        
                    $this->_setGeoDataForAddress($_address, $_record, array($_address . 'postalcode'));
                    return;
                }
                
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' ' . ($_address == 'adr_one_' ? 'Company address' : 'Private address') . ' Could not find place.');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                    ' ' . $_record->{$_address . 'street'} . ', ' . $_record->{$_address . 'postalcode'} . ', ' . $_record->{$_address . 'locality'} . ', ' . $_record->{$_address . 'countryname'});
                
                $_record->{$_address . 'lon'} = NULL;
                $_record->{$_address . 'lat'} = NULL;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            
            // the address has changed, the old values for lon/lat can not be valid anymore
            $_record->{$_address . 'lon'} = NULL;
            $_record->{$_address . 'lat'} = NULL;
        }
    }

    /**
     * @return Zend_Service_Nominatim
     */
    protected function _getNominatimService(): Zend_Service_Nominatim
    {
        $httpClient = Tinebase_Core::getHttpClient();
        $url = Tinebase_Config::getInstance()->{Tinebase_Config::NOMINATIM_SERVICE_URL};
        if ($url && substr($url, -1) !== '/') {
            // Nominatim service needs a trailing slash
            $url .= '/';
        }
        return new Zend_Service_Nominatim($url, $httpClient);
    }

    /**
     * _applyNominatimPlaceToRecord
     * 
     * @param string $address
     * @param Addressbook_Model_Contact $record
     * @param Zend_Service_Nominatim_Result $place
     */
    protected function _applyNominatimPlaceToRecord($address, $record, $place)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Place: ' . var_export($place, true));
        
        $record->{$address . 'lon'} = $place->lon;
        $record->{$address . 'lat'} = $place->lat;
        
        if (empty($record->{$address . 'countryname'}) && ! empty($place->country_code)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Updating record countryname from Nominatim: ' . $place->country_code);
            $record->{$address . 'countryname'} = $place->country_code;
        }
        
        if (empty($record->{$address . 'postalcode'}) && ! empty($place->postcode)) {
            $this->_applyNominatimPostcode($address, $record, $place->postcode);
        }
        
        if (empty($record->{$address . 'locality'}) && ! empty($place->city)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Updating record locality from Nominatim: ' . $place->city);
            $record->{$address . 'locality'} = $place->city;
        }

        if (empty($record->{$address . 'region'}) && ! empty($place->state)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Updating record region from Nominatim: ' . $place->state);
            $record->{$address . 'region'} = $place->state;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
            ($address == 'adr_one_' ? ' Company' : ' Private') . ' Place found: lon/lat ' . $record->{$address . 'lon'} . ' / ' . $record->{$address . 'lat'});
    }
    
    /**
     * _applyNominatimPostcode
     * 
     * @param string $address
     * @param Addressbook_Model_Contact $record
     * @param string $postcode
     */
    protected function _applyNominatimPostcode($address, $record, $postcode)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got postalcode from Nominatim: ' . $postcode);
        
        // @see 0009424: missing postalcode prevents saving of contact
        if (strpos($postcode, ',') !== false) {
            $postcodes = explode(',', $postcode);
            $postcode = $postcodes[0];
            if (preg_match('/^[0-9]+$/',$postcode)) {
                // find the similar numbers to create a postcode with placeholders ('x')
                foreach ($postcodes as $code) {
                    for ($i = 0; $i < strlen($postcode); $i++) {
                        if ($code[$i] !== $postcode[$i]) {
                            $postcode[$i] = 'x';
                        }
                    }
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Updating record postalcode from Nominatim: ' . $postcode);
        
        $record->{$address . 'postalcode'} = $postcode;
    }
    
    /**
     * set geodata of record
     * 
     * @param Addressbook_Model_Contact $_record
     * @return void
     */
    protected function _setGeoData(Addressbook_Model_Contact $_record)
    {
        if (! $this->_setGeoDataForContacts) {
            return;
        }
        
        $this->_setGeoDataForAddress('adr_one_', $_record);
        $this->_setGeoDataForAddress('adr_two_', $_record);
        foreach (Addressbook_Model_Contact::getAdditionalAddressFields() as $field) {
            $this->_setGeoDataForAddressRecord($field, $_record);
        }
    }

    /**
     * Set Short Name for a Record default 1 letter given 2 letters family
     * If already exists tries to add one letter from given then one from family
     * 
     * @param Addressbook_Model_Contact $_record
     */
    protected function _setShortName(Addressbook_Model_Contact $_record)
    {
        if (!empty($_record->n_given) && !empty($_record->n_family)) {
            $name = false;
            $i = $j = $k = 0;
            $l = 1;
       
            while (! $name) {
                $names = [];
                $i = $i+3;
                while ($j < $i) {
                    if ($j+1 <= strlen($_record->n_given) && $k+2 <= strlen($_record->n_family)) {
                        $names[] = strtoupper(substr(Tinebase_Helper::replaceSpecialChars($_record->n_given), 0, $j + 1) . substr(Tinebase_Helper::replaceSpecialChars($_record->n_family), 0, 2 + $k));
                        $j++;
                        $names[] = strtoupper(substr(Tinebase_Helper::replaceSpecialChars($_record->n_given), 0, $j + 1) . substr(Tinebase_Helper::replaceSpecialChars($_record->n_family), 0, 2 + $k));
                        $k++;
                    } else {
                        $names[] = strtoupper(substr(Tinebase_Helper::replaceSpecialChars($_record->n_given), 0, 1) . substr(Tinebase_Helper::replaceSpecialChars($_record->n_family), 0, 2) . $l);
                        $l++;
                        $j++;
                    }
                }
                $name = $this->findUnusedShortName($names);
            }

            $_record->n_short = $name;
        }
    }

    /**
     * @param $_value
     * @return bool
     */
    public function findUnusedShortName($_names) {
        $filter  = new Addressbook_Model_ContactFilter(array(
            array('field' => 'n_short', 'operator' => 'in', 'value' => $_names)
        ));
        $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
        if (count($contacts) > 0) {
            foreach ($_names as $name) {
                $found = false;
                foreach ($contacts as $contact) {
                    if ($contact->n_short == $name) {
                        $found = true;
                    }
                }
                if (!$found) {
                    return $name;
                }
            }
        } else {
            return $_names[0];
        }
        return null;
    }
    
    /**
     * get number from street (and remove it)
     * 
     * @param string $_street
     * @return string
     */
    protected function _splitNumberAndStreet(&$_street)
    {
        $pattern = '([0-9]+)';
        preg_match('/ ' . $pattern . '$/', $_street, $matches);
        
        if (empty($matches)) {
            // look at the beginning
            preg_match('/^' . $pattern . ' /', $_street, $matches);
        }
        
        if ((isset($matches[1]) || array_key_exists(1, $matches))) {
            $result = $matches[1];
            $_street = str_replace($matches[0], '', $_street);
        } else {
            $result = '';
        }
        
        return $result;
    }
    
    /**
     * get contact information from string by parsing it using predefined rules
     * 
     * @param string $_address
     * @return array with Addressbook_Model_Contact + array of unrecognized tokens
     */
    public function parseAddressData($_address)
    {
        $converter = new Addressbook_Convert_Contact_String();
        
        $result = array(
            'contact'             => $converter->toTine20Model($_address),
            'unrecognizedTokens'  => $converter->getUnrecognizedTokens(),
        );
                    
        return $result;
    }

    /**
     * inspect data used to create user
     *
     * @param Tinebase_Model_FullUser $_addedUser
     * @param Tinebase_Model_FullUser $_newUserProperties
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        // $_addedUser is the result of user sql backend create -> lacks "virtual" property container_id
        $_addedUser->container_id = $_newUserProperties->container_id;

        $contactId = $_addedUser->contact_id;
        if (!empty($contactId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . " Added user does have contact_id set: " . $_addedUser->accountLoginName . ' updating existing contact now.');

            $this->inspectUpdateUser($_addedUser, $_newUserProperties);
            return;
        }

        // create new contact
        $contact = Tinebase_User::user2Contact($_addedUser);

        $userController = Tinebase_User::getInstance();
        if ($userController instanceof Tinebase_User_Interface_SyncAble && Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKEND)->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::SYNC_USER_CONTACT_DATA} &&
            (!is_array($this->_requestContext) || !isset($this->_requestContext[self::CONTEXT_NO_SYNC_CONTACT_DATA]) || !$this->_requestContext[self::CONTEXT_NO_SYNC_CONTACT_DATA])) {
            // let the syncbackend e.g. Tinebase_User_Ldap etc. decide what to add to our $contact
            $userController->updateContactFromSyncBackend($_addedUser, $contact);
        }

        if (is_array($this->_requestContext) && isset($this->_requestContext[self::CONTEXT_NO_SYNC_PHOTO]) &&
            $this->_requestContext[self::CONTEXT_NO_SYNC_PHOTO] && isset($contact->jpegphoto)) {
            unset($contact->jpegphoto);
        }

        // we need to set context to avoid _inspectBeforeCreate to freak out about $contact->account_id
        $oldContext = $this->_requestContext;
        if (!is_array($this->_requestContext)) {
            $this->_requestContext = array();
        }
        if (!isset($this->_requestContext[self::CONTEXT_ALLOW_CREATE_USER])) {
            $this->_requestContext[self::CONTEXT_ALLOW_CREATE_USER] = true;
        }
        if (!isset($this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE])) {
            $this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE] = true;
        }
        $oldACL = $this->doContainerACLChecks(false);


        $contact = $this->create($contact, false);

        $this->_requestContext = $oldContext;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Added contact " . $contact->n_given);

        $_addedUser->contact_id = $contact->getId();
        $_addedUser->container_id = $contact->container_id;
        $userController->updateUserInSqlBackend($_addedUser);

        $this->doContainerACLChecks($oldACL);
        $this->_requestContext = $oldContext;
    }

    /**
     * inspect data used to update user
     *
     * @param Tinebase_Model_FullUser $_updatedUser
     * @param Tinebase_Model_FullUser $_newUserProperties
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $contactId = $_updatedUser->contact_id;
        if (empty($contactId)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . " updatedUser does not have contact_id set: " . $_updatedUser->accountLoginName . ' creating new contact now.');

            $this->inspectAddUser($_updatedUser, $_newUserProperties);
            return;
        }

        $oldACL = $this->doContainerACLChecks(false);

        try {
            $oldContact = $this->get($_updatedUser->contact_id);
        } catch(Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . " updatedUser does has contact_id set which was not found by get: " . $_updatedUser->accountLoginName . ' creating new contact now.');

            $_updatedUser->contact_id = null;
            $this->inspectAddUser($_updatedUser, $_newUserProperties);
            return;
        }

        // $_updatedUser is the result of user sql backend load -> lacks "virtual" property container_id
        $_updatedUser->container_id = $_newUserProperties->container_id;

        // update base information
        $contact = Tinebase_User::user2Contact($_updatedUser, clone $oldContact);

        $userController = Tinebase_User::getInstance();
        if ($userController instanceof Tinebase_User_Interface_SyncAble && Tinebase_Config::getInstance()->get(Tinebase_Config::USERBACKEND)->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::SYNC_USER_CONTACT_DATA} &&
            (!is_array($this->_requestContext) || !isset($this->_requestContext[self::CONTEXT_NO_SYNC_CONTACT_DATA]) || !$this->_requestContext[self::CONTEXT_NO_SYNC_CONTACT_DATA])) {
            // let the syncbackend e.g. Tinebase_User_Ldap etc. decide what to add to our $contact
            try {
                $userController->updateContactFromSyncBackend($_updatedUser, $contact);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' do not update contact - user is not found in sync backend: '
                    . $tenf->getMessage());
            }
        }

        if (is_array($this->_requestContext) && isset($this->_requestContext[self::CONTEXT_NO_SYNC_PHOTO]) &&
            $this->_requestContext[self::CONTEXT_NO_SYNC_PHOTO]) {
            $syncPhoto = false;
            unset($contact->jpegphoto);
        } else {
            $syncPhoto = true;

            if ($oldContact->jpegphoto == 1) {
                $adb = new Addressbook_Backend_Sql();
                $oldContact->jpegphoto = $adb->getImage($oldContact->getId());
            }
            if ($contact->jpegphoto == 1) {
                $contact->jpegphoto = false;
            }
        }

        $omitFields = ['n_fn', 'n_fileas'];
        if (! $syncPhoto) {
            $omitFields[] = 'jpegphoto';
        }
        /** @var Tinebase_Model_Diff $diff */
        $diff = $contact->diff($oldContact, $omitFields);
        if (! $diff->isEmpty() || ($oldContact->jpegphoto === 0 && !empty($contact->jpegphoto))) {
            $oldContext = $this->_requestContext;
            if (!is_array($this->_requestContext)) {
                $this->_requestContext = array();
            }
            if (!isset($this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE])) {
                $this->_requestContext[self::CONTEXT_NO_ACCOUNT_UPDATE] = true;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " Diff " . print_r($diff->toArray(), true));
            }

            $this->update($contact, false);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Updated contact " . $contact->n_fn);
            }

            $this->_requestContext = $oldContext;
        }

        $this->doContainerACLChecks($oldACL);
    }

    /**
     * delete user by id
     *
     * @param   Tinebase_Model_FullUser $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        // this will be handled in \Addressbook_Controller::_handleEvent
    }

    /**
     * update/set email user password
     *
     * @param string $_userId
     * @param string $_password
     * @param bool $_encrypt
     * @param bool $_mustChange
     * @param array $_additionalData
     * @return void
     */
    public function inspectSetPassword($_userId, string $_password, bool $_encrypt = true, bool $_mustChange = false, array &$_additionalData = [])
    {
    }

    /**
     * inspect get user by property
     *
     * @param Tinebase_Model_User $_user the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
    }

    /**
     * @param $email
     * @return NULL|Tinebase_Record_Interface
     */
    public function getContactByEmail($email)
    {
        $contacts = $this->search(new Addressbook_Model_ContactFilter(array(
            array(
                'condition' => 'OR',
                'filters' => array(
                    array('field' => 'email_query', 'operator' => 'contains', 'value' => $email)
                )
            ),
        )), new Tinebase_Model_Pagination(array(
            'sort' => 'type', // prefer user over contact
            'dir' => 'DESC',
            'limit' => 1
        )));

        return $contacts->getFirstRecord();
    }

    /**
     * get contacts be email arrays
     *
     * the result are mixed with contact and list
     *
     * @param array $emails
     * @param array $names
     * @param array $types
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function searchRecipientTokensByEmailArrays(array $emails = [], array $names = [], array $types = []): array
    {
        $tokens = [];
        $contactFilters = [];
        $listFilters = [];
        $emails = array_filter($emails);
        $names = array_filter($names);
        $types = array_filter($types, function ($type) {
            return !empty($type) && !str_contains($type, 'Member') && $type !== 'mailingList';
        });

        if (count($emails) > 0) {
            $queryFilters = array_map(function($email) {return ['field' => 'email_query', 'operator' => 'contains', 'value' => $email];}, $emails);
            $contactFilters[] = [
                'condition' => 'OR',
                'filters' => $queryFilters
            ];
            $listFilters[] = ['field' => 'email', 'operator' => 'in', 'value' => $emails];
        }

        if (count($names) > 0) {
            $contactFilters[] = [
                'condition' => 'OR',
                'filters' => [
                    ['field' => 'n_fileas', 'operator' => 'in', 'value' => $names],
                    ['field' => 'n_fn', 'operator' => 'in', 'value' => $names]
                ]
            ];
            $listFilters[] = ['field' => 'name', 'operator' => 'in', 'value' => $names];
        }

        if (count($types) > 0) {
            $contactFilters[] = [
                'condition' => 'AND',
                'filters' => [
                    ['field' => 'type', 'operator' => 'in', 'value' => $types],
                ]
            ];
        }

        if (count($contactFilters) > 0) {
            $contacts = Addressbook_Controller_Contact::getInstance()->search(
                new Addressbook_Model_ContactFilter($contactFilters),
                new Tinebase_Model_Pagination(['sort' => 'type', 'dir' => 'DESC']),// prefer user to contact
            );
            foreach ($contacts as $contact) {
                $tokens = array_merge($tokens, $contact->getRecipientTokens());
            }
        }

        if (count($listFilters) > 0) {
            $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter($listFilters));
            foreach ($lists as $list) {
                $tokens = array_merge($tokens, $list->getRecipientTokens());
            }
        }

        return $tokens;
    }

    /**
     * @param Addressbook_Model_Contact $_record
     * @param string $_action
     * @param bool $_throw
     * @param string $_errorMessage
     * @param ?Addressbook_Model_Contact $_oldRecord
     * @return bool
     */
    public function checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if (Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA === $_action && $_record->account_id &&
                is_object(Tinebase_Core::getUser()) && $_record->getIdFromProperty('account_id') === Tinebase_Core::getUser()->getId()) {
            return true;
        }
        return parent::checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    public static function modelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        try {
            Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . AMCPD::TABLE_NAME);
        } catch (Zend_Db_Statement_Exception $e) {
            return;
        }

        $propDefs = Addressbook_Controller_ContactProperties_Definition::getInstance()->getAll();
        foreach ($propDefs->filter(AMCPD::FLD_IS_SYSTEM, true) as $cpDef) {
            /** @var Addressbook_Model_ContactProperties_Interface $model */
            $model = $cpDef->{AMCPD::FLD_MODEL};
            $model::applyJsonFacadeMC($_fields, $cpDef);

            if (is_array($cpDef->{AMCPD::FLD_GRANT_MATRIX})) {
                $_fields[$cpDef->{AMCPD::FLD_NAME}][TMCC::REQUIRED_GRANTS] = $cpDef->{AMCPD::FLD_GRANT_MATRIX};
            }
        }
        $telFields = Addressbook_Model_Contact::getTelefoneFields();
        $phoneDefs = $propDefs->filter(AMCPD::FLD_MODEL, Addressbook_Model_ContactProperties_Phone::class);
        foreach ($phoneDefs as $phoneDef) {
            $telFields[$phoneDef->{AMCPD::FLD_NAME}] = $phoneDef->{AMCPD::FLD_NAME};
        }
        Addressbook_Model_Contact::setTelefoneFields($telFields);

        $filterModel = $mc->filterModel;
        $filterModel['telephone'][TMCC::OPTIONS][TMCC::FIELDS] = array_values($telFields);
        $filterModel['telephone_normalized'][TMCC::OPTIONS][TMCC::FIELDS] = [];
        foreach ($telFields as $telField) {
            $filterModel['telephone_normalized'][TMCC::OPTIONS][TMCC::FIELDS][] = $telField . '_normalized';
        }

        $emailFields = Addressbook_Model_Contact::getEmailFields();
        $emailDefs = $propDefs->filter(AMCPD::FLD_MODEL, Addressbook_Model_ContactProperties_Email::class)->sort('sorting');
        foreach ($emailDefs as $emailDef) {
            $emailFields[$emailDef->{AMCPD::FLD_NAME}] = $emailDef;
        }
        $filterModel['email_query'][TMCC::OPTIONS][TMCC::FIELDS] = array_keys($emailFields);
        foreach (array_keys($emailFields) as $emailField) {
            if (!in_array($emailField, $filterModel['name_email_query'][TMCC::OPTIONS][TMCC::FIELDS])) {
                $filterModel['name_email_query'][TMCC::OPTIONS][TMCC::FIELDS][] = $emailField;
            }
        }
        Addressbook_Model_Contact::setEmailFields($emailFields);
        $mc->setFilterModel($filterModel);

        $additionalAdrFields = Addressbook_Model_Contact::getAdditionalAddressFields();
        $adrDefs = $propDefs->filter(AMCPD::FLD_MODEL, Addressbook_Model_ContactProperties_Address::class)
            ->filter(AMCPD::FLD_IS_SYSTEM, false);
        foreach ($adrDefs as $adrDef) {
            $additionalAdrFields[$adrDef->{AMCPD::FLD_NAME}] = $adrDef->{AMCPD::FLD_NAME};
        }
        Addressbook_Model_Contact::setAdditionalAddressFields($additionalAdrFields);

        $expanderDef = $mc->jsonExpander;
        foreach (Addressbook_Model_Contact::getAdditionalAddressFields() as $val) {
            $expanderDef[Tinebase_Record_Expander::EXPANDER_PROPERTIES][$val] = [];
        }
        $mc->setJsonExpander($expanderDef);
    }

    protected function _checkDelegatedGrant(Tinebase_Record_Interface $_record,
                                            string $_action,
                                            bool $_throw,
                                            string $_errorMessage,
                                            ?Tinebase_Record_Interface $_oldRecord): bool
    {
        $tead = null;
        try {
            if (parent::_checkDelegatedGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord)) {
                return true;
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        if ($_action === self::ACTION_CREATE) {
            return parent::_checkDelegatedGrant($_record, self::ACTION_UPDATE, $_throw, $_errorMessage, $_oldRecord);
        }

        if (null !== $tead) {
            throw $tead;
        }
        return false;
    }
}
