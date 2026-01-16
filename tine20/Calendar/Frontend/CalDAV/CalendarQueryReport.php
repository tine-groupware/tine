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
use Sabre\Xml\Reader;

class Calendar_Frontend_CalDAV_CalendarQueryReport extends \Sabre\CalDAV\Xml\Request\CalendarQueryReport
{
    public $limitRecurrenceSet = null;

    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statically, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @return mixed
     */
    public static function xmlDeserialize(Reader $reader)
    {
        $elems = $reader->parseInnerTree([
            '{urn:ietf:params:xml:ns:caldav}comp-filter' => 'Sabre\\CalDAV\\Xml\\Filter\\CompFilter',
            '{urn:ietf:params:xml:ns:caldav}prop-filter' => 'Sabre\\CalDAV\\Xml\\Filter\\PropFilter',
            '{urn:ietf:params:xml:ns:caldav}param-filter' => 'Sabre\\CalDAV\\Xml\\Filter\\ParamFilter',
            '{urn:ietf:params:xml:ns:caldav}calendar-data' => Calendar_Frontend_CalDAV_CalendarQueryFilterCalendarData::class,
            '{DAV:}prop' => 'Sabre\\Xml\\Element\\KeyValue',
        ]);

        $newProps = [
            'filters' => null,
            'properties' => [],
        ];

        if (!is_array($elems)) {
            $elems = [];
        }

        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{DAV:}prop':
                    $newProps['properties'] = array_keys($elem['value']);
                    if (isset($elem['value']['{'.Plugin::NS_CALDAV.'}calendar-data'])) {
                        $newProps += $elem['value']['{'.Plugin::NS_CALDAV.'}calendar-data'];
                    }
                    break;
                case '{'.Plugin::NS_CALDAV.'}filter':
                    foreach ($elem['value'] as $subElem) {
                        if ($subElem['name'] === '{'.Plugin::NS_CALDAV.'}comp-filter') {
                            if (!is_null($newProps['filters'])) {
                                throw new BadRequest('Only one top-level comp-filter may be defined');
                            }
                            $newProps['filters'] = $subElem['value'];
                        }
                    }
                    break;
            }
        }

        if (is_null($newProps['filters'])) {
            throw new BadRequest('The {'.Plugin::NS_CALDAV.'}filter element is required for this request');
        }

        $obj = new self();
        foreach ($newProps as $key => $value) {
            $obj->$key = $value;
        }

        return $obj;
    }
}
