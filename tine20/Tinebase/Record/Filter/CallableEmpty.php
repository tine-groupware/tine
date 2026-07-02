<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Record_Filter_CallableEmpty implements Tinebase_Record_Filter_DefaultValue
{
    public const ALLOW_ZERO = 'allowZero';
    public const ALLOW_ZERO_STRING = 'allowZeroString';

    protected bool $allowZero = false;
    protected bool $allowZeroString = false;
    public function __construct(protected $replacement, array $options = [])
    {
        if ($options[self::ALLOW_ZERO] ?? false) {
            $this->allowZero = (bool)$options[self::ALLOW_ZERO];
        }
        if ($options[self::ALLOW_ZERO_STRING] ?? false) {
            $this->allowZeroString = (bool)$options[self::ALLOW_ZERO_STRING];
        }
    }

    public function filter($value)
    {
        if ($this->allowZero && 0 === $value) return $value;
        if ($this->allowZeroString && '0' === $value) return $value;
        return empty($value) ? $this->getReplacement() : $value;
    }

    protected function getReplacement()
    {
        if (is_callable($this->replacement[0] ?? null)) {
            $tmp = $this->replacement;
            return call_user_func_array(array_shift($tmp), $tmp);
        }
        return $this->replacement;
    }

    public function applyDefault(string $property, Tinebase_Record_Interface $record): mixed
    {
        if ($this->allowZero && 0 === $record->$property) return $record->$property;
        if ($this->allowZeroString && '0' === $record->$property) return $record->$property;
        return $this->getReplacement();
    }
}
