<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Calendar_Import_CalDav_Decorator_MacOSX extends Calendar_Import_CalDav_Decorator_Abstract
{
    public function preparefindAllCalendarsRequest($request)
    {
        $doc = new DOMDocument();
        $doc->loadXML($request);
        //$bulk = $doc->createElementNS('http://me.com/_namespace/', 'osxme:bulk-requests');
        $color = $doc->createElementNS('http://apple.com/ns/ical/', 'osxical:calendar-color');
        $prop = $doc->getElementsByTagNameNS('DAV:', 'prop')->item(0);
        //$prop->appendChild($bulk);
        $prop->appendChild($color);
        return $doc->saveXML();
    }
    
    public function processAdditionalCalendarProperties(array &$calendar, array $response)
    {
        if (isset($response['{http://apple.com/ns/ical/}calendar-color'])) {
            $calendar['color'] = $response['{http://apple.com/ns/ical/}calendar-color'];
            // cut off last two digits as this contains the alpha channel
            if (strlen((string)$calendar['color']) == 9) {
                $calendar['color'] = substr($calendar['color'], 0, 7);
            }
        }
    }
    
    public function initCalendarImport(array $options = [])
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';
    }
}