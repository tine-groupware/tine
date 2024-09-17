<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for Addressbook preferences
 *
 * @package     Addressbook
 */
class Addressbook_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/
    
    /**
     * default addressbook all newly created contacts are placed in
     */
    const DEFAULTADDRESSBOOK = 'defaultAddressbook';

    /**
     * have name of default favorite an a central palce
     * _("All contacts")
     */
    const DEFAULTPERSISTENTFILTER_NAME = "All contacts";
    
    /**
     * the default ods export configuration
     * 
     * @var string
     */
    const DEFAULT_CONTACT_ODS_EXPORTCONFIG = 'defaultContactODSExportconfig';
    
    /**
     * the default xls export configuration
     *
     * @var string
     */
    const DEFAULT_CONTACT_XLS_EXPORTCONFIG = 'defaultContactXLSExportconfig';

    /**
     * contact title template
     *
     * @var string
     */
    const CONTACT_TITLE_TEMPLATE = 'contactTitleTemplate';
    
    /**
     * @var string application
     */
    protected $_application = 'Addressbook';
        
    /**************************** public functions *********************************/
    
    /**
     * get all possible application prefs
     *
     * @return  array   all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::DEFAULTADDRESSBOOK,
            self::DEFAULTPERSISTENTFILTER,
            self::DEFAULT_CONTACT_ODS_EXPORTCONFIG,
            self::DEFAULT_CONTACT_XLS_EXPORTCONFIG,
            self::CONTACT_TITLE_TEMPLATE,
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
            self::DEFAULTADDRESSBOOK  => array(
                'label'         => $translate->_('Default Addressbook'),
                'description'   => $translate->_('The default addressbook for new contacts'),
            ),
            self::DEFAULTPERSISTENTFILTER  => array(
                'label'         => $translate->_('Default Favorite'),
                'description'   => $translate->_('The default favorite which is loaded on addressbook startup'),
            ),
            self::DEFAULT_CONTACT_ODS_EXPORTCONFIG  => array(
                'label'         => $translate->_('Contacts ODS export configuration'),
                'description'   => $translate->_('Use this configuration for the contact ODS export.'),
            ),
            self::DEFAULT_CONTACT_XLS_EXPORTCONFIG  => array(
                'label'         => $translate->_('Contacts XLS export configuration'),
                'description'   => $translate->_('Use this configuration for the contact XLS export.'),
            ),
            self:: CONTACT_TITLE_TEMPLATE => [
                'label'         => $translate->_('Contact Title Template'),
                'description'   => $translate->_('How to build contact titles.'),
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
            case self::DEFAULTADDRESSBOOK:
                $this->_getDefaultContainerPreferenceDefaults($preference, $_accountId);
                break;
            case self::DEFAULTPERSISTENTFILTER:
                $preference->value          = Tinebase_PersistentFilter::getPreferenceValues('Addressbook', $_accountId, self::DEFAULTPERSISTENTFILTER_NAME);
                break;
            case self::DEFAULT_CONTACT_ODS_EXPORTCONFIG:
                $preference->value      = 'adb_default_ods';
                break;
            case self::DEFAULT_CONTACT_XLS_EXPORTCONFIG:
                $preference->value      = 'adb_default_xls';
                break;
            case self::CONTACT_TITLE_TEMPLATE:
                $translate = Tinebase_Translation::getTranslation($this->_application);
                $preference->uiconfig = [
                    self::CLIENT_NEEDS_RELOAD => true
                ];
                $preference->value      = '{{ n_fileas }}';
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <option>
                            <value>{{ n_fileas }}</value>
                            <label>'. $translate->_('Display Name') . '</label>
                        </option>
                        <option>
                            <value>{{ n_fileas }}{% if record.getPreferredEmail().email %} ({{ record.getPreferredEmail().email }}){% endif %}</value>
                            <label>'. $translate->_('Display Name (email)') . '</label>
                        </option>
                        <option>
                            <value>{{ n_fileas }}{% if org_name %} - {{ org_name }}{% endif %}</value>
                            <label>'. $translate->_('Display Name - Company / Organisation') . '</label>
                        </option>
                        <option>
                            <value>{% if salutation %}{{ keyField("Addressbook", "contactSalutation", salutation) }} {% endif %}{% if n_prefix %}{{ n_prefix }} {% endif %}{% if n_family %}{{ n_family }}{% endif %}{% if org_name %} ({{ org_name }}){% endif %}</value>
                            <label>'. $translate->_('Salutation Title Last Name (Company / Organisation)') . '</label>
                        </option>
                    </options>';
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
            case self::DEFAULT_CONTACT_ODS_EXPORTCONFIG:
            case self::DEFAULT_CONTACT_XLS_EXPORTCONFIG:
                if ($_value == self::DEFAULT_CONTACT_XLS_EXPORTCONFIG) {
                    $plugin = 'Addressbook_Export_Xls';
                } else {
                    $plugin = 'Addressbook_Export_Ods';
                }
                
                // get names from import export definitions
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, array(
                    array('field' => 'plugin', 'operator' => 'equals', 'value' => $plugin),
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
            case self::DEFAULTCONTAINER_OPTIONS:
                $result = $this->_getDefaultContainerOptions(null, $_accountId);
                break;
            default:
                $result = parent::_getSpecialOptions($_value, $_accountId);
        }
    
        return $result;
    }
}
