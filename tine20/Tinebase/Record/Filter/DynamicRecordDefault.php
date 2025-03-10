<?php declare(strict_types=1);

use Tinebase_ModelConfiguration_Const as TMCC;
class Tinebase_Record_Filter_DynamicRecordDefault implements Tinebase_Record_Filter_DefaultValue
{
    public function applyDefault(string $property, Tinebase_Record_Interface $record): mixed
    {
        return new $record->{$record::getConfiguration()->_fields[$property][TMCC::CONFIG][TMCC::REF_MODEL_FIELD]};
    }

    public function filter($value)
    {
        return $value;
    }
}
