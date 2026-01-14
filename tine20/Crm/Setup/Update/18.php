<?php

/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Crm_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
        $this->addApplicationUpdate(Crm_Config::APP_NAME, '18.0',
            self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
        <field>
            <name>description</name>
            <type>text</type>
            <length>65535</length>
        </field>');
        $this->_backend->alterCol('metacrm_lead', $declaration);

        if ($this->getTableVersion('metacrm_lead') < 13) {
            $this->setTableVersion('metacrm_lead', 13);
        }

        $this->addApplicationUpdate(Crm_Config::APP_NAME, '18.1',
            self::RELEASE018_UPDATE001);
    }
}
