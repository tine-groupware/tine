<?php
/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Snom_PhoneSettings extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'phone_id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'macaddress'            => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'phone_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'web_language'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'display_method'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_notification'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_dialtone'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headset_device'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'message_led_other'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'global_missed_counter' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pickup_indication'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'scroll_outgoing'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_local_line'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_call_status'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call_waiting'          => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    
    /**
     * converts a int, string or Voipmanager_Model_Snom_PhoneSetting to an phoneSetting id
     *
     * @param int|string|Voipmanager_Model_Snom_Phone $_phoneSettingId the phone id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertSnomPhoneSettingsIdToInt($_phoneSettingsId)
    {
        if ($_phoneSettingsId instanceof Voipmanager_Model_Snom_PhoneSettings) {
            if (empty($_phoneSettingsId->phone_id)) {
                throw new Voipmanager_Exception_InvalidArgument('no phoneSettings id set');
            }
            $id = (string) $_phoneSettingsId->phone_id;
        } else {
            $id = (string) $_phoneSettingsId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('phoneSettings id can not be 0');
        }

        return $id;
    }

}
