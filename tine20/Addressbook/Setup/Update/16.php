<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Addressbook_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            // order matters!
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            // order matters!
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
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ]
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Addressbook', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
            Addressbook_Model_ContactProperties_Address::class,
            Addressbook_Model_ContactProperties_Definition::class,
        ]);

        $this->addApplicationUpdate('Addressbook', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Addressbook_Model_Contact::TABLE_NAME .
            ' SET preferred_address = "adr_two" WHERE preferred_address = "1"');
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Addressbook_Model_Contact::TABLE_NAME .
            ' SET preferred_address = "adr_one" WHERE preferred_address <> "adr_two" OR preferred_address IS NULL');

        Addressbook_Setup_Initialize::createInitialContactProperties();

        $this->addApplicationUpdate('Addressbook', '16.2', self::RELEASE016_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
        ]);

        $this->addApplicationUpdate('Addressbook', '16.3', self::RELEASE016_UPDATE003);
    }
}
