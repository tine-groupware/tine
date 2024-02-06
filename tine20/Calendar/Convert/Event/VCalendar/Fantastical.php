<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sebastian Hagedorn <Hagedorn@uni-koeln.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        #'geo',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        #'tags',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
        'is_all_day_event',
        #'rrule_until',
        'originator_tz',
    );
    
    /**
     * get attendee array for given contact
     * 
     * @param  \Tine20\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Tine20\VObject\Property\ICalendar\CalAddress $calAddress)
    {
        
        $newAttendee = parent::_getAttendee($calAddress);
        
        return $newAttendee;
    }

    /**
     * do version specific magic here
     *
     * @param \Tine20\VObject\Component\VCalendar $vcalendar
     * @return \Tine20\VObject\Component\VCalendar | null
     */
    protected function _findMainEvent(\Tine20\VObject\Component\VCalendar $vcalendar)
    {
        $return = parent::_findMainEvent($vcalendar);

        return $return;
    }

    /**
     * parse VEVENT part of VCALENDAR
     *
     * @param  \Tine20\VObject\Component\VEvent  $vevent  the VEVENT to parse
     * @param  Calendar_Model_Event             $event   the Tine 2.0 event to update
     * @param  array                            $options
     */
    protected function _convertVevent(\Tine20\VObject\Component\VEvent $vevent, Calendar_Model_Event $event, $options)
    {
        $return = parent::_convertVevent($vevent, $event, $options);

        return $return;
    }
}
