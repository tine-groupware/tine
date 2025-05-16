<?php

use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * CalDAV plugin for rfc6638 section 5 "Request for Busy Time Information"
 * 
 * This plugin provides functionality added by RFC6638
 * 
 * see: https://datatracker.ietf.org/doc/rfc6638/
 *
 *       
 * @package    Calendar
 * @subpackage Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright  Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Paul Mehrer <p.mehrer@metaways.de>
 */
class Calendar_Frontend_CalDAV_ScheduleOutbox extends \Sabre\DAV\ServerPlugin
{
    /**
     * Reference to server object
     *
     * @var Server
     */
    protected $server;

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     * 
     * @return string 
     */
    public function getPluginName(): string
    {
        return 'calendarScheduleOutbox';
    }

    public function initialize(Server $server): void
    {
        $this->server = $server;
        $server->on('method:POST', [$this, 'httpPOSTHandler']);
        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
    }

    public function httpPOSTHandler(RequestInterface $request, ResponseInterface $response): bool
    {
        [$root, $principalId, $outbox] = array_merge(explode('/', $request->getPath(), 3), [null, null]);
        if ($root !== \Sabre\CalDAV\Plugin::CALENDAR_ROOT || $outbox !== 'outbox') {
            return true; // not our request to handle
        }

        if ($principalId !== Tinebase_Core::getUser()->contact_id) {
            $response->setStatus(403); // FORBIDDEN
            return false; // we handled the request
        }

        $body = $request->getBodyAsString();
        $vcalendar = \Tinebase_Convert_VCalendar_Abstract::getVObject($body);
        if (!isset($vcalendar->METHOD) || 'REQUEST' !== $vcalendar->METHOD->getValue()) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        }

        if (!isset($vcalendar->VFREEBUSY)) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        }
        $vFreeBusy = $vcalendar->VFREEBUSY;
        $uid = ($vFreeBusy->UID?->getValue()) ?? Tinebase_Record_Abstract::generateUID();

        // specs says organizer is mandatory and needs to be an email of the uri owner, $principalId
        // but we don't need that, if the client wants to spoof himself and send himself a bogus organizer ... we don't care
        // we just send the organizer we got, if we got one, back (see below) and that is that
        /* if (null === ($organizer = $vFreeBusy->ORGANIZER?->getValue())) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        } */

        if (null === ($attendees = $vFreeBusy->ATTENDEE)) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        }
        if (null === ($dtstart = $vFreeBusy->DTSTART)) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        }
        $dtstart = \Tinebase_Convert_VCalendar_Abstract::convertToTinebaseDateTime($dtstart);
        if (null === ($dtend = $vFreeBusy->DTEND)) {
            $response->setStatus(400); // BAD REQUEST
            return false; // we handled the request
        }
        $dtend = \Tinebase_Convert_VCalendar_Abstract::convertToTinebaseDateTime($dtend);

        $period = ['from' => $dtstart->getClone(), 'until' => $dtend->getClone()];

        $w = new XMLWriter();
        $w->openMemory();
        $w->startDocument();
        $w->startElementNs('C', 'schedule-response', 'urn:ietf:params:xml:ns:caldav');
        $w->writeAttribute('xmlns:D', 'DAV');

        foreach ($attendees as $attendee) {
            $email = str_replace('mailto:', '', $attendee->getValue());

            Calendar_Model_Attender::emailsToAttendee($event = new Calendar_Model_Event([], true), [[
                'email' => $email,
                'userType' => Calendar_Model_Attender::USERTYPE_USER,
                'role' => Calendar_Model_Attender::ROLE_REQUIRED,
            ]], false);

            $w->startElement('C:response');
            $w->startElement('C:recipient');
            $w->startElement('D:href');
            $w->writeCdata($attendee->getValue());
            $w->endElement(); // D:href
            $w->endElement(); // C:recipient
            $w->startElement('C:request-status');
            $w->writeCdata('2.0;Success'); // spec says we cant mix 2.XX with other codes... also says 2.xx requires calendar-data
            $w->endElement(); // C:request-status

            $w->startElement('C:calendar-data');

            $vcalendar = new \Sabre\VObject\Component\VCalendar();
            $vcalendar->METHOD = 'REPLY'; // @phpstan-ignore-line
            $vFree = $vcalendar->createComponent('VFREEBUSY', array_merge([
                'UID' => $uid,
                'DTSTAMP' => Tinebase_DateTime::now(),
                'DTSTART' => $dtstart->getClone(),
                'DTEND' => $dtend->getClone(),
                'ATTENDEE' => $attendee,
            ], $vFreeBusy->ORGANIZER ? ['ORGANIZER' => $vFreeBusy->ORGANIZER] : []));
            $vcalendar->add($vFree);

            if ($event->attendee?->count() > 0) {
                $allFb = Calendar_Controller_Event::getInstance()->getFreeBusyInfo(
                    new Calendar_Model_EventFilter([
                        ['field' => 'period', 'operator' => 'within', 'value' => $period],
                    ]), $event->attendee, [$uid]);

                if ($allFb->count() > 0) {
                    /** @var Calendar_Model_FreeBusy $freeBusy */
                    foreach ($allFb as $freeBusy) {
                        if (Calendar_Model_FreeBusy::FREEBUSY_FREE === $freeBusy->type) {
                            continue;
                        }
                        $type = 'BUSY';
                        if (Calendar_Model_FreeBusy::FREEBUSY_BUSY_TENTATIVE === $freeBusy->type) {
                            $type = 'BUSY-TENTATIVE';
                        }
                        $vFree->add('FREEBUSY', $freeBusy->dtstart->format('Ymd\THis\Z') . '/' . $freeBusy->dtend->format('Ymd\THis\Z'), ['FBTYPE' => $type]);
                    }
                }
            }

            $w->writeCdata($vcalendar->serialize());
            $w->endElement(); // C:calendar-data
            $w->endElement(); // C:response
        }

        $w->endElement(); // C:schedule-response
        $w->endDocument();

        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody($w->outputMemory());

        return false; // we handled the request
    }
}
