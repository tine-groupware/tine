<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */


/**
 * backend for Timetracker preferences
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Timetracker_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * use tine user credentials for imap connection
     *
     */
    const TSODSEXPORTCONFIG = 'tsOdsExportConfig';

    /**
     * Quicktag
     */
    const QUICKTAG = 'quickTag';

    /**
     * application
     *
     * @var string
     */
    protected $_application = 'Timetracker';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::TSODSEXPORTCONFIG,
            self::QUICKTAG
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
            self::TSODSEXPORTCONFIG  => array(
                'label'         => $translate->_('Timesheets ODS export configuration'),
                'description'   => $translate->_('Use this configuration for the timesheet ODS export.'),
            ),
            self::QUICKTAG => array(
                'label'         => $translate->_('A Tag which is available in the context menu for fast assignment'),
                'description'   => $translate->_('Quick Tag allows you to simply assign a predefined tag by the context menu.')
            )
        );
        
        return $prefDescriptions;
    }
    
    /**
     * get preference defaults if no default is found in the database
     *
     * @param string $_preferenceName
     * @return Tinebase_Model_Preference
     */
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId = NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        
        switch($_preferenceName) {
            case self::TSODSEXPORTCONFIG:
                $preference->value      = 'ts_default_ods';
                break;
            case self::QUICKTAG:
                $preference->value      = 'false';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * get special options
     *
     * @param string $_value
     * @return array
     */
    protected function _getSpecialOptions($_value, $_accountId = null)
    {
        $translate = Tinebase_Translation::getTranslation($this->_application);
        
        $result = array();
        switch($_value) {
            case self::TSODSEXPORTCONFIG:
                // get names from import export definitions
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, array(
                    array('field' => 'plugin', 'operator' => 'equals', 'value' => 'Timetracker_Export_Ods_Timesheet'),
                ));
                $configs = Tinebase_ImportExportDefinition::getInstance()->search($filter);
                if (! empty($configs)) {
                    foreach($configs as $tsConfig) {
                        $result[] = array($tsConfig->name, $tsConfig->name);
                    }
                } else {
                    $result[] = array('default', $translate->_('default'));
                }
                break;

            case self::QUICKTAG:
                // Get all shared tags
                $tagController = Tinebase_Tags::getInstance();
                $filter = new Tinebase_Model_TagFilter(array(
                    'type' => Tinebase_Model_Tag::TYPE_SHARED,
                ));
                $tags = $tagController->searchTags($filter);

                $availableTags = array();

                /* @var $tag Tinebase_Model_Tag */
                foreach($tags as $tag) {
                    $availableTags[] = array($tag->id, $tag->name);
                }

                return $availableTags;
                break;
            default:
                $result = parent::_getSpecialOptions($_value, $_accountId);
        }
        
        return $result;
    }
}
