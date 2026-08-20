<?php
/**
 * tine-groupware
 *
 * @package     MatrixSynapseIntegrator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class MatrixSynapseIntegrator_Preference extends Tinebase_Preference_Abstract
{
    /**************************** application preferences/settings *****************/

    /**
     * start client in background
     */
    const START_CLIENT_IN_BACKGROUND = 'startClientInBackground';

    /**
     * @var string application
     */
    protected $_application = 'MatrixSynapseIntegrator';

    /**************************** public functions *********************************/

    /**
     * get all possible application prefs
     *
     * @return  array  all application prefs
     */
    public function getAllApplicationPreferences()
    {
        $allPrefs = array(
            self::START_CLIENT_IN_BACKGROUND,
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
            self::START_CLIENT_IN_BACKGROUND  => array(
                'label'         => $translate->_('Start client in background'),
                'description'   => $translate->_('Automatically starts Chat client in the background to show new messages (e.g. the unread count). When disabled, the app only starts when you open it directly.'),
            ),
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
            case self::START_CLIENT_IN_BACKGROUND:
                $preference->value      = 1;
                $preference->options    = '<?xml version="1.0" encoding="UTF-8"?>
                    <options>
                        <special>' . Tinebase_Preference_Abstract::YES_NO_OPTIONS . '</special>
                    </options>';
                break;
            default:
                throw new Tinebase_Exception_NotFound('Default preference with name ' . $_preferenceName . ' not found.');
        }

        return $preference;
    }
}