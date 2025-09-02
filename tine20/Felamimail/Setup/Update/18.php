<?php

/**
 * tine Groupware
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Felamimail_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    public const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
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

    public function update000(): void
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('felamimail_account') < 31) {
            $this->setTableVersion('felamimail_account', 31);
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003(): void
    {
        if (!$this->hasApplicationUpdateRan(Felamimail_Config::APP_NAME, Felamimail_Setup_Update_17::RELEASE017_UPDATE006)) {
            Tinebase_TransactionManager::getInstance()->rollBack();

            Felamimail_Controller::getInstance()->truncateEmailCache();
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'from_email_ft');
            } catch (Zend_Db_Statement_Exception) {
            }
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'from_name_ft');
            } catch (Zend_Db_Statement_Exception) {
            }
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'to_list');
            } catch (Zend_Db_Statement_Exception) {
            }
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'cc_list');
            } catch (Zend_Db_Statement_Exception) {
            }
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'bcc_list');
            } catch (Zend_Db_Statement_Exception) {
            }
            try {
                $this->_backend->dropIndex('felamimail_cache_message', 'subject');
            } catch (Zend_Db_Statement_Exception) {
            }

            if (!$this->_backend->columnExists('aggregated_data', 'felamimail_cache_message')) {
                $declaration = new Setup_Backend_Schema_Field_Xml('<field>
                    <name>aggregated_data</name>
                    <type>text</type>
                </field>');
                $this->_backend->addCol('felamimail_cache_message', $declaration, 3);
            }
        }
        
        if ($this->getTableVersion('felamimail_cache_message') < 20) {
            $this->setTableVersion('felamimail_cache_message', 20);
        }

        $this->addApplicationUpdate(Felamimail_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'felamimail_account SET display_format = "' . Tinebase_ModelConfiguration_Const::FOLLOW_PREFERENCE .
            '" WHERE display_format = "' . Felamimail_Model_Account::DISPLAY_HTML . '"');

        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>display_format</name>
                <type>text</type>
                <length>64</length>
                <default>follow_preference</default>
            </field>
        ');

        $this->_backend->alterCol('felamimail_account', $declaration);

        $this->setTableVersion('felamimail_account', 32);

        $this->addApplicationUpdate('Felamimail', '18.4', self::RELEASE018_UPDATE004);
    }
}
