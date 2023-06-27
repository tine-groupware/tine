<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * Projects Controller (composite)
 * 
 * The Projects 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Projects
 * @subpackage  Controller
 */
class Projects_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Projects';
    }
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Projects_Model_Project';
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds self
     * @var Projects_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Projects_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Projects_Controller();
        }
        return self::$_instance;
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed $_account
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function createPersonalFolder($_account)
    {
        $personalContainer = Tinebase_Container::getInstance()->createDefaultContainer(
            static::$_defaultModel,
            $this->_applicationName,
            $_account
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
                    $this->deletePersonalFolder($_eventObject->account, Projects_Model_Project::class);
                }
                break;
        }
    }

    public static function tasksMCHookFun(array &$fields, Tinebase_ModelConfiguration $mc): void
    {
        if (!in_array(Projects_Model_Project::class, $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS])) {
            $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS][] = Projects_Model_Project::class;
        }
        $filterModels = $mc->filterModel;
        if (!isset($filterModels['source:' . Projects_Model_Project::class])) {
            $filterModels['source:' . Projects_Model_Project::class] = [
                TMCC::FILTER         => Tinebase_Model_Filter_ForeignId::class,
                TMCC::LABEL          => 'source:' . Projects_Model_Project::class,
                TMCC::OPTIONS => [
                    TMCC::CONTROLLER    => Projects_Controller_Project::class,
                    TMCC::FILTER_GROUP  => Projects_Model_Project::class,
                    TMCC::FIELD         => 'source'
                ],
            ];
            $mc->setFilterModel($filterModels);
        }
    }
}
