<?php
/**
 * CalDAV plugin for calendar-auto-schedule
 * 
 * This plugin provides functionality added by RFC6638
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/rfc6638
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginAutoSchedule extends \Sabre\DAV\ServerPlugin {

    /**
     * Reference to server object
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-auto-schedule');

    }

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() {

        return 'caldavAutoSchedule';

    }

    /**
     * Initializes the plugin 
     * 
     * @param \Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(\Sabre\DAV\Server $server) {

        $this->server = $server;
        $server->on('propFind', [$this, 'propFind']);
        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        // auto-scheduling extension
        array_push($server->protectedProperties,
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-default-calendar-URL',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-tag'
        );
    }

    public function propFind(\Sabre\DAV\PropFind $propFind, \Sabre\DAV\INode $node)
    {
        if ($node instanceof \Sabre\DAVACL\IPrincipal) {
            // schedule-inbox-URL property
            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-inbox-URL', function () use ($node) {
                $principalId = $node->getName();
                return new \Sabre\DAV\Xml\Property\Href(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/inbox');
            });
            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-outbox-URL', function () use ($node) {
                $principalId = $node->getName();
                return new \Sabre\DAV\Xml\Property\Href(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/outbox');
            });
        }
    }
}
