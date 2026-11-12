<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Calendar
 * @license     https://www.gnu.org/licenses/agpl-3.0.html
 * @copyright   Copyright (c) 2011-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Calendar_Frontend_CalDAV
 * 
 * @package     Calendar
 */
class Calendar_Frontend_CalDAVTest extends TestCase
{
    /**
     * Tree
     *
     * @var Sabre\DAV\ObjectTree
     */
    protected $_webdavTree;
    
    /**
     * test getChildren
     */
    public function testGetChildren()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $children = $collection->getChildren();
        
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV_Container);
        
        $this->assertTrue(array_reduce($children, function($result, $container){
            return $result || $container instanceof Tasks_Frontend_WebDAV_Container;
        }, FALSE), 'tasks container is missing');

        return $children;
    }

    public function testGetUserDirectory()
    {
        $grants = Tinebase_Model_Grants::getPersonalGrants($this->_personas['sclever']->getId());
        $grants->merge(new Tinebase_Record_RecordSet($grants->getRecordClassName(), array(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_EXPORT => true,
            Tinebase_Model_Grants::GRANT_SYNC => true,
        ))));
        $scleverTestCal = $this->_getCalendarTestContainer(Tinebase_Model_Container::TYPE_PERSONAL, $grants);

        $_SERVER['HTTP_USER_AGENT'] = 'xxx';
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $this->_personas['sclever']->contact_id, true);
        $children = $collection->getChildren();

        $containerIds = [];
        foreach ($children as $child) {
            $containerIds[] = $child->getName();
        }
        self::assertTrue(in_array($scleverTestCal->getId(), $containerIds));
    }
    
    /**
     * test getChild
     */
    public function testGetChild()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $child = $collection->getChild($this->_getCalendarTestContainer()->getId());
        
        $this->assertTrue($child instanceof Calendar_Frontend_WebDAV_Container);
    }

    /**
     * testGetChildrenWithSharedFolder
     *
     * @see 0011078: CalDav calender not working after upgrade from 2013.10 (postgresql)
     */
    public function testGetChildrenWithSharedFolder()
    {
        $this->_getCalendarTestContainer(Tinebase_Model_Container::TYPE_SHARED);
        $calendarRoot = \Sabre\CalDAV\Plugin::CALENDAR_ROOT;
        $_SERVER['REQUEST_URI'] = '/tine20/' . $calendarRoot;
        $collection = new Calendar_Frontend_WebDAV($calendarRoot, true);

        $children = $collection->getChildren();

        $this->assertTrue(is_array($children));
        $this->assertTrue(count($children) > 0);
        $this->assertTrue($children[0] instanceof Calendar_Frontend_WebDAV);
    }

    /**
     * test get calendar inbox
     */
    public function testGetCalendarInbox()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/calendars/' . Tinebase_Core::getUser()->contact_id . '/inbox');
        
        $this->assertInstanceOf('Calendar_Frontend_CalDAV_ScheduleInbox', $node, 'wrong child class');
    }
    
    /**
     * test get calendar outbox
     */
    public function testGetCalendarOutbox()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/calendars/' . Tinebase_Core::getUser()->contact_id . '/outbox');
        
        $this->assertInstanceOf('\Sabre\CalDAV\Schedule\Outbox', $node, 'wrong child class');
    }
    
    /**
     * test getChild
     */
    public function testGetTasksChild()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        $collection = new Calendar_Frontend_WebDAV(
            \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id,
            array('useIdAsName' => true)
        );
        $children = $this->testGetChildren();
        
        $taskContainer = array_reduce($children, function($result, $container){
            return $container instanceof Tasks_Frontend_WebDAV_Container ? $container : NULL;
        }, NULL);
        $child = $collection->getChild($taskContainer->getName());
    
        $this->assertTrue($child instanceof Tasks_Frontend_WebDAV_Container, 'got ' . get_class($child));
    }
    
    /**
     * test testGetTasksChild (Mac_OS_X)
     * 
     * @backupGlobals enabled
     */
    public function testGetTasksChildMacOSX()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
        
        new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT, true);
        $this->testGetChildren();
    }
    
    /**
     * test to a create file. this should not be possible at this level
     */
    public function testCreateFile()
    {
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $this->expectException('Sabre\DAV\Exception\Forbidden');
        
        $collection->createFile('foobar');
    }
    
    /**
     * test to create a new directory
     */
    public function testCreateDirectory()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createDirectory($randomName);
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Calendar_Model_Event::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        
        $this->assertTrue($container instanceof Tinebase_Model_Container);
    }

    /**
     * test to create a new directory
     */
    public function testCreateExtendedCollectionVEvent()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createExtendedCollection(
            'B1B3BEA0-F1F9-409F-B1A0-43E41119F851', new \Sabre\DAV\MkCol(
                ['{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'],
            array(
                '{DAV:}displayname' => $randomName,
                '{http://apple.com/ns/ical/}calendar-color' => '#711A76FF',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(array('VEVENT'))
            ))
        );
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Calendar_Model_Event::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);
        
        $subCollection = $collection->getChild('B1B3BEA0-F1F9-409F-B1A0-43E41119F851');
        $this->assertEquals('B1B3BEA0-F1F9-409F-B1A0-43E41119F851', $subCollection->getName());
        
        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    /**
     * test to create a new directory
     */
    public function testCreateExtendedCollectionVTodo()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();
        
        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        
        $collection->createExtendedCollection(
            'B1B3BEA0-F1F9-409F-B1A0-43E41119F851', new \Sabre\DAV\MkCol(
            array('{DAV:}collection', '{urn:ietf:params:xml:ns:caldav}calendar'),
            array(
                '{DAV:}displayname' => $randomName,
                '{http://apple.com/ns/ical/}calendar-color' => '#711A76FF',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(array('VTODO'))
            ))
        );
        
        $container = Tinebase_Container::getInstance()->getContainerByName(Tasks_Model_Task::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);

        Calendar_Frontend_WebDAV::clearClassCache();
        $subCollection = $collection->getChild('B1B3BEA0-F1F9-409F-B1A0-43E41119F851');
        $this->assertEquals('B1B3BEA0-F1F9-409F-B1A0-43E41119F851', $subCollection->getName());
        
        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    public function testCreateVTodoXML()
    {
        $randomName = Tinebase_Record_Abstract::generateUID();

        $body = '<?xml version="1.0" encoding="UTF-8"?>
<B:mkcalendar xmlns:B="urn:ietf:params:xml:ns:caldav">
  <A:set xmlns:A="DAV:">
    <A:prop>
      <B:schedule-calendar-transp>
        <B:transparent/>
      </B:schedule-calendar-transp>
      <B:calendar-timezone>BEGIN:VCALENDAR&#13;
VERSION:2.0&#13;
CALSCALE:GREGORIAN&#13;
BEGIN:VTIMEZONE&#13;
TZID:Europe/Berlin&#13;
BEGIN:DAYLIGHT&#13;
TZOFFSETFROM:+0100&#13;
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU&#13;
DTSTART:19810329T020000&#13;
TZNAME:MESZ&#13;
TZOFFSETTO:+0200&#13;
END:DAYLIGHT&#13;
BEGIN:STANDARD&#13;
TZOFFSETFROM:+0200&#13;
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU&#13;
DTSTART:19961027T030000&#13;
TZNAME:MEZ&#13;
TZOFFSETTO:+0100&#13;
END:STANDARD&#13;
END:VTIMEZONE&#13;
END:VCALENDAR&#13;
</B:calendar-timezone>
      <B:supported-calendar-component-set>
        <B:comp name="VTODO"/>
      </B:supported-calendar-component-set>
      <D:calendar-order xmlns:D="http://apple.com/ns/ical/">1</D:calendar-order>
      <A:displayname>' . $randomName. '</A:displayname>
    </A:prop>
  </A:set>
</B:mkcalendar>';

        $request = new Sabre\HTTP\Request('MKCALENDAR', '/' . \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id . '/9708036E-674E-4067-A0E9-698066374C6B/');
        $request->setBody($body);

        $server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->httpRequest = $request;
        $server->exec();

        $container = Tinebase_Container::getInstance()->getContainerByName(Tasks_Model_Task::class, $randomName, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
        $this->assertTrue($container instanceof Tinebase_Model_Container);

        $collection = new Calendar_Frontend_WebDAV(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id, true);
        $subCollection = $collection->getChild('9708036E-674E-4067-A0E9-698066374C6B');
        $this->assertEquals('9708036E-674E-4067-A0E9-698066374C6B', $subCollection->getName());

        $properties = $subCollection->getProperties(array('{DAV:}displayname'));
        $this->assertEquals($randomName, $properties['{DAV:}displayname']);
    }

    public function testGetChildrenXML()
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
<A:propfind xmlns:A="DAV:">
  <A:prop>
    <A:add-member/>
    <C:allowed-sharing-modes xmlns:C="http://calendarserver.org/ns/"/>
    <D:autoprovisioned xmlns:D="http://apple.com/ns/ical/"/>
    <E:bulk-requests xmlns:E="http://me.com/_namespace/"/>
    <B:calendar-alarm xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <D:calendar-color xmlns:D="http://apple.com/ns/ical/"/>
    <B:calendar-description xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:calendar-free-busy-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <D:calendar-order xmlns:D="http://apple.com/ns/ical/"/>
    <B:calendar-timezone xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:current-user-privilege-set/>
    <B:default-alarm-vevent-date xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:default-alarm-vevent-datetime xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:displayname/>
    <C:getctag xmlns:C="http://calendarserver.org/ns/"/>
    <C:invite xmlns:C="http://calendarserver.org/ns/"/>
    <D:language-code xmlns:D="http://apple.com/ns/ical/"/>
    <D:location-code xmlns:D="http://apple.com/ns/ical/"/>
    <A:owner/>
    <C:pre-publish-url xmlns:C="http://calendarserver.org/ns/"/>
    <C:publish-url xmlns:C="http://calendarserver.org/ns/"/>
    <C:push-transports xmlns:C="http://calendarserver.org/ns/"/>
    <C:pushkey xmlns:C="http://calendarserver.org/ns/"/>
    <A:quota-available-bytes/>
    <A:quota-used-bytes/>
    <D:refreshrate xmlns:D="http://apple.com/ns/ical/"/>
    <A:resource-id/>
    <A:resourcetype/>
    <B:schedule-calendar-transp xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:schedule-default-calendar-URL xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <C:source xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-alarms xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-attachments xmlns:C="http://calendarserver.org/ns/"/>
    <C:subscribed-strip-todos xmlns:C="http://calendarserver.org/ns/"/>
    <B:supported-calendar-component-set xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <B:supported-calendar-component-sets xmlns:B="urn:ietf:params:xml:ns:caldav"/>
    <A:supported-report-set/>
    <A:sync-token/>
  </A:prop>
</A:propfind>';

        $request = new Sabre\HTTP\Request('PROPFIND', '/' . \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id . '/');
        $request->setBody($body);



        $server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->httpRequest = $request;
        $response = new Tinebase_WebDav_Sabre_ResponseMock();
        $server->httpResponse = $response;
        $server->exec();

        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($response->body);
//        $responseDoc->formatOutput = true; echo $responseDoc->saveXML();
        $xpath = new DomXPath($responseDoc);
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

        $nodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp[@name="VTODO"]');
        $this->assertGreaterThanOrEqual(1, $nodes->length);
    }

    /**
     * Thunderbird declines one instance of an event series with an external organizer.
     * With the preference EXCLUDE_CANCELLED_EVENTS_WEBDAV, Thunderbird must not receive the declined instance.
     */
    public function testThunderbirdDeclineRecurInstanceOfExternalOrganizerSeriesExcludeCancelled(): void
    {
        $prefs = Tinebase_Core::getPreference(Calendar_Config::APP_NAME);
        $prefs->setValue(Calendar_Preference::EXCLUDE_CANCELLED_EVENTS_WEBDAV, true);
        $prefRaii = new Tinebase_RAII(fn() => $prefs->resetAppPrefsCache());
        $this->assertTrue((bool)$prefs->getValueForUser(
            Calendar_Preference::EXCLUDE_CANCELLED_EVENTS_WEBDAV,
            Tinebase_Core::getUser()->getId()
        ), 'preference is not set');

        $oldUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $userAgentRaii = new Tinebase_RAII(function () use ($oldUserAgent) {
            if (null === $oldUserAgent) {
                unset($_SERVER['HTTP_USER_AGENT']);
            } else {
                $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
            }
        });
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Thunderbird/128.5.0';

        // external organizer event series (daily, 5 instances), the current user is an attendee
        $personalContainer = $this->_getCalendarTestContainer();
        $externalOrganizerEmail = 'external.organizer@' . Tinebase_Record_Abstract::generateUID(8) . '.example.org';
        $dtstart = Tinebase_DateTime::now()->addDay(1)->setTime(10, 0);

        $calCtrl = Calendar_Controller_Event::getInstance();
        $oldSendNotifications = $calCtrl->sendNotifications(false);
        $notificationRaii = new Tinebase_RAII(fn() => $calCtrl->sendNotifications($oldSendNotifications));

        $series = $calCtrl->create(new Calendar_Model_Event([
            'summary'           => 'external organizer series',
            'dtstart'           => $dtstart,
            'dtend'             => $dtstart->getClone()->addHour(1),
            'originator_tz'     => Tinebase_Core::getUserTimezone(),
            'rrule'             => 'FREQ=DAILY;INTERVAL=1;COUNT=5',
            'container_id'      => $personalContainer->getId(),
            'organizer_type'    => Calendar_Model_Event::ORGANIZER_TYPE_EMAIL,
            'organizer_email'   => $externalOrganizerEmail,
            'attendee'          => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [
                [
                    'user_type' => Calendar_Model_Attender::USERTYPE_EMAIL,
                    'user_email' => $externalOrganizerEmail,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                ], [
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => Tinebase_Core::getUser()->contact_id,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status'    => Calendar_Model_Attender::STATUS_NEEDSACTION,
                ],
            ]),
        ]));
        $this->assertTrue($series->hasExternalOrganizer(), 'event series must have an external organizer');
        $this->assertNotSame(
            $personalContainer->getId(),
            $series->getIdFromProperty('container_id'),
            'event series must be in the external organizer container'
        );

        $calendarUri = '/' . \Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . Tinebase_Core::getUser()->contact_id . '/'
            . $personalContainer->getId() . '/';
        $eventUri = $calendarUri . $series->getId() . '.ics';

        // Thunderbird fetches the event series
        $response = $this->_execThunderbirdRequest(new Sabre\HTTP\Request('GET', $eventUri));
        $responseBody = $response->getBodyAsString();
        $this->assertSame(200, $response->getStatus(), $responseBody);
        $etag = $response->getHeader('ETag');
        $vcalendar = \Sabre\VObject\Reader::read($responseBody);
        $this->assertCount(1, $vcalendar->select('VEVENT'));

        // Thunderbird declines the second instance: it adds an override VEVENT with RECURRENCE-ID and PARTSTAT=DECLINED
        $baseVEvent = $vcalendar->VEVENT;
        $secondInstanceStart = $baseVEvent->DTSTART->getDateTime()->modify('+1 day');
        $override = clone $baseVEvent;
        unset($override->RRULE);
        unset($override->EXDATE);
        unset($override->VALARM);
        $override->{'RECURRENCE-ID'} = $secondInstanceStart;
        $override->DTSTART = $secondInstanceStart;
        $override->DTEND = $baseVEvent->DTEND->getDateTime()->modify('+1 day');
        $ownAttendeeFound = false;
        $ownMailto = strtolower('mailto:' . Tinebase_Core::getUser()->accountEmailAddress);
        foreach ($override->ATTENDEE as $attendee) {
            if ($ownMailto === strtolower($attendee->getValue())) {
                $attendee['PARTSTAT'] = 'DECLINED';
                $ownAttendeeFound = true;
            }
        }
        $this->assertTrue($ownAttendeeFound, 'own attendee missing in vcalendar: ' . $vcalendar->serialize());
        $vcalendar->add($override);

        $request = new Sabre\HTTP\Request('PUT', $eventUri, [
            'If-Match'      => $etag,
            'Content-Type'  => 'text/calendar; charset=utf-8',
        ]);
        $request->setBody($vcalendar->serialize());
        $response = $this->_execThunderbirdRequest($request);
        $this->assertContains($response->getStatus(), [200, 201, 204], $response->getBodyAsString());

        // the server stored the decline of the second instance
        $exceptions = $calCtrl->search(new Calendar_Model_EventFilter([
            ['field' => 'base_event_id', 'operator' => 'equals', 'value' => $series->getId()],
            ['field' => 'recurid', 'operator' => 'notnull', 'value' => null],
        ]));
        $this->assertCount(1, $exceptions, 'declined instance must be stored as recur exception');
        $declinedInstance = $exceptions->getFirstRecord();
        $this->assertSame(
            $secondInstanceStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            $declinedInstance->dtstart->getClone()->setTimezone('UTC')->format('Y-m-d H:i:s')
        );
        $ownExceptionAttendee = $declinedInstance->attendee->filter(
            fn(Calendar_Model_Attender $attender) => $attender->user_type === Calendar_Model_Attender::USERTYPE_USER
                && $attender->getUserId() === Tinebase_Core::getUser()->contact_id
        );
        $this->assertCount(1, $ownExceptionAttendee, 'own attendee must exist once in the declined instance: '
            . print_r($declinedInstance->attendee->toArray(), true));
        $this->assertSame(Calendar_Model_Attender::STATUS_DECLINED, $ownExceptionAttendee->getFirstRecord()->status);

        // Thunderbird syncs the calendar: list the events
        $request = new Sabre\HTTP\Request('PROPFIND', $calendarUri, [
            'Depth'         => '1',
            'Content-Type'  => 'application/xml; charset=utf-8',
        ]);
        $request->setBody('<?xml version="1.0" encoding="UTF-8"?>
<D:propfind xmlns:D="DAV:"><D:prop><D:getcontenttype/><D:resourcetype/><D:getetag/></D:prop></D:propfind>');
        $response = $this->_execThunderbirdRequest($request);
        $responseBody = $response->getBodyAsString();
        $this->assertSame(207, $response->getStatus(), $responseBody);
        $xpath = $this->_getMultiStatusXPath($responseBody);
        $hrefs = [];
        foreach ($xpath->query('//d:multistatus/d:response/d:href') as $hrefNode) {
            $hrefs[] = $hrefNode->nodeValue;
        }
        $this->assertContains($eventUri, $hrefs, 'event series must still be synced: ' . print_r($hrefs, true));
        $this->assertNotContains(
            $calendarUri . $declinedInstance->getId() . '.ics',
            $hrefs,
            'declined instance must not be synced as separate object'
        );

        // Thunderbird syncs the calendar: fetch the event data
        $request = new Sabre\HTTP\Request('REPORT', $calendarUri, [
            'Depth'         => '1',
            'Content-Type'  => 'application/xml; charset=utf-8',
        ]);
        $request->setBody('<?xml version="1.0" encoding="UTF-8"?>
<C:calendar-multiget xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop><D:getetag/><C:calendar-data/></D:prop>
  <D:href>' . $eventUri . '</D:href>
</C:calendar-multiget>');
        $response = $this->_execThunderbirdRequest($request);
        $responseBody = $response->getBodyAsString();
        $this->assertSame(207, $response->getStatus(), $responseBody);
        $xpath = $this->_getMultiStatusXPath($responseBody);
        $calendarDataNodes = $xpath->query('//d:multistatus/d:response/d:propstat/d:prop/cal:calendar-data');
        $this->assertSame(1, $calendarDataNodes->length, $responseBody);

        $syncedVCalendar = \Sabre\VObject\Reader::read($calendarDataNodes->item(0)->nodeValue);
        $vevents = $syncedVCalendar->select('VEVENT');
        $this->assertCount(1, $vevents, 'declined instance must not be synced: ' . $syncedVCalendar->serialize());
        $syncedBaseVEvent = $syncedVCalendar->VEVENT;
        $this->assertFalse(isset($syncedBaseVEvent->{'RECURRENCE-ID'}), 'base event expected');

        $exdates = [];
        foreach ($syncedBaseVEvent->select('EXDATE') as $exdateProperty) {
            foreach ($exdateProperty->getDateTimes() as $exdate) {
                $exdates[] = $exdate->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }
        $this->assertContains(
            $secondInstanceStart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            $exdates,
            'declined instance must be excluded from the series: ' . $syncedVCalendar->serialize()
        );

        unset($notificationRaii);
        unset($userAgentRaii);
        unset($prefRaii);
    }

    /**
     * execute a CalDAV request like Tinebase_Server_WebDAV::handle()
     *
     * @param \Sabre\HTTP\Request $request
     * @return Tinebase_WebDav_Sabre_ResponseMock
     */
    protected function _execThunderbirdRequest(\Sabre\HTTP\Request $request): Tinebase_WebDav_Sabre_ResponseMock
    {
        $request->setHeader('User-Agent', $_SERVER['HTTP_USER_AGENT']);

        // same as Tinebase_Server_WebDAV::handle(): use the user preference for declined events
        $calCtrl = Calendar_Controller_Event::getInstance();
        $oldNoSyncValue = $calCtrl->doNoSyncDeclinedAttendeeEvents(false);
        $noSyncRaii = new Tinebase_RAII(fn() => $calCtrl->doNoSyncDeclinedAttendeeEvents($oldNoSyncValue));
        $excludeDeclined = Tinebase_Core::getPreference(Calendar_Config::APP_NAME)->getValueForUser(
            Calendar_Preference::EXCLUDE_CANCELLED_EVENTS_WEBDAV,
            Tinebase_Core::getUser()->getId()
        );
        if ($excludeDeclined) {
            $calCtrl->doNoSyncDeclinedAttendeeEvents(true);
        }

        // the tine request is the same as the WebDAV request in Tinebase_Server_WebDAV
        $ifMatchHeader = null;
        if ($request->hasHeader('If-Match')) {
            $ifMatchHeader = new Zend\Http\Header\IfMatch($request->getHeader('If-Match'));
            Tinebase_Core::getRequest()->getHeaders()->addHeader($ifMatchHeader);
        }
        $ifMatchRaii = new Tinebase_RAII(function () use ($ifMatchHeader) {
            if (null !== $ifMatchHeader) {
                Tinebase_Core::getRequest()->getHeaders()->removeHeader($ifMatchHeader);
            }
        });

        Calendar_Frontend_WebDAV::clearClassCache();
        $server = new \Sabre\DAV\Server(
            new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()),
            new Tinebase_WebDav_Sabre_SapiMock()
        );
        $server->setBaseUri('/');
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->httpRequest = $request;
        $response = new Tinebase_WebDav_Sabre_ResponseMock();
        $server->httpResponse = $response;
        $server->exec();

        unset($ifMatchRaii);
        unset($noSyncRaii);

        return $response;
    }

    /**
     * @param string $body
     * @return DOMXPath
     */
    protected function _getMultiStatusXPath(string $body): DOMXPath
    {
        $responseDoc = new DOMDocument();
        $responseDoc->loadXML($body);
        $xpath = new DOMXPath($responseDoc);
        $xpath->registerNamespace('d', 'DAV:');
        $xpath->registerNamespace('cal', 'urn:ietf:params:xml:ns:caldav');

        return $xpath;
    }

    /**
     * 
     * @return \Sabre\DAV\Tree
     */
    protected function _getWebDAVTree()
    {
        if (! $this->_webdavTree instanceof \Sabre\DAV\Tree) {
            $this->_webdavTree = new \Sabre\DAV\Tree(new Tinebase_WebDav_Root());
        }
        
        return $this->_webdavTree;
    }
    
    /**
     * fetch test calendar for app
     *
     * @return Tinebase_Model_Container
     */
    protected function _getCalendarTestContainer($type = Tinebase_Model_Container::TYPE_PERSONAL, $grants = null)
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Calendar_Model_Event',
            'type'              => $type,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        )), $grants);
        
        return $container;
    }
    
    /**
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getTasksTestContainer()
    {
        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'model'             => 'Tasks_Model_Task',
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => Tasks_Model_Task::class,
        )));
        
        return $container;
    }    

    /**
     * test that a declined recur instance is not synced
     *
     * The user declines the second instance of a recurring event with a
     * Thunderbird-like PUT (full series plus exception with RECURRENCE-ID,
     * If-Match set). With the noSyncDeclinedAttendeeEvents flag enabled (set
     * by Tinebase_Server_WebDAV from the EXCLUDE_CANCELLED_EVENTS_WEBDAV
     * preference in production) the following sync-collection must not return
     * the declined exception as separate VEVENT. The base event is returned
     * with an EXDATE for the declined instance instead.
     */
    public function testDeclinedRecurInstanceNotSynced()
    {
        $user = Tinebase_Core::getUser();
        $eventController = Calendar_Controller_Event::getInstance();

        // personal container: the user is organizer and attendee and has full grants
        $container = $this->_getCalendarTestContainer();
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($user->getId());
        $event = new Calendar_Model_Event([
            'summary'        => 'Declined recur instance sync test',
            'dtstart'        => Tinebase_DateTime::now()->addDay(1)->setTime(10, 0, 0),
            'dtend'          => Tinebase_DateTime::now()->addDay(1)->setTime(10, 30, 0),
            'rrule'          => 'FREQ=DAILY;COUNT=5',
            'uid'            => Tinebase_Record_Abstract::generateUID(),
            'container_id'   => $container->getId(),
            'organizer'      => $contact->getId(),
            'attendee'       => new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [[
                'user_id'        => $user->contact_id,
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ]]),
        ]);
        $event = $eventController->create($event);
        $containerUri = '/calendars/' . $user->contact_id . '/' . $container->getId();

        // initial sync: only the base event, without EXDATE
        $initialSync = $this->_syncCollection($containerUri, '');
        $baseHref = $containerUri . '/' . $event->getId() . '.ics';
        $this->assertArrayHasKey($baseHref, $initialSync['responses'], 'initial sync must contain the base event');
        $this->assertNotNull(
            $initialSync['responses'][$baseHref]['calendarData'],
            'initial sync must return calendar-data'
        );
        $this->assertNotNull($initialSync['responses'][$baseHref]['etag'], 'initial sync must return the etag');
        $this->assertStringContainsString('RRULE', $initialSync['responses'][$baseHref]['calendarData']);
        $this->assertStringNotContainsString('EXDATE', $initialSync['responses'][$baseHref]['calendarData']);

        // enable the exclusion (in production Tinebase_Server_WebDAV sets this from the
        // EXCLUDE_CANCELLED_EVENTS_WEBDAV preference)
        $oldFlag = $eventController->doNoSyncDeclinedAttendeeEvents(true);
        $noSyncRaii = new Tinebase_RAII(fn() => $eventController->doNoSyncDeclinedAttendeeEvents($oldFlag));
        Calendar_Controller_MSEventFacade::unsetInstance();

        // decline the second instance with a Thunderbird-like PUT (If-Match triggers a full update)
        $oldUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:115.0) Gecko/20100101 Thunderbird/115.0';
        $ifMatch = new Zend\Http\Header\IfMatch($initialSync['responses'][$baseHref]['etag']);
        Tinebase_Core::getRequest()->getHeaders()->addHeader($ifMatch);
        try {
            $card = $this->_declineRecurInstance($initialSync['responses'][$baseHref]['calendarData'], $user);
            // a fresh server per request: Sabre\DAV\Tree caches nodes and production
            // creates a new server for every http request
            $server = $this->_getWebDavServer();
            $server->httpRequest = new Sabre\HTTP\Request(
                'PUT',
                $baseHref,
                ['If-Match' => $ifMatch->getFieldValue()],
                $card
            );
            $server->httpResponse = new Tinebase_WebDav_Sabre_ResponseMock();
            $server->exec();

            $this->assertSame(204, $server->httpResponse->getStatus(), 'PUT failed: ' . $server->httpResponse->body);
        } finally {
            Tinebase_Core::getRequest()->getHeaders()->removeHeader($ifMatch);
            $_SERVER['HTTP_USER_AGENT'] = $oldUserAgent;
        }

        // the declined exception is persisted and the own attender is declined
        $exceptions = $eventController->getRecurExceptions($eventController->get($event->getId()), false);
        $this->assertCount(1, $exceptions, 'expected exactly one recur exception after the decline PUT');
        $exception = $exceptions->getFirstRecord();
        $attender = $exception->attendee->find('user_id', $user->contact_id);
        $this->assertNotNull($attender, 'the exception must have an attender for the user');
        $this->assertSame(Calendar_Model_Attender::STATUS_DECLINED, $attender->status);

        // second sync: the base event is returned with an EXDATE, no VEVENT for the declined instance
        $secondSync = $this->_syncCollection($containerUri, $initialSync['syncToken']);
        $this->assertArrayHasKey($baseHref, $secondSync['responses'], 'second sync must contain the base event');
        $this->assertCount(1, $secondSync['responses'], 'the declined exception must not be synced as separate entry');

        $vobject = \Sabre\VObject\Reader::read($secondSync['responses'][$baseHref]['calendarData']);
        $baseVevent = null;
        $recurrenceIds = [];
        foreach ($vobject->VEVENT as $vevent) {
            if ($recurrenceId = $vevent->{'RECURRENCE-ID'}) {
                $recurrenceIds[] = (string) $recurrenceId;
            }
            if ($vevent->RRULE) {
                $baseVevent = $vevent;
            }
        }
        $this->assertNotNull($baseVevent, 'second sync must contain the base event with RRULE');
        $this->assertEmpty($recurrenceIds, 'second sync must not contain a VEVENT for the declined instance');

        $exdate = $baseVevent->EXDATE;
        $this->assertNotNull($exdate, 'the declined instance must be excluded via EXDATE on the base event');
        $this->assertSame(
            $exception->dtstart->getTimestamp(),
            $exdate->getDateTime()->getTimestamp(),
            'the EXDATE must be the declined occurrence'
        );
    }

    /**
     * create a sabre DAV server backed by the tine webdav tree
     *
     * @return \Sabre\DAV\Server
     */
    protected function _getWebDavServer()
    {
        $server = new \Sabre\DAV\Server(
            new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()),
            new Tinebase_WebDav_Sabre_SapiMock()
        );
        $server->addPlugin(new \Sabre\CalDAV\Plugin());
        $server->addPlugin(new Tinebase_WebDav_Plugin_SyncToken());
        return $server;
    }

    /**
     * run a sync-collection REPORT and parse the multistatus response
     *
     * @param string $containerUri
     * @param string $syncToken
     * @return array{syncToken: string, responses: array<string, array{etag: ?string, calendarData: ?string}>}
     */
    protected function _syncCollection($containerUri, $syncToken)
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>
                <A:sync-collection xmlns:A="DAV:">
                    <A:sync-token>' . $syncToken . '</A:sync-token>
                    <A:sync-level>1</A:sync-level>
                    <A:prop>
                        <A:getetag/>
                        <C:calendar-data xmlns:C="urn:ietf:params:xml:ns:caldav"/>
                    </A:prop>
                </A:sync-collection>';
        $server = $this->_getWebDavServer();
        $server->httpRequest = new Sabre\HTTP\Request('REPORT', $containerUri, ['DEPTH' => '1'], $body);
        $server->httpResponse = new Tinebase_WebDav_Sabre_ResponseMock();
        $server->exec();

        $this->assertSame(
            207,
            $server->httpResponse->getStatus(),
            'sync-collection failed: ' . $server->httpResponse->body
        );

        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($server->httpResponse->body), 'invalid multistatus XML');
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('d', 'DAV:');
        $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');

        $newSyncToken = '';
        if ($token = $xpath->query('//d:sync-token')->item(0)) {
            $newSyncToken = $token->nodeValue;
        }

        $responses = [];
        foreach ($xpath->query('//d:response') as $response) {
            $href = $response->getElementsByTagNameNS('DAV:', 'href')->item(0)->nodeValue;
            $entry = ['etag' => null, 'calendarData' => null];
            foreach ($xpath->query('./d:propstat/d:prop', $response) as $prop) {
                if ($n = $prop->getElementsByTagNameNS('DAV:', 'getetag')->item(0)) {
                    $entry['etag'] = $n->nodeValue;
                }
                if ($n = $prop->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav', 'calendar-data')->item(0)) {
                    $entry['calendarData'] = $n->nodeValue;
                }
            }
            $responses[$href] = $entry;
        }

        return ['syncToken' => $newSyncToken, 'responses' => $responses];
    }

    /**
     * build a Thunderbird-like PUT body: the full series plus a declined
     * exception for the second occurrence of the given recurring event
     *
     * @param string $calendarData
     * @param Tinebase_Model_FullUser $user
     * @return string
     */
    protected function _declineRecurInstance($calendarData, $user)
    {
        $vobject = \Sabre\VObject\Reader::read($calendarData);
        $base = null;
        foreach ($vobject->VEVENT as $vevent) {
            if ($vevent->RRULE) {
                $base = $vevent;
                break;
            }
        }
        $this->assertNotNull($base, 'expected a recurring VEVENT in the calendar-data');

        $day = new DateInterval('P1D');
        $exception = clone $base;
        $vobject->add($exception);
        $exception->remove('RRULE');
        $exception->DTSTART->setDateTime($base->DTSTART->getDateTime()->add($day));
        $exception->DTEND->setDateTime($base->DTEND->getDateTime()->add($day));
        $dtstartParams = [];
        foreach ($exception->DTSTART->parameters() as $param) {
            $dtstartParams[$param->name] = $param->getValue();
        }
        $exception->add('RECURRENCE-ID', $exception->DTSTART->getValue(), $dtstartParams);

        // decline the own attender
        $identifiers = array_filter([$user->accountEmailAddress, (string) $user->contact_id]);
        $declined = false;
        foreach ($exception->select('ATTENDEE') as $attendee) {
            $value = (string) $attendee->getValue();
            foreach ($identifiers as $identifier) {
                if (str_contains($value, $identifier)) {
                    $attendee['PARTSTAT'] = 'DECLINED';
                    $declined = true;
                    break 2;
                }
            }
        }
        $this->assertTrue($declined, 'the own attender was not found in the VEVENT');

        // thunderbird also refreshes DTSTAMP and bumps SEQUENCE on the series
        $sequence = ((int) ($base->SEQUENCE ? $base->SEQUENCE->getValue() : 0)) + 1;
        $base->DTSTAMP = gmdate('Ymd\\THis\\Z');
        $base->SEQUENCE = (string) $sequence;
        $exception->DTSTAMP = gmdate('Ymd\\THis\\Z');
        $exception->SEQUENCE = (string) $sequence;

        return $vobject->serialize();
    }
}
