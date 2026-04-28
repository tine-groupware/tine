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
interface Tinebase_Http_CC_RequestLimitInterface
{
    public function hasFreeCapacity(): bool;
    public function checkKey(string $key): bool;
    public function freeKey(string $key): void;
    public function reset(): void;
}