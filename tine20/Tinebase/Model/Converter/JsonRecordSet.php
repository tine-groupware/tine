<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as MCC;

/**
 * Tinebase_Model_Converter_JsonRecordSet
 *
 * Json RecordSet Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_JsonRecordSet implements Tinebase_Model_Converter_Interface
{
    protected $refId;

    public function __construct($refId = false)
    {
        $this->refId = $refId;
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($blob instanceof Tinebase_Record_RecordSet) {
            return $blob;
        }

        if (is_string($blob)) {
            $blob = json_decode($blob, true);
        }
        if (is_array($blob)) {
            if ($this->refId && (empty($blob) || !($blob[key($blob)]['id'] ?? false))) {
                return $blob;
            }
            $rs = new Tinebase_Record_RecordSet($record::getConfiguration()
                ->recordsFields[$key][MCC::CONFIG][MCC::RECORD_CLASS_NAME], $blob, $record->byPassFilters());
            $rs->runConvertToRecord();
            return $rs;
        }
        return null;
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if ($this->refId) {
            if ($fieldValue instanceof Tinebase_Record_RecordSet) {
                return json_encode($fieldValue->getArrayOfIds());
            } elseif (is_array($fieldValue)) {
                if ($fieldValue[key($fieldValue)]['id'] ?? false) {
                    $ids = [];
                    foreach ($fieldValue as $record) {
                        if ($record['id'] ?? false) {
                            $ids[] = $record['id'];
                        }
                    }
                    $fieldValue = $ids;
                }
                return json_encode($fieldValue);
            } else {
                return null;
            }
        }

        if (! $fieldValue instanceof Tinebase_Record_RecordSet) {
            if (empty($fieldValue)) {
                return null;
            } elseif (is_array($fieldValue)) {
                return json_encode($fieldValue);
            } else {
                return $fieldValue;
            }
        }

        $fieldValue->runConvertToData();
        return json_encode($fieldValue->toArray());
    }
}
