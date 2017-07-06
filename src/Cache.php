<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2016 Tomáš Kliner
 */

namespace Speedy\Cache;

/**
 * Class Unit
 *
 * @package     Speedy\Cache
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class Cache
{
    /** defined base constant for options */
    public const EXPIRATION = 'expiration';
    public const SHIFT = 'shift';
    public const ALL = 'all';
    public const FILE = 'file';
    public const CALLBACK = 'callback';

    /** prefix for to prevent collisions */
    private const PREFIX_SEPARATOR = '__';

    /** @var DriverInterface */
    private $driver;

    /** @var string */
    private $prefix;

    /**
     * Unit constructor.
     *
     * @param null|DriverInterface $driver
     * @param null                 $prefix
     */
    public function __construct(?DriverInterface $driver = null, $prefix = null)
    {
        $this->driver = $driver;
        $this->prefix = $prefix . self::PREFIX_SEPARATOR;
    }

    /**
     * Return actual driver instance
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Add new data to cache driver driver
     *
     * @param string $key
     * @param        $data
     * @param array  $options
     *
     * @return mixed
     */
    public function add(string $key, $data, array $options = []): bool
    {
        $key = $this->getKey($key);
        $options = $this->processOptions($options);

        if (isset($options[self::EXPIRATION]) && $options[self::EXPIRATION] <= 0) {
            $this->driver->delete($key);
        }

        return $this->driver->write($key, $data, $options);
    }

    /**
     * Get data from cache and return
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->driver->read($this->getKey($key));
    }


    /**
     * Remove data from cache
     *
     * @param $key
     *
     * @return bool
     */
    public function remove($key): bool
    {
        return $this->driver->delete($this->getKey($key));
    }

    /**
     * Clear cache
     *
     * @param array $options
     *
     * @return bool
     */
    public function clean(array $options): bool
    {
        return $this->driver->clear($options);
    }

    /**
     * Return has of key with or without prefix
     *
     * @param $key
     *
     * @return string
     */
    protected function getKey($key): string
    {
        if (0 === strpos($this->prefix, self::PREFIX_SEPARATOR)) {
            return md5((string)$key);
        }

        return $this->prefix . md5((string)$key);
    }

    /******************** SUPPORT ********************/

    /**
     * Return processed options array with valid expiration time and callback for checking changed files
     *
     * @param array $options
     *
     * @return array
     */
    protected function processOptions(array $options): array
    {
        // process expiration cache to obtain a valid cache expiration time e.g. 10 sec
        if (isset($options[self::EXPIRATION])) {
            $options[self::EXPIRATION] = $this->processTime($options[self::EXPIRATION]);
        }

        // call static method isFileChange for information if file was change or not
        if (isset($options[self::FILE])) {
            foreach ((array)$options[self::FILE] as $option) {
                $options[self::CALLBACK][] = [[__CLASS__, 'isFileChange'], $option, @filemtime($option) ?: null];
            }
            unset($options[self::FILE]);
        }

        return $options;
    }

    /**
     * Run callbacks and return their state
     *
     * @param array $callbacks
     *
     * @return mixed
     */
    public static function callCallback(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            // return callback state which must be boolean type
            return !(false === array_shift($callback)(...$callback));
        }
    }

    /**
     * Return processed expiration time in second
     *
     * @param string $time
     *
     * @return false|int
     */
    protected function processTime(string $time)
    {
        return strtotime($time) - time();
    }

    /**
     * Check if file was change or not
     *
     * @param string   $filePath
     * @param int|null $time
     *
     * @return bool
     */
    protected static function isFileChange(string $filePath, ?int $time): bool
    {
        return @filemtime($filePath) === $time;
    }

}