<?php

/**
 * tine Groupware
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Addressbook_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = $this->getDb();

        $result = $db->query(
            'select id from ' . SQL_TABLE_PREFIX .
            Addressbook_Model_Contact::TABLE_NAME .
            " where adr_one_countryname != '' or adr_two_countryname != ''"
        );

        $contactIds = $result->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        foreach ($contactIds as $contact_id) {
            $countryValues = ['adr_one_countryname', 'adr_two_countryname'];
            $contact = Addressbook_Controller_Contact::getInstance()->get($contact_id);
            foreach ($countryValues as $adr_country) {
                $countryName = $contact->$adr_country;

                if (empty($countryName)) {
                    continue;
                }

                $ISOCode = Tinebase_Translation::getRegionCodeByCountryName($countryName);

                if ($ISOCode && $countryName !== $ISOCode) {
                    $db->query(
                        'UPDATE ' . SQL_TABLE_PREFIX . Addressbook_Model_Contact::TABLE_NAME .
                        ' SET ' . $adr_country . ' = ' . $db->quote($ISOCode) .
                        ' WHERE id = ' . $db->quote($contact_id)
                    );
                }
            }
        }

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '18.4',
            self::RELEASE018_UPDATE004);
    }
}
