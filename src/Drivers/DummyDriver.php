<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

namespace Speedy\Cache\Drivers;

/**
 * Class DummyDriver
 * @package     Speedy\Cache\Drivers
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class DummyDriver
{

    public function __construct()
    {
    }

    public function read($key)
    {
    }

    public function write(string $key, $data, array $options)
    {
    }

    public function delete(string $key)
    {
    }

    public function clear(array $options)
    {
    }

    public function lock($key)
    {
    }

}