<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Locking utility class
 *
 * @package     Tinebase
 */
class Tinebase_Lock
{
    /**
     * @var array<Tinebase_Lock_Interface>
     */
    protected static array $locks = [];
    protected static ?int $lastKeepAlive = null;

    /**
     * tries to release all locked locks (catches and logs exceptions silently)
     * removes all lock objects
     */
    public static function clearLocks(): void
    {
        foreach (static::$locks as $lock) {
            try {
                if ($lock->isLocked()) {
                    $lock->release();
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        static::$locks = [];
    }

    public static function keepLocksAlive(): void
    {
        // only do this once a minute
        if (null !== static::$lastKeepAlive && time() - static::$lastKeepAlive < 60) {
            return;
        }
        static::$lastKeepAlive = time();

        /** @var Tinebase_Lock_Abstract $lock */
        foreach (static::$locks as $lock) {
            // each lock will check that it is still owns the lock
            $lock->keepAlive();
        }
    }

    public static function getLock(string $id): Tinebase_Lock_Interface
    {
        $id = static::preFixId($id);
        if (!isset(static::$locks[$id])) {
            static::$locks[$id] = static::getBackend($id);
        }
        return static::$locks[$id];
    }

    public static function tryAcquireLock(string $id): bool
    {
        return static::getLock($id)->tryAcquire(0);
    }

    public static function acquireLock(string $id): bool
    {
        return static::getLock($id)->tryAcquire();
    }

    public static function releaseLock(string $id): bool
    {
        $id = static::preFixId($id);
        if (isset(static::$locks[$id])) {
            return static::$locks[$id]->release();
        }
        return false;
    }

    public static function preFixId(string $id): string
    {
        return 'tine20_' . $id;
    }

    protected static function getBackend(string $id): Tinebase_Lock_Interface
    {
        return new Tinebase_Lock_Mysql($id);
    }

    public static function resetKeepAliveTime(): void
    {
        static::$lastKeepAlive = null;
    }
}