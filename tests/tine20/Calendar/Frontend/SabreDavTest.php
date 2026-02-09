<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Calendar_Frontend_SabreDavTest extends Calendar_TestCase
{
    protected \Sabre\DAV\Server $server;

    public function setUp(): void
    {
        parent::setUp();

        $this->server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());
        $this->server->httpResponse = new Tinebase_WebDav_Sabre_ResponseMock();

        // rw cal agent
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';
    }

    public function testPutExistingEvent(): void
    {
        $event = Calendar_Controller_Event::getInstance()->create($this->_getEvent(true));
        $this->assertNull($event->etag);
        $container = Tinebase_Container::getInstance()->get($event->getIdFromProperty('container_id'));

        $uri = '/calendars/' . Tinebase_Core::getUser()->contact_id . '/' . $container->getId() . '/' . $event->getId();
        $request = new Sabre\HTTP\Request('PUT', $uri, [
            'If-Match' => (new Calendar_Frontend_WebDAV_Event($container, $event))->getETag()
        ]);
        $request->setBody((new Calendar_Convert_Event_VCalendar_Generic)->fromTine20Model($event)->serialize());

        $this->server->httpRequest = $request;
        $this->server->exec();

        static::assertSame(204, $this->server->httpResponse->getStatus(), $this->server->httpResponse->getBody() ?? '');
    }
}