<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Tine 2.0 VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_TineSyncClient extends Calendar_Convert_Event_VCalendar_Abstract
{
    const HEADER_MATCH = '/Tine20SyncClient\/(?P<version>.+)/';

    public static bool $skipOrganizerOverwrite = false;
    public static bool $skipAttendeeOverwrite = false;

    protected  function _fromVEvent_Organizer(Calendar_Model_Event $event, array &$newAttendees, \Sabre\VObject\Property $property): void
    {
        if (static::$skipOrganizerOverwrite) {
            parent::_fromVEvent_Organizer($event, $newAttendees, $property);
            return;
        }

        $email = null;

        if (!empty($property['EMAIL'])) {
            $email = (string)$property['EMAIL'];
        } elseif (preg_match('/mailto:(?P<email>.*)/i', $property->getValue(), $matches)) {
            $email = $matches['email'];
        }
        if (($email !== null) && is_string($email)) {
            $email = trim($email);
        }

        if (!empty($email)) {
            // it's not possible to change the organizer by spec
            if (empty($event->organizer_email)) {
                $event->organizer = null;
                $event->organizer_type = Calendar_Model_Event::ORGANIZER_TYPE_EMAIL;
                $event->organizer_email = $email;
                $event->organizer_displayname = isset($property['CN']) && $property['CN'] instanceof \Sabre\VObject\Property ? $property['CN']->getValue() : $email;
            }

            // Lightning attaches organizer ATTENDEE properties to ORGANIZER property and does not add an ATTENDEE for the organizer
            if (isset($property['PARTSTAT']) && $property instanceof \Sabre\VObject\Property\ICalendar\CalAddress) {
                $newAttendees[] = $this->_getAttendee($property);
            }
        }
    }

    /**
     * get attendee array for given contact
     *
     * @param  \Sabre\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress): ?array
    {
        if (static::$skipAttendeeOverwrite) {
            return parent::_getAttendee($calAddress);
        }

        if (null !== ($attendee = parent::_getAttendee($calAddress))) {
            $attendee['userType'] = Calendar_Model_Attender::USERTYPE_EMAIL;
        }
        return $attendee;
    }
}
