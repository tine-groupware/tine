<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Felamimail_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Felamimail', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        if ($this->getTableVersion('felamimail_account') < 29) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>message_sent_copy_behavior</name>
                    <type>text</type>
                    <length>255</length>
                    <default>sent</default>
                </field>
            ');
            $this->_backend->addCol('felamimail_account', $declaration);
            $this->setTableVersion('felamimail_account', 29);
        }
        $db = $this->getDb();
        $db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'preferences WHERE name = "autoAttachNote"');

        $this->addApplicationUpdate('Felamimail', '16.1', self::RELEASE016_UPDATE001);
    }
}
