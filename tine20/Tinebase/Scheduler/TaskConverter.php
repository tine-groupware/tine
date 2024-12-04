<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Converter - converts the configuration property to/from Tinebase_Scheduler_Task
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 */

class Tinebase_Scheduler_TaskConverter implements Tinebase_Model_Converter_Interface
{
    /**
     * @param Tinebase_Record_Interface $record
     * @param string $key
     * @param string|array $blob
     * @return Tinebase_Scheduler_Task
     */
    function convertToRecord($record, $key, $blob)
    {
        $data = is_array($blob) ? $blob : json_decode($blob, true);
        return new Tinebase_Scheduler_Task(is_array($data) ? $data : []);
    }

    /**
     * @param Tinebase_Scheduler_Task $fieldValue
     * @return string
     */
    function convertToData($record, $key, $fieldValue)
    {
        $value = $fieldValue ? (is_array($fieldValue) ? $fieldValue : $fieldValue->toArray()) : [];
        return json_encode($value);
    }
}
