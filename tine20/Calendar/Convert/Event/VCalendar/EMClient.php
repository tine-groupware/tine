<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Pawassarat <tomp@topanet.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert an emclient VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_EMClient extends Calendar_Convert_Event_VCalendar_Abstract
{
    // eM Client/5.0.17595.0
    const HEADER_MATCH = '/e[Mm] ?Client\/(?P<version>.*)/';

}
