<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Lock
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Lock interface
 *
 * @package     Tinebase
 * @subpackage  Lock
 */
interface Tinebase_Lock_Interface
{

    public function __construct(string $lockId);

    /**
     * blocks indefinetly by default, set timeout to 0 to only try non-blocking
     */
    public function tryAcquire(int $timeout = -1): bool;

    public function release(): bool;

    public function isLocked(): bool;

    public function keepAlive(): void;
}