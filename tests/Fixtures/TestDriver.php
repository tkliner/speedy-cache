<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

namespace Speedy\Fixtures;

use Speedy\Cache\DriverInterface;

/**
 * Class TestDriver for base tests
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 */
class TestDriver implements DriverInterface
{
    /** @var array */
    public $storage = [];

    /**
     * Write
     *
     * @param string $key
     * @param        $data
     * @param array  $options
     *
     * @return bool
     */
    public function write(string $key, $data, array $options): bool
    {
        $this->storage[$key] = $data;

        return true;
    }

    /**
     * Read
     *
     * @param $key
     *
     * @return array|mixed|null
     */
    public function read($key)
    {
        return $this->storage[$key] ?? null;
    }

    /**
     * Not support for tests
     *
     * @param $key
     *
     * @return void
     */
    public function lock($key): void
    {
    }

    /**
     * Delete
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clear
     *
     * @param array $options
     *
     * @return bool
     */
    public function clear(array $options): bool
    {
        $this->storage = [];

        return true;
    }

}