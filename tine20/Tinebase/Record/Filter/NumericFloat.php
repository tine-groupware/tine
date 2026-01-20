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

class Tinebase_Record_Filter_NumericFloat implements Zend_Filter_Interface
{
    public function __construct(protected mixed $replacement = null)
    {
    }

    public function filter($value)
    {
        return is_numeric($value) ? floatval($value) : $this->replacement;
    }
}