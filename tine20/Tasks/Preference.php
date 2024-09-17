<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tasks preferences
 *
 * @package     Tasks
 * @subpackage  Preference
 */
class Tasks_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * have name of default favorite on a central place
     * _("All my tasks")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All my tasks";
    
    /**
     * default task list where all new tasks are placed in
     */
    const DEFAULTTASKLIST = 'defaultTaskList';

    /**
     * enable default alarm
     */
    const DEFAULTALARM_ENABLED = 'defaultalarmenabled';

    /**
     * default alarm time in minutes before
     */
    const DEFAULTALARM_MINUTESBEFORE = 'defaultalarmminutesbefore';

    const TASK_TITLE_TEMPLATE = 'taskTitleTemplate';

    /**
     * @var string application
     */
    protected $_application = 'Tasks';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTPERSISTENTFILTER,
            self::DEFAULTTASKLIST,
            self::DEFAULTALARM_ENABLED,
            self::DEFAULTALARM_MINUTESBEFORE,
            self::TASK_TITLE_TEMPLATE,
        );
            
        return $allPrefs;
    }
    
    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications preferences
     */
    public function getTranslatedPreferences()
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);

        $prefDescriptions = array(
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on Tasks startup'),
            ),
            self::DEFAULTTASKLIST  => array(
                'label'         => $translate->_('Default Task List'),
                'description'   => $translate->_('The default task list to create new tasks in.'),
            ),
            self::DEFAULTALARM_ENABLED => array(
                'label'         => $translate->_('Enable Standard Alarm'),
                'description'   => $translate->_('New task get a standard alarm as defined below'),
            ),
            self::DEFAULTALARM_MINUTESBEFORE => array(
                'label'         => $translate->_('Standard Alarm Time'),
                'description'   => $translate->_('Minutes before the task ends'),
            ),
            self::TASK_TITLE_TEMPLATE => [
                'label'         => $translate->_('Task Title Template'),
                'description'   => $translate->_('How to build task titles.'),
            ],
        );
        
        return $prefDescriptions;
    }
    
    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @param string|Tinebase_Model_User $_accountId
     * @param string $_accountType
     * @return Tinebase_Model_Preference
     */
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Tasks', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                break;
            case self::DEFAULTTASKLIST:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                $preference->uiconfig = [self::CLIENT_NEEDS_RELOAD => true];
                break;
            case self::DEFAULTALARM_ENABLED:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::DEFAULTALARM_MINUTESBEFORE:
                $preference->value      = 15;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>5</label>
                            <value>5</value>
                        </option>
                        <option>
                            <label>10</label>
                            <value>10</value>
                        </option>
                        <option>
                            <label>15</label>
                            <value>15</value>
                        </option>
                        <option>
                            <label>20</label>
                            <value>20</value>
                        </option>
                        <option>
                            <label>30</label>
                            <value>30</value>
                        </option>
                        <option>
                            <label>60</label>
                            <value>60</value>
                        </option>
                    </options>';
                break;
            case self::TASK_TITLE_TEMPLATE:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                $preference->uiconfig = [
                    self::CLIENT_NEEDS_RELOAD => true
                ];
                $preference->value      = '{{ summary }}';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>{{ summary }}</value>
                            <label>'. $translate->_('Summary') . '</label>
                        </option>
                        <option>
                            <value>{% if source %}({{ renderModel(source_model) }}: {{ renderTitle(source, source_model) }}) {% endif %}{{ summary }}</value>
                            <label>'. $translate->_('(source): Summary') . '</label>
                        </option>
                    </options>';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
}
