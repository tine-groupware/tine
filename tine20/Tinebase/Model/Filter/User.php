<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_User
 * 
 * filters for user id
 * 
 * adds a inGroup operator
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_User extends Tinebase_Model_Filter_ForeignId
{
    protected $_operators = [
        'equals', //expects ID as value
        'in', //expects IDs as value
        'not', //expects ID as value
        'notin', //expects IDs as value
    ];

    protected $_userOperator = NULL;
    protected $_userValue = NULL;
    
    /**
     * sets operator
     *
     * @param string $_operator
     */
    public function setOperator($_operator)
    {
        if ($_operator == 'inGroup') {
            $this->_userOperator = $_operator;
            $_operator = 'in';
        }
        
        parent::setOperator($_operator);
    }

    protected function _setOptions(array $_options)
    {
        $_options['controller'] = $_options['filtergroup'] = '';

        parent::_setOptions($_options);
    }
    
    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        // cope with resolved records
        if (is_array($_value)) {
            if (isset($_value['accountId'])) {
                $_value = $_value['accountId'];
            } elseif (isset($_value[0]['accountId'])) {
                foreach ($_value as &$val) {
                    $val = $val['accountId'] ?? null;
                }
                $_value = array_filter($_value);
            }
        }

        // transform current user
        if ($_value == Tinebase_Model_User::CURRENTACCOUNT && is_object(Tinebase_Core::getUser())) {
            $_value = Tinebase_Core::getUser()->getId();
        }

        $this->_userValue = $_value;
        
        if ($this->_userOperator && $this->_userOperator == 'inGroup') {
            $_value = Tinebase_Group::getInstance()->getGroupMembers($this->_userValue);
        }
        
        parent::setValue($_value);
    }

    protected function _resolveRecord($value)
    {
        return $value;
    }

    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($this->_userOperator && $this->_userOperator == 'inGroup') {
            $result['operator'] = $this->_userOperator;
            $result['value']    = $this->_userValue;
        } elseif ($this->_userValue === Tinebase_Model_User::CURRENTACCOUNT && !$_valueToJson) {
            // switch back to CURRENTACCOUNT to make sure filter is saved and shown in client correctly
            $result['value'] = $this->_userValue;
        }
        
        if ($_valueToJson === true ) {
            if ($this->_userOperator && $this->_userOperator == 'inGroup' && $this->_userValue) {
                try {
                    $result['value'] = Tinebase_Group::getInstance()->getGroupById($this->_userValue)->toArray();
                } catch (Tinebase_Exception_Record_NotDefined) {
                    $result['value'] = $this->_userValue;
                }
            } else {
                switch ($this->_operator) {
                    case 'equals':
                    case 'not':
                        try {
                            if ($this->_userValue) {
                                $result['value'] = Tinebase_User::getInstance()->getUserById($this->_userValue)->toArray();
                            }
                        } catch (Tinebase_Exception_NotFound) {
                            $result['value'] = $this->_userValue;
                        }
                        break;
                    case 'in':
                    case 'notin':
                        $result['value'] = array();
                        if (! is_array($this->_userValue)) {
                            // somehow the client sent us a scalar - put this into the value array
                            $result['value'][] = $this->_userValue;
                        } else {
                            foreach ($this->_userValue as $userId) {
                                try {
                                    $result['value'][] = Tinebase_User::getInstance()->getUserById($userId)->toArray();
                                } catch(Tinebase_Exception_NotFound) {
                                    $result['value'][] = $userId;
                                }
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        } else {
            if ($this->_operator === 'equals' && is_array($result['value']) && count($result['value']) === 1 && isset($result['value'][0])) {
                $result['value'] = $result['value'][0];
            }
        }
        return $result;
    }
}
