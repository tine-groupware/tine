<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Tinebase_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';


    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE => [
            self::RELEASE018_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('accounts') < 20) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>login_failures</name>
                    <type>text</type>
                    <length>4000</length>
                </field>
            ');
            $this->_backend->alterCol('accounts', $declaration);
            $this->setTableVersion('accounts', 20);
        }

        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'accounts SET login_failures = ' .
            'JSON_OBJECT("JSON-RPC", CAST(login_failures AS INTEGER)) WHERE login_failures IS NOT NULL');
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }
}