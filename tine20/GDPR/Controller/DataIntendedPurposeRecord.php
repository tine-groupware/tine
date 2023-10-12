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
        if ($_record->withdrawDate && $_record->withdrawDate < $_record->agreeDate) {
            throw new Tinebase_Exception_Record_Validation('agreeDate must not be after withdrawDate');
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
        if ($this->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, $filter)) > 0) {
            throw new Tinebase_Exception_Record_Validation('agreeDate and withdrawDate must not overlap');
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
}
