<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sabre speedup plugin
 *
 * This plugin prefetches data in ONE request, to speedup multiget report
 *
 * @package     Calendar
 * @subpackage  Frontend
 */

class Calendar_Frontend_CalDAV_SpeedUpPlugin extends \Sabre\DAV\ServerPlugin 
{
    /**
     * Reference to server object 
     * 
     * @var \Sabre\DAV\Server 
     */
    private $server;
    
    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    public function getSupportedReportSet($uri) 
    {
        $node = $this->server->tree->getNodeForPath($uri);

        $reports = array();
        if ($node instanceof \Sabre\CalDAV\ICalendar || $node instanceof \Sabre\CalDAV\ICalendarObject) {
            $reports[] = '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-multiget';
        }
        
        return $reports;
    }

    
    /**
     * Initializes the plugin 
     * 
     * @param \Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(\Sabre\DAV\Server $server) 
    {
        $this->server = $server;
        
        $server->on('report', [$this, 'report'], 0);
    }

    public function report(string $reportName, mixed $multiGetReport)
    {
        if ($multiGetReport instanceof \Sabre\CalDAV\Xml\Request\CalendarMultiGetReport) {
            $this->calendarMultiGetReport($multiGetReport);
        }
    }
    
    /**
     * prefetch events into calendar container class, to avoid single lookup of events
     */
    public function calendarMultiGetReport(\Sabre\CalDAV\Xml\Request\CalendarMultiGetReport $multiGetReport)
    {
        $filters = [
            'name'         => 'VCALENDAR',
            'comp-filters' => [[
                'name'         => 'VEVENT',
                'prop-filters' => [],
            ]],
        ];

        foreach($multiGetReport->hrefs as $href) {
            list(, $baseName) = \Tinebase_WebDav_XMLUtil::splitPath($href);
            
            $filters['comp-filters'][0]['prop-filters'][] = array(
                'name' => 'UID',
                'text-match' => array(
                    'value' => $baseName
                )
            );
        };

        /** @var Calendar_Frontend_WebDAV_Container $node */
        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        Calendar_Frontend_CalDAV_FixMultiGet404Plugin::$currentCalendarQueryReportRequest = null;
        $node->calendarQuery($filters);
    }
}
