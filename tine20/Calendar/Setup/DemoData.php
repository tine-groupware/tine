<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Calendar initialization
 *
 * @package     Setup
 */
class Calendar_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Calendar_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * 
     * required apps
     * @var array
     */
    protected static $_requiredApplications = array('Admin');
    
    /**
     * models to work on
     * 
     * @var array
     */
    protected $_models = array('event');

    /**
     * the event controller
     * 
     * @var Calendar_Controller_Event
     */
    protected $_controller = NULL;
    
    /**
     * private calendars
     * 
     * @var Array
     */
    protected $_calendars = array();

    /**
     * @var Tinebase_Model_Container|null
     */
    protected ?Tinebase_Model_Container $sharedCalendar = null;

    protected $_ressources = [];

    protected $sharedEventsData = [];

    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_controller = Calendar_Controller_Event::getInstance();
        $this->_controller->sendNotifications(false);
    }

    /**
     * the singleton pattern
     *
     * @return Calendar_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Setup_DemoData;
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Calendar_Controller_Event::getInstance();
        
        $f = new Calendar_Model_EventFilter(array(
            array('field' => 'summary', 'operator' => 'equals', 'value' => 'Meeting for further education'),
            array('field' => 'summary', 'operator' => 'equals', 'value' => 'Fortbildungsveranstaltung')
        ), 'OR');
        
        return ($c->search($f)->count() > 0) ? true : false;
    }
    
    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate() {

        foreach ($this->_personas as $loginName => $persona) {
            $this->_calendars[$loginName] = Tinebase_Container::getInstance()->getContainerById(Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $persona->getId()));

            if (isset($this->_personas['sclever'])) {
                Tinebase_Container::getInstance()->addGrants(
                    $this->_calendars[$loginName]->getId(),
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    $this->_personas['sclever']->getId(),
                    $this->_secretaryGrants,
                    true
                );
            }
            if (isset($this->_personas['rwright'])) {
                Tinebase_Container::getInstance()->addGrants(
                    $this->_calendars[$loginName]->getId(),
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    $this->_personas['rwright']->getId(),
                    $this->_controllerGrants,
                    true);
            }

            Tinebase_Container::getInstance()->addGrants(
                $this->_calendars[$loginName]->getId(),
                Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
                $this->_adminGrants,
                true);
        }
    }

    public function getSharedCalendar()
    {
        return $this->sharedCalendar;
    }

    protected function _createFloorPlanConfig()
    {
        Calendar_Config::getInstance()->{Calendar_Config::FLOORPLANS} = [[
            'name' => 'Second Floor',
            'image' => 'https://tine-docu.s3web.rz1.metaways.net/images/Calendar/Floorplans/second_floor.svg',
            'resources' => [[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_1',
                'resourceDisplayName' => 'Table 1',
                'polygon' => [[ [677, 88], [677, 120], [742, 120], [742, 88]]] // or path
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_2',
                'resourceDisplayName' => 'Table 2',
                'polygon' => [[[677, 124], [677, 156], [742, 156], [742, 124]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_3',
                'resourceDisplayName' => 'Table 3',
                'polygon' => [[[677, 210], [677, 243], [742, 243], [742, 210]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_4',
                'resourceDisplayName' => 'Table 4',
                'polygon' => [[[677, 246], [677, 279], [742, 279], [742, 246]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_5',
                'resourceDisplayName' => 'Table 5',
                'polygon' => [[[677, 327], [677, 359], [742, 359], [742, 327]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_6',
                'resourceDisplayName' => 'Table 6',
                'polygon' => [[[677, 363], [677, 395], [742, 395], [742, 363]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_7',
                'resourceDisplayName' => 'Table 7',
                'polygon' => [[[677, 471], [677, 504], [742, 504], [742, 471]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'og_2_table_8',
                'resourceDisplayName' => 'Table 8',
                'polygon' => [[[677, 507], [677, 540], [742, 540], [742, 507]]]
            ]],
            'referenceImageDim' => [[1000, 1353]]
        ],[
            'name' => 'First Floor',
            'image' => 'https://tine-docu.s3web.rz1.metaways.net/images/Calendar/Floorplans/basement.svg',
            'resources' => [[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_1',
                'resourceDisplayName' => 'Table 1',
                'polygon' => [[[677, 246], [677, 279], [742, 279], [742, 246]]] // or path
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_2',
                'resourceDisplayName' => 'Table 2',
                'polygon' => [[[677, 282], [677, 315], [742, 315], [742, 282]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_3',
                'resourceDisplayName' => 'Table 3',
                'polygon' => [[[677, 393], [677, 425], [742, 425], [742, 393]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_4',
                'resourceDisplayName' => 'Table 4',
                'polygon' => [[[677, 429], [677, 461], [742, 461], [742, 429]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_5',
                'resourceDisplayName' => 'Table 5',
                'polygon' => [[[677, 554], [677, 586], [742, 586], [742, 554]]]
            ],[
                'eventSaveLocation' => 'RESOURCE_CAL',
                'resourceName' => 'eg_table_6',
                'resourceDisplayName' => 'Table 6',
                'polygon' => [[[677, 590], [677, 622], [742, 622], [742, 590]]]
            ]],
            'referenceImageDim' => [[1000, 1000]]
        ]];
    }
    /**
     * creates a shared calendar
     */
    private function _createSharedCalendar()
    {
        // create shared calendar
        $this->sharedCalendar = $this->_createTestCal(
            static::$_de ? 'Gemeinsamer Kalender' : 'Shared Calendar',
            Tinebase_Model_Container::TYPE_SHARED
        );

        $group = Tinebase_Group::getInstance()->getDefaultGroup();
        Tinebase_Container::getInstance()->addGrants($this->sharedCalendar->getId(), 'group', $group->getId(), $this->_userGrants, true);
        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($this->sharedCalendar->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }

        // create some resorces as well
        $this->_ressources = array();
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => static::$_de ? 'Besprechnungsraum Mars (1.OG)' : 'Meeting Room Mars (first floor)',
            'description'          => static::$_de ? 'Bis zu 10 Personen' : 'Up to 10 people',
            'email'                => 'mars@tin20.com',
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::RESOURCE_READ => true,
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => static::$_de ? 'Besprechnungsraum Venus (2.OG)' : 'Meeting Room Venus (second floor)',
            'description'          => static::$_de ? 'Bis zu 14 Personen' : 'Up to 14 people',
            'email'                => 'venus@tin20.com',
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::RESOURCE_READ => true,
            ]]
        )));
        // Creating resource for the tables
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_1',
            'description'          => 'EG Table 1',
            'email'                => 'eg_table_1@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_2',
            'description'          => 'EG Table 2',
            'email'                => 'eg_table_2@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_3',
            'description'          => 'EG Table 3',
            'email'                => 'eg_table_3@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_4',
            'description'          => 'EG Table 4',
            'email'                => 'eg_table_4@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_5',
            'description'          => 'EG Table 5',
            'email'                => 'eg_table_5@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'eg_table_6',
            'description'          => 'EG Table 6',
            'email'                => 'eg_table_6@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_1',
            'description'          => 'OG 2 Table 1',
            'email'                => 'og_2_table_1@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_2',
            'description'          => 'OG 2 Table 2',
            'email'                => 'og_2_table_2@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_3',
            'description'          => 'OG 2 Table 3',
            'email'                => 'og_2_table_3@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_4',
            'description'          => 'OG 2 Table 4',
            'email'                => 'og_2_table_4@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_5',
            'description'          => 'OG 2 Table 5',
            'email'                => 'og_2_table_5@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_6',
            'description'          => 'OG 2 Table 6',
            'email'                => 'og_2_table_6@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_7',
            'description'          => 'OG 2 Table 7',
            'email'                => 'og_2_table_7@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));
        $this->_ressources[] = Calendar_Controller_Resource::getInstance()->create(new Calendar_Model_Resource(array(
            'name'                 => 'og_2_table_8',
            'description'          => 'OG 2 Table 8',
            'email'                => 'og_2_table_8@tin20.com',
            'busy_type'            => Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE,
            'grants'               => [[
                'account_id'      => Tinebase_Core::getUser()->getId(),
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Calendar_Model_ResourceGrants::RESOURCE_ADMIN => true,
            ],[
                'account_id'      => 0,
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Calendar_Model_ResourceGrants::EVENTS_ADD => true,
                Calendar_Model_ResourceGrants::EVENTS_READ => true,
                Calendar_Model_ResourceGrants::EVENTS_DELETE => true,
                Calendar_Model_ResourceGrants::RESOURCE_INVITE => true,
                Calendar_Model_ResourceGrants::EVENTS_FREEBUSY => true
            ]]
        )));

    }
    
    /**
     * creates shared events
     */
    protected function _createSharedEvents()
    {
        $this->_createSharedCalendar();

        $monday = new Tinebase_DateTime('monday');
        $tuesday = new Tinebase_DateTime('tuesday');
        $wednesday = new Tinebase_DateTime('wednesday');
        $thursday = new Tinebase_DateTime('thursday');
        $lastMonday = new Tinebase_DateTime('last monday');
        $lastFriday = new Tinebase_DateTime('last friday');

        $defaultAttendeeData = array(
            'quantity'  => "1",
            'role'  => "REQ",
            'status'  => "ACCEPTED",
            'transp'  => "OPAQUE",
            'user_type'  => "user"
        );
        $defaultData = array(
            'container_id' => $this->sharedCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        );
        $defaultData['attendee'] = array();
        foreach ($this->_personas as $persona) {
            $defaultData['attendee'][] = array_merge($defaultAttendeeData,
                array('user_id'  => $persona->toArray())
            );
        }

        $lastMonday->add(date_interval_create_from_date_string('20 weeks'));
        
        // shared events data
        $this->sharedEventsData = array(
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Mittagspause' : 'lunchtime',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 12:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 13:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Projektleitermeeting' : 'project leader meeting',
                    'description' => static::$_de ? 'Treffen aller Projektleiter' : 'meeting of all project leaders',
                    'dtstart'     => $monday->format('d-m-Y') . ' 14:15:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Geschäftsführerbesprechung' : 'CEO Meeting',
                    'description' => static::$_de ? 'Treffen aller Geschäftsführer' : 'Meeting of all CEO',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 12:30:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 13:45:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Fortbildungsveranstaltung' : 'Meeting for further education',
                    'description' => static::$_de ? 'Wie verhalte ich mich meinen Mitarbeitern gegenüber in Problemsituationen.' : 'How to manage problematic situations with the employees',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Projektbesprechung Alpha' : 'project meeting alpha',
                    'description' => static::$_de ? 'Besprechung des Projekts Alpha' : 'Meeting of the Alpha project',
                    'dtstart'     => $wednesday->format('d-m-Y') . ' 08:30:00',
                    'dtend'       => $wednesday->format('d-m-Y') . ' 09:45:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'WE',
                        'byday' => 'WE',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Projektbesprechung Beta' : 'project meeting beta',
                    'description' => static::$_de ? 'Besprechung des Projekts Beta' : 'Meeting of the beta project',
                    'dtstart'     => $wednesday->format('d-m-Y') . ' 10:00:00',
                    'dtend'       => $wednesday->format('d-m-Y') . ' 11:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'WE',
                        'byday' => 'WE',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Betriebsausflug' : 'company trip',
                    'description' => static::$_de ? 'Fahrt in die Semperoper nach Dresden' : 'Trip to the Semper Opera in Dresden',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 12:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 13:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Präsentation Projekt Alpha' : 'Presentation project Alpha',
                    'description' => static::$_de ? 'Das Projekt Alpha wird der Firma GammaTecSolutions vorgestellt' : 'presentation of Project Alpha for GammaTecSolutions',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 16:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 17:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => static::$_de ? 'Montagsmeeting' : 'monday meeting',
                    'description' => static::$_de ? 'Wöchentliches Meeting am Montag' : 'weekly meeting on monday',
                    'dtstart'     => $lastMonday->format('d-m-Y') . ' 10:00:00',
                    'dtend'       => $lastMonday->format('d-m-Y') . ' 12:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
        )),
        array_merge_recursive(
            $defaultData,
            array(
                'summary'     => static::$_de ? 'Freitagsmeeting' : 'friday meeting',
                'description' => static::$_de ? 'Wöchentliches Meeting am Freitag' : 'weekly meeting on friday',
                'dtstart'     => $lastFriday->format('d-m-Y') . ' 16:00:00',
                'dtend'       => $lastFriday->format('d-m-Y') . ' 17:30:00',
                'rrule' => array(
                    'freq' => 'WEEKLY',
                    'interval' => '1',
                    'count' => 20,
                    'wkst' => 'FR',
                    'byday' => 'FR',
                ),
            ))
        );

        // create shared events
        foreach($this->sharedEventsData as $eData) {
            $this->_createEvent($eData, false);
        }
    }

    /**
     * creates events for pwulf
     */
    protected function _createEventsForPwulf() {

        // Paul Wulf
        $monday = new Tinebase_DateTime('monday');
        $tuesday = new Tinebase_DateTime('tuesday');
        $thursday = new Tinebase_DateTime('thursday');
        $friday = new Tinebase_DateTime('friday');
        $saturday = new Tinebase_DateTime('saturday');
        $sunday = new Tinebase_DateTime('sunday');
        $lastMonday = new Tinebase_DateTime('last monday');
        $lastSaturday = new Tinebase_DateTime('last saturday');

        $cal = $this->_calendars['pwulf'];
        $user = $this->_personas['pwulf'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Lucy\'s Geburtstag' : 'Lucy\'s birthday',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Lucy\'s Geburtstagsfeier' : 'Lucy\'s birthday party',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'FR',
                        'byday' => 'FR',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Wettlauf mit Kevin' : 'Race with Kevin',
                    'description' => static::$_de ? 'Treffpunkt ist am oberen Parkplatz' : 'Meet at upper parking lot',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'SA',
                        'byday' => 'SA',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Schwimmen gehen' : 'go swimming',
                    'description' => '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Schwimmen gehen' : 'go swimming',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Auto aus der Werkstatt abholen' : 'fetch car from the garage',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Oper mit Lucy' : 'Got to the Opera with Lucy',
                    'description' => 'Brighton Centre',
                    'dtstart'     => $sunday->format('d-m-Y') . ' 20:00:00',
                    'dtend'       => $sunday->format('d-m-Y') . ' 21:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'SU',
                        'byday' => 'SU',
                    ),
                )
            ),

        );
        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

        $cal = $this->_createTestCal();

        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }
        if (isset($this->_personas['rwright'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);
        }

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Projektbesprechung Projekt Epsilon mit John' : 'Project Epsilon Meeting with John',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 08:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 09:30:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }
    }
    
    /**
     * creates events for jsmith
     */
    protected function _createEventsForJsmith() {

        // John Smith
        $monday = new Tinebase_DateTime('monday');
        $tuesday = new Tinebase_DateTime('tuesday');
        $thursday = new Tinebase_DateTime('thursday');
        $friday = new Tinebase_DateTime('friday');
        $saturday = new Tinebase_DateTime('saturday');
        $lastSaturday = new Tinebase_DateTime('last saturday');

        $cal = $this->_calendars['jsmith'];
        $user = $this->_personas['jsmith'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Catherine\'s Geburtstag' : 'Catherine\'s birthday',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false, "recurid" => null, "minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Elternabend Anne' : 'Talk to Ann\'s teacher',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'FR',
                        'byday' => 'FR',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'I-Phone vom I-Store abholen'  : 'Fetch Iphone from store',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'SA',
                        'byday' => 'SA',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Anne vom Sport abholen' : 'Pick up Ann after her sports lesson',
                    'description' => '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Paul vom Klavierunterricht abholen' : 'Pick up Paul after his piano lesson',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )
            ),

        );
        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

        $cal = $this->_createTestCal();

        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }

        if (isset($this->_personas['rwright'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);
        }

        $defaultEventData['container_id'] = $cal->getId();
        $defaultEventData['attendee'][] = array(
            'quantity'  => "1",
            'role'  => "REQ",
            'status'  => "NEEDS-ACTION",
            'transp'  => "OPAQUE",
            'user_id'  => $this->_personas['jsmith']->toArray(),
            'user_type'  => "user"
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Projektbesprechung Projekt Epsilon mit John' : 'Project Epsilon Meeting with John',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }
    }

    /**
     * creates events for rwright
     */
    protected function _createEventsForRwright() {
        // Roberta Wright
        $monday = new Tinebase_DateTime('monday');
        $tuesday = new Tinebase_DateTime('tuesday');
        $wednesday = new Tinebase_DateTime('wednesday');
        $friday = new Tinebase_DateTime('friday');
        $saturday = new Tinebase_DateTime('saturday');
        $lastSaturday = new Tinebase_DateTime('last saturday');

        $cal = $this->_calendars['rwright'];
        $user = $this->_personas['rwright'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Joshuas Geburtstag' : 'Joshua\'s Birthday',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'James Geburtstag' : 'James\'s Birthday',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Shoppen mit Susan' : 'Go shopping with Susan',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Joga Kurs' : 'yoga course',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 16:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        "bymonth" => $saturday->format('m'),
                        "bymonthday" => $saturday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Controlling einfach gemacht' : 'Controlling made easy',
                    'description' => static::$_de ? 'Fortbildungsveranstaltung' : 'further education',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            )
        );
        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

        $cal = $this->_createTestCal();

        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Präsentation Quartalszahlen' : 'presentation quarter figures',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Kostenstellenanalyse' : 'cost put analysis',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Controller Meeting' : 'Controllers meeting',
                    'description' => '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 12:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            ),
        );

        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

    }
    
    /**
     * creates events for sclever
     */
    protected function _createEventsForSclever() {
        // Susan Clever
        $monday = new Tinebase_DateTime('monday');
        $tuesday = new Tinebase_DateTime('tuesday');
        $wednesday = new Tinebase_DateTime('wednesday');
        $friday = new Tinebase_DateTime('friday');
        $saturday = new Tinebase_DateTime('saturday');
        $lastSaturday = new Tinebase_DateTime('last saturday');

        $cal = $this->_calendars['sclever'];
        $user = $this->_personas['sclever'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Elvis\' Geburtstag' : 'Elvis\' birthday',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'James Geburtstag' : 'James\'s Birthday',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Shoppen mit Roberta' : 'Go shopping with Roberta',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Shoppen gehen' : 'go shopping',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Shoppen gehen' : 'go shopping',
                    'description' => '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Tanzen gehen mit Elvis' : 'Dance with Elvis',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'FR',
                        'byday' => 'FR',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Disco Fever' : 'Disco fever',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'SA',
                        'byday' => 'SA',
                    ),
                )
            ),
        );
        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

    }

    protected function _createEventsForJmcblack() {

        // James McBlack
        $monday =       new Tinebase_DateTime('monday');
        $tuesday =      new Tinebase_DateTime('tuesday');
        $wednesday =    new Tinebase_DateTime('wednesday');
        $thursday =     new Tinebase_DateTime('thursday');
        $friday =       new Tinebase_DateTime('friday');
        $saturday =     new Tinebase_DateTime('saturday');

        $cal = $this->_calendars['jmcblack'];
        $user = $this->_personas['jmcblack'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Catherines Geburtstag' : 'Catherine\'s Birthday',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $tuesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $thursday->format('m'),
                        "bymonthday" => $thursday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Alyssas Geburtstag' : 'Alyssa\'s Birthday',
                    'description' => '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Brendas\' Geburtstag' : 'Brenda\'s Birthday',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $tuesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $thursday->format('m'),
                        "bymonthday" => $thursday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Automesse in Liverpool' : 'Auto fair in Liverpool',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Weinverkostung auf der Burg' : 'Wine tasting at the castle',
                    'description' => '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'SA',
                        'byday' => 'SA',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Eigentümerversammlung' : 'Owners\' meeting',
                    'description' => '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TU',
                        'byday' => 'TU',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Datamining Konferenz' : 'Data mining conference',
                    'description' => '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'TH',
                        'byday' => 'TH',
                    ),
                )
            )
        );
        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }

        $cal = $this->_createTestCal();

        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }
        if (isset($this->_personas['rwright'])) {
            Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);
        }

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Projektbesprechung Projekt Gamma mit Herrn Pearson' : 'Project Gamma Meeting with Mr. Pearson',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => 'MDH Pitch',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => static::$_de ? 'Mitarbeitergespräch mit Jack' : 'employee appraisal with Jack',
                    'description' => '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                )
            ),
        );

        foreach($eventsData as $eData) {
            $this->_createEvent($eData, false);
        }
    }

    protected function _createEvent($eData, $checkBusy = true)
    {
        $tz = static::$_de ? 'Europe/Berlin' : 'UTC';

        $eData['originator_tz'] = $tz;
        date_default_timezone_set($tz);
        $event = new Calendar_Model_Event($eData);
        $event->setTimezone('UTC');
        date_default_timezone_set('UTC');

        $this->_controller->create($event, $checkBusy);
    }

    protected function _createTestCal(?string $name = null, string $type = Tinebase_Model_Container::TYPE_PERSONAL): Tinebase_Model_Container
    {
        $name = $name ?: (static::$_de ? 'Geschäftlich' : 'Business');
        return Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $name,
            'type'           => $type,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00CCFF',
            'model'             => Calendar_Model_Event::class,
        ), true), null, false, true);
    }
}
