<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to convert a single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Abstract extends Tinebase_Convert_VCalendar_Abstract implements Tinebase_Convert_Interface
{
    use Calendar_Convert_Event_VCalendar_AbstractTrait;

    /**
     * add attachment content as binary base64 encoded string
     * @const
     */
    const OPTION_ADD_ATTACHMENTS_BINARY = 'addAttachmentsBinary';

    /**
     * add attachment content max size (bytes)
     * @const
     */
    const OPTION_ADD_ATTACHMENTS_MAX_SIZE = 'addAttachmentsMaxSize';

    /**
     * add attachment url
     * @const
     */
    const OPTION_ADD_ATTACHMENTS_URL = 'addAttachmentsURL';

    const OPTION_USE_EXTERNAL_ID_UID = 'useExternalIdUid';

    public static $cutypeMap = array(
        Calendar_Model_Attender::USERTYPE_EMAIL         => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_USER          => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUPMEMBER   => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUP         => 'GROUP',
        Calendar_Model_Attender::USERTYPE_RESOURCE      => 'RESOURCE',
    );
    
    protected $_modelName = 'Calendar_Model_Event';

    /**
     * value of METHOD property
     * @var string
     */
    protected $_method;

    /**
     * @var Calendar_Model_Attender
     */
    protected $_calendarUser = NULL;

    /**
     * sets current calendar user
     *
     * @param Calendar_Model_Attender $_calUser
     * @return Calendar_Model_Attender oldUser
     */
    public function setCalendarUser(Calendar_Model_Attender $_calUser)
    {
        $oldUser = $this->_calendarUser;
        $this->_calendarUser = $_calUser;

        return $oldUser;
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @return \Sabre\VObject\Component\VCalendar
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     */
    public function createVCalendar(Tinebase_Record_Interface $_record)
    {
        $vcalendar = new \Sabre\VObject\Component\VCalendar(array(
            'PRODID'   => $this->_getProdId(),
            'VERSION'  => '2.0',
            'CALSCALE' => 'GREGORIAN'
        ));

        $this->_setVTimezone($_record, $vcalendar);

        if (isset($this->_method)) {
            $vcalendar->add('METHOD', $this->_method);
        }

        return $vcalendar;
    }

    /**
     * @param Calendar_Model_Event $event
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     */
    protected function _setVTimezone(Calendar_Model_Event $event, \Sabre\VObject\Component\VCalendar $vcalendar)
    {
        $originatorTz = $event ? $event->originator_tz : Tinebase_Core::getUserTimezone();

        try {
            $vtimezone = new Sabre_VObject_Component_VTimezone($originatorTz);
        } catch (Exception $e) {
            $userTz = Tinebase_Core::getUserTimezone();
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ .
                '::' . __LINE__ . ' Could not add event tz: ' . $e->getMessage() . ' - use default user tz: ' . $userTz);
            $vtimezone = new Sabre_VObject_Component_VTimezone($userTz);
        }

        $vcalendar->add($vtimezone);
    }

    /**
     * convert Tinebase_Record_RecordSet to Sabre\VObject\Component
     *
     * @param ?Tinebase_Record_RecordSet $_records
     * @param ?Tinebase_Model_Filter_FilterGroup $_filter
     * @param ?Tinebase_Model_Pagination $_pagination
     *
     * @return Sabre\VObject\Component
     */
    public function fromTine20RecordSet(?Tinebase_Record_RecordSet $_records = null,
                                        ?Tinebase_Model_Filter_FilterGroup $_filter = null,
                                        ?Tinebase_Model_Pagination $_pagination = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' start serializing');

        $vcalendar = $this->createVCalendar($_records->getFirstRecord());

        foreach ($_records as $record) {
            $this->addEventToVCalendar($vcalendar, $record);
        }
        
        $this->_afterFromTine20Model($vcalendar);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' done serializing');
        
        return $vcalendar;
    }

    /**
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @param Calendar_Model_Event $event
     * @throws Tinebase_Exception_Record_Validation
     */
    public function addEventToVCalendar(\Sabre\VObject\Component\VCalendar $vcalendar, Calendar_Model_Event $event)
    {
        $this->_convertCalendarModelEvent($vcalendar, $event);

        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            $eventExceptions = $event->exdate->filter('is_deleted', false);

            foreach ($eventExceptions as $eventException) {
                $this->_convertCalendarModelEvent($vcalendar, $eventException, $event);
            }
        }
    }

    /**
     * convert Calendar_Model_Event to Sabre\VObject\Component
     *
     * @param  Calendar_Model_Event  $_record
     * @return Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $_records = new Tinebase_Record_RecordSet(get_class($_record), array($_record), true, false);
        
        return $this->fromTine20RecordSet($_records);
    }
    
    /**
     * convert calendar event to Sabre\VObject\Component
     * 
     * @param  \Sabre\VObject\Component\VCalendar $vcalendar
     * @param  Calendar_Model_Event               $_event
     * @param  Calendar_Model_Event               $_mainEvent
     */
    protected function _convertCalendarModelEvent(\Sabre\VObject\Component\VCalendar $vcalendar, Calendar_Model_Event $_event, ?\Calendar_Model_Event $_mainEvent = null)
    {
        // clone the event and change the timezone
        $event = clone $_event;
        try {
            $event->setTimezone($event->originator_tz);
        } catch (Exception $e) {
            $userTz = Tinebase_Core::getUserTimezone();
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ .
                '::' . __LINE__ . ' Could not set event tz: ' . $e->getMessage() . ' - use default user tz: ' . $userTz);
            $event->setTimezone($userTz);
            $event->originator_tz = $userTz;
            $_event->originator_tz = $userTz;
        }
        
        $lastModifiedDateTime = $_event->last_modified_time ? $_event->last_modified_time : $_event->creation_time;
        if (! $event->creation_time instanceof Tinebase_DateTime) {
            throw new Tinebase_Exception_Record_Validation('creation_time needed for conversion to Sabre\VObject\Component');
        }

        /** @var \Sabre\VObject\Component\VEvent $vevent */
        $vevent = $vcalendar->create('VEVENT', array(
            'CREATED'       => $_event->creation_time->getClone()->setTimezone('UTC'),
            'LAST-MODIFIED' => $lastModifiedDateTime->getClone()->setTimezone('UTC'),
            'DTSTAMP'       => Tinebase_DateTime::now(),
            'UID'           => $event->uid,
        ));
        
        $vevent->add('SEQUENCE', $event->hasExternalOrganizer() ? $event->external_seq : $event->seq);
        
        if ($event->isRecurException()) {
            $originalDtStart = $_event->getOriginalDtStart()->setTimezone($_event->originator_tz);
            
            $recurrenceId = $vevent->add('RECURRENCE-ID', $originalDtStart);

            if (null === $_mainEvent && $event->base_event_id) {
                $oldAclVal = Calendar_Controller_Event::getInstance()->doContainerACLChecks(false);
                try {
                    $_mainEvent = Calendar_Controller_Event::getInstance()->get($event->base_event_id);
                } finally {
                    Calendar_Controller_Event::getInstance()->doContainerACLChecks($oldAclVal);
                }
                if ($_mainEvent->is_all_day_event) {
                    $recurrenceId->offsetSet('VALUE', 'DATE');
                }
            }
        }
        
        // dtstart and dtend
        $dtstart = $vevent->add('DTSTART', $_event->dtstart->getClone()->setTimezone($event->originator_tz));
        
        if ($event->is_all_day_event == true) {
            $dtstart->offsetSet('VALUE', 'DATE');
            
            // whole day events ends at 23:59:(00|59) in Tine 2.0 but 00:00 the next day in vcalendar
            $event->dtend->addSecond($event->dtend->get('s') == 59 ? 1 : 0);
            $event->dtend->addMinute($event->dtend->get('i') == 59 ? 1 : 0);
            
            $dtend = $vevent->add('DTEND', $event->dtend);
            $dtend->offsetSet('VALUE', 'DATE');
        } else {
            $dtend = $vevent->add('DTEND', $event->dtend);
        }
        
        // auto status for deleted events
        if ($event->is_deleted) {
            $event->status = Calendar_Model_Event::STATUS_CANCELED;
        }

        $this->_addEventOrganizer($vevent, $event);
        $this->_addEventAttendee($vevent, $event);
        
        $optionalProperties = array(
            'class',
            'status',
            'description',
            'geo',
            'location',
            'priority',
            'summary',
            'transp',
            'url'
        );
        
        foreach ($optionalProperties as $property) {
            if (!empty($event->$property)) {
                $vevent->add(strtoupper($property), $event->$property);
            }
        }

        $class = $event->class == Calendar_Model_Event::CLASS_PUBLIC ? 'PUBLIC' : 'CONFIDENTIAL';
        $vevent->add('X-CALENDARSERVER-ACCESS', $class);
        if (! $_mainEvent && $vcalendar->__get('X-CALENDARSERVER-ACCESS') === null) {
            // add one time only
            $vcalendar->add('X-CALENDARSERVER-ACCESS', $class);
        }

        // categories
        if (!isset($event->tags)) {
            $event->tags = Tinebase_Tags::getInstance()->getTagsOfRecord($event);
        }
        if (count($event->tags) > 0 && $event->tags instanceof Tinebase_Record_RecordSet) {
            $vevent->add('CATEGORIES', (array) $event->tags->name);
        }
        
        // repeating event properties
        if ($event->rrule) {
            $rrule = $event->rrule instanceof Calendar_Model_Rrule ? $event->rrule : Calendar_Model_Rrule::getRruleFromString($event->rrule);
            if ($rrule['freq'] === Calendar_Model_Rrule::FREQ_INDIVIDUAL) {
                $rrule['freq'] = Calendar_Model_Rrule::FREQ_DAILY;
            }
            $event->rrule = $rrule;
            $event->rrule->setTimezone('UTC');
            if ($event->is_all_day_event == true) {
                $vevent->add('RRULE', preg_replace_callback('/UNTIL=([\d :-]{19})(?=;?)/', function($matches) {
                    $dtUntil = new Tinebase_DateTime($matches[1], 'UTC');
                    $dtUntil->setTimezone((string) Tinebase_Core::getUserTimezone());
                    
                    return 'UNTIL=' . $dtUntil->format('Ymd');
                }, $event->rrule));
            } else {
                $vevent->add('RRULE', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $event->rrule));
            }
            
            if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                $deletedEvents = $event->exdate->filter('is_deleted', true);
                
                foreach ($deletedEvents as $deletedEvent) {
                    $dateTime = $deletedEvent->getOriginalDtStart();

                    /** @var \Sabre\VObject\Property $exdate */
                    $exdate = $vevent->add('EXDATE');
                    
                    if ($event->is_all_day_event == true) {
                        $dateTime->setTimezone($event->originator_tz);
                        $exdate->offsetSet('VALUE', 'DATE');
                    }
                    
                    $exdate->setValue($dateTime);
                }
            }
        }
        
        if ($event->alarms instanceof Tinebase_Record_RecordSet) {
            $mozLastAck = NULL;
            $mozSnooze = NULL;
            
            foreach ($event->alarms as $alarm) {
                $valarm = $vcalendar->create('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add('DESCRIPTION', $event->summary);
                
                if ($dtack = Calendar_Controller_Alarm::getAcknowledgeTime($alarm)) {
                    $valarm->add('ACKNOWLEDGED', $dtack->getClone()->setTimezone('UTC')->format('Ymd\\THis\\Z'));
                    $mozLastAck = $dtack > $mozLastAck ? $dtack : $mozLastAck;
                }
                
                if ($dtsnooze = Calendar_Controller_Alarm::getSnoozeTime($alarm)) {
                    $mozSnooze = $dtsnooze > $mozSnooze ? $dtsnooze : $mozSnooze;
                }
                if (is_numeric($alarm->minutes_before)) {
                    if ($event->dtstart == $alarm->alarm_time) {
                        $periodString = 'PT0S';
                    } else {
                        $interval = $event->dtstart->diff($alarm->alarm_time);
                        $periodString = sprintf('%sP%s%s%s%s',
                            $interval->format('%r'),
                            $interval->format('%d') > 0 ? $interval->format('%dD') : null,
                            ($interval->format('%h') > 0 || $interval->format('%i') > 0) ? 'T' : null,
                            $interval->format('%h') > 0 ? $interval->format('%hH') : null,
                            $interval->format('%i') > 0 ? $interval->format('%iM') : null
                        );
                    }
                    # TRIGGER;VALUE=DURATION:-PT1H15M
                    $trigger = $valarm->add('TRIGGER', $periodString);
                    $trigger['VALUE'] = "DURATION";
                } else {
                    # TRIGGER;VALUE=DATE-TIME:...
                    $trigger = $valarm->add('TRIGGER', $alarm->alarm_time->getClone()->setTimezone('UTC')->format('Ymd\\THis\\Z'));
                    $trigger['VALUE'] = "DATE-TIME";
                }
                
                $vevent->add($valarm);
            }
            
            if ($mozLastAck instanceof DateTime) {
                $vevent->add('X-MOZ-LASTACK', $mozLastAck->getClone()->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
            }
            
            if ($mozSnooze instanceof DateTime) {
                $vevent->add('X-MOZ-SNOOZE-TIME', $mozSnooze->getClone()->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
            }
        }

        if (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES])) {
            $sabrePropertyParser = new Calendar_Convert_Event_VCalendar_SabrePropertyParser($vcalendar);
            foreach ($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] as $prop) {
                try {
                    $propArr = explode("\r\n", $prop);
                    $prop = array_shift($propArr);
                    foreach ($propArr as $line) {
                        if ($line[0] === "\t" || $line[0] === ' ') {
                            $prop .= substr($line, 1);
                        } else {
                            $prop .= $line;
                        }
                    }
                    $propObj = $sabrePropertyParser->parseProperty($prop);
                    $vevent->__set($propObj->name, $propObj);
                } catch (\Sabre\VObject\ParseException $svope) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ .
                        '::' . __LINE__ . ' failed adding events imip x-property: ' . $prop . ' -> ' .
                        $svope->getMessage());
                }
            }
        }
        
        if ($event->attachments instanceof Tinebase_Record_RecordSet) {
            $this->_addAttachments($vevent, $vcalendar, $event);
        }
        
        $vcalendar->add($vevent);
    }

    protected function _addAttachments($vevent, $vcalendar, $event)
    {
        $baseUrl = Tinebase_Core::getHostname() . "/webdav/Calendar/records/Calendar_Model_Event/{$event->getId()}/";
        $maxSize = isset($this->_options[self::OPTION_ADD_ATTACHMENTS_MAX_SIZE])
            ? $this->_options[self::OPTION_ADD_ATTACHMENTS_MAX_SIZE]
            : 10 * 1024 * 1024; // 10 MB
        foreach ($event->attachments as $attachment) {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Adding attachment: ' . print_r($attachment->toArray(), true));

            if ($attachment->size > $maxSize) {
                // NOTE: sabredav component->serialize fails with bigger files (> 10 MB)
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Not adding attachment because it is bigger than configured max size: ' . Tinebase_Helper::formatBytes($attachment->size));
                continue;
            }

            $filename = rawurlencode($this->_getAttachmentFilename($attachment->name));
            if (isset($this->_options[self::OPTION_ADD_ATTACHMENTS_BINARY])
                && $this->_options[self::OPTION_ADD_ATTACHMENTS_BINARY]
            ) {
                $content = Tinebase_FileSystem::getInstance()->getNodeContents($attachment);
                $value = base64_encode($content);
                $attachmentData = [
                    'ENCODING' => 'BASE64',
                    'VALUE' => 'BINARY',
                    'FILENAME' => $filename,
                    'X-FILENAME' => $filename,
                    'X-APPLE-FILENAME' => $filename,
                    'FMTTYPE' => $attachment->contenttype,
                ];
            } else {
                $value = "{$baseUrl}{$filename}";
                $attachmentData = [
                    'MANAGED-ID' => $attachment->hash,
                    'FMTTYPE'    => $attachment->contenttype,
                    'SIZE'       => $attachment->size,
                    'FILENAME'   => $filename,
                ];
            }
            $attach = $vcalendar->createProperty('ATTACH', $value, $attachmentData, 'TEXT');
            $vevent->add($attach);
        }
        if ($event->attachments->count()
            && isset($this->_options[self::OPTION_ADD_ATTACHMENTS_URL])
            && $this->_options[self::OPTION_ADD_ATTACHMENTS_URL]
        ) {
            $vevent->add($vcalendar->createProperty('URL', $baseUrl));
        }
    }

    /**
     * convert to ascii string
     *
     * @param string $string
     * @return string
     *
     * TODO move this to Tinebase_Helper
     */
    protected function _getAttachmentFilename($string)
    {
        $string = str_replace([' ', '/'], '_', $string);
        if (false === ($filename = @iconv("UTF-8", "ascii//TRANSLIT", $string))) {
            $filename = iconv("UTF-8", "ascii//IGNORE", $string);
        }

        return $filename;
    }

    protected function _addEventOrganizer(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event): void
    {
        $mailTo = $cn = null;
        if (!empty($event->organizer)) {
            $organizerContact = $event->resolveOrganizer();
            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $mailTo = $event->organizer_email ?: $organizerContact->email;
                $cn = $event->organizer_displayname ?:$organizerContact->n_fileas;
            }
        }
        if (null === $mailTo && Calendar_Model_Event::ORGANIZER_TYPE_EMAIL === $event->organizer_type &&
            $event->organizer_email
        ) {
            $mailTo = $event->organizer_email;
            $cn = $event->organizer_displayname ?: 'Organizer';
        }

        if ($mailTo && is_scalar($mailTo) && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $mailTo)) {
            $vevent->add(
                'ORGANIZER',
                'mailto:' . $mailTo,
                ['CN' => $cn, 'EMAIL' => $mailTo]
            );
        } else if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' No valid organizer email found: ' . $mailTo);
        }
    }

    /**
     * add event attendee to VEVENT object 
     * 
     * @param \Sabre\VObject\Component\VEvent $vevent
     * @param Calendar_Model_Event            $event
     */
    protected function _addEventAttendee(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        if (empty($event->attendee)) {
            return;
        }
        
        Calendar_Model_Attender::resolveAttendee($event->attendee, FALSE, $event);

        /** @var Calendar_Model_Attender $eventAttendee */
        foreach ($event->attendee as $eventAttendee) {
            try {
                $attendeeEmail = $eventAttendee->getEmail();
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' Attender not found (skipping): ' . $tenf->getMessage());
                }
                continue;
            }

            $role = in_array($eventAttendee->role, ['REQ', 'OPT']) ? $eventAttendee->role : 'REQ';
            $parameters = array(
                'CN'       => $eventAttendee->getName(),
                'CUTYPE'   => $this->_getAttendeeCUType($eventAttendee),
                'PARTSTAT' => $eventAttendee->status,
                'ROLE'     => "{$role}-PARTICIPANT",
                'RSVP'     => $eventAttendee->isSame($this->_calendarUser) ? 'TRUE' : 'FALSE',
            );
            if (strpos($attendeeEmail, '@') !== false) {
                $parameters['EMAIL'] = $attendeeEmail;
            }
            $vevent->add('ATTENDEE', (strpos($attendeeEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $attendeeEmail, $parameters);
        }
    }

    /**
     * returns CUTYPE for given attendee
     *
     * @param Calendar_Model_Attender $eventAttendee
     * @return string
     */
    protected function _getAttendeeCUType($eventAttendee)
    {
        return Calendar_Convert_Event_VCalendar_Abstract::$cutypeMap[$eventAttendee->user_type];
    }

    /**
     * set the METHOD for the generated VCALENDAR
     *
     * @param  string  $method  the method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * converts vcalendar to Calendar_Model_Event
     * 
     * @param  mixed                 $blob    the VCALENDAR to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @param  array                 $options  array of options
     * @return Calendar_Model_Event
     */
    public function toTine20Model($blob, ?\Tinebase_Record_Interface $_record = null, $options = array())
    {
        $vcalendar = self::getVObject($blob);
        
        // contains the VCALENDAR any VEVENTS
        if (! isset($vcalendar->VEVENT)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_record instanceof Calendar_Model_Event) {
            $event = $_record;
            $existingDtStart = clone $event->dtstart;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        if (isset($vcalendar->METHOD)) {
            $this->setMethod($vcalendar->METHOD);
        }
        
        $baseVevent = $this->_findMainEvent($vcalendar);
        
        if (! $baseVevent) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No main VEVENT found');
            
            if ((null === $_record || $event->isRecurException()) && count($vcalendar->VEVENT) > 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Convert recur exception without existing event using first VEVENT');
                $this->_convertVevent($vcalendar->VEVENT[0], $event, $options);
            }
        } else {
            $this->_convertVevent($baseVevent, $event, $options);
        }

        if (isset($existingDtStart)) {
            $options['dtStartDiff'] = $event->dtstart->getClone()->setTimezone($event->originator_tz)
            ->diff($existingDtStart->getClone()->setTimezone($event->originator_tz));
        }
        if (!$event->isRecurException()) {
            $this->_parseEventExceptions($event, $vcalendar, $baseVevent, $options);
        }

        // check for removed exdates ?
        //   => this is also done by msEventFacade, lets skip it here

        $event->isValid(true);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Event: ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * find the main event - the main event has no RECURRENCE-ID
     * 
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @return \Sabre\VObject\Component\VCalendar | null
     */
    protected function _findMainEvent(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
        /** @var \Sabre\VObject\Component $vevent */
        foreach ($vcalendar->__get('VEVENT') as $vevent) {
            if (!$vevent->__isset('RECURRENCE-ID')) {
                return $vevent;
            }
        }
        
        return null;
    }
    
    /**
     * parse event exceptions and add them to Tine 2.0 event record
     * 
     * @param  Calendar_Model_Event                $event
     * @param  \Sabre\VObject\Component\VCalendar  $vcalendar
     * @param  \Sabre\VObject\Component\VCalendar  $baseVevent
     * @param  array                               $options
     */
    protected function _parseEventExceptions(Calendar_Model_Event $event, \Sabre\VObject\Component\VCalendar $vcalendar, $baseVevent = null, $options = array())
    {
        if (! $event->exdate instanceof Tinebase_Record_RecordSet) {
            $event->exdate = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        }
        $recurExceptions = $event->exdate->filter('is_deleted', false);

        foreach ($vcalendar->__get('VEVENT') as $vevent) {
            /** @var \Sabre\VObject\Component $vevent */
            if ($vevent->__isset('RECURRENCE-ID') && $event->uid == $vevent->__get('UID')) {
                $recurException = $this->_getRecurException($recurExceptions, $vevent, $options);
                
                // initialize attendee with attendee from base events for new exceptions
                // this way we can keep attendee extra values like groupmember type
                // attendees which do not attend to the new exception will be removed in _convertVevent
                if (! $recurException->attendee instanceof Tinebase_Record_RecordSet) {
                    $recurException->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                    foreach ($event->attendee as $attendee) {
                        $recurException->attendee->addRecord(new Calendar_Model_Attender(array(
                            'user_id'   => $attendee->user_id,
                            'user_type' => $attendee->user_type,
                            'user_email' => $attendee->user_email,
                            'user_displayname' => $attendee->user_displayname,
                            'role'      => $attendee->role,
                            'status'    => $attendee->status
                        )));
                    }
                }
                
                // initialize attachments from base event as clients may skip parameters like
                // name and content type and we can't backward relove them from managedId
                if ($event->attachments instanceof Tinebase_Record_RecordSet && 
                        ! $recurException->attachments instanceof Tinebase_Record_RecordSet) {
                    $recurException->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
                    foreach ($event->attachments as $attachment) {
                        $recurException->attachments->addRecord(new Tinebase_Model_Tree_Node(array(
                            'name'         => $attachment->name,
                            'type'         => Tinebase_Model_Tree_FileObject::TYPE_FILE,
                            'contenttype'  => $attachment->contenttype,
                            'hash'         => $attachment->hash,
                        ), true));
                    }
                }
                
                if ($baseVevent) {
                    $this->_adaptBaseEventProperties($vevent, $baseVevent);
                }
                
                $this->_convertVevent($vevent, $recurException, $options);
                
                if (! $recurException->getId()) {
                    $event->exdate->addRecord($recurException);
                }

                // remove 'processed' so we know which exceptions no longer exist
                $recurExceptions->removeRecord($recurException);
            }
        }

        // delete exceptions not longer in data
        foreach($recurExceptions as $noLongerExisting) {
            $toRemove = $event->exdate->getById($noLongerExisting->getId());
            if ($toRemove) {
                $event->exdate->removeRecord($toRemove);
            }
        }

    }
    
    /**
     * adapt X-MOZ-LASTACK / X-MOZ-SNOOZE-TIME from base vevent
     * 
     * @see 0009396: alarm_ack_time and alarm_snooze_time are not updated
     */
    protected function _adaptBaseEventProperties($vevent, $baseVevent)
    {
        $propertiesToAdapt = array('X-MOZ-LASTACK', 'X-MOZ-SNOOZE-TIME');
        
        foreach ($propertiesToAdapt as $property) {
            if (isset($baseVevent->{$property})) {
                $vevent->{$property} = $baseVevent->{$property};
            }
        }
    }
    
    /**
     * convert VCALENDAR to Tinebase_Record_RecordSet of Calendar_Model_Event
     * 
     * @param  mixed  $blob  the vcalendar to parse
     * @param  array  $options
     * @return Tinebase_Record_RecordSet
     */
    public function toTine20RecordSet($blob, $options = array())
    {
        $vcalendar = self::getVObject($blob);
        
        $result = new Tinebase_Record_RecordSet('Calendar_Model_Event');

        /** @var \Sabre\VObject\Component $vevent */
        foreach ($vcalendar->__get('VEVENT') as $vevent) {
            if (! isset($vevent->{'RECURRENCE-ID'})) {
                $event = new Calendar_Model_Event();
                $this->_convertVevent($vevent, $event, $options);
                if (! empty($event->rrule)) {
                    $this->_parseEventExceptions($event, $vcalendar, $options);
                }
                $result->addRecord($event);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Converted ' . count($result) . ' events from VCALENDAR blob.');
            }
        }
        
        return $result;
    }
    
    /**
     * get METHOD of current VCALENDAR or supplied blob
     * 
     * @param  string  $blob
     * @return string|NULL
     */
    public function getMethod($blob = NULL)
    {
        if ($this->_method) {
            return $this->_method;
        }
        
        if ($blob !== NULL) {
            $vcalendar = self::getVObject($blob);
            return $vcalendar->__get('METHOD');
        }
        
        return null;
    }

    /**
     * find a matching exdate or return an empty event record
     * 
     * @param  Tinebase_Record_RecordSet        $oldExdates
     * @param  \Sabre\VObject\Component\VEvent  $vevent
     * @param   array                           $options
     * @return Calendar_Model_Event
     */
    protected function _getRecurException(Tinebase_Record_RecordSet $oldExdates,Sabre\VObject\Component\VEvent $vevent, $options)
    {
        $exDate = $this->_convertToTinebaseDateTime($vevent->__get('RECURRENCE-ID'));
        // dtstart might have been updated
        if (isset($options['dtStartDiff'])) {
            $exDate->modifyTime($options['dtStartDiff']);
        }
        $exDate->setTimezone('UTC');

        $exDateString = $exDate->format('Y-m-d H:i:s');

        foreach ($oldExdates as $id => $oldExdate) {
            if ($exDateString == substr((string) $oldExdate->recurid, -19)) {
                return $oldExdate;
            }
        }
        
        return new Calendar_Model_Event();
    }

    /**
     * get attendee array for given contact
     * 
     * @param  \Sabre\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' attendee ' . $calAddress->serialize());
        
        if ($calAddress->offsetExists('CUTYPE') && in_array($calAddress->offsetGet('CUTYPE')->getValue(), array('INDIVIDUAL', Calendar_Model_Attender::USERTYPE_GROUP, Calendar_Model_Attender::USERTYPE_RESOURCE))) {
            $type = $calAddress->offsetGet('CUTYPE')->getValue() == 'INDIVIDUAL' ? Calendar_Model_Attender::USERTYPE_USER : $calAddress->offsetGet('CUTYPE')->getValue();
        } else {
            $type = Calendar_Model_Attender::USERTYPE_USER;
        }
        
        if ($calAddress->offsetExists('ROLE') && in_array($calAddress->offsetGet('ROLE')->getValue(), array(Calendar_Model_Attender::ROLE_OPTIONAL, Calendar_Model_Attender::ROLE_REQUIRED))) {
            $role = $calAddress->offsetGet('ROLE')->getValue();
        } else {
            $role = Calendar_Model_Attender::ROLE_REQUIRED;
        }
        
        if ($calAddress->offsetExists('PARTSTAT') && in_array($calAddress->offsetGet('PARTSTAT')->getValue(), array(
            Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_NEEDSACTION,
            Calendar_Model_Attender::STATUS_TENTATIVE
        ))) {
            $status = $calAddress->offsetGet('PARTSTAT')->getValue();
        } else {
            $status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }
        
        if (!empty($calAddress->offsetGet('EMAIL'))) {
            $email = $calAddress->offsetGet('EMAIL')->getValue();
        } else {
            if (! preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/i', $calAddress->getValue(), $matches)) {
                if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $calAddress->getValue())) {
                    $email = $calAddress->getValue();
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' Invalid attendee provided: ' . $calAddress->getValue()
                            . ' / Attendee data: ' . $calAddress->serialize());
                    }
                    return null;
                }
            } else {
                $email = $matches['email'];
            }
        }
        
        $fullName = $calAddress->offsetExists('CN') ? $calAddress->offsetGet('CN')->getValue() : $email;
        
        $parsedName = Addressbook_Model_Contact::splitName($fullName);

        $attendee = array(
            'userType'  => $type,
            'firstName' => $parsedName['n_given'],
            'lastName'  => $parsedName['n_family'],
            'displayName'=> $fullName,
            'partStat'  => $status,
            'role'      => $role,
            'email'     => $email
        );
        
        return $attendee;
    }

    /**
     * @param Calendar_Model_Event $event
     * @return void
     */
    protected function _setDefaultsForEmptyValues(Calendar_Model_Event $event): void
    {
        if (empty($event->seq)) {
            $event->seq = 1;
        }
        if (empty($event->class)) {
            $event->class = Calendar_Model_Event::CLASS_PUBLIC;
        }
        if (empty($event->transp)) {
            $event->transp = Calendar_Model_Event::TRANSP_OPAQUE;
        }

        if (empty($event->dtend)) {
            if (empty($event->dtstart)) {
                throw new Tinebase_Exception_UnexpectedValue("Got event without dtstart and dtend");
            }

            // TODO find out duration (see TRIGGER DURATION)
            // if (isset($vevent->DURATION)) {
            // }

            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Got event without dtend. Assuming 30 minutes duration');
            $event->dtend = clone $event->dtstart;
            $event->dtend->addMinute(30);
        }
    }

    /**
     * get utc datetime from date string and handle dates (ie 20140922) in usertime
     * 
     * @param string $dateString
     * 
     * TODO maybe this can be replaced with _convertToTinebaseDateTime
     */
    static public function getUTCDateFromStringInUsertime($dateString)
    {
        if (strlen((string)$dateString) < 10) {
            $date = date_create($dateString, new DateTimeZone ((string) Tinebase_Core::getUserTimezone()));
        } else {
            $date = date_create($dateString);
        }
        if (! $date) {
            throw new Tinebase_Exception_UnexpectedValue('Could not create DateTime from date string: ' . $dateString);
        }
        $date->setTimezone(new DateTimeZone('UTC'));
        return $date;
    }
}
