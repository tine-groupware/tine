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
class Tinebase_Http_CC_RequestClusterLimit implements Tinebase_Http_CC_RequestLimitInterface
{
    /** @var array<string, int> */
    protected array $limits = [];
    protected int $total = 0;

    public function __construct(
        public int $limitPerKey,
        public int $totalLimit
    ) {}

    public function checkKey(string $key): bool
    {
        if (!$this->hasFreeCapacity() || ($this->limits[$key] ?? 0) >= $this->limitPerKey) {
            return false;
        }
        $this->limits[$key] = ($this->limits[$key] ?? 0) + 1;
        ++$this->total;
        return true;
    }

    public function freeKey(string $key): void
    {
        $this->limits[$key] -= 1;
        --$this->total;
        assert($this->limits[$key] >= 0);
        assert($this->total >= 0);
    }

    public function hasFreeCapacity(): bool
    {
        return $this->total < $this->totalLimit;
    }

    public function reset(): void
    {
        $this->limits = [];
        $this->total = 0;
    }
}