<?php
/**
 * CalDAV plugin for caldav-privateevents extension
 *
 * see: http://svn.calendarserver.org/repository/calendarserver/CalendarServer/trunk/doc/Extensions/caldav-privateevents.txt
 *
 * NOTE: Handling of X-CALENDARSERVER-ACCESS property is done in the converter
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Cornelius Weiss <c.weiss@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginPrivateEvents extends \Tine20\DAV\ServerPlugin
{
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
    public function getFeatures()
    {
        return array('calendarserver-private-events');
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Tine20\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'calendarPrivateEvents';
    }

    /**
     * Initializes the plugin
     *
     * @param \Tine20\DAV\Server $server
     * @return void
     */
    public function initialize(\Tine20\DAV\Server $server)
    {
        $this->server = $server;
    }
}