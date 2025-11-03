<?php

/**
 * tine Groupware
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class HumanResources_Setup_Update_18 extends Setup_Update_Abstract
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
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Employee::class,
            HumanResources_Model_AttendanceRecord::class,
        ]);
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002(): void
    {
        foreach ($this->_db->query('SELECT id, ' . HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE . ' FROM ' . SQL_TABLE_PREFIX . HumanResources_Model_WorkingTimeScheme::TABLE_NAME)->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            if ($row[1] && false !== strpos($row[1], HumanResources_Model_BLDailyWTReport_ConvertTsPtWtToTimeSlot::class) && ($data = json_decode($row[1], true))) {
                $rs = new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_Config::class, $data);
                $rs->runConvertToRecord();
                $rs->removeRecords($rs->filter(fn($rec) => HumanResources_Model_BLDailyWTReport_ConvertTsPtWtToTimeSlot::class === $rec->{HumanResources_Model_BLDailyWTReport_Config::FLDS_CLASSNAME}));
                $rs->runConvertToData(); /** @phpstan-ignore-line  */
                $this->_db->update(SQL_TABLE_PREFIX . HumanResources_Model_WorkingTimeScheme::TABLE_NAME, [HumanResources_Model_WorkingTimeScheme::FLDS_BLPIPE => json_encode($rs->toArray())], $this->_db->quoteInto('id = ?', $row[0]));
            }
        }
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003(): void
    {
        $deviceCtrl = HumanResources_Controller_AttendanceRecorderDevice::getInstance();
        try {
            $deviceCtrl->create(new HumanResources_Model_AttendanceRecorderDevice([
                'id' => HumanResources_Model_AttendanceRecorderDevice::SYSTEM_STANDALONE_PROJECT_TIME_ID,
                HumanResources_Model_AttendanceRecorderDevice::FLD_NAME => 'tine system standalone project time',
                HumanResources_Model_AttendanceRecorderDevice::FLD_DESCRIPTION => 'tine system standalone project time',
            ]));
        } catch (Zend_Db_Statement_Exception) {
            // already there
        }

        $device = $deviceCtrl->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID);
        $device->{HumanResources_Model_AttendanceRecorderDevice::FLD_DESCRIPTION} = 'tine system project time in conjunction with working time';
        $deviceCtrl->update($device);

        $device = $deviceCtrl->get(HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID);
        $device->{HumanResources_Model_AttendanceRecorderDevice::FLD_DESCRIPTION} = 'tine system working time in conjunction with project time';
        $deviceCtrl->update($device);

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004(): void
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_AttendanceRecorderDevice::class,
            HumanResources_Model_Employee::class,
        ]);
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005(): void
    {
        $employees = HumanResources_Controller_Employee::getInstance()->getAll();
        $employees->{HumanResources_Model_Employee::FLD_AR_PT_DEVICE_ID} = HumanResources_Model_AttendanceRecorderDevice::SYSTEM_PROJECT_TIME_ID;
        $employees->{HumanResources_Model_Employee::FLD_AR_WT_DEVICE_ID} = HumanResources_Model_AttendanceRecorderDevice::SYSTEM_WORKING_TIME_ID;

        foreach ($employees as $employee) {
            HumanResources_Controller_Employee::getInstance()->update($employee);
        }

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }

    public function update006(): void
    {
        HumanResources_Scheduler_Task::addAutoCreateAccounts(Tinebase_Core::getScheduler());

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);
    }
}
