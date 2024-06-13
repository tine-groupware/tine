<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Mac OS X VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_BusyCal extends Calendar_Convert_Event_VCalendar_Abstract
{
	// BusyCal-2.6.6
    const HEADER_MATCH = '/^BusyCal-(?P<version>\S+)/';
    
    /**
     * get attendee array for given contact
     * 
     * @param  \Sabre\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress): ?array
    {
        
        $newAttendee = parent::_getAttendee($calAddress);
        
        // beginning with mavericks iCal adds organiser as attendee without role
        // so we remove attendee without role 
        // @TODO check if this attendee is currentuser & organizer?
        if (version_compare($this->_version, '10.9', '>=')) {
            if (! isset($calAddress['ROLE'])) {
                return NULL;
            }
        }
        
        return $newAttendee;
    }

    /**
     * do version specific magic here
     *
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @return \Sabre\VObject\Component\VCalendar | null
     */
    protected function _findMainEvent(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
        $return = parent::_findMainEvent($vcalendar);

        // NOTE 10.7 and 10.10 sometimes write access into calendar property
        if ($vcalendar->__isset('X-CALENDARSERVER-ACCESS')) {
            foreach ($vcalendar->__get('VEVENT') as $vevent) {
                $vevent->__set('X-CALENDARSERVER-ACCESS', $vcalendar->__get('X-CALENDARSERVER-ACCESS'));
            }
        }

        return $return;
    }

    /**
     * parse VEVENT part of VCALENDAR
     *
     * @param  \Sabre\VObject\Component\VEvent  $vevent  the VEVENT to parse
     * @param  Calendar_Model_Event             $event   the Tine 2.0 event to update
     * @param  array                            $options
     */
    protected function _convertVevent(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event, $options)
    {
        $return = parent::_convertVevent($vevent, $event, $options);

        // NOTE: 10.7 sometimes uses (internal?) int's
        if (isset($vevent->{'X-CALENDARSERVER-ACCESS'}) && (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} > 0) {
            $event->class = (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} == 1 ?
                Calendar_Model_Event::CLASS_PUBLIC :
                Calendar_Model_Event::CLASS_PRIVATE;
        }

        return $return;
    }

    /**
     * iCal supports manged attachments
     *
     * @param Calendar_Model_Event          $event
     * @param Tinebase_Record_RecordSet     $attachments
     */
    protected function _manageAttachmentsFromClient(Calendar_Model_Event $event, Tinebase_Record_RecordSet $attachments): void
    {
        $event->attachments = $attachments;
    }
}
