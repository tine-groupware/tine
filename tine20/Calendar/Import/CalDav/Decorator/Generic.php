<?php declare(strict_types=1);
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
    protected static array $raiis = [];

    public function initCalendarImport(array $options = [])
    {
        static::$raiis = [];

        $_SERVER['HTTP_USER_AGENT'] = 'Tine20SyncClient/' . TINE20_PACKAGESTRING;

        $oldOrganizerValue = Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite;
        static::$raiis[] = new Tinebase_RAII(fn() => Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite = $oldOrganizerValue);
        if ($options[Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER] ?? false) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' setting match organizer to true');
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite = true;
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' setting match organizer to false');
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipOrganizerOverwrite = false;
        }

        $oldAttendeeValue = Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite;
        static::$raiis[] = new Tinebase_RAII(fn() => Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite = $oldAttendeeValue);
        if ($options[Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES] ?? false) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' setting match attendees to true');
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite = true;
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' setting match attendees to false');
            Calendar_Convert_Event_VCalendar_TineSyncClient::$skipAttendeeOverwrite = false;
        }
    }
}