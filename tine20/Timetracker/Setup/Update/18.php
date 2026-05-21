<?php

/**
 * tine Groupware
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Timetracker_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE018_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE018_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
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
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002(): void
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timeaccount::class,
        ]);
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003(): void
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Timetracker_Setup_Initialize::createTasksCf();

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = $this->getDb();
        // sanitize accounting time & duration
        foreach ([
            Timetracker_Model_Timesheet::FLD_DURATION,
            Timetracker_Model_Timesheet::FLD_ACCOUNTING_TIME,
         ] as $unsignedIntField) {
            $db->update(SQL_TABLE_PREFIX . Timetracker_Model_Timesheet::TABLE_NAME,
                [$unsignedIntField => 0],
                [$db->quoteIdentifier($unsignedIntField) . ' < 0']
            );
        }

        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }

    /**
     * @return void
     *
     * Background: process_status was introduced in 2022.11 with lowercase IDs (requested, accepted, declined),
     * then switched to uppercase (REQUESTED, etc.) shortly after. Existing rows in timetracker_timesheet were never
     * migrated.
     *
     */
    public function update006(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->query(
            'UPDATE ' . SQL_TABLE_PREFIX . Timetracker_Model_Timesheet::TABLE_NAME
            . " SET process_status = UPPER(process_status) WHERE BINARY process_status in ('accepted','declined','requested')"
        );

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);
    }
}
