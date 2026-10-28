<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2027.11 (ONLY!)
 */
class Tinebase_Setup_Update_20 extends Setup_Update_Abstract
{
    protected const RELEASE020_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE020_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_UPDATE          => [
            self::RELEASE020_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE020_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '20.0', self::RELEASE020_UPDATE000);
    }

    public function update001(): void
    {
        if (!$this->_backend->columnExists('model', 'tags_context')) {
            $this->_backend->addCol('tags_context', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>model</name>
                    <type>text</type>
                    <length>100</length>
                    <notnull>true</notnull>
                    <default/>
                </field>'
            ));
            // drop and add in one statement, the primary key is needed by the tag_id foreign key
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'tags_context')
                . ' DROP PRIMARY KEY, ADD PRIMARY KEY (' . $this->_db->quoteIdentifier('tag_id') . ', '
                . $this->_db->quoteIdentifier('application_id') . ', ' . $this->_db->quoteIdentifier('model') . ')');
        }
        if ($this->getTableVersion('tags_context') < 3) {
            $this->setTableVersion('tags_context', 3);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '20.1', self::RELEASE020_UPDATE001);
    }
}
