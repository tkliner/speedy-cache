<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

use PHPUnit\Framework\TestCase;
use Speedy\Cache\Cache;
use Speedy\Cache\Drivers\MemcachedDriver;

/**
 * Class MemcachedDriverTest
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class MemcachedDriverTest extends TestCase
{
    protected const FILE = 'test.yml';

    /**
     * Check if memcached extension is loaded before each test
     */
    public function setUp()
    {
        if (!MemcachedDriver::isAvailable()) {
            throw new \LogicException('Extension memcached is not load!');
        }
    }

    /**
     * Test for method getDriver
     */
    public function testCreateInstance()
    {
        $memcached = new MemcachedDriver();
        $this->assertInstanceOf(Memcached::class, $memcached->getDriver(), 'Returned driver isn\'t instance of Memcached.');
    }

    /**
     * Test for method write and read
     */
    public function testSimpleWriteAndGet()
    {
        $memcached = new MemcachedDriver();
        $memcached->write('test', 'test', []);

        $this->assertSame('test', $memcached->read('test'));
    }

    /**
     * Test for delete method
     */
    public function testWriteAndDelete()
    {
        $memcached = new MemcachedDriver();
        $memcached->write('test', 'test', []);
        $memcached->delete('test');

        $this->assertNull($memcached->read('test'), 'Method read return test item.');
    }

    /**
     * Test for method clear
     */
    public function testWriteAndClear()
    {
        $memcached = new MemcachedDriver();
        $memcached->write('test', 'test', []);
        $memcached->write('test2', 'test2', []);
        $memcached->write('test3', 'test3', []);
        $memcached->clear([Cache::ALL => 'all']);

        $this->assertNull($memcached->read('test'), 'Method read return test item.');
        $this->assertNull($memcached->read('test2'), 'Method read return test2 item.');
        $this->assertNull($memcached->read('test3'), 'Method read return test3 item.');
    }

    /**
     * Test for method write with expiration
     */
    public function testWriteWithExpiration()
    {
        $memcached = new MemcachedDriver();
        $memcached->write('test', 'test', [Cache::EXPIRATION => 2]);

        $this->assertSame('test', $memcached->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(3);
        $this->assertNull($memcached->read('test'), 'Method read return test item when should return null.');
    }

    /**
     * Test fot method write with expiration and shift time option
     */
    public function testWriteWithShiftExpiration()
    {
        $memcached = new MemcachedDriver();
        $memcached->write('test', 'test', [Cache::EXPIRATION => 3, Cache::SHIFT => true]);

        $this->assertSame('test', $memcached->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(1);
        $this->assertSame('test', $memcached->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(2);
        $this->assertSame('test', $memcached->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(5);
        $this->assertNull($memcached->read('test'), 'Method read return test item when should return null.');
    }

    /**
     * Test for method write with file option
     */
    public function testWriteAndExpirationWhenFileIsChanged()
    {
        $cache = new Cache(new MemcachedDriver());
        file_put_contents(__DIR__ . '/' . self::FILE, '- test');
        $cache->add('test', 'test', [Cache::FILE => __DIR__ . '/' . self::FILE]);

        $this->assertSame('test', $cache->get('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(4);
        unlink(__DIR__ . '/' . self::FILE);
        file_put_contents(__DIR__ . '/' . self::FILE, '- new_test', FILE_APPEND);
        $this->assertNull($cache->get('test'), 'Method read return test item when should return null.');

        unlink(__DIR__ . '/' . self::FILE);
    }

}