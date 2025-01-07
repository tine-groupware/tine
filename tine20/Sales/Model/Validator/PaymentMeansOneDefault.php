<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SubValidate Record(Set)
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Sales_Model_Validator_PaymentMeansOneDefault implements Zend_Validate_Interface
{
    protected bool $_success = true;

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        if ($value instanceof Tinebase_Record_RecordSet &&
                Sales_Model_PaymentMeans::class === $value->getRecordClassName() &&
                $value->filter(Sales_Model_PaymentMeans::FLD_DEFAULT, true)->count() === 1) {
            return $this->_success = true;
        }
        return $this->_success = false;
    }

    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->_success ? [] : ['payment means needs to contain exactly one default record'];
    }
}
