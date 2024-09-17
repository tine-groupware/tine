<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Inventory Controller (composite)
 * 
 * The Inventory 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Inventory
 * @subpackage  Controller
 */
class Inventory_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Inventory_Model_InventoryItem';

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Inventory';
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }
    
    /**
     * holds self
     * @var Inventory_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Inventory_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Inventory_Controller();
        }
        return self::$_instance;
    }

    /**
     * creates the initial folder for new accounts
     *
     * @param string|Tinebase_Model_User $_accountId the account object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $personalContainer = Tinebase_Container::getInstance()->createDefaultContainer(
            'Inventory_Model_InventoryItem',
            'Inventory',
            $_accountId
        );

        return new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Tinebase_Event_User_DeleteAccount':
                /**
                 * @var Tinebase_Event_User_DeleteAccount $_eventObject
                 */
                if ($_eventObject->deletePersonalContainers()) {
                    $this->deletePersonalFolder($_eventObject->account, Inventory_Model_InventoryItem::class);
                }
                break;
        }
    }

    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => Inventory_Model_Type::class,
            'application_id' => $application,
            'model' => Inventory_Model_Type::class,
            'label' => 'Types' // _('Types')
        )));
        
        return $result;
    }
}
