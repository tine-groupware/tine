<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

use \PalePurple\RateLimit\RateLimit;
use \PalePurple\RateLimit\Adapter\Redis as RedisAdapter;

/**
 * Rate Limit functionality
 *
 * uses https://github.com/DavidGoodwin/RateLimit
 * see https://stackoverflow.com/a/668327/670662
 * see https://en.wikipedia.org/wiki/Token_bucket
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_RateLimit
{
    protected array $_config = [];
    protected ?Redis $_redis = null;

    public function __construct()
    {
        $cacheConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::CACHE);
        $active = strtolower($cacheConfig->backend) === 'redis';
        $redisConfig = isset($cacheConfig['redis']) ? $cacheConfig['redis'] : $cacheConfig;
        $this->_config = [
            'active' => $active,
            'redis' => $redisConfig,
            'ratelimits' => Tinebase_Config::getInstance()->get(Tinebase_Config::RATE_LIMITS),
        ];
    }

    public function hasRateLimit(string $user, string $method)
    {
        if (! $this->_config['active']) {
            return false;
        }

        if (!isset($this->_config['ratelimits'][$user])) {
            return false;
        }

        $definition = $this->getLimitDefinition($user, $method);
        return ($definition !== null
            && array_key_exists('maxrequests', $definition)
            && array_key_exists('period', $definition)
        );
    }

    public function getLimitDefinition(string $user, string $method): ?array
    {
        $limits = array_filter($this->_config['ratelimits'][$user], function($ratelimit) use ($method) {
            return $ratelimit['method'] === $method;
        });

        return count($limits) > 0 ? array_pop($limits) : null;
    }

    /**
     * @param string $user
     * @param string $method
     * @return bool
     */
    public function check(string $user, string $method): bool
    {
        $rateLimit = $this->_getRateLimit($user, $method);
        $id = $this->_getId($user, $method);
        return $rateLimit->check($id);
    }

    protected function _getRateLimit(string $user, string $method): RateLimit
    {
        $adapter = $this->_getAdapter();
        $rateLimitDefinition = $this->getLimitDefinition($user, $method);
        return new RateLimit($user . '_' . $method,
            $rateLimitDefinition['maxrequests'],
            $rateLimitDefinition['period'],
            $adapter);
    }

    public function purge(string $user, string $method): void
    {
        $rateLimit = $this->_getRateLimit($user, $method);
        $id = $this->_getId($user, $method);
        $rateLimit->purge($id);
    }

    protected function _getId(string $user, string $method)
    {
        $prefix = $this->_config['redis']['prefix'] ?? '';
        return $prefix . '_ratelimit_'. $user . '_' . $method;
    }

    protected function _getAdapter(): RedisAdapter
    {
        $this->redisConnect();
        return new RedisAdapter($this->_redis);
    }

    /**
     * connect to redis server
     */
    public function redisConnect()
    {
        if ($this->_redis instanceof Redis) {
            $this->_redis->close();
        }

        $host    = $this->_config['redis']['host'];
        $port    = $this->_config['redis']['port'] == null;

        $this->_redis = new Redis;
        if (! $this->_redis->connect($host, $port)) {
            $message = 'Could not connect to redis server at ' . $host . ':' . $port;
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $message);
            throw new Tinebase_Exception_Backend($message);
        }
    }
}
