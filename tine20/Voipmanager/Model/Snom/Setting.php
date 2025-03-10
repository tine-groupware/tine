<?php
/**
 * class to hold snom setting data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold snom setting data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Snom_Setting extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
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
    protected $_filters; /* = array(
        '*'                     => 'StringTrim'
    );
    */
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'web_language'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'display_method'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_notification'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_dialtone'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headset_device'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'message_led_other'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'global_missed_counter'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pickup_indication'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'scroll_outgoing'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_local_line'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_call_status'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call_waiting'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'web_language_w'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language_w'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'display_method_w'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call_waiting_w'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_notification_w' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_dialtone_w'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headset_device_w'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'message_led_other_w' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'global_missed_counter_w' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pickup_indication_w' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'scroll_outgoing_w'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_local_line_w'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_call_status_w' => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // set default value if field is empty
        $this->_filters['web_language']          = new Zend_Filter_Empty('English');
        $this->_filters['language']              = new Zend_Filter_Empty('English');
        $this->_filters['display_method']        = new Zend_Filter_Empty('full_contact');
        $this->_filters['mwi_notification']      = new Zend_Filter_Empty('silent');
        $this->_filters['mwi_dialtone']          = new Zend_Filter_Empty('normal');
        $this->_filters['headset_device']        = new Zend_Filter_Empty('none');
        $this->_filters['message_led_other']     = new Zend_Filter_Empty(0);
        $this->_filters['global_missed_counter'] = new Zend_Filter_Empty(0);
        $this->_filters['pickup_indication']     = new Zend_Filter_Empty(0);
        $this->_filters['scroll_outgoing']       = new Zend_Filter_Empty(0);
        $this->_filters['show_local_line']       = new Zend_Filter_Empty(0);
        $this->_filters['show_call_status']      = new Zend_Filter_Empty(0);
        $this->_filters['call_waiting']          = new Zend_Filter_Empty('on');
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * converts a int, string or Voipmanager_Model_Setting to an setting id
     *
     * @param int|string|Voipmanager_Model_Setting $_settingId the setting id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertSnomSettingIdToInt($_settingId)
    {
        if ($_settingId instanceof Voipmanager_Model_Snom_Setting) {
            if (empty($_settingId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no setting id set');
            }
            $id = (string) $_settingId->id;
        } else {
            $id = (string) $_settingId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('setting id can not be 0');
        }
        
        return $id;
    }

}
