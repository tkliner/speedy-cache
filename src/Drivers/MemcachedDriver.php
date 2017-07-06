<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

namespace Speedy\Cache\Drivers;

use Speedy\Cache\Cache;
use Speedy\Cache\DriverInterface;

/**
 * Class MemcachedDriver
 * @package     Speedy\Cache\Drivers
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class MemcachedDriver implements DriverInterface
{
    /** create default constants for options */
    public const OPTIONS_DATA = 'data';
    public const OPTIONS_TTL = 'ttl';
    public const OPTIONS_CALLBACK = 'callback';

    /** @var null|string */
    private $prefix;

    /** @var \Memcached */
    private $memcached;

    /**
     * Check if memcached php extensions was install
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('memcached');
    }

    /**
     * MemcachedDriver constructor.
     *
     * @param string $host
     * @param int    $port
     * @param null   $prefix
     * @param bool   $persist
     *
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function __construct(string $host = 'localhost', int $port = 11211, $prefix = null, $persist = null)
    {
        if (!self::isAvailable()) {
            throw new \LogicException('Extension memcached is not load!');
        }

        $this->prefix = $prefix;
        $this->memcached = (null === $persist) ? new \Memcached() : new \Memcached($persist);
        $this->addServer($host, $port);
    }

    /**
     * Get memcached extension instance
     *
     * @return \Memcached
     */
    public function getDriver(): \Memcached
    {
        return $this->memcached;
    }

    /**
     * Add new server to memcached php extension
     *
     * @param string $host
     * @param int    $port
     *
     * @throws \RuntimeException
     */
    public function addServer(string $host, int $port): void
    {
        if (false === $this->memcached->addServer($host, $port, 1)) {
            $error = error_get_last();
            throw new \RuntimeException('Memcached method addServer error: ' . $error['message']);
        }
    }

    /**
     * Return data from cache or null
     *
     * @param $key
     *
     * @return mixed|null
     */
    public function read($key)
    {
        $key = urlencode($this->prefix . $key);
        $data = $this->memcached->get($key);

        if (false === $data) {
            return null;
        }

        // if TTL is set shift expiration and uprate key in memcached
        if (isset($data[self::OPTIONS_TTL])) {
            $this->memcached->touch($key, $data[self::OPTIONS_TTL] + time());
        }

        if (isset($data[self::OPTIONS_CALLBACK]) && !Cache::callCallback($data[self::OPTIONS_CALLBACK])) {
            $this->delete($key);

            return null;
        }

        return $data[self::OPTIONS_DATA];
    }

    /**
     * Write data to cache and process expiration ttl, time expiration shift or callbacks
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

        return $this->memcached->set($key, $temp, $ex);
    }

    /**
     * Delete dara from cache
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->memcached->delete(urlencode($this->prefix . $key));
    }

    /**
     * This method is not supported in memcached driver
     */
    public function lock($key): void
    {
    }

    /**
     * Clear all items from cache!
     *
     * @param array $options
     *
     * @return bool
     */
    public function clear(array $options): bool
    {
        if (isset($options[Cache::ALL])) {
            return $this->memcached->flush();
        }

        return false;
    }

}