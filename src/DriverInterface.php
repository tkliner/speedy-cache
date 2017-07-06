<?php

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

namespace Speedy\Cache;

/**
 * Interface DriverInterface
 * @package Speedy\Cache
 */
interface DriverInterface
{
    /**
     * Write data to cache under defined key with set options
     *
     * @param string $key
     * @param        $data
     * @param array  $options
     *
     * @return mixed
     */
    public function write(string $key, $data, array $options): bool;

    /**
     * Read data from cache by key
     *
     * @param $key
     *
     * @return mixed
     */
    public function read($key);

    /**
     * Delete data under defined key from cache
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Clear all data from cache!
     *
     * @param array $options
     *
     * @return bool
     */
    public function clear(array $options): bool;

    /**
     * Lock data entity by key
     *
     * @return mixed
     */
    public function lock($key);
}