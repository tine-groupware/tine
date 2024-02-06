<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * Tinebase_Model_Converter_Perspective
 *
 * Perspective Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */
class Tinebase_Model_Converter_Perspective implements Tinebase_Model_Converter_RunOnNullInterface
{
    /**
     * @param Tinebase_Record_PerspectiveInterface|Tinebase_Record_Interface $record
     * @param string $key
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($record::isHydratingFromBackend()) {
            if (is_string($blob)) $blob = json_decode($blob, true);
            if (!is_array($blob)) $blob = [];
        } elseif (null !== ($data = $record->getPerspectiveData($key))) {
            $blob = $data;
        } else {
            $blob = [$record->getPerspectiveKey($record->getPerspectiveRecord()) => $blob];
        }
        if (($converters = $record::getConfiguration()->getFields()[$key][TMCC::PERSPECTIVE_CONVERTERS] ?? false) &&
                !empty($blob)) {
            // actually we should set the perspective to the record, run the conversion then...
            /** @var Tinebase_Model_Converter_Interface $converter */
            foreach ($converters as $converter) {
                $converter = new $converter();
                foreach ($blob as &$val) {
                    if (null !== $val || $converter instanceof Tinebase_Model_Converter_RunOnNullInterface) {
                        $val = $converter->convertToRecord($record, $key, $val);
                    }
                }
            }
            unset($val);
        }
        return $record->setPerspectiveData($key, $blob);
    }

    /**
     * @param Tinebase_Record_PerspectiveInterface|Tinebase_Record_Interface $record
     * @param string $key
     * @param $fieldValue
     * @return mixed
     */
    public function convertToData($record, $key, $fieldValue)
    {
        $value = $record->getPerspectiveData($key);
        if (!empty($value)) {
            if ($converters = $record::getConfiguration()->getFields()[$key][TMCC::PERSPECTIVE_CONVERTERS] ?? false) {
                // actually we should set the perspective to the record, run the conversion then...
                /** @var Tinebase_Model_Converter_Interface $converter */
                foreach ($converters as $converter) {
                    $converter = new $converter();
                    foreach ($value as &$val) {
                        if (null !== $val || $converter instanceof Tinebase_Model_Converter_RunOnNullInterface) {
                            $val = $converter->convertToData($record, $key, $val);
                        }
                    }
                }
                unset($val);
            }
            $value = json_encode($value);
        } else {
            $value = null;
        }
        return $value;
    }
}
