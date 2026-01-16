<?php

use Sabre\CalDAV\CalendarQueryValidator;
use Sabre\CalDAV\ICalendarObject;
use Sabre\CalDAV\ICalendarObjectContainer;
use Sabre\DAV\Exception\BadRequest;

/**
 * Tine 2.0
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2015-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Paul Mehrer <p.mehrer@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_FixMultiGet404Plugin extends Sabre\CalDAV\Plugin
{
    protected $_fakeEvent = null;
    protected $_calBackend = null;

    public static $currentCalendarQueryReportRequest = null;

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param \Sabre\CalDAV\Xml\Request\CalendarMultiGetReport $dom
     * @return void
     */
    public function calendarMultiGetReport($report)
    {
        $needsJson = 'application/calendar+json' === $report->contentType;

        $timeZones = [];
        $propertyList = [];

        $paths = array_map(
            [$this->server, 'calculateUri'],
            $report->hrefs
        );

        foreach ($paths as $uri) {
            try {
                $objProps = $this->server->getPropertiesForPath($uri, $report->properties)[0];
                if (($needsJson || $report->expand) && isset($objProps[200]['{'.self::NS_CALDAV.'}calendar-data'])) {
                    $vObject = \Sabre\VObject\Reader::read($objProps[200]['{'.self::NS_CALDAV.'}calendar-data']);

                    if ($report->expand) {
                        // We're expanding, and for that we need to figure out the
                        // calendar's timezone.
                        list($calendarPath) = \Sabre\Uri\split($uri);
                        if (!isset($timeZones[$calendarPath])) {
                            // Checking the calendar-timezone property.
                            $tzProp = '{'.self::NS_CALDAV.'}calendar-timezone';
                            $tzResult = $this->server->getProperties($calendarPath, [$tzProp]);
                            if (isset($tzResult[$tzProp])) {
                                // This property contains a VCALENDAR with a single
                                // VTIMEZONE.
                                $vtimezoneObj = \Sabre\VObject\Reader::read($tzResult[$tzProp]);
                                $timeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                            } else {
                                // Defaulting to UTC.
                                $timeZone = new DateTimeZone('UTC');
                            }
                            $timeZones[$calendarPath] = $timeZone;
                        }

                        $vObject = $vObject->expand($report->expand['start'], $report->expand['end'], $timeZones[$calendarPath]);
                    }
                    if ($needsJson) {
                        $objProps[200]['{'.self::NS_CALDAV.'}calendar-data'] = json_encode($vObject->jsonSerialize());
                    } else {
                        $objProps[200]['{'.self::NS_CALDAV.'}calendar-data'] = $vObject->serialize();
                    }
                    // Destroy circular references so PHP will garbage collect the
                    // object.
                    $vObject->destroy();
                }
            } catch (Sabre\DAV\Exception\NotFound) {

                try {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' returning fake properties for:' . $uri);

                    // return fake events properties
                    $node = $this->_getFakeEventFacade($uri);
                    $objProps = $this->_getFakeProperties($uri, $node, $report->properties);

                } catch (Tinebase_Exception_NotFound) {
                    $objProps = ['href' => $uri];
                }
            }
            $propertyList[] = $objProps;
        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->generateMultiStatus($propertyList, 'minimal' === $prefer['return']));
    }

    public function generateMultiStatus(array $fileProperties, bool $strip404s = false)
    {
        $w = $this->server->xml->getWriter();
        $w->openMemory();
        $this->writeMultiStatus($w, $fileProperties, $strip404s);

        return $w->outputMemory();
    }

    private function writeMultiStatus(\Sabre\Xml\Writer $w, array $fileProperties, bool $strip404s)
    {
        $w->contextUri = $this->server->getBaseUri();
        $w->startDocument();

        $w->startElement('{DAV:}multistatus');

        foreach ($fileProperties as $entry) {
            $href = $entry['href'];
            unset($entry['href']);
            $status = empty($entry) ? 404 : null;
            if ($strip404s) {
                unset($entry[404]);
            }
            $response = new \Sabre\DAV\Xml\Element\Response(
                ltrim($href, '/'),
                $entry,
                $status
            );
            $w->write([
                'name' => '{DAV:}response',
                'value' => $response,
            ]);
        }
        $w->endElement();
        $w->endDocument();
    }

    /**
     * @param string $path
     * @param Calendar_Frontend_WebDAV_Event $node
     * @param array $properties
     * @return array
     */
    protected function _getFakeProperties($path, $node, $properties)
    {
        $newProperties = ['href' => trim($path,'/')];

        if (count($properties) === 0) {
            // Default list of propertyNames, when all properties were requested.
            $properties = array(
                '{DAV:}getlastmodified',
                '{DAV:}getcontentlength',
                '{DAV:}resourcetype',
                '{DAV:}quota-used-bytes',
                '{DAV:}quota-available-bytes',
                '{DAV:}getetag',
                '{DAV:}getcontenttype',
            );
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' requested fake properties:' . print_r($properties, true));

        foreach ($properties as $prop) {
            switch($prop) {
                case '{DAV:}getetag'               : if ($node instanceof Sabre\DAV\IFile && $etag = $node->getETag())  $newProperties[200][$prop] = $etag; break;
                case '{DAV:}getcontenttype'        : if ($node instanceof Sabre\DAV\IFile && $ct = $node->getContentType())  $newProperties[200][$prop] = $ct; break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-data':
                                                     if ($node instanceof Sabre\CalDAV\ICalendarObject) {
                                                         $val = $node->get();
                                                         if (is_resource($val))
                                                             $val = stream_get_contents($val);
                                                         $newProperties[200][$prop] = str_replace("\r","", $val);
                                                         break;
                                                     }
                                                     // don't break here!
                /** DO NOT ADD A CASE HERE, WE FALL THROUGH IN THE ABOVE CASE! */
                default:
                    $newProperties[404][$prop] = null;
                    break;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' returning fake properties:' . print_r($newProperties, true));

        return $newProperties;
    }

    /**
     * @param string $path
     * @return Calendar_Frontend_WebDAV_Event
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getFakeEventFacade($path)
    {
        $path = rtrim($path,'/');
        $parentPath = explode('/', $path);

        $id = array_pop($parentPath);
        if (($icsPos = stripos($id, '.ics')) !== false) {
            $id = substr($id, 0, $icsPos);
        }

        $parentPath = join('/', $parentPath);
        /** @var Calendar_Frontend_WebDAV_Event $parentNode */
        $parentNode = $this->server->tree->getNodeForPath($parentPath);

        if (null === $this->_fakeEvent) {
            $this->_fakeEvent = new Calendar_Model_Event(
                array(
                    'originator_tz'     => 'UTC',
                    'creation_time'     => '1976-06-06 06:06:06',
                    'dtstart'           => '1977-07-07 07:07:07',
                    'dtend'             => '1977-07-07 07:14:07',
                    'summary'           => '-',
                ), true);

            $this->_calBackend = new Calendar_Backend_Sql(Tinebase_Core::getDb());
        }

        list($id, $seq) = $this->_calBackend->getIdSeq($id, $parentNode->getId());
        $this->_fakeEvent->setId($id);
        $this->_fakeEvent->seq = $seq;

        return new Calendar_Frontend_WebDAV_Event($parentNode->getContainer(), $this->_fakeEvent);
    }

    public function getCalendarHomeForPrincipal($principalUrl)
    {
        $parts = explode('/', trim($principalUrl, '/'));
        if (($count = count($parts)) < 2 || 'principals' !== $parts[0]) {
            return;
        }

        return self::CALENDAR_ROOT . '/' . $parts[$count-1];
    }

    /**
     * This function handles the calendar-query REPORT.
     *
     * This report is used by clients to request calendar objects based on
     * complex conditions.
     *
     * @param Calendar_Frontend_CalDAV_CalendarQueryReport $report
     */
    public function calendarQueryReport($report)
    {
        static::$currentCalendarQueryReportRequest = $report;

        $path = $this->server->getRequestUri();

        // we dont support that $needsJson = 'application/calendar+json' === $report->contentType;
        if ('application/calendar+json' === $report->contentType) {
            throw new BadRequest('application/calendar+json is not supported');
        }

        $node = $this->server->tree->getNodeForPath($path);
        $depth = $this->server->getHTTPDepth(0);

        // The default result is an empty array
        $result = [];

        // The calendarobject was requested directly. In this case we handle
        // this locally.
        if (0 === $depth && $node instanceof ICalendarObject) {

            $parentPath = explode('/', $path);
            array_pop($parentPath);
            $parentPath = join('/', $parentPath);
            $parentNode = $this->server->tree->getNodeForPath($parentPath);
            if ($parentNode instanceof ICalendarObjectContainer && ($nodePaths = $parentNode->calendarQuery($report->filters)) &&
                    in_array($node->getName(), $nodePaths)) {

                $properties = $this->server->getPropertiesForPath(
                    $path,
                    $report->properties,
                    0
                );

                // This array should have only 1 element, the first calendar
                // object.
                $properties = current($properties);

                $result = [$properties];
            }
        }

        if ($node instanceof ICalendarObjectContainer && 0 === $depth) {
            if (0 === strpos((string) $this->server->httpRequest->getHeader('User-Agent'), 'MSFT-')) {
                // Microsoft clients incorrectly supplied depth as 0, when it actually
                // should have set depth to 1. We're implementing a workaround here
                // to deal with this.
                //
                // This targets at least the following clients:
                //   Windows 10
                //   Windows Phone 8, 10
                $depth = 1;
            } else {
                throw new BadRequest('A calendar-query REPORT on a calendar with a Depth: 0 is undefined. Set Depth to 1');
            }
        }

        // If we're dealing with a calendar, the calendar itself is responsible
        // for the calendar-query.
        if ($node instanceof ICalendarObjectContainer && 1 === $depth) {
            $nodePaths = $node->calendarQuery($report->filters);

            foreach ($nodePaths as $path) {
                list($properties) =
                    $this->server->getPropertiesForPath($this->server->getRequestUri().'/'.$path, $report->properties);

                $result[] = $properties;
            }
        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, 'minimal' === $prefer['return']));
    }

    public function initialize(\Sabre\DAV\Server $server)
    {
        parent::initialize($server);

        $server->xml->elementMap['{'.self::NS_CALDAV.'}calendar-query'] = Calendar_Frontend_CalDAV_CalendarQueryReport::class;
    }
}