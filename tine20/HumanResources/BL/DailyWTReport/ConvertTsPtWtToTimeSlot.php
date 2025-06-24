<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_DailyWTReport_ConvertTsPtWtToTimeSlot implements Tinebase_BL_ElementInterface
{
    /** @var HumanResources_Model_BLDailyWTReport_LimitWorkingTimeConfig */
    protected $_config;
    /** @var Tinebase_Record_RecordSet */
    protected $_timeSheets;

    public function __construct(HumanResources_Model_BLDailyWTReport_ConvertTsPtWtToTimeSlot $_config)
    {
        $this->_config = clone $_config;
    }

    public function setTimeSheets(?Tinebase_Record_RecordSet $_timeSheets): void
    {
        $this->_timeSheets = $_timeSheets;
    }

    /**
     * @param Tinebase_BL_PipeContext $_context
     * @param HumanResources_BL_DailyWTReport_Data $_data
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        if (!$this->_timeSheets || !$this->_timeSheets->count()) {
            return;
        }

        $wtTAid = HumanResources_Controller_WorkingTimeScheme::getInstance()
            ->getWorkingTimeAccount($_data->result->employee_id)->getId();
        $tss = $this->_timeSheets->filter(function(Timetracker_Model_Timesheet $ts) use($wtTAid) {
            return $ts->getIdFromProperty('timeaccount_id') === $wtTAid;
        });
        if (!$tss->count()) {
            $tss = $this->_timeSheets;
        }

        $_data->convertTimeSheetsToTimeSlots($tss);
    }
}