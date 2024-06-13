<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar VCALENDAR converter factory class
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar2_Factory
{
    public const CLIENT_GENERIC     = 'generic';

    /**
     * cache parsed user-agent strings
     */
    static protected  array $_parsedUserAgentCache = [];

    static public function factory(string $_backend, ?string $_version = null): Calendar_Convert_Event_VCalendar2_Interface
    {
        switch ($_backend) {
            case Calendar_Convert_Event_VCalendar2_Factory::CLIENT_GENERIC:
                return new Calendar_Convert_Event_VCalendar2_Generic($_version);
        }
        throw new Tinebase_Exception_NotImplemented('backend ' . $_backend . ' is not implemented in ' . __CLASS__);
    }

    static public function parseUserAgent(string $_userAgent = ''): array
    {
        if (isset(self::$_parsedUserAgentCache[$_userAgent])) {
            return self::$_parsedUserAgentCache[$_userAgent];
        }

        $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC;
        $version = null;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $_userAgent ->  backend: $backend version: $version");
        
        self::$_parsedUserAgentCache[$_userAgent] = $result = [$backend, $version];
        
        return $result;
    }
}
