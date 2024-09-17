<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * Tasks Controller (composite)
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Tasks';
    }
    

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Tasks_Model_Task';
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds self
     * @var Tasks_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Tasks_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller();
        }
        return self::$_instance;
    }

    /**
     * temporary function to get a default container]
     *
     * @param string $_referingApplication
     * @return Tinebase_Model_Container container
     *
     * @throws Tinebase_Exception_NotFound
     * @todo replace this by Tinebase_Container::getDefaultContainer
     */
    public function getDefaultContainer($_referingApplication = 'tasks')
    {
        $taskConfig = Tasks_Config::getInstance();
        $configString = 'defaultcontainer_' . ( empty($_referingApplication) ? 'tasks' : $_referingApplication );
        
        if (isset($taskConfig->$configString)) {
            $defaultContainer = Tinebase_Container::getInstance()->getContainerById((int)$taskConfig->$configString);
        } else {
            $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer(Tasks_Model_Task::class,
                null, Tasks_Preference::DEFAULTTASKLIST);
        }
        
        return $defaultContainer;
    }
        
    /**
     * creates the initial folder for new accounts
     *
     * @param string|Tinebase_Model_User $_accountId the account object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $result = new Tinebase_Record_RecordSet(Tinebase_Model_Container::class);
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);
        $account = Tinebase_User::getInstance()->getUserById($_accountId);
        $name = sprintf($translation->_("%s's personal tasks"), $account->accountFullName);
        $container = Tinebase_Container::getInstance()->createDefaultContainer(
            static::$_defaultModel,
            $this->_applicationName,
            $account,
            $name
        );
        $result->addRecord($container);
        return $result;
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
                    $this->deletePersonalFolder($_eventObject->account, Tasks_Model_Task::class);
                }
                break;
        }
    }

    public static function timesheetMCHookFun(array &$fields, Tinebase_ModelConfiguration $mc): void
    {
        if (!in_array(Tasks_Model_Task::class, $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS])) {
            $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS][] = Tasks_Model_Task::class;
        }
        $filterModels = $mc->filterModel;
        if (!isset($filterModels['source:' . Tasks_Model_Task::class])) {
            $filterModels['source:' . Tasks_Model_Task::class] = [
                TMCC::FILTER         => Tinebase_Model_Filter_ForeignId::class,
                TMCC::OPTIONS => [
                    TMCC::CONTROLLER    => Tasks_Controller_Task::class,
                    TMCC::FILTER_GROUP  => Tasks_Model_Task::class,
                    TMCC::FIELD         => 'source'
                ],
            ];
            $mc->setFilterModel($filterModels);
        }
    }
}
