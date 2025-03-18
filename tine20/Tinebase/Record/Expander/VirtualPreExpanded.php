<?php declare(strict_types=1);
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_VirtualPreExpanded extends Tinebase_Record_Expander_Property
{
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            if ($record->{$this->_property} instanceof Tinebase_Record_RecordSet && $record->{$this->_property}->count() > 0) {
                foreach ($this->_subExpanders as $expander) {
                    $expander->_lookForDataToFetch($record->{$this->_property});
                }
            }
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data): never
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}
