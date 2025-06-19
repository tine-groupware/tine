<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * tasks vtodo convert factory class
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_Factory
{
    const CLIENT_GENERIC     = 'generic';
    const CLIENT_IPHONE      = 'iphone';
    const CLIENT_KDE         = 'kde';
    const CLIENT_MACOSX      = 'macosx';
    const CLIENT_THUNDERBIRD = 'thunderbird';
    const CLIENT_EMCLIENT    = 'emclient';
    const CLIENT_EVOLUTION   = 'evolution';
    const CLIENT_CALDAVSYNCHRONIZER = 'caldavsynchronizer';
    const CLIENT_DAVDROID    = 'davdroid';
    
    /**
     * cache parsed user-agent strings
     * 
     * @var array
     */
    static protected $_parsedUserAgentCache = array();
    
    /**
     * factory function to return a selected vcalendar backend class
     *
     * @param   string $_backend
     * @param   string $_version
     * @return  Tinebase_Convert_Interface
     */
    static public function factory($_backend, $_version = null)
    {
        switch ($_backend) {
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC:
                return new Tasks_Convert_Task_VCalendar_Generic($_version);
                             
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_IPHONE:
                return new Tasks_Convert_Task_VCalendar_Iphone($_version);
                                
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_KDE:
                return new Tasks_Convert_Task_VCalendar_KDE($_version);
                
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_MACOSX:
                return new Tasks_Convert_Task_VCalendar_MacOSX($_version);
                
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_THUNDERBIRD:
                return new Tasks_Convert_Task_VCalendar_Thunderbird($_version);
                 
            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT:
                return new Tasks_Convert_Task_VCalendar_EMClient($_version);

            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_EVOLUTION:
                 return new Tasks_Convert_Task_VCalendar_Evolution($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_CALDAVSYNCHRONIZER:
                return new Tasks_Convert_Task_VCalendar_CalDAVSynchronizer($_version);

            case Tasks_Convert_Task_VCalendar_Factory::CLIENT_DAVDROID:
                return new Tasks_Convert_Task_VCalendar_DavDroid($_version);
        }
	return new Tasks_Convert_Task_VCalendar_Generic($_version);
    }
    
    /**
     * parse iseragent and return backend and version
     * 
     * @return array
     */
    static public function parseUserAgent($_userAgent)
    {
        if (isset(self::$_parsedUserAgentCache[$_userAgent])) {
            return self::$_parsedUserAgentCache[$_userAgent];
        }
        
        // MacOS X
        if (preg_match(Tasks_Convert_Task_VCalendar_MacOSX::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_MACOSX;
            $version = $matches['version'];

        // iPhone
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_Iphone::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_IPHONE;
            $version = $matches['version'];

        // KDE
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_KDE::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_KDE;
            $version = $matches['version'];

        // Thunderbird
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_Thunderbird::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_THUNDERBIRD;
            $version = $matches['version'];

        // EMClient       
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_EMClient::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_EMCLIENT;
            $version = $matches['version'];

        // CalDAVSynchronizer
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_CalDAVSynchronizer::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_CALDAVSYNCHRONIZER;
            $version = $matches['version'];

        // DavDroid
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_DavDroid::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_DAVDROID;
            $version = $matches['version'];

         // Evolution       
        } elseif (preg_match(Tasks_Convert_Task_VCalendar_Evolution::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_EVOLUTION;
            $version = $matches['version'];
        
        } else {
            $backend = Tasks_Convert_Task_VCalendar_Factory::CLIENT_GENERIC;
            $version = null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $_userAgent ->  backend: $backend version: $version");
        
        self::$_parsedUserAgentCache[$_userAgent] = array($backend, $version);
        
        return array($backend, $version);
    }
}
