<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Zend_RedisProxy as Redis;

/**
 * class to broadcast CRUD actions on records
 *
 * @package     Tinebase
 */
class Tinebase_BroadcastHub
{
    /** @use Tinebase_Controller_SingletonTrait<Tinebase_BroadcastHub> */
    use Tinebase_Controller_SingletonTrait;

    public function isActive(): bool
    {
        return $this->_isActive;
    }

    public function push(string $verb, string $model, string $recordId, ?string $containerId): Redis|int|false
    {
        try {
            return $this->_getRedis()->publish($this->_pubSubName, json_encode([
                'verb' => $verb,
                'model' => $model,
                'recordId' => $recordId,
                'containerId' => $containerId,
            ]));
        } catch (Throwable $t) {
            Tinebase_Exception::log(Tinebase_Exception::wrap($t, 'warn', true));
            return -1;
        }
    }

    public function pushAfterCommit(string $verb, string $model, string $recordId, ?string $containerId): void
    {
        $tam = Tinebase_TransactionManager::getInstance();
        if ($tam->hasOpenTransactions() === false) {
            $this->push($verb, $model, $recordId, $containerId);
        } else {
            // the same message is pushed once per transaction only
            $key = $verb . '|' . $model . '|' . $recordId . '|' . $containerId;
            if (isset($this->_pendingAfterCommit[$key])) {
                return;
            }
            $this->_pendingAfterCommit[$key] = true;

            $tam->registerAfterCommitCallback(function () use ($verb, $model, $recordId, $containerId, $key) {
                $bchub = Tinebase_BroadcastHub::getInstance();
                unset($bchub->_pendingAfterCommit[$key]);
                $bchub->push($verb, $model, $recordId, $containerId);
            });
            $tam->registerOnRollbackCallback(function () use ($key) {
                unset(Tinebase_BroadcastHub::getInstance()->_pendingAfterCommit[$key]);
            });
        }
    }

    protected $_config;
    protected $_isActive;
    protected $_redis;
    protected $_pubSubName;
    protected array $_pendingAfterCommit = [];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_config = Tinebase_Config::getInstance()->{Tinebase_Config::BROADCASTHUB};
        $this->_isActive = $this->_config->{Tinebase_Config::BROADCASTHUB_ACTIVE};
        $this->_pubSubName = $this->_config->{Tinebase_Config::BROADCASTHUB_PUBSUBNAME};
    }

    protected function _getRedis(): Redis
    {
        if (null === $this->_redis) {
            if (!$this->_isActive) {
                throw new Tinebase_Exception_Backend(self::class . ' is not activated');
            }
            $this->_redis = new Redis();
            $this->_redis->connect(
                $this->_config->{Tinebase_Config::BROADCASTHUB_REDIS}->{Tinebase_Config::BROADCASTHUB_REDIS_HOST},
                $this->_config->{Tinebase_Config::BROADCASTHUB_REDIS}->{Tinebase_Config::BROADCASTHUB_REDIS_PORT},
                1 /* 1 sec timeout */
            );
        }

        return $this->_redis;
    }
}
