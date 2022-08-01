<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * Generic decorator for caldav
 *
 * Uses Calendar_Convert_Event_VCalendar_Tine for import.
 */
class Calendar_Import_CalDav_Decorator_Generic extends Calendar_Import_CalDav_Decorator_Abstract
{
    public function initCalendarImport()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Tine20SyncClient/' . TINE20_PACKAGESTRING;
    }
}