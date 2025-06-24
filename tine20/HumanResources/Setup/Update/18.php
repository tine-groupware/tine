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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
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
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
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

}
