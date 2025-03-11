<?php
/**
 * class to hold software data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold software data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Snom_Software extends Tinebase_Record_Abstract
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
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'name'                         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'softwareimage_snom300'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'softwareimage_snom320'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'softwareimage_snom360'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'softwareimage_snom370'     => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * converts a int, string or Voipmanager_Model_Snom_Software to an software id
     *
     * @param int|string|Voipmanager_Model_Snom_Software $_softwareId the software id to convert
     * @return int
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    static public function convertSoftwareIdToInt($_softwareId)
    {
        if ($_softwareId instanceof Voipmanager_Model_Snom_Software) {
            if (empty($_softwareId->id)) {
                throw new Voipmanager_Exception_InvalidArgument('no software id set');
            }
            $id = (string) $_softwareId->id;
        } else {
            $id = (string) $_softwareId;
        }
        
        if ($id == '') {
            throw new Voipmanager_Exception_InvalidArgument('software id can not be 0');
        }
        
        return $id;
    }

}
