<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * filters for record ids, accepts either a string of 40 or an array ['id' => string(40)] or a Record Interface
 * returns null or string(40)
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Filter_RecordId implements Zend_Filter_Interface
{
    public function __construct(protected bool $strict = true)
    {}

    /**
     * Returns the result of filtering $value
     *
     * @param  mixed $value
     * @throws Zend_Filter_Exception If filtering $value is impossible
     * @return mixed
     */
    public function filter($value)
    {
        if (is_array($value) && isset($value['id'])) {
            $value = $value['id'];
        } elseif ($value instanceof Tinebase_Record_Interface) {
            $value = $value->getId();
        }

        if (!$this->strict && (is_string($value) || is_int($value))) {
            return $value;
        }
        if (is_string($value) && strlen($value) === 40) {
            return $value;
        }

        return null;
    }
}