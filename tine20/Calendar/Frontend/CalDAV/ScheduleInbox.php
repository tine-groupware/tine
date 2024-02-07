<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle schedule inbox in CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_CalDAV_ScheduleInbox extends \Sabre\DAV\Collection implements \Sabre\DAV\IProperties, \Sabre\DAVACL\IACL, \Sabre\CalDAV\ICalendar
{
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    /**
     * @var Calendar_Controller_MSEventFacade
     */
    protected $_controller;
    
    const NAME='inbox';
    
    public function __construct($_userId)
    {
        $this->_user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : Tinebase_User::getInstance()->get($_userId);
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        try {
            $event = $_name instanceof Calendar_Model_Event ? $_name : $this->_getController()->get($this->_getIdFromName($_name));
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new \Sabre\DAV\Exception\NotFound('Object not found');
        }
        
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee);
        $displayContainer = $ownAttendee->displaycontainer_id instanceof Tinebase_Model_Container ? 
            $ownAttendee->displaycontainer_id :
            Tinebase_Container::getInstance()->get($ownAttendee->displaycontainer_id);
        
        return new Calendar_Frontend_WebDAV_Event($displayContainer, $event);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * NOTE: We have not found a client which uses the schedule inbox yet.
     * NOTE: The inbox concept seems not to fit the Tine 2.0 concept where
     *       invitations are directly stored in a display calendar.
     *       => to be on the safe side we don't use inbox yet
     * 
     * @see Sabre\DAV\Collection::getChildren()
     */
    function getChildren()
    {
        return array();
        
//         $filter = new Calendar_Model_EventFilter(array(
//             array(
//                 'field'     => 'attender',
//                 'operator'  => 'equals',
//                 'value'     => array(
//                     'user_type' => Calendar_Model_Attender::USERTYPE_USER,
//                     'user_id'   => $this->_user->contact_id,
//                 )
//             ),
//             array(
//                 'field'     => 'container_id',
//                 'operator'  => 'equals',
//                 'value'     => array(
//                     'path' => '/personal/' . $this->_user->getId()
//                 )
//             ),
//             array(
//                 'field'     => 'attender_status',
//                 'operator'  => 'equals',
//                 'value'     => Calendar_Model_Attender::STATUS_NEEDSACTION
//             ),
//             array(
//                 'field'     => 'period',
//                 'operator'  => 'within',
//                 'value'     => array(
//                     'from'    => Tinebase_DateTime::now()->subWeek(1),
//                     'until'   => Tinebase_DateTime::now()->addYear(10),
//                 )
//             ),
//         ));
        
//         $objects = $this->_getController()->search($filter);
//         $ownAttendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
//         foreach ($object as $event) {
//             $ownAttendee->addRecord(Calendar_Model_Attender::getOwnAttender($event->attendee));
//         }
//         Tinebase_Container::getInstance()->getGrantsOfRecords($ownAttendee, $this->_user, 'displaycontainer_id');
        
//         $children = array();
    
//         foreach ($objects as $object) {
//             $children[] = $this->getChild($object);
//         }
    
//         return $children;
    }
    
    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup() 
    {
        return null;
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @return string|null
     */
    public function getOwner() 
    {
        return 'principals/users/' . $this->_user->contact_id;
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $properties = array(
            // static ctag as this inbox implementation actually does nothing, it is a dummy
            '{http://calendarserver.org/ns/}getctag' => 'samesame',
            'id'                => 'schedule-inbox',
            'uri'               => 'schedule-inbox',
            '{DAV:}resource-id'    => 'urn:uuid:schedule-inbox',
            '{DAV:}owner'       => new \Sabre\DAVACL\Property\Principal(Sabre\DAVACL\Property\Principal::HREF, 'principals/users/' . $this->_user->contact_id),
            #'principaluri'      => $principalUri,
            '{DAV:}displayname' => 'Schedule Inbox',
            '{http://apple.com/ns/ical/}calendar-color' => '#666666',
            // static sync-token, only -1 works!
            '{DAV:}sync-token'  => Tinebase_WebDav_Plugin_SyncToken::SYNCTOKEN_PREFIX . '-1',
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new \Sabre\CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-data'          => new \Sabre\CalDAV\Property\SupportedCalendarData(),
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-description'             => 'Calendar schedule inbox',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-timezone'                => Tinebase_WebDav_Container_Abstract::getCalendarVTimezone('Calendar')
        );
    
        $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $this->_user->getId());
        if (!empty($defaultCalendarId)) {
            $properties['{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}schedule-default-calendar-URL'] = new \Sabre\DAV\Property\Href('calendars/' . $this->_user->contact_id . '/' . $defaultCalendarId);
        }
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $properties['{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set'    ] = new \Sabre\DAV\Property\HrefList(array('mailto:' . Tinebase_Core::getUser()->accountEmailAddress), false);
        }
    
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
    
        return $response;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL() 
    {
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            )
        );
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) 
    {
        throw new \Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');
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
     * @param array $mutations
     * @return bool|array
     */
    public function updateProperties($mutations)
    {
        return false;
    }
    
    /**
     * get controller
     * 
     * @return Calendar_Controller_MSEventFacade
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Calendar_Controller_MSEventFacade::getInstance();
        }
        
        return $this->_controller;
    }
    
    /**
     * get id from name => strip of everything after last dot
     * 
     * @param  string  $_name  the name for example vcard.vcf
     * @return string
     */
    protected function _getIdFromName($_name)
    {
        $id = ($pos = strrpos($_name, '.')) === false ? $_name : substr($_name, 0, $pos);
        
        return $id;
    }
    
    public function calendarQuery(array $filters)
    {
        return array();
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }

    /**
     * indicates whether the concrete class supports sync-token
     *
     * @return bool
     */
    public function supportsSyncToken()
    {
        return true;
    }

    /**
     * returns the changes happened since the provided syncToken which is the content sequence
     *
     * @param string $syncToken
     * @return array
     */
    public function getChanges($syncToken)
    {
        return array(
            'syncToken' => '-1',
            Tinebase_Model_ContainerContent::ACTION_CREATE => array(),
            Tinebase_Model_ContainerContent::ACTION_UPDATE => array(),
            Tinebase_Model_ContainerContent::ACTION_DELETE => array(),
        );
    }
}
