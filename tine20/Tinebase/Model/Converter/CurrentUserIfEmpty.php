<?php
/**
 * tine groupware
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Converter_CurrentUserIfEmpty
 *
 * CurrentUserIfEmpty Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */
class Tinebase_Model_Converter_CurrentUserIfEmpty implements Tinebase_Model_Converter_RunOnNullInterface
{
    public function convertToRecord($record, $key, $blob)
    {
        return empty($blob) ? Tinebase_Core::getUser() : $blob;
    }

    public function convertToData($record, $key, $fieldValue)
    {
        if (is_array($fieldValue)) {
            return isset($fieldValue['accountId']) ? $fieldValue['accountId'] : null;
        }
        return $fieldValue;
    }
}
