<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as MCC;

/**
 * Tinebase_Model_Converter_JsonRecordSetDefault
 *
 * Json RecordSet Converter with default value
 *
 * @package     Tinebase
 * @subpackage  Converter
 */
class Tinebase_Model_Converter_JsonRecordSetDefault implements Tinebase_Model_Converter_RunOnNullInterface
{
    public function __construct(protected $defaultVal)
    {
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if (null === $blob) {
            $blob = $this->defaultVal;
        }

        if ($blob instanceof Tinebase_Record_RecordSet) {
            return $blob;
        }

        if (is_string($blob)) {
            $blob = json_decode($blob, true);
        }
        if (is_array($blob)) {
            $rs = new Tinebase_Record_RecordSet($record::getConfiguration()
                ->recordsFields[$key][MCC::CONFIG][MCC::RECORD_CLASS_NAME], $blob, $record->byPassFilters());
            $rs->runConvertToRecord();
            return $rs;
        }
        return $this->defaultVal;
    }

    /**
     * @param $fieldValue
     * @return string
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if (null === $fieldValue) {
            $fieldValue = $this->defaultVal;
        }

        if (! $fieldValue instanceof Tinebase_Record_RecordSet) {
            if (is_array($fieldValue) || null === json_decode((string) $fieldValue, true)) {
                return json_encode($fieldValue);
            } else {
                return $fieldValue;
            }
        }

        /** @phpstan-ignore-next-line */
        $fieldValue->runConvertToData();
        return json_encode($fieldValue->toArray());
    }
}
