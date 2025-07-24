<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Generic decorator for caldav
 *
 * Uses Calendar_Convert_Event_VCalendar_Tine for import.
 */
class Calendar_Import_CalDav_Decorator_Generic extends Calendar_Import_CalDav_Decorator_Abstract
{
    protected array $raiis = [];

    public function initCalendarImport(array $options = [])
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Tine20SyncClient/' . TINE20_PACKAGESTRING;

        if ($options[Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER] ?? false) {
            $oldValue = Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite;
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite = true;
            $this->raiis[] = new Tinebase_RAII(fn() => Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite = $oldValue);
        }

        if ($options[Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES] ?? false) {
            $oldValue = Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite;
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite = true;
            $this->raiis[] = new Tinebase_RAII(fn() => Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite = $oldValue);
        }
    }
}