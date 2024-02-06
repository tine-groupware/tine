<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Mac OS X VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_MacOSX extends Calendar_Convert_Event_VCalendar_Abstract
{
    // DAVKit/4.0.3 (732.2); CalendarStore/4.0.4 (997.7); iCal/4.0.4 (1395.7); Mac OS X/10.6.8 (10K549)
    // CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)
    // Mac OS X/10.8 (12A269) CalendarAgent/47 
    // Mac_OS_X/10.9 (13A603) CalendarAgent/174
    // Mac+OS+X/10.10 (14A389) CalendarAgent/315"
    // macOS/11.0 (20A5343i) CalendarAgent/950
    // macOS/13.0 (22A380) dataaccessd/1.0
    const HEADER_MATCH = '/'.
        '(?J)((CalendarStore.*Mac OS X\/(?P<version>\S+) )|'.
        '(^(M|m)ac[ _+]{0,1}OS([ _+]X){0,1}\/(?P<version>\S+).*(CalendarAgent|dataaccessd)))'.
    '/';

    const INTELLIGROUP = 'INTELLIGROUP';
    
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
     * convert Tinebase_Record_RecordSet to Tine20\VObject\Component
     *
     * @return Tine20\VObject\Component
     * @param ?Tinebase_Record_RecordSet $_records
     * @param ?Tinebase_Model_Filter_FilterGroup $_filter
     * @param ?Tinebase_Model_Pagination $_pagination
     *
     * @throws Tinebase_Exception_NotImplemented
     */
    public function fromTine20RecordSet(?Tinebase_Record_RecordSet $_records = null,
                                        ?Tinebase_Model_Filter_FilterGroup $_filter = null,
                                        ?Tinebase_Model_Pagination $_pagination = null)
    {
        $oldGroupValue = static::$cutypeMap[Calendar_Model_Attender::USERTYPE_GROUP];

        // rewrite GROUP TO INTELLIGROUP
        static::$cutypeMap[Calendar_Model_Attender::USERTYPE_GROUP] = self::INTELLIGROUP;

        $result = parent::fromTine20RecordSet($_records);

        // restore old value
        static::$cutypeMap[Calendar_Model_Attender::USERTYPE_GROUP] = $oldGroupValue;

        return $result;
    }

    /**
     * get attendee array for given contact
     * 
     * @param  \Tine20\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Tine20\VObject\Property\ICalendar\CalAddress $calAddress)
    {
        $newAttendee = parent::_getAttendee($calAddress);

        // skip implicit organizer attendee.
        // NOTE: when the organizer edits the event he becomes attendee anyway, see comments in MSEventFacade::update

        // in mavericks iCal adds organiser as attendee without role
        if ($this->_version && version_compare($this->_version, '10.9', '>=') && version_compare($this->_version, '10.10', '<')) {
            if (!isset($calAddress['ROLE'])) {
                return NULL;
            }
        // in yosemite iCal adds organiser with role "chair" but has no roles for other attendee
        } elseif ($this->_version && version_compare($this->_version, '10.10', '>=')) {
            if (isset($calAddress['ROLE']) && $calAddress['ROLE'] == 'CHAIR') {
                return NULL;
            }
        }

        // rewrite INTELLIGROUP TO GROUP
        if (self::INTELLIGROUP === $newAttendee['userType']) {
            $newAttendee['userType'] = static::$cutypeMap[Calendar_Model_Attender::USERTYPE_GROUP];
        }
        
        return $newAttendee;
    }

    /**
     * add event attendee to VEVENT object
     *
     * @param \Tine20\VObject\Component\VEvent $vevent
     * @param Calendar_Model_Event            $event
     */
    protected function _addEventAttendee(\Tine20\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        parent::_addEventAttendee($vevent, $event);

        if (empty($event->attendee)) {
            return;
        }

        // add organizer as CHAIR Attendee if he's no organizer, otherwise yosemite would add an attendee
        // when editing the event again.
        // NOTE: when the organizer edits the event he becomes attendee anyway, see comments in MSEventFacade::update
        if (version_compare($this->_version, '10.10', '>=')) {
            if (!empty($event->organizer)) {
                $organizerContact = $event->resolveOrganizer();

                if ($organizerContact instanceof Addressbook_Model_Contact) {

                    $organizerAttendee = Calendar_Model_Attender::getAttendee($event->attendee, new Calendar_Model_Attender(array(
                        'user_id' => $organizerContact->getId(),
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER
                    )));

                    if (! $organizerAttendee) {
                        $parameters = array(
                            'CN'       => $organizerContact->n_fileas,
                            'CUTYPE'   => 'INDIVIDUAL',
                            'PARTSTAT' => 'ACCEPTED',
                            'ROLE'     => 'CHAIR',
                        );
                        $organizerEmail = $organizerContact->email;
                        if (strpos($organizerEmail, '@') !== false) {
                            $parameters['EMAIL'] = $organizerEmail;
                        }
                        $vevent->add('ATTENDEE', (strpos($organizerEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $organizerEmail, $parameters);
                    }
                }
            }
        }

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

        // NOTE 10.7 and 10.10 sometimes write access into calendar property
        if (isset($vcalendar->{'X-CALENDARSERVER-ACCESS'})) {
            foreach ($vcalendar->VEVENT as $vevent) {
                $vevent->{'X-CALENDARSERVER-ACCESS'} = $vcalendar->{'X-CALENDARSERVER-ACCESS'};
            }
        }

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

        // NOTE: 10.7 sometimes uses (internal?) int's
        if (isset($vevent->{'X-CALENDARSERVER-ACCESS'}) && (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} > 0) {
            $event->class = (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} == 1 ?
                Calendar_Model_Event::CLASS_PUBLIC :
                Calendar_Model_Event::CLASS_PRIVATE;
        }

        // 10.10 sends UNTIL in wrong timezone for all day events it sends 23:59:59 UTC - which is the next day
        // in users timezone already. BUT the client itselfs stick to the error and shows the extra event
        // for edit - thisandfuture this behaviour leads to double events therefore we skip the wrong event
        // even if the client shows it
        if ($event->is_all_day_event
            && version_compare($this->_version, '10.10', '>=')
            && version_compare($this->_version, '10.11', '<=')
        ) {
            $event->rrule = preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) use ($vevent) {
                // refetch UNTIL from vevent and drop timepart
                preg_match('/UNTIL=([\dTZ]+)(?=;?)/', $vevent->RRULE, $matches);
                $dtUntil = Calendar_Convert_Event_VCalendar_Abstract::getUTCDateFromStringInUsertime(substr($matches[1], 0, 8));
                return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
            }, (string)$event->rrule);
        }
        return $return;
    }

    /**
     * iCal supports manged attachments
     *
     * @param Calendar_Model_Event          $event
     * @param Tinebase_Record_RecordSet     $attachments
     */
    protected function _manageAttachmentsFromClient($event, $attachments)
    {
        $event->attachments = $attachments;
    }
}
