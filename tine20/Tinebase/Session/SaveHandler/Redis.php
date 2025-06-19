<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Zend_RedisProxy as Redis;

/**
 * Class for making use of the redis proxy for sessions too
 *
 * @package     Tinebase
 * @subpackage  Session
 */
class Tinebase_Session_SaveHandler_Redis extends SessionHandler implements Zend_Session_SaveHandler_Interface
{
    protected $_redis;
    protected $_lifeTimeSec;
    protected $_prefix;

    public function __construct(Redis $redis, $lifeTimeSec, $_prefix)
    {
        $this->_redis = $redis;
        if (($this->_lifeTimeSec = (int)$lifeTimeSec) < 1) {
            throw new Tinebase_Exception_Backend('session lifetime needs to be bigger than 1 sec');
        }
        $this->_prefix = $_prefix ?? 'tine20SESSION_';
    }

    public function setLifeTimeSec(int $lifeTime): void
    {
        $this->_lifeTimeSec = $lifeTime;
    }

    public function setRedisLogDelegator(?callable $delegator = null)
    {
        $this->_redis->setLogDelegator($delegator);
    }

    /**
     * @inheritDoc
     */
    public function open($save_path, $name): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
        if (false !== ($data = $this->_redis->get($this->_prefix . $id))) {
            $this->_redis->expire($this->_prefix . $id, $this->_lifeTimeSec);
        } else {
            $data = '';
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data): bool
    {
        return true === $this->_redis->setEx($this->_prefix . $id, $this->_lifeTimeSec, $data);
    }

    /**
     * @inheritDoc
     */
    public function destroy($id): bool
    {
        return false !== $this->_redis->del($this->_prefix . $id);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        if ($this->_lifeTimeSec <= $maxlifetime) {
            // nothing to do, let redis ttl handle this
            return true;
        }

        $redisIterator = null;
        while (false !== ($result = $this->_redis->scan($redisIterator, $this->_prefix . '*', 30))) {
            foreach ($result as $key) {
                if ($this->_lifeTimeSec - (int)$this->_redis->ttl($key) >= $maxlifetime) {
                    $this->_redis->del($key);
                }
            }
            if (0 === $redisIterator) {
                break;
            }
        }
        return true;
    }

    /**
     * Validate session id
     * @param string $session_id The session id
     * @return bool <p>
     * Note this value is returned internally to PHP for processing.
     * </p>
     */
    public function validateId($session_id): bool
    {
        return 1 === (int)$this->_redis->exists($this->_prefix . $session_id);
    }

    /**
     * Update timestamp of a session
     * @param string $session_id The session id
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool
     */
    public function updateTimestamp($session_id, $session_data)
    {
        return $this->write($session_id, $session_data);
    }
}
