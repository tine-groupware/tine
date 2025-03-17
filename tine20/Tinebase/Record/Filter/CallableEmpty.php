<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Record_Filter_CallableEmpty implements Zend_Filter_Interface
{
    public function __construct(protected $replacement)
    {}

    public function filter($value)
    {
        return empty($value) ? $this->getReplacement() : $value;
    }

    protected function getReplacement()
    {
        if (is_callable($this->replacement)) {
            $tmp = $this->replacement;
            return call_user_func_array(array_shift($tmp), $tmp);
        }
        return $this->replacement;
    }
}
