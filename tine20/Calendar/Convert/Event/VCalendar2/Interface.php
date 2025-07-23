<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar VCALENDAR converter interface
 *
 * @package     Calendar
 * @subpackage  Convert
 */
interface Calendar_Convert_Event_VCalendar2_Interface
{
    /**
     * @param string $blob
     * @param array $options
     * @param Tinebase_Record_RecordSet<Calendar_Model_Event>|null $mergeEvents
     * @return Tinebase_Record_RecordSet<Calendar_Model_Event>
     */
    public function toTine20Models(string $blob, array $options = [], ?Tinebase_Record_RecordSet $mergeEvents = null): Tinebase_Record_RecordSet;
    public function setMethod(?string $method): void;
    public function getMethod(): ?string;
}