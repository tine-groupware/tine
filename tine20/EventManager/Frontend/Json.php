<?php
/**
 * Tine 2.0
 * 
 * @package     EventManager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *
 * This class handles all Json requests for the EventManager application
 *
 * @package     EventManager
 * @subpackage  Frontend
 */
class EventManager_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_configuredModels = [
        EventManager_Model_Event::MODEL_NAME_PART,
        EventManager_Model_Option::MODEL_NAME_PART,
        EventManager_Model_BookedOption::MODEL_NAME_PART,
        EventManager_Model_Registration::MODEL_NAME_PART,
        EventManager_Model_OptionLocalization::MODEL_NAME_PART,
        EventManager_Model_EventLocalization::MODEL_NAME_PART,
    ];
    
    /**
     * the constructor
     */
    public function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
    }
}
