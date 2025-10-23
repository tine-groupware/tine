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

class Tinebase_Record_Filter_NotSetDefault implements Tinebase_Record_Filter_DefaultValue
{
    public function __construct(protected $replacement)
    {}

    public function applyDefault(string $property, Tinebase_Record_Interface $record): mixed
    {
        return $this->replacement;
    }

    public function filter($value)
    {
        return $value;
    }
}
