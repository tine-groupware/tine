<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SubValidate Record(Set)
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Validator_KeyField implements Zend_Validate_Interface
{
    protected array $_messages = [];

    public function __construct(
            protected Tinebase_Config_KeyField $keyField,
            protected bool $nullable = false
        ){}

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
        if (!$this->_isValid($value)) {
            $this->_messages[] = print_r($value, true) . ' is not a valid key field record for "' . $this->keyField->name . '"';
            return false;
        }
        return true;
    }

    protected function _isValid($value): bool
    {
        if (null === $value) {
            return $this->nullable;
        }

        if (!is_string($value)) {
            return false;
        }

        return (bool)$this->keyField->records->getById($value);
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
