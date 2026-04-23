<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Http
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_Http_CC_CurlRequest
{
    public function __construct(
        public string $uri,
        public string $clusterKey = '',
        public ?bool $returnCurlGetInfo = null
    ) {}

    public function createCurlHandle(): false|CurlHandle
    {
        return curl_init($this->uri);
    }
}