<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Preference
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */


/**
 * backend for tinebase preferences
 *
 * @package     Tinebase
 * @subpackage  Preference
 */
class Tinebase_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * page size in grids
     * 
     */
    public const PAGE_SIZE = 'pageSize';
    
    /**
     * strip rows in grids
     * 
     */
    public const GRID_STRIPE_ROWS = 'gridStripeRows';
    
    /**
     * show load mask in grids
     * 
     */
    public const GRID_LOAD_MASK = 'gridLoadMask';

    /**
     * grid resizing strategy
     *
     */
    public const GRID_RESIZING_STRATEGY = 'gridResizingStrategy';

    /**
     * auto search on filter change
     * 
     */
    public const FILTER_CHANGE_AUTO_SEARCH = 'filterChangeAutoSearch';
   
    /**
     *  timezone pref const
     *
     */
    public const TIMEZONE = 'timezone';

    /**
     * locale pref const
     *
     */
    public const LOCALE = 'locale';
    
    /**
     * default application
     *
     */
    public const DEFAULT_APP = 'defaultapp';

    /**
     * preferred window type
     *
     */
    public const WINDOW_TYPE = 'windowtype';
    
    /**
     * show logout confirmation
     *
     */
    public const CONFIRM_LOGOUT = 'confirmLogout';

    /**
     * advanced search through relations and so on
     */
    public const ADVANCED_SEARCH = 'advancedSearch';

    /**
     * Preference for file row double click default action
     */
    public const FILE_DBLCLICK_ACTION = 'fileDblClickAction';
    
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = $this->_application === 'Tinebase' ? [
                self::TIMEZONE,
                self::LOCALE,
                self::DEFAULT_APP,
                self::WINDOW_TYPE,
                self::CONFIRM_LOGOUT,
                self::PAGE_SIZE,
                self::GRID_STRIPE_ROWS,
                self::GRID_LOAD_MASK,
                self::GRID_RESIZING_STRATEGY,
                self::FILTER_CHANGE_AUTO_SEARCH,
                self::ADVANCED_SEARCH,
                self::FILE_DBLCLICK_ACTION
            ] : [];
            
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
            self::PAGE_SIZE  => array(
                'label'         => $translate->_('Page size'),
                'description'   => $translate->_('Page size in grids'),
            ),
            self::GRID_STRIPE_ROWS  => array(
                'label'         => $translate->_('Grid stripe rows'),
                'description'   => $translate->_('Stripe rows in grids'),
            ),
            self::GRID_LOAD_MASK  => array(
                'label'         => $translate->_('Grid load mask'),
                'description'   => $translate->_('Show load mask in grids'),
            ),
            self::GRID_RESIZING_STRATEGY  => array(
                'label'         => $translate->_('Grid resizing strategy'),
                'description'   => $translate->_('How to resize grid columns'),
            ),
            self::FILTER_CHANGE_AUTO_SEARCH  => array(
                'label'         => $translate->_('Auto search on filter change'),
                'description'   => $translate->_('Perform auto search when filter is changed'),
            ),
            self::TIMEZONE  => array(
                'label'         => $translate->_('Timezone'),
                'description'   => $translate->_('The timezone in which dates are shown in Tine 2.0.'),
            ),
            self::LOCALE  => array(
                'label'         => $translate->_('Language'),
                'description'   => $translate->_('The language of the Tine 2.0 GUI.'),
            ),
            self::DEFAULT_APP  => array(
                'label'         => $translate->_('Default Application'),
                'description'   => $translate->_('The default application to show after login.'),
            ),
            self::WINDOW_TYPE  => array(
                'label'         => $translate->_('Window Type'),
                'description'   => $translate->_('You can choose between modal windows or normal browser popup windows.'),
            ),
            self::CONFIRM_LOGOUT  => array(
                'label'         => $translate->_('Confirm Logout'),
                'description'   => $translate->_('Show confirmation dialog on logout.'),
            ),
            self::ADVANCED_SEARCH => array(
                'label'         => $translate->_('Enable advanced search'),
                'description'   => $translate->_('If enabled quickfilter searches through relations as well.')
            ),
            self::FILE_DBLCLICK_ACTION => array(
                'label' => $translate->_('File double-clicking action'),
                'description' => $translate->_('Which action should be executed by default when a file is double-clicked.'),
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
    public function getApplicationPreferenceDefaults($_preferenceName, $_accountId=NULL, $_accountType=Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
    {
        $preference = $this->_getDefaultBasePreference($_preferenceName);
        $translate = Tinebase_Translation::getTranslation($this->_application);
        
        switch($_preferenceName) {
            case self::PAGE_SIZE:
                $preference->value  = 50; 
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>15</label>
                            <value>15</value>
                        </option>
                        <option>
                            <label>30</label>
                            <value>30</value>
                        </option>
                        <option>
                            <label>50</label>
                            <value>50</value>
                        </option>
                        <option>
                            <label>100</label>
                            <value>100</value>
                        </option>
                    </options>';
                $preference->personal_only = FALSE;
                break;

            case self::GRID_STRIPE_ROWS:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                  $preference->personal_only = FALSE;
                break;
            case self::GRID_RESIZING_STRATEGY:
                $preference->value      = 'fractional';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>' . $translate->_('Right side columns fractional') . '</label>
                            <value>fractional</value>
                        </option>
                        <option>
                            <label>' . $translate->_('Neighbours only') . '</label>
                            <value>neighbours</value>
                        </option>
                    </options>';
                break;
            case self::GRID_LOAD_MASK:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                  $preference->personal_only = FALSE;
                break;
            case self::FILTER_CHANGE_AUTO_SEARCH:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                $preference->personal_only = FALSE;
                break;
            case self::TIMEZONE:
                $preference->value      = 'Europe/Berlin';
                break;
            case self::LOCALE:
                $preference->value      = 'auto';
                break;
            case self::DEFAULT_APP:
                $preference->value      = Tinebase_Config::getInstance()->get(Tinebase_Config::DEFAULT_APP);
                break;
            case self::WINDOW_TYPE:
                $preference->value      = 'autodetect';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <label>Autodetect</label>
                            <value>autodetect</value>
                        </option>
                        <option>
                            <label>Native windows</label>
                            <value>Browser</value>
                        </option>
                        <option>
                            <label>Overlay windows</label>
                            <value>Ext</value>
                        </option>
                    </options>';
                break;
            case self::CONFIRM_LOGOUT:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::ADVANCED_SEARCH:
                $preference->value      = 0;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            case self::FILE_DBLCLICK_ACTION:
                $OOIAvailable = class_exists('OnlyOfficeIntegrator_Config') && Tinebase_Application::getInstance()
                        ->isInstalled(OnlyOfficeIntegrator_Config::APP_NAME, true);
                
                $preference->value = $OOIAvailable ? 'openwithonlyoffice' : 'download';

                $preference->options = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>download</value>
                            <label>' . $translate->_('Download') . '</label>
                        </option>
                        <option>
                            <value>preview</value>
                            <label>' . $translate->_('Preview') . '</label>
                        </option>
                        ' . ($OOIAvailable ? '<option>
                            <value>openwithonlyoffice</value>
                            <label>' . $translate->_('Edit') . '</label>
                        </option>' : '') . '
                    </options>';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }
        
        return $preference;
    }
    
    /**
     * overwrite this to add more special options for other apps
     *
     * - result array has to have the following format:
     *  array(
     *      array('value1', 'label1'),
     *      array('value2', 'label2'),
     *      ...
     *  )
     *
     * @param string $_value
     * @return array
     *
     * @todo add application title translations?
     */
    protected function _getSpecialOptions($_value, $_accountId = null)
    {
        $result = array();

        switch ($_value) {

            case Tinebase_Preference::TIMEZONE:
                $locale =  Tinebase_Core::getLocale();

                $availableTimezonesTranslations = Zend_Locale::getTranslationList('citytotimezone', $locale);
                $availableTimezones = DateTimeZone::listIdentifiers();
                foreach ($availableTimezones as $timezone) {
                    $result[] = array($timezone, $timezone);
                }
                break;

            case Tinebase_Preference::LOCALE:
                $availableTranslations = Tinebase_Translation::getAvailableTranslations();
                foreach ($availableTranslations as $lang) {
                    $region = (!empty($lang['region'])) ? ' / ' . $lang['region'] : '';
                    $result[] = array($lang['locale'], $lang['language'] . $region);
                }
                break;

            case Tinebase_Preference::DEFAULT_APP:
                $applications = Tinebase_Application::getInstance()->getApplications();
                foreach ($applications as $app) {
                    if (
                    $app->status == 'enabled'
                    && $app->name != 'Tinebase'
                    && Tinebase_Core::getUser()->hasRight($app->name, Tinebase_Acl_Rights_Abstract::RUN)
                    ) {
                        $result[] = array($app->name, $app->name);
                    }
                }
                break;

            default:
                $result = parent::_getSpecialOptions($_value, $_accountId);
                break;
        }

        return $result;
    }
    
    /**
     * do some call json functions if preferences name match
     * - every app should define its own special handlers
     *
     * @param Tinebase_Frontend_Json_Abstract $_jsonFrontend
     * @param string $name
     * @param string $value
     * @param string $appName
     * @param string $accountId
     */
    public function doSpecialJsonFrontendActions(Tinebase_Frontend_Json_Abstract $_jsonFrontend, $name, $value, $appName, $accountId = null)
    {
        if ($appName == $this->_application) {
            // get default prefs if value = use default
            if ($value == Tinebase_Model_Preference::DEFAULT_VALUE) {
                $value = $this->{$name};
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using default (' . $value . ') for ' . $name);
            }
            
            $session = Tinebase_Core::get(Tinebase_Session::SESSION);
            $saveAsPreference = (bool)$accountId;
            
            if ($accountId && $accountId !== Tinebase_Core::getUser()->getId()) {
                return;
            }
            
            switch ($name) {
                case Tinebase_Preference::LOCALE:
                    unset($session->userLocale);
                    $setCookie = (bool)$accountId === false;
                    $_jsonFrontend->setLocale($value, $saveAsPreference, $setCookie, $accountId);
                    
                    break;
                case Tinebase_Preference::TIMEZONE:
                    unset($session->timezone);
                    $_jsonFrontend->setTimezone($value, $saveAsPreference, $accountId);
                    break;
            }
        }
    }
}
