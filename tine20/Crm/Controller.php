<?php
/**
 * MAIN controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * leads controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * default settings
     * 
     * @var array
     */
    protected $_defaultsSettings = array(
        'leadstate_id'  => 1,
        'leadtype_id'   => 1,
        'leadsource_id' => 1,
    );
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Crm_Model_Lead';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Crm';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Crm_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }
        
        return static::$_instance;
    }    
        
    /********************* event handler and personal folder ***************************/
    
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
                    $this->deletePersonalFolder($_eventObject->account, Crm_Model_Lead::class);
                }
                break;
        }
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
            static::$_defaultModel,
            'Crm',
            $_accountId
        );

        return new Tinebase_Record_RecordSet(Tinebase_Model_Container::class, array($personalContainer));
    }

    public static function tasksMCHookFun(array &$fields, Tinebase_ModelConfiguration $mc): void
    {
        if (!in_array(Crm_Model_Lead::class, $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS])) {
            $fields['source_model'][TMCC::CONFIG][TMCC::AVAILABLE_MODELS][] = Crm_Model_Lead::class;
        }
        $filterModels = $mc->filterModel;
        if (!isset($filterModels['source~' . Crm_Model_Lead::class])) {
            $filterModels['source~' . Crm_Model_Lead::class] = [
                TMCC::FILTER         => Tinebase_Model_Filter_ForeignId::class,
                TMCC::OPTIONS => [
                    TMCC::CONTROLLER    => Crm_Controller_Lead::class,
                    TMCC::FILTER_GROUP  => Crm_Model_Lead::class,
                    TMCC::FIELD         => 'source'
                ],
            ];
            $mc->setFilterModel($filterModels);
        }
    }
}
