<?php
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class GDPR_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE016_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        // we need to make sure to run this before our normal app structure updates
        self::PRIO_TINEBASE_UPDATE => [
            self::RELEASE016_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE016_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('GDPR', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        if (!Tinebase_Core::isReplica()) {
            $appId = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId();

            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME,
                Addressbook_Model_Contact::class, true, true);
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::UI_CONFIG]
                = ['group'                         => 'GDPR',];
            Tinebase_CustomField::getInstance()->updateCustomField($cf);

            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_BLACKLIST_CUSTOM_FIELD_NAME,
                Addressbook_Model_Contact::class, true, true);
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::LABEL]
                = 'GDPR Blacklisted';
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::UI_CONFIG]
                = ['group'                         => 'GDPR',];
            Tinebase_CustomField::getInstance()->updateCustomField($cf);

            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME,
                Addressbook_Model_Contact::class, true, true);
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::UI_CONFIG]
                = ['group'                         => 'GDPR',];
            Tinebase_CustomField::getInstance()->updateCustomField($cf);

            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataProvenance::ADB_CONTACT_REASON_CUSTOM_FIELD_NAME,
                Addressbook_Model_Contact::class, true, true);
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::LABEL]
                = 'GDPR Data Editing Reason';
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::UI_CONFIG]
                = ['group'                         => 'GDPR', 'omitDuplicateResolving'        => true,];
            Tinebase_CustomField::getInstance()->updateCustomField($cf);

            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_EXPIRY_CUSTOM_FIELD_NAME,
                Addressbook_Model_Contact::class, true, true);
            $cf->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::UI_CONFIG]
                = ['group'                         => 'GDPR',];
            Tinebase_CustomField::getInstance()->updateCustomField($cf);

            $this->addApplicationUpdate('GDPR', '16.1', self::RELEASE016_UPDATE001);
        }
    }
    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            GDPR_Model_DataIntendedPurposeRecord::class,
            GDPR_Model_DataIntendedPurpose::class,
        ]);
        $this->addApplicationUpdate('GDPR', '16.2', self::RELEASE016_UPDATE002);
    }

    public function update003()
    {
        if (!Tinebase_Core::isReplica()) {
            $appId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();

            $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME, Addressbook_Model_Contact::class, true);
            $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][TMCC::CONFIG][TMCC::FILTER_OPTIONS][TMCC::DISABLED] = true;
            Tinebase_CustomField::getInstance()->updateCustomField($cfc);
        }
        $this->addApplicationUpdate(GDPR_Config::APP_NAME, '16.3', self::RELEASE016_UPDATE003);
    }

    public function update004()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            GDPR_Model_DataIntendedPurposeLocalization::class,
        ]);

        $defaultLocale = Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE};

        foreach ($this->getDb()->select()->from(SQL_TABLE_PREFIX . GDPR_Model_DataIntendedPurpose::TABLE_NAME, [
                    GDPR_Model_DataIntendedPurpose::ID,
                    GDPR_Model_DataIntendedPurpose::FLD_NAME
                ])->query()->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            GDPR_Controller_DataIntendedPurposeLocalization::getInstance()->create(
                new GDPR_Model_DataIntendedPurposeLocalization([
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TYPE => GDPR_Model_DataIntendedPurpose::FLD_NAME,
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_RECORD_ID => $row[0],
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => $row[1],
                    GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => $defaultLocale,
                ]));
        }

        Setup_SchemaTool::updateSchema([
            GDPR_Model_DataIntendedPurpose::class,
            GDPR_Model_DataIntendedPurposeRecord::class,
            GDPR_Model_DataIntendedPurposeLocalization::class,
        ]);
        $this->addApplicationUpdate('GDPR', '16.4', self::RELEASE016_UPDATE004);
    }
}
