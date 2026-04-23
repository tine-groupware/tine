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
class Tinebase_Http_CC_ClusterLimit implements Tinebase_Http_CC_ClusterLimitInterface
{
    /** @var array<string, int> */
    protected array $limits = [];

    public function __construct(
        public int $limitPerKey
    ) {}

    public function checkKey(string $key): bool
    {
        if (($this->limits[$key] ?? 0) >= $this->limitPerKey) {
            return false;
        }
        $this->limits[$key] = ($this->limits[$key] ?? 0) + 1;
        return true;
    }

    public function freeKey(string $key): void
    {
        $this->limits[$key] -= 1;
        assert($this->limits[$key] >= 0);
    }
}