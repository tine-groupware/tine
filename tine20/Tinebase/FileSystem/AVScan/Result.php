<?php
/**
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * result of an av scan
 */
class Tinebase_FileSystem_AVScan_Result
{
    public const RESULT_OK = \Xenolope\Quahog\Client::RESULT_OK;
    public const RESULT_FOUND = \Xenolope\Quahog\Client::RESULT_FOUND;
    public const RESULT_ERROR = \Xenolope\Quahog\Client::RESULT_ERROR;

    public $result;

    public function __construct($result, public $message)
    {
        if (self::RESULT_OK !== $result && self::RESULT_FOUND !== $result) {
            $this->result = self::RESULT_ERROR;
        } else {
            $this->result = $result;
        }
    }
}