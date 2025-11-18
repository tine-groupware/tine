<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Tinebase_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT        => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        if (!$this->_backend->columnExists('request_id', 'timemachine_modlog')) {
            $this->_backend->addCol('timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>request_id</name>
                    <type>text</type>
                    <length>6</length>
                    <notnull>false</notnull>
                </field>'));
            if ($this->getTableVersion('timemachine_modlog') < 6) {
                $this->setTableVersion('timemachine_modlog', 6);
            }
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }
}
