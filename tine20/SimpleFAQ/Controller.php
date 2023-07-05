<?php
/**
 * Main controller for SimpleFAQ application
 * 
 * the main logic of the SimpleFAQ application
 *
 * @package     SimpleFAQ
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * faq controller Class for SimpleFAQ application
 *
 * @package     SimpleFAQ
 * @subpackage  Controller
 */
Class SimpleFAQ_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * default settings
     *
     * @var array
     */
    protected $_defaultsSettings = array (
        'faqstatus_id'  => 1,
        'faqtype_id'    => 2,
    );
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'SimpleFAQ_Model_Faq';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function  __construct()
    {
        $this->_applicationName = 'SimpleFAQ';
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
     * @var SimpleFAQ_Controller
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return SimpleFAQ_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new SimpleFAQ_Controller;
        }

        return self::$_instance;
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
                    $this->deletePersonalFolder($_eventObject->account, SimpleFAQ_Model_Faq::class);
                }
                break;
        }
    }

    /**
     * creates the initial folder for new accounts
     *
     * @param mixed $_accountId
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function createPersonalFolder($_accountId)
    {
        $personalContainer = Tinebase_Container::getInstance()->createDefaultContainer(
            static::$_defaultModel,
            'SimpleFAQ',
            $_accountId
        );

        return new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
    }

    /**
     * Returns settings for SimpleFAQ app
     * - result is cached
     *
     * @param boolean $_resolve if some values should be resolved (here yet unused)
     * @return  SimpleFAQ_Model_Config
     *
     */
    public function getConfigSettings($_resolve = FALSE)
    {
        
        $cache = Tinebase_Core::get('cache');
        $cacheId = Tinebase_Helper::convertCacheId('getSimpleFAQSettings');
        $result = $cache->load($cacheId);
        
        if (! $result) {

            $translate = Tinebase_Translation::getTranslation('SimpleFAQ');

            $result = new SimpleFAQ_Model_Config(array(
                'defaults' => parent::getConfigSettings()
            ));
            $others = array(
                SimpleFAQ_Model_Config::FAQSTATUSES => array(
                    array('id' => 1, 'faqstatus' => $translate->_('Draft')),
                    array('id' => 2, 'faqstatus' => $translate->_('released')),
                    array('id' => 3, 'faqstatus' => $translate->_('obsolete'))
                ),
                SimpleFAQ_Model_Config::FAQTYPES => array(
                    array('id' => 1, 'faqtype' => $translate->_('Internal')),
                    array('id' => 2, 'faqtype' => $translate->_('Public')),
                )
            );
            foreach ($others as $setting => $defaults) {
                $result->$setting = SimpleFAQ_Config::getInstance()->get($setting, new Tinebase_Config_Struct($defaults))->toArray();
            }

            // save result and tag it with 'settings'
            $cache->save($result, $cacheId, array('settings'));
        }

        return $result;
    }

    /**
     * save SimpleFAQ settings
     *
     * @param SimpleFAQ_Model_Config $_settings
     * @return SimpleFAQ_Model_Config
     *
     * @todo generalize this
     */
    public function saveConfigSettings($_settings)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Updating SimpleFAQ Settings: ' . print_r($_settings->toArray(), TRUE));

        foreach ($_settings->toArray() as $field => $value) {
            if ($field == 'id') {
                continue;
            } else if ($field == 'defaults') {
                parent::saveConfigSettings($value);
            } else {
                SimpleFAQ_Config::getInstance()->set($field, $value);
            }
        }

        // invalidate cache
        Tinebase_Core::get('cache')->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('settings'));

        return $this->getConfigSettings();
    }
}