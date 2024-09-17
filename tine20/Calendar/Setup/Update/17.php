<?php

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Calendar_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Calendar_Model_Event::class,
            Calendar_Model_Attender::class,
        ]);

        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Calendar_Model_EventType::class,
            Calendar_Model_EventTypes::class,
        ]);

        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }
}
