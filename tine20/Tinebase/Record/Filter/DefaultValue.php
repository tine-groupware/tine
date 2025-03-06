<?php declare(strict_types=1);

interface Tinebase_Record_Filter_DefaultValue extends Zend_Filter_Interface
{
    public function applyDefault(string $property, Tinebase_Record_Interface $record): mixed;
}
