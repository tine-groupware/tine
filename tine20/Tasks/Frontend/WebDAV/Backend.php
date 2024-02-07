<?php
/**
 * Tine 2.0
 * @package     Tasks
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Tasks
 * @subpackage  Frontend
 * @todo fix namespace - move to Tasks/Frontend/CalDAV ?
 * @todo refactor: why does this return calendars/events??
 */
class Tasks_Frontend_CalDAV_Backend extends Sabre\CalDAV\Backend\AbstractBackend
{
    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is 
     *    accessed.
     *  * principalUri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'. 
     *
     * @param string $principalUri 
     * @return array 
     */
    public function getCalendarsForUser($principalUri)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $principalUri: ' . $principalUri);
        
        $principalParts = explode('/', $principalUri);
        
        if (count($principalParts) == 2) {
            $owner = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $principalParts[1]);
            $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), Calendar_Model_Event::class, $owner, Tinebase_Model_Grants::GRANT_READ);
            $containers->sort('name');
        } else {
            throw new Sabre\DAV\Exception\PreconditionFailed('unsupported pricipalUri');
        }
        
        $calendars = array();
//        $sessionUriMap = (array) Tinebase_Session::getSessionNamespace()->CalDAVUriMap;
        
        foreach($containers as $container) {
            $containerId = $container->getId();
//            $uri = array_search($containerId, $sessionUriMap);
            
            $calendars[] = array(
                'id'                => $containerId,
                'uri'               => /*$uri ? $uri : */$containerId,
                'principaluri'      => $principalUri,
                '{DAV:}displayname' => $container->name,
                '{http://apple.com/ns/ical/}calendar-color' => $container->color,
                '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre\CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            );
        }
        
        return $calendars;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar.
     *
     * This function must return a server-wide unique id that can be used 
     * later to reference the calendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string|int 
     */
    public function createCalendar($principalUri,$calendarUri,array $properties)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('createCalendar');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $principalUri: ' . $principalUri . ' $calendarUri: ' . $calendarUri . ' $properties' . print_r($properties, TRUE));
        
        
        // NOTE: at the moment we only support a predefined set of colors
//        if ((isset($properties['{http://apple.com/ns/ical/}calendar-color']) || array_key_exists('{http://apple.com/ns/ical/}calendar-color', $properties))) {
//            $color = substr($properties['{http://apple.com/ns/ical/}calendar-color'], 0, 7);
//            $container->color = $color;
//        }
        
        $principalParts = explode('/', $principalUri);
        
        if (count($principalParts) == 2) {
            $container = new Tinebase_Model_Container(array(
                'name'              => $properties['{DAV:}displayname'],
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'owner_id'          => Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountLoginName', $principalParts[1]),
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'backend'           => 'Sql',
                'model'             => Calendar_Model_Event::class,
            ));
            
            $container = Tinebase_Container::getInstance()->addContainer($container);
        } else {
            throw new Sabre\DAV\Exception\PreconditionFailed('unsupported pricipalUri');
        }
        
        return $container->getId();
    }

    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existant property is always succesful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname. 
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param string $calendarId
     * @param array $properties
     * @return bool|array 
     */
    public function updateCalendar($calendarId, array $properties)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('updateCalendar');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $calendarId: ' . $calendarId . ' $properties' . print_r($properties, TRUE));
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            if ((isset($properties['{DAV:}displayname']) || array_key_exists('{DAV:}displayname', $properties))) {
                Tinebase_Container::getInstance()->setContainerName($calendarId, $properties['{DAV:}displayname']);
            }
            
            // NOTE: at the moment we only support a predefined set of colors
//            if ((isset($properties['{http://apple.com/ns/ical/}calendar-color']) || array_key_exists('{http://apple.com/ns/ical/}calendar-color', $properties))) {
//                $color = substr($properties['{http://apple.com/ns/ical/}calendar-color'], 0, 7);
//                Tinebase_Container::getInstance()->setContainerColor($calendarId, $color);
//            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            return false;
        }
        
        return true; 
    }

    /**
     * Delete a calendar and all it's objects 
     * 
     * @param string $calendarId 
     * @return void
     */
    public function deleteCalendar($calendarId)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('deleteCalendar');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $calendarId: ' . $calendarId);
        
        Tinebase_Container::getInstance()->deleteContainer($calendarId);
    }

    /**
     * Modificar
     * Returns all calendar objects within a calendar object.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calnedar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     * 
     * @param string $calendarId 
     * @return array 
     */
    public function getCalendarObjects($calendarId)
    {
        $calendarObjects = array();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $calendarId: ' . $calendarId);

        $events = Calendar_Controller_MSEventFacade::getInstance()->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $calendarId),
        )));
        
        foreach($events as $event) {
            $calendarObjects[] = $this->_convertCalendarObject($event);
        }
        
        return $calendarObjects;
    }

    /**
     * Returns information from a single calendar object, based on it's object uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return array 
     */
    public function getCalendarObject($calendarId,$objectUri)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $calendarId: ' . $calendarId . ' $objectUri: ' . $objectUri);
        
        try {
            $event = Calendar_Controller_MSEventFacade::getInstance()->get($objectUri);
            return $this->_convertCalendarObject($event);
        } catch (Tinebase_Exception_NotFound $e) {
            return array();
        }
    }

    /**
     * converts events to calendar objects
     * 
     * @param Calendar_Model_Event $event (from MSFacade atm.)
     */
    protected function _convertCalendarObject($event)
    {
        $eventId = $event->getId();
        $lastModified = $event->last_modified_time ? $event->last_modified_time : $event->creation_time;

        $converter = new Calendar_Convert_Event_VCalendar_Tine();
        $vcalendar = $converter->fromTine20Model($event);

        return array(
            'id'            => $eventId,
            'uri'           => $eventId,
            'lastmodified'  => $lastModified->getTimeStamp(),
            'calendardata'  => $vcalendar->serialize(),
        );
    }
    
    /**
     * Creates a new calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function createCalendarObject($calendarId,$objectUri,$calendarData)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' $calendarId: ' . $calendarId . ' $objectUri: ' . $objectUri. ' $calendarData: '. print_r($calendarData, TRUE));
        
        throw new Sabre\DAV\Exception\MethodNotAllowed('createCalendarObject');
    }

    /**
     * Updates an existing calendarobject, based on it's uri. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @param string $calendarData 
     * @return void
     */
    public function updateCalendarObject($calendarId,$objectUri,$calendarData)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('updateCalendarObject');
    }

    /**
     * Deletes an existing calendar object. 
     * 
     * @param string $calendarId 
     * @param string $objectUri 
     * @return void
     */
    public function deleteCalendarObject($calendarId,$objectUri)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('deleteCalendarObject');
    }
}
