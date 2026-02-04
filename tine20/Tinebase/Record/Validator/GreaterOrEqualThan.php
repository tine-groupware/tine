<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * validates if a value is a json string
 * arrays (converted json strings to php arrays), empty strings and null are accepted too
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Validator_GreaterOrEqualThan implements Zend_Validate_Interface
{
    protected $_messages = [];

    public function __construct(protected int|float $min) {}

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
        $this->_messages = [];
        if (!is_integer($value) && !is_float($value)) {
            $this->_messages[] = 'value needs to be a int|float';
            return false;
        }

        if (! ($value >= $this->min)) {
            $this->_messages[] = 'value needs to be greater or equal to ' . (string)$this->min;
            return false;
        }

        return true;
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
        return $this->_messages;
    }
}