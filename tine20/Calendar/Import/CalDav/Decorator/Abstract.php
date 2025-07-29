<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */


abstract class Calendar_Import_CalDav_Decorator_Abstract
{
    protected $client;
    
    public function __construct($client)
    {
        $this->client = $client;
    }
    
    public function preparefindAllCalendarsRequest($request)
    {
        return $request;
    }
    
    public function processAdditionalCalendarProperties(array &$calendar, array $response) {}
    
    public function initCalendarImport(array $options = []) {}
    
    public function setCalendarProperties(Tinebase_Model_Container $calendarContainer, array $calendar)
    {
        if (isset($calendar['color']) && $calendarContainer->color !== $calendar['color']) {
            $calendarContainer->color = $calendar['color'];
            Tinebase_Container::getInstance()->update($calendarContainer);
        }
    }
}