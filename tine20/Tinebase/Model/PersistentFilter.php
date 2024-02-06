<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  PersistentFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_PersistentFilter
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_PersistentFilter extends Tinebase_Record_Abstract 
{
    /**
     * key in $_validators/$_properties array for the field which 
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
    protected $_application = 'Tinebase';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'application_id'        => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true), // if this is null, this is a shared filter
        'model'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'filters'               => array(Zend_Filter_Input::ALLOW_EMPTY => true,  'presence'=>'required'),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'grants'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_grants'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array &$_data)
    {
        if (isset($_data['filters']) && ! $_data['filters'] instanceof Tinebase_Model_Filter_FilterGroup) {
            $errorMessage = 'Sort out missing ' . $_data['model'] . ' (no model and filter found)';
            if (
                ! class_exists($_data['model'])
                && ! class_exists(preg_replace('/Filter$/', '', $_data['model']))
            ) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(
                        __METHOD__ . '::' . __LINE__ . ' ' . $errorMessage
                    );
                }
            } else {
                try {
                    $_data['filters'] = $this->getFilterGroup($_data['model'], $_data['filters']);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(
                            __METHOD__ . '::' . __LINE__ . ' ' . $errorMessage
                        );
                    }
                }
            }
        }
        
        return parent::setFromArray($_data);
    }
    
    /**
     * wrapper for setFromJason which expects datetimes in array to be in
     * users timezone and converts them to UTC
     *
     * @param  string $_data json encoded data
     */
    public function setFromJsonInUsersTimezone(&$_data)
    {
        if (isset($_data['filters']) && ! $_data['filters'] instanceof Tinebase_Model_Filter_FilterGroup) {
            
            $filtersData = $_data['filters'];
            unset($_data['filters']);
        }
        
        parent::setFromJsonInUsersTimezone($_data);
        
        if (isset($filtersData)) {
            $this->filters = $this->getFilterGroup($_data['model'], $filtersData, TRUE);
        }
    }
    
    /**
     * gets filtergroup 
     * 
     * @param  $_filterModel    filtermodel
     * @param  $_filterData     array data of all filters
     * @param  $_fromUserTime   filterData is in user time
     * @return Tinebase_Model_Filter_FilterGroup
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public static function getFilterGroup($_filterModel, $_filterData, $_fromUserTime = FALSE)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($_filterModel);

        if (!$filter) {
            throw new Tinebase_Exception_NotFound('Did not find filter.');
        }

        if ($_fromUserTime === TRUE) {
            $filter->setFromArrayInUsersTimezone($_filterData);
        } else {
            $filter->setFromUser(true);
            try {
                $filter->setFromArray($_filterData);
            } finally {
                $filter->setFromUser(false);
            }
        }
        
        return $filter;
    }

    /**
     * returns true if this is a personal filter
     * 
     * @return boolean
     */
    public function isPersonal()
    {
        return ! empty($this->account_id);
    }
}
