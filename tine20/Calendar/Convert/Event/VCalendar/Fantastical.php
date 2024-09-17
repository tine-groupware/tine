<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sebastian Hagedorn <Hagedorn@uni-koeln.de>
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Fantastical VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Fantastical extends Calendar_Convert_Event_VCalendar_Abstract
{
    // Fantastical 2 for Mac/2.2.4 Mac OS X/10.11.5
    // Fantastical 2 for Mac (Calendar)/3.3.4 Mac OS X/11.2.1 Darwin/20.3.0 (x86_64)
    const HEADER_MATCH = '/(?J)(Fantastical 2 for Mac( \(Calendar\))?\/(?P<version>\S+) )/';
}
