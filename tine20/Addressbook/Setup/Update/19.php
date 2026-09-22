<?php

/**
 * tine Groupware
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Addressbook_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE019_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_List::class,
        ]);

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002(): void
    {
        if (!$this->_backend->columnExists('purge_date', 'addressbook_industry')) {
            $this->_backend->addCol('addressbook_industry', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>purge_date</name>
                    <type>date</type>
                    <notnull>false</notnull>
                </field>'));
            if ($this->getTableVersion('addressbook_industry') < 2) {
                $this->setTableVersion('addressbook_industry', 2);
            }
        }

        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }
}
