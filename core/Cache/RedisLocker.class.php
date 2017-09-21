<?php
/**
 * @author Mikhail Kulakovskiy <m@klkvsk.ru>
 * @date 2017-08-29
 */

/**
 * Redis based locking.
 *
 * @ingroup Lockers
 **/
class RedisLocker extends BaseLocker implements Instantiatable
{
    const VALUE = 0x1;

    /** @var RedisNoSQL */
    protected $redisClient;
    /** @var string */
    protected $namespace;

    public static function me()
    {
        return Singleton::getInstance(__CLASS__);
    }

    protected function __construct($namespace = '')
    {
        $this->namespace = $namespace;
    }

    /**
     * @param $namespace
     * @return static
     */
    public static function namespaced($namespace)
    {
        return new static($namespace);
    }

    /**
     * @param RedisNoSQL $redis
     * @return $this
     */
    public function setRedisClient(RedisNoSQL $redis)
    {
        $this->redisClient = $redis;

        return $this;
    }

    public function get($key)
    {
        assert($this->redisClient != null, 'redis client not set');

        return $this->redisClient->add(
            $this->namespace . ':' . $key,
            self::VALUE,
            2 * Cache::EXPIRES_MINIMUM
        );
    }

    public function free($key)
    {
        assert($this->redisClient != null, 'redis client not set');

        return $this->redisClient->delete($this->namespace . ':' . $key);
    }

    public function drop($key)
    {
        assert($this->redisClient != null, 'redis client not set');

        return $this->free($this->namespace . ':' . $key);
    }

    public function clean()
    {
        assert($this->redisClient != null, 'redis client not set');
        assert(!empty($this->namespace), 'cleanup works only for namespaced lockers');

        $this->redisClient->deleteByPattern($this->namespace . ':' . '*');
    }

}