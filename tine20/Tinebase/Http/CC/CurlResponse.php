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
class Tinebase_Http_CC_CurlResponse
{
    public function __construct(
        public Tinebase_Http_CC_CurlRequest $request,
        public ?string $content = null,
        public ?array $curlInfo = null,
        public int|false|null $curlErrorCode = null
    ) {}
}