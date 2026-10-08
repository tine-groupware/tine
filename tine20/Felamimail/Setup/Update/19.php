<?php

/**
 * tine Groupware
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Felamimail_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE019_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE019_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE019_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        if (!$this->_backend->columnExists('sieve_spam_move', 'felamimail_account')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                <name>sieve_spam_move</name>
                <type>boolean</type>
                <default>false</default>
            </field>');
            $this->_backend->addCol('felamimail_account', $declaration);
        }

        if ($this->getTableVersion('felamimail_account') < 33) {
            $this->setTableVersion('felamimail_account', 33);
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002(): void
    {
        if (!$this->_backend->columnExists('purge_date', 'felamimail_account')) {
            $this->_backend->addCol('felamimail_account', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>purge_date</name>
                    <type>date</type>
                    <notnull>false</notnull>
                </field>'));
            if ($this->getTableVersion('felamimail_account') < 34) {
                $this->setTableVersion('felamimail_account', 34);
            }
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }

    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if (! $this->_backend->columnExists('contact_ids', 'felamimail_sieve_vacation')) {
            $this->_backend->addCol('felamimail_sieve_vacation', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>contact_ids</name>
                    <type>text</type>
                </field>'));
        }

        if (! $this->_backend->columnExists('template_id', 'felamimail_sieve_vacation')) {
            $this->_backend->addCol('felamimail_sieve_vacation', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>template_id</name>
                    <type>text</type>
                    <length>255</length>
                </field>'));
        }

        if ($this->getTableVersion('felamimail_sieve_vacation') < 6) {
            $this->setTableVersion('felamimail_sieve_vacation', 6);
        }

        $this->addApplicationUpdate('Felamimail', '19.3', self::RELEASE019_UPDATE003);
    }
}
