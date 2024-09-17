<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sabre speedup plugin for propfind
 *
 * This plugin checks if all properties requested by propfind can be served with one single query.
 *
 * @package     Calendar
 * @subpackage  Frontend
 */

class Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin extends \Sabre\DAV\ServerPlugin
{
    /**
     * Reference to server object
     *
     * @var \Sabre\DAV\Server
     */
    private $server;

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'speedUpPropfindPlugin';
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

        $server->on('method:PROPFIND', [$this, 'propfind'], -100);
        //$server->on('report', [$this, 'report'], -100);
    }

    /*public function report(string $reportName, mixed $queryReport): ?bool
    {
        if ($this->server->httpRequest->getHeader('Depth') !== '1' ||
                !$queryReport instanceof \Sabre\CalDAV\Xml\Request\CalendarQueryReport) {
            return null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " in report speedup");

        if (count($queryReport->properties) != 2 || !in_array('{DAV:}getetag', $queryReport->properties) || !in_array('{DAV:}getcontenttype',$queryReport->properties)) {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " requested properties dont match speedup conditions, continuing");
            return null;
        }

        if (($queryReport->filters['name'] ?? null) !== 'VCALENDAR' &&
                ($queryReport->filters['name'] ?? null) !== 'VTODO') {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " requested properties dont match speedup conditions, continuing");
            return null;
        }
        return $this->_speedUpRequest($path);
    }*/

    public function propfind(\Sabre\HTTP\RequestInterface $request, \Sabre\HTTP\ResponseInterface $response): bool
    {
        if ($request->getHeader('Depth') !== '1') {
            return true;
        }

        /**
         * @var Calendar_Frontend_WebDAV_Container
         */
        $node = $this->server->tree->getNodeForPath($request->getPath());
        if (!($node instanceof Calendar_Frontend_WebDAV_Container) ) {
            return true;
        }

        $requestBody = $request->getBodyAsString();
        if (is_resource($body = $request->getBody())) {
            rewind($body);
        };

        if (strlen($requestBody)) {
            try {
                if (! ($propFindXml = $this->server->xml->expect('{DAV:}propfind', $requestBody)) instanceof \Sabre\DAV\Xml\Request\PropFind) {
                    return true;
                }
            } catch (\Sabre\Xml\ParseException $e) {
                throw new \Sabre\DAV\Exception\BadRequest($e->getMessage(), 0, $e);
            }
        } else {
            return true;
        }


        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " in propfind speedup");

        if (count($propFindXml->properties) != 2 || !in_array('{DAV:}getetag', $propFindXml->properties) || !in_array('{DAV:}getcontenttype',$propFindXml->properties)) {

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " requested properties dont match speedup conditions, continuing");

            return true;
        }

        return $this->_speedUpRequest($node, $request->getPath());
    }

    protected function _speedUpRequest(Calendar_Frontend_WebDAV_Container $node, string $uri): bool
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup sql start");

        $db = Tinebase_Core::getDb();

        $stmt = $db->query('SELECT ev.id, ev.seq, ev.base_event_id FROM ' . SQL_TABLE_PREFIX . 'cal_events AS ev WHERE ev.is_deleted = 0 AND ' .
            /*ev.recurid IS NULL AND*/' (ev.container_id = ' . $db->quote($node->getId()) . ' OR ev.id IN (
            SELECT cal_event_id FROM ' . SQL_TABLE_PREFIX . 'cal_attendee WHERE displaycontainer_id = ' . $db->quote($node->getId()) . '))');

        $result = $stmt->fetchAll();

        $baseEvents = [];
        array_walk($result, function($val) use(&$baseEvents) {
            if (empty($val['base_event_id']) || $val['base_event_id'] === $val['id']) {
                $baseEvents[$val['id']] = $val;
            }
        });
        array_walk($result, function($val) use(&$baseEvents) {
            if (!empty($val['base_event_id']) && !isset($baseEvents[$val['base_event_id']])) {
                $baseEvents[$val['id']] = $val;
            }
        });

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup sql done");

        $result = [];
        foreach ($baseEvents as $row) {
            $result[] = [
                'href' => $uri . '/' . $row['id'] . '.ics',
                200 => [
                    '{DAV:}getetag' => '"' . sha1($row['id'] . $row['seq']) . '"',
                    '{DAV:}getcontenttype' => 'text/calendar',
                ],
            ];
        }

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " speedup successfully responded to request");

        return false;
    }
}