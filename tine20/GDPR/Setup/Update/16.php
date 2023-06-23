<?php
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class GDPR_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
        }

        $this->addApplicationUpdate('GDPR', '16.1', self::RELEASE016_UPDATE001);
    }
}
