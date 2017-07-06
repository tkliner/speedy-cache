<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

namespace Speedy\Cache\Drivers;

use Predis\Client;
use Speedy\Cache\Cache;
use Speedy\Cache\DriverInterface;

/**
 * Class MemcachedDriver
 * @package     Speedy\Cache\Drivers
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class PRedisDriver implements DriverInterface
{
    /** create default constants for options */
    public const OPTIONS_DATA = 'data';
    public const OPTIONS_TTL = 'ttl';
    public const OPTIONS_CALLBACK = 'callback';

    /** @var null|string */
    private $prefix;

    /** @var \Predis\Client */
    private $predis;

    /**
     * Check if predis class was install
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return class_exists('\Predis\Client');
    }

    /**
     * PRedisDriver constructor.
     *
     * @param string $scheme
     * @param string $host
     * @param int    $port
     * @param null   $prefix
     *
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function __construct(string $scheme = 'tcp', string $host = '127.0.0.1', int $port = 6379, $prefix = null)
    {
        if (!self::isAvailable()) {
            throw new \LogicException('Class \Predis\Client not loaded, please install PRedisDriver package!');
        }

        $this->prefix = $prefix;
        if ($host instanceof Client) {
            $this->predis = $host;
        } else {
            $this->predis = $this->createClient($scheme, $host, $port);
        }
    }

    /**
     * Return new instance of \Predis\Client by parameters
     *
     * @param string $scheme
     * @param string $host
     * @param int    $port
     *
     * @return Client
     * @throws \RuntimeException
     */
    protected function createClient(string $scheme, string $host, int $port): Client
    {
        try {
            $client = new Client(['scheme' => $scheme, 'host' => $host, 'port' => $port,]);
        } catch (\RuntimeException $e) {
            throw $e;
        }

        return $client;
    }

    /**
     * Get Predis client instance
     *
     * @return \Predis\Client
     */
    public function getDriver(): Client
    {
        return $this->predis;
    }

    /**
     * Return data from cache or null if data isn't in cache or callback return false
     *
     * @param $key
     *
     * @return null|string
     */
    public function read($key)
    {
        $key = urlencode($this->prefix . $key);
        $data = $this->predis->get($key);

        $data = null !== $data ? unserialize($data, ['allowed_classes' => false]) : null;

        if (null === $data) {
            return null;
        }

        // if TTL is set shift expiration and uprate key in memcached
        if (isset($data[self::OPTIONS_TTL])) {
            $this->predis->expireat($key, $data[self::OPTIONS_TTL] + time());
        }

        if (isset($data[self::OPTIONS_CALLBACK]) && !Cache::callCallback($data[self::OPTIONS_CALLBACK])) {
            $this->delete($key);

            return null;
        }

        return $data[self::OPTIONS_DATA];
    }

    /**
     * Write data with options to cache
     *
     * @param string $key
     * @param        $data
     * @param array  $options
     *
     * @return bool
     */
    public function write(string $key, $data, array $options): bool
    {
        $key = urlencode($this->prefix . $key);

        $temp = [
            self::OPTIONS_DATA => $data,
        ];

        $ex = 0;
        if (isset($options[Cache::EXPIRATION])) {
            $ex = (int)$options[Cache::EXPIRATION];

            // add expiration
            if (!empty($options[Cache::SHIFT])) {
                $temp[self::OPTIONS_TTL] = $ex;
            }
        }

        if (isset($options[Cache::CALLBACK])) {
            $temp[self::OPTIONS_CALLBACK] = $options[Cache::CALLBACK];
        }

        if (0 !== $ex) {
            if ('OK' === $this->predis->set($key, serialize($temp), 'ex', $ex)) {
                return true;
            }
        } else {
            if ('OK' === $this->predis->set($key, serialize($temp))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete data from cache (Predis return 0 if delete false and 1 if delete true), this number convert to boolean
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return (bool)$this->predis->del(urlencode($this->prefix . $key));
    }

    /**
     * !!! VERY IMPORTANT: this method delete all data from redis !!!
     * After call this method, redis storage will be empty!
     *
     * @param array $options
     *
     * @return bool
     */
    public function clear(array $options): bool
    {
        if (isset($options[Cache::ALL])) {
            if ('OK' === (string)$this->predis->flushall()) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method is not supported in predis class driver
     */
    public function lock($key)
    {
    }

}