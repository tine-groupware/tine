<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Sabre\CalDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\DateTimeParser;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

class Calendar_Frontend_CalDAV_CalendarQueryFilterCalendarData implements XmlDeserializable
{
    public static function xmlDeserialize(Reader $reader)
    {
        $result = [
            'contentType' => $reader->getAttribute('content-type') ?: 'text/calendar',
            'version' => $reader->getAttribute('version') ?: '2.0',
        ];

        $elems = (array) $reader->parseInnerTree();
        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{'.Plugin::NS_CALDAV.'}expand':
                    $result['expand'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end' => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];

                    if (!$result['expand']['start'] || !$result['expand']['end']) {
                        throw new BadRequest('The "start" and "end" attributes are required when expanding calendar-data');
                    }
                    if ($result['expand']['end'] <= $result['expand']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date when expanding calendar-data');
                    }
                    break;

                case '{'.Plugin::NS_CALDAV.'}limit-recurrence-set':
                    $result['limitRecurrenceSet'] = [
                        'start' => isset($elem['attributes']['start']) ? DateTimeParser::parseDateTime($elem['attributes']['start']) : null,
                        'end' => isset($elem['attributes']['end']) ? DateTimeParser::parseDateTime($elem['attributes']['end']) : null,
                    ];

                    if (!$result['limitRecurrenceSet']['start'] || !$result['limitRecurrenceSet']['end']) {
                        throw new BadRequest('The "start" and "end" attributes are required when limiting recurrence set in calendar-data');
                    }
                    if ($result['limitRecurrenceSet']['end'] <= $result['limitRecurrenceSet']['start']) {
                        throw new BadRequest('The end-date must be larger than the start-date when limiting recurrence set in calendar-data');
                    }
                    break;
            }
        }

        return $result;
    }
}
