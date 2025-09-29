<?php

/**
 * tine Groupware
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class EventManager_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE018_UPDATE005 = __CLASS__ . '::update005';

    protected static $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Appointment::class,
            EventManager_Model_Event::class,
            EventManager_Model_Option::class,
            EventManager_Model_Registration::class,
            EventManager_Model_Selection::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002()
    {
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        $this->_backend->dropTable('eventmanager_options_rule', EventManager_Config::APP_NAME);
        EventManager_Setup_Initialize::createEventFolder();
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Option::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }
}
