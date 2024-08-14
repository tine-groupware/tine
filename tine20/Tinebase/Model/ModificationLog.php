<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Timemachine 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Model of an logbook entry
 * 
 * NOTE: record_type is a free-form field, which could be used by the application
 *       to distinguish different tables, mask multiple keys and so on.
 * NOTE: new_value is redundant, but it makes it a lot more easy to compute records
 *       at a given point in time!
 * 
 * @package Tinebase
 * @subpackage Timemachine
 *
 * @property string $id
 * @property string $instance_id
 * @property int    $instance_seq
 * @property string $change_type
 * @property string $application_id
 * @property string $record_id
 * @property string $record_type
 * @property string $record_backend
 * @property Tinebase_DateTime $modification_time
 * @property string $modification_account
 * @property string $modified_attribute
 * @property string $old_value
 * @property string $new_value
 * @property string $seq
 */
class Tinebase_Model_ModificationLog extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * Defintion of properties. All properties of record _must_ be declared here!
     * This validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array list of zend validator
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true, 'Alnum'),
        'instance_id'          => array('allowEmpty' => true),
        'instance_seq'         => array('allowEmpty' => true),
        'change_type'          => array('allowEmpty' => true),
        'application_id'       => array('presence' => 'required', 'allowEmpty' => false, 'Alnum'),
        'record_id'            => array('presence' => 'required', 'allowEmpty' => false,),
        'record_type'          => array('allowEmpty' => true),
        'record_backend'       => array('presence' => 'required', 'allowEmpty' => false),
        'modification_time'    => array('presence' => 'required', 'allowEmpty' => false),
        'modification_account' => array('presence' => 'required', 'allowEmpty' => false,),
        'modified_attribute'   => array('allowEmpty' => true),
        'old_value'            => array('allowEmpty' => true),
        'new_value'            => array('allowEmpty' => true),
        'seq'                  => array('allowEmpty' => true),
        'client'               => array('allowEmpty' => true)
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'modification_time'
    );
    
    /**
     * sets record related properties
     * 
     * @param string $_name of property
     * @param mixed $_value of property
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __set($_name, $_value)
    {
        switch ($_name) {
            case 'application_id':
                $_value = Tinebase_Model_Application::convertApplicationIdToInt($_value);
        }
        
        /**
         * @TODO
         * - ask model for datatype
         * - cope with record/recordSet
         */
        if (is_string($_value) && strlen($_value) == 19 && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_value)) {
            $_value = new Tinebase_DateTime($_value);
        }
        
        parent::__set($_name, $_value);
    }
}
