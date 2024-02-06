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
class Calendar_Frontend_CalDAV_PluginAutoSchedule extends \Tine20\DAV\ServerPlugin {

    /**
     * Reference to server object
     *
     * @var \Tine20\DAV\Server
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
     * using \Tine20\DAV\Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() {

        return 'caldavAutoSchedule';

    }

    /**
     * Initializes the plugin 
     * 
     * @param \Tine20\DAV\Server $server 
     * @return void
     */
    public function initialize(\Tine20\DAV\Server $server) {

        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));

        $server->xmlNamespaces[\Tine20\CalDAV\Plugin::NS_CALDAV] = 'cal';

        $server->resourceTypeMapping['\\Tine20\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        // auto-scheduling extension
        array_push($server->protectedProperties,
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}schedule-calendar-transp',
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}schedule-default-calendar-URL',
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}schedule-tag'
        );
    }
    
    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param \Tine20\DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, \Tine20\DAV\INode $node, &$requestedProperties, &$returnedProperties) {

        if ($node instanceof \Tine20\DAVACL\IPrincipal) {
            // schedule-inbox-URL property
            $scheduleProp = '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}schedule-inbox-URL';
            if (in_array($scheduleProp,$requestedProperties)) {
                $principalId = $node->getName();
                $outboxPath = \Tine20\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/inbox';

                unset($requestedProperties[array_search($scheduleProp, $requestedProperties)]);
                $returnedProperties[200][$scheduleProp] = new \Tine20\DAV\Property\Href($outboxPath);

            }
        }
    }
}
