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
        $redisConfig = $cacheConfig['redis'] ?? $cacheConfig;
        $this->_config = [
            'active' => $active,
            'redis' => $redisConfig,
            'ratelimits' => Tinebase_Config::getInstance()->get(Tinebase_Config::RATE_LIMITS),
        ];
    }

    public function hasRateLimit(string $frontend, string $method)
    {
        if (! $this->_config['active']) {
            return false;
        }

        $definition = $this->getLimitDefinition($frontend, $method);
        return ($definition !== null
            && array_key_exists('maxrequests', $definition)
            && array_key_exists('period', $definition)
        );
    }

    public function getLimitDefinition(string $frontend, string $method): ?array
    {
        $ratelimitConfigs = $this->_config['ratelimits'];

        $matchesMethod = function($pattern, $methodToMatch) {
            $regexPattern = str_replace(['*', '/'], ['.*', '\/'], $pattern);
            $match = preg_match('/^' . $regexPattern . '$/', $methodToMatch) === 1;
            if ($match) {
                return true;
            }
            return false;
        };

        $user = $this->_getUsername();

        $allMatched = [];
        $rateLimitArray = $ratelimitConfigs->toArray();

        foreach ($rateLimitArray as $group => $groupRateLimits) {
            foreach ($groupRateLimits as $key => $groupRateLimit) {
                $matched = false;
                if ($group === Tinebase_Config::RATE_LIMITS_IP) {
                    $matched = Tinebase_Helper::ipAddressMatchNetmasks([$key]);
                }
                if ($group === Tinebase_Config::RATE_LIMITS_USER) {
                    $matched = $key === $user;
                }
                if ($group === Tinebase_Config::RATE_LIMITS_FRONTENDS) {
                    $matched = $key === $frontend;
                }
                if ($matched || $key === '*') {
                    foreach ($groupRateLimit as $rateLimit) {
                        if ($matchesMethod($rateLimit['method'], $method)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                                __METHOD__ . '::' . __LINE__ . ' Found matched rate limit config from group "' . $group . '" with key "' . $key . '"');
                            // Store with information needed for sorting
                            $allMatched[] = [
                                'keyPriority' => ($matched) ? 0 : 1,
                                'group' => $group,
                                'key' => $key,
                                'rateLimit' => $rateLimit
                            ];
                        }
                    }
                }
            }
        }

        $prefix = $this->_config['redis']['prefix'] ?? '';

        if (count($allMatched) > 0){
            $matched = array_shift($allMatched);
            $matched['rateLimit']['name'] = $prefix . '_ratelimit_' . $user;
            return $matched['rateLimit'];
        }

        return null;
    }

    protected function _getUsername(): string
    {
        $user = Tinebase_Core::isRegistered(Tinebase_Core::USER) ? Tinebase_Core::getUser() : null;
        if ($user) {
            return $user->accountLoginName;
        } else {
            return Tinebase_Core::USER_ANONYMOUS;
        }
    }

    /**
     * @param string $method
     * @return bool
     */
    public function check(string $frontend, string $method): bool
    {
        $rateLimit = $this->_getRateLimit($frontend, $method);
        $id = $this->_getId($frontend, $method);
        return $rateLimit->check($id);
    }

    protected function _getRateLimit(string $frontend, string $method): RateLimit
    {
        $adapter = $this->_getAdapter();
        $rateLimitDefinition = $this->getLimitDefinition($frontend, $method);
        return new RateLimit(
            $rateLimitDefinition['name'],
            $rateLimitDefinition['maxrequests'],
            $rateLimitDefinition['period'],
            $adapter);
    }

    public function purge( string $frontend, string $method): void
    {
        $rateLimit = $this->_getRateLimit($frontend, $method);
        $id = $this->_getId($frontend, $method);
        $rateLimit->purge($id);
    }

    protected function _getId(string $frontend, string $method)
    {
        $ip = Tinebase_Helper::getIpAddress();
        return $frontend . '_' . $method . '_' . (!empty($ip) ? $ip : 'localhost');
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
