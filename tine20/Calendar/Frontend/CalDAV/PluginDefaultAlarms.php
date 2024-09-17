<?php
/**
 * CalDAV plugin for draft-daboo-valarm-extensions-04
 * 
 * This plugin provides functionality added by RFC6638
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/draft-daboo-valarm-extensions-04
 *
 * NOTE: At the moment we disable all default alarms as iCal shows alarms
 *       for events having no alarm. Acknowliging this alarms may lead to problems.
 *       
 * NOTE: iCal Montain Lion & Mavericks sets default alarms for the whole account, 
 *       but respects when we set default alarms per calendar. 
 *       
 *       So in future we might disable default alarms for shared cals and
 *       use the default alarms configured for each personal cal.
 *       
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginDefaultAlarms extends \Sabre\DAV\ServerPlugin
{
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
    public function getFeatures() 
    {
        return array('calendar-default-alarms');
    }

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
        return 'calendarDefaultAlarms';
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
        $server->on('propFind', [$this, 'propFind']);
        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
    }

    public function propFind(\Sabre\DAV\PropFind $propFind, \Sabre\DAV\INode $node)
    {
        if ($node instanceof \Sabre\CalDAV\ICalendar || $node instanceof Calendar_Frontend_WebDAV) {
            $vcalendar = new \Sabre\VObject\Component\VCalendar();

            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-datetime', function() {

                // ?!? what is this?
                $valarm = (new \Sabre\VObject\Component\VCalendar())->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     'E35C3EB2-4DC1-4223-AA5D-B4B491F2C111');
                
                // Taking out \r to not screw up the xml output
                return str_replace("\r","", $valarm->serialize());
            });

            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vevent-date', function() {

                // ?!? what is this?
                $valarm = (new \Sabre\VObject\Component\VCalendar())->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     '17DC9682-230E-47D6-A035-EEAB602B1229');
                
                // Taking out \r to not screw up the xml output
                return str_replace("\r","", $valarm->serialize());
            });

            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-datetime', function() {

                // ?!? what is this?
                $valarm = (new \Sabre\VObject\Component\VCalendar())->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     'D35C3EB2-4DC1-4223-AA5D-B4B491F2C111');
                
                // Taking out \r to not screw up the xml output
                return str_replace("\r","", $valarm->serialize());
            });

            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}default-alarm-vtodo-date', function() {

                // ?!? what is this?
                $valarm = (new \Sabre\VObject\Component\VCalendar())->create('VALARM');
                $valarm->add('ACTION',  'NONE');
                $valarm->add('TRIGGER', '19760401T005545Z', array('VALUE' => 'DATE-TIME'));
                $valarm->add('UID',     '27DC9682-230E-47D6-A035-EEAB602B1229');
                
                // Taking out \r to not screw up the xml output
                return str_replace("\r","", $valarm->serialize());
            });
        }
    }
}
