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
     * @param Tinebase_Record_PerspectiveInterface $record
     * @param string $key
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($data = $record->getPerspectiveData($key)) {
            $blob = $data;
        } else {
            if (is_string($blob)) $blob = json_decode($blob, true);
            if (!is_array($blob)) $blob = null;
        }
        return $record->setPerspectiveData($key, $blob);
    }

    /**
     * @param Tinebase_Record_PerspectiveInterface $record
     * @param string $key
     * @param $fieldValue
     * @return mixed
     */
    public function convertToData($record, $key, $fieldValue)
    {
        $value = $record->getPerspectiveData($key);
        if ($value) {
            $value = json_encode($value);
        }
        return $value;
    }
}
