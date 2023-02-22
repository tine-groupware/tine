<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

interface Tinebase_Record_JsonFacadeInterface
{
    public static function jsonFacadeToJson(Tinebase_Record_Interface $record, string $fieldKey, array $def): void;
    public function jsonFacadeFromJson(Tinebase_Record_Interface $record, array $def): void;
}