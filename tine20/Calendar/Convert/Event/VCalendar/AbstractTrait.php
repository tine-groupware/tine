<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar VCALENDAR converter abstract trait
 *
 * @package     Calendar
 * @subpackage  Convert
 *
 * @method Tinebase_DateTime _convertToTinebaseDateTime(\Sabre\VObject\Property $dateTimeProperty, bool $_useUserTZ = false)
 */
trait Calendar_Convert_Event_VCalendar_AbstractTrait
{
    /**
     * options array
     * @var array<string, mixed>
     *
     * current options:
     *  - onlyBasicData (only use basic event data when converting from VCALENDAR to Tine 2.0)
     *  - addAttachmentsURL
     *  - addAttachmentsMaxSize
     *  - addAttachmentsBinary
     */
    protected array $_options = [];

    /**
     * set options
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    public function setOptionsValue(string $key, mixed $value): void
    {
        $this->_options[$key] = $value;
    }

    public function getOptionsValue(string $key): mixed
    {
        return !isset($this->_options[$key]) ? null : $this->_options[$key];
    }

    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress): ?array
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' attendee ' . $calAddress->serialize());

        if (isset($calAddress['CUTYPE']) && in_array($calAddress['CUTYPE']->getValue(), array('INDIVIDUAL', Calendar_Model_Attender::USERTYPE_GROUP, Calendar_Model_Attender::USERTYPE_RESOURCE))) {
            $type = $calAddress['CUTYPE']->getValue() == 'INDIVIDUAL' ? Calendar_Model_Attender::USERTYPE_USER : $calAddress['CUTYPE']->getValue();
        } else {
            $type = Calendar_Model_Attender::USERTYPE_USER;
        }

        if (isset($calAddress['ROLE']) && in_array($calAddress['ROLE']->getValue(), array(Calendar_Model_Attender::ROLE_OPTIONAL, Calendar_Model_Attender::ROLE_REQUIRED))) {
            $role = $calAddress['ROLE']->getValue();
        } else {
            $role = Calendar_Model_Attender::ROLE_REQUIRED;
        }

        if (isset($calAddress['PARTSTAT']) && in_array($calAddress['PARTSTAT']->getValue(), array(
                Calendar_Model_Attender::STATUS_ACCEPTED,
                Calendar_Model_Attender::STATUS_DECLINED,
                Calendar_Model_Attender::STATUS_NEEDSACTION,
                Calendar_Model_Attender::STATUS_TENTATIVE
            ))) {
            $status = $calAddress['PARTSTAT']->getValue();
        } else {
            $status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        }

        if (!empty($calAddress['EMAIL'])) {
            $email = $calAddress['EMAIL']->getValue();
        } else {
            if (! preg_match('/(?P<protocol>mailto:|urn:uuid:)(?P<email>.*)/i', $calAddress->getValue(), $matches)) {
                if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $calAddress->getValue())) {
                    $email = $calAddress->getValue();
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid attendee provided: ' . $calAddress->getValue());
                    return null;
                }
            } else {
                $email = $matches['email'];
            }
        }

        $fullName = isset($calAddress['CN']) ? $calAddress['CN']->getValue() : $email;

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

    public static function getEmailFromProperty(\Sabre\VObject\Property $property): ?string
    {
        $email = null;
        if (!empty($property['EMAIL'])) {
            $email = (string)$property['EMAIL'];
        } elseif (preg_match('/mailto:(?P<email>.*)/i', $property->getValue(), $matches)) {
            $email = $matches['email'];
        }
        if (null !== $email && is_string($email)) {
            return trim($email);
        }
        return null;
    }

    protected  function _fromVEvent_Organizer(Calendar_Model_Event $event, array &$newAttendees, \Sabre\VObject\Property $property): void
    {
        $email = static::getEmailFromProperty($property);

        if (!empty($email)) {
            // it's not possible to change the organizer by spec
            if (empty($event->organizer) && empty($event->organizer_email)) {
                $name = isset($property['CN']) ? $property['CN']->getValue() : $email;
                if (null === ($contact = Calendar_Model_Attender::resolveEmailToContact([
                        'email'     => $email,
                        'lastName'  => $name,
                    ], false))) {
                    $event->organizer_type = Calendar_Model_Event::ORGANIZER_TYPE_EMAIL;
                    $event->organizer_email = $email;
                    $event->organizer_displayname = $name;
                } else {
                    $event->organizer = $contact->getId();
                    $event->organizer_email = $email;
                }
            }

            // Lightning attaches organizer ATTENDEE properties to ORGANIZER property and does not add an ATTENDEE for the organizer
            if (isset($property['PARTSTAT']) && $property instanceof \Sabre\VObject\Property\ICalendar\CalAddress
                    && $attendee = $this->_getAttendee($property)) {
                $newAttendees[] = $attendee;
            }
        }
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' vevent ' . $vevent->serialize());

        $newAttendees = array();
        $attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        $event->alarms = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class);

        $imipProps = [];

        /** @var \Sabre\VObject\Property $property */
        foreach ($vevent->children() as $property) {
            switch ($property->name) {
                case 'DTSTAMP':
                    $imipProps['DTSTAMP'] = trim($property->serialize());
                    if (! isset($options[static::OPTION_USE_SERVER_MODLOG]) || $options[static::OPTION_USE_SERVER_MODLOG] !== true) {
                        $event->last_modified_time = $this->_convertToTinebaseDateTime($property);
                    }
                    break;
                case 'CREATED':
                    $imipProps['CREATED'] = trim($property->serialize());
                    if (! isset($options[static::OPTION_USE_SERVER_MODLOG]) || $options[static::OPTION_USE_SERVER_MODLOG] !== true) {
                        $event->creation_time = $this->_convertToTinebaseDateTime($property);
                    }
                    break;

                case 'LAST-MODIFIED':
                    $event->last_modified_time = $this->_convertToTinebaseDateTime($property);
                    $imipProps['LAST-MODIFIED'] = trim($property->serialize());
                    break;

                case 'ATTENDEE':
                    $newAttendee = $this->_getAttendee($property);
                    if ($newAttendee) {
                        $newAttendees[] = $newAttendee;
                    }
                    break;

                case 'CLASS':
                    if (in_array($property->getValue(), array(Calendar_Model_Event::CLASS_PRIVATE, Calendar_Model_Event::CLASS_PUBLIC))) {
                        $event->class = $property->getValue();
                    } else {
                        $event->class = Calendar_Model_Event::CLASS_PUBLIC;
                    }

                    break;

                case 'STATUS':
                    if (in_array($property->getValue(), array(Calendar_Model_Event::STATUS_CONFIRMED, Calendar_Model_Event::STATUS_TENTATIVE, Calendar_Model_Event::STATUS_CANCELED))) {
                        $event->status = $property->getValue();
                    } else if ($property->getValue() == 'CANCELED'){
                        $event->status = Calendar_Model_Event::STATUS_CANCELED;
                    } else {
                        $event->status = Calendar_Model_Event::STATUS_CONFIRMED;
                    }
                    break;

                case 'DTEND':

                    if (isset($property['VALUE']) && strtoupper((string)$property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        $dtend = $this->_convertToTinebaseDateTime($property, TRUE);

                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        $event->is_all_day_event = false;
                        $dtend = $this->_convertToTinebaseDateTime($property);
                    }

                    $event->dtend = $dtend;

                    break;

                case 'DTSTART':
                    if (isset($property['VALUE']) && strtoupper((string)$property['VALUE']) == 'DATE') {
                        // all day event
                        $event->is_all_day_event = true;
                        $dtstart = $this->_convertToTinebaseDateTime($property, TRUE);
                    } else {
                        $event->is_all_day_event = false;
                        $dtstart = $this->_convertToTinebaseDateTime($property);
                    }

                    $event->originator_tz = $dtstart->getTimezone()->getName();
                    $event->dtstart = $dtstart;

                    break;

                case 'DESCRIPTION':
                case 'LOCATION':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    $value = $property->getValue();
                    if (in_array($key, array('location', 'summary')) && extension_loaded('mbstring')) {
                        $value = mb_substr($value, 0, 1024, 'UTF-8');
                    }

                    $event->$key = Tinebase_Core::filterInputForDatabase($value);
                    break;

                case 'ORGANIZER':
                    $this->_fromVEvent_Organizer($event, $newAttendees, $property);
                    break;

                case 'RECURRENCE-ID':
                    $imipProps[$property->name] = rtrim($property->serialize());
                    // original start of the event
                    $event->recurid = $this->_convertToTinebaseDateTime($property);

                    // convert recurrence id to utc
                    $event->recurid->setTimezone('UTC');

                    break;

                case 'RRULE':
                    $rruleString = $property->getValue();

                    // convert date format
                    $rruleString = preg_replace_callback('/UNTIL=([\dTZ]+)(?=;?)/', function($matches) {
                        $dtUntil = Calendar_Convert_Event_VCalendar_Abstract::getUTCDateFromStringInUsertime($matches[1]);
                        return 'UNTIL=' . $dtUntil->format(Tinebase_Record_Abstract::ISO8601LONG);
                    }, $rruleString);

                    // remove additional days from BYMONTHDAY property (BYMONTHDAY=11,15 => BYMONTHDAY=11)
                    $rruleString = preg_replace('/(BYMONTHDAY=)([\d]+),([,\d]+)/', '$1$2', $rruleString);

                    // remove COUNT=9999 as we can't handle this large recordsets
                    $rruleString = preg_replace('/;{0,1}COUNT=9999/', '', $rruleString);

                    $event->rrule = $rruleString;

                    if ($event->exdate instanceof Tinebase_Record_RecordSet) {
                        foreach($event->exdate as $exdate) {
                            if ($exdate->is_deleted) {
                                $event->exdate->removeRecord($exdate);
                            }
                        }
                    }

                    // NOTE: EXDATE in ical are fallouts only!
                    if (isset($vevent->EXDATE)) {
                        $event->exdate = $event->exdate instanceof Tinebase_Record_RecordSet ?
                            $event->exdate :
                            new Tinebase_Record_RecordSet(Calendar_Model_Event::class);

                        foreach ($vevent->EXDATE as $exdate) {
                            foreach ($exdate->getDateTimes() as $exception) {
                                if (isset($exdate['VALUE']) && strtoupper((string)$exdate['VALUE']) == 'DATE') {
                                    // TODO FIXME tz handling is questionable, check this!
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), (string) Tinebase_Core::getUserTimezone());
                                } else {
                                    $recurid = new Tinebase_DateTime($exception->format(Tinebase_Record_Abstract::ISO8601LONG), $exception->getTimezone());
                                }
                                $recurid->setTimezone(new DateTimeZone('UTC'));

                                $eventException = new Calendar_Model_Event(array(
                                    'dtstart'    => $recurid,
                                    'is_deleted' => true
                                ));

                                $event->exdate->addRecord($eventException);
                            }
                        }
                    }

                    break;

                case 'TRANSP':
                    if (in_array($property->getValue(), array(Calendar_Model_Event::TRANSP_OPAQUE, Calendar_Model_Event::TRANSP_TRANSP))) {
                        $event->transp = $property->getValue();
                    } else {
                        $event->transp = Calendar_Model_Event::TRANSP_TRANSP;
                    }

                    break;

                case 'UID':
                    // it's not possible to change the uid by spec
                    // TODO FIXME isnt this braking stuff? we need to remove that option!?
                    if (empty($event->uid)) {
                        $event->uid = $property->getValue();
                    }
                    break;

                case 'VALARM':
                    $this->_parseAlarm($event, $property, $vevent);
                    break;

                case 'CATEGORIES':
                    $tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Calendar');
                    if (! isset($event->tags)) {
                        $event->tags = $tags;
                    } else {
                        $event->tags->merge($tags);
                    }
                    break;

                case 'ATTACH':
                    $name = (string) $property['FILENAME'];
                    $managedId = (string) $property['MANAGED-ID'];
                    $value = (string) $property['VALUE'];
                    $attachment = NULL;
                    $readFromURL = false;
                    $url = '';

                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' attachment found: ' . $name . ' ' . $managedId);

                    if ($managedId) {
                        $attachment = $event->attachments instanceof Tinebase_Record_RecordSet ?
                            $event->attachments->filter('hash', $property['MANAGED-ID'])->getFirstRecord() :
                            NULL;

                        // NOTE: we might miss a attachment here for the following reasons
                        //       1. client reuses a managed id (we are server):
                        //          We havn't observerd this yet. iCal client reuse manged id's
                        //          from base events in exceptions but this is covered as we
                        //          initialize new exceptions with base event attachments
                        //
                        //          When a client reuses a managed id it's not clear yet if
                        //          this managed id needs to be in the same series/calendar/server
                        //
                        //          As we use the object hash the managed id might be used in the
                        //          same files with different names. We need to evaluate the name
                        //          (if attached) in this case as well.
                        //
                        //       2. server send his managed id (we are client)
                        //          * we need to download the attachment (here?)
                        //          * we need to have a mapping externalid / internalid (where?)

                        if (! $attachment) {
                            $readFromURL = true;
                            $url = $property->getValue();
                        } else {
                            $attachments->addRecord($attachment);
                        }
                    } elseif('URI' === $value) {
                        /*
                         * ATTACH;VALUE=URI:https://server.com/calendars/__uids__/0AA0
 3A3B-F7B6-459A-AB3E-4726E53637D0/dropbox/4971F93F-8657-412B-841A-A0FD913
 9CD61.dropbox/Canada.png
                         */
                        $url = $property->getValue();
                        $urlParts = parse_url($url);
                        $host = $urlParts['host'] ?? null;
                        $name = pathinfo($urlParts['path'], PATHINFO_BASENAME);

                        // iCal 10.7 places URI before uploading
                        if (parse_url(Tinebase_Core::getHostname(), PHP_URL_HOST) != $host) {
                            $readFromURL = true;
                        }
                    }
                    // base64
                    else {
                        // @TODO: implement (check if add / update / update is needed)
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' attachment found that could not be imported due to missing managed id');
                    }

                    if ($readFromURL) {
                        if (preg_match('#^(https?://)(.*)$#', str_replace(array("\n","\r"), '', $url), $matches)) {
                            // we are client and found an external hosted attachment that we need to import
                            $userCredentialCache = Tinebase_Core::getUserCredentialCache();
                            $url = $matches[1] . $userCredentialCache->username . ':' . $userCredentialCache->password . '@' . $matches[2];
                            $attachmentInfo = $matches[1] . $matches[2]. ' ' . $name . ' ' . $managedId;
                            if (Tinebase_Helper::urlExists($url)) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                        . ' Downloading attachment: ' . $attachmentInfo);

                                $stream = @fopen($url, 'r');
                                if ($stream) {
                                    $attachment = new Tinebase_Model_Tree_Node(array(
                                        'name' => rawurldecode($name),
                                        'type' => Tinebase_Model_Tree_FileObject::TYPE_FILE,
                                        'contenttype' => (string)$property['FMTTYPE'],
                                        'tempFile' => $stream,
                                    ), true);
                                    $attachments->addRecord($attachment);
                                } else {
                                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' Could not open url (maybe no permissions?): ' . $attachmentInfo . ' - Skipping attachment');
                                }
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                    . ' Url not found (got 404): ' . $attachmentInfo . ' - Skipping attachment');
                            }
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                    . ' Attachment found with malformed url: ' . $url);
                        }
                    }
                    break;

                case 'X-MOZ-LASTACK':
                    $lastAck = $this->_convertToTinebaseDateTime($property);
                    break;

                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = $this->_convertToTinebaseDateTime($property);
                    break;

                case 'EXDATE':
                    // ignore this, we dont want it to land in default -> imipProps!
                    break;
                case 'URL':
                    $event->url = $property->getValue();
                    break;
                default:
                    // thunderbird saves snooze time for recurring event occurrences in properties with names like this -
                    // we just assume that the event/recur series has only one snooze time
                    if (preg_match('/^X-MOZ-SNOOZE-TIME-[0-9]+$/', $property->name)) {
                        $snoozeTime = $this->_convertToTinebaseDateTime($property);
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Found snooze time for recur occurrence: ' . $snoozeTime->toString());
                    } elseif ($property instanceof \Sabre\VObject\Property) {
                        $imipProps[$property->name] = trim($property->serialize());
                    }
                    break;
            }
        }
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            $event->exdate->uid = $event->uid;
            $event->exdate->setRecurId(null); /** @phpstan-ignore method.notFound */
        }
        if (!empty($imipProps) && !$event->hasExternalOrganizer()) {
            unset($imipProps['DTSTAMP']);
            unset($imipProps['CREATED']);
            unset($imipProps['LAST-MODIFIED']);
        }
        if (!empty($imipProps)) {
            if (isset($event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES])) {
                $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] += $imipProps;
            } else {
                $event->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES] = $imipProps;
            }
        }

        // evaluate seq after organizer is parsed
        if ($vevent->SEQUENCE) {
            $seq = $vevent->SEQUENCE->getValue();
            if (!$event->hasExternalOrganizer()) {
                if (!isset($options[static::OPTION_USE_SERVER_MODLOG]) || $options[static::OPTION_USE_SERVER_MODLOG] !== true) {
                    $event->seq = $seq;
                }
            } else {
                $event->external_seq = $seq;
            }
        }

        // NOTE: X-CALENDARSERVER-ACCESS overwrites CLASS
        if (isset($vevent->{'X-CALENDARSERVER-ACCESS'})) {
            $event->class = $vevent->{'X-CALENDARSERVER-ACCESS'} == 'PUBLIC' ?
                Calendar_Model_Event::CLASS_PUBLIC :
                Calendar_Model_Event::CLASS_PRIVATE;
        }

        if (isset($lastAck)) {
            Calendar_Controller_Alarm::setAcknowledgeTime($event->alarms, $lastAck);
        }
        if (isset($snoozeTime)) {
            Calendar_Controller_Alarm::setSnoozeTime($event->alarms, $snoozeTime);
        }

        // merge old and new attendee
        Calendar_Model_Attender::emailsToAttendee($event, $newAttendees, false);

        $this->_setDefaultsForEmptyValues($event);

        $this->_manageAttachmentsFromClient($event, $attachments);

        // convert all datetime fields to UTC
        $event->setTimezone('UTC');

        if ($event->isRecurException() && $event->dtstart) {
            $event->setRecurId(null);
        }
    }

    protected function _setDefaultsForEmptyValues(Calendar_Model_Event $event): void
    {
    }

    protected function _manageAttachmentsFromClient(Calendar_Model_Event $event, Tinebase_Record_RecordSet $attachments): void
    {
    }


}