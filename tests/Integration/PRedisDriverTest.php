<?php declare(strict_types=1);
/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

use PHPUnit\Framework\TestCase;
use Speedy\Cache\Cache;
use Speedy\Cache\Drivers\PRedisDriver;

/**
 * Class PRedisDriverTest
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class PRedisDriverTest extends TestCase
{
    protected const FILE = 'test.yml';

    /**
     * Check if memcached extension is loaded before each test
     */
    public function setUp()
    {
        if (!PRedisDriver::isAvailable()) {
            throw new \LogicException('Class \Predis\Client not loaded, please install PRedisDriver package!');
        }
    }

    /**
     * Test for method createClient and getDriver
     */
    public function testCreateClient()
    {
        $redis = new PRedisDriver();
        $this->assertInstanceOf(\Predis\Client::class, $redis->getDriver(), 'Returned driver isn\'t instance of \Predis\Client.');
    }

    /**
     * Test for method write and read
     */
    public function testSimpleWriteAndGet()
    {
        $redis = new PRedisDriver();
        $redis->write('test', 'test', []);

        $this->assertSame('test', $redis->read('test'));
    }

    /**
     * Test for delete method
     */
    public function testWriteAndDelete()
    {
        $redis = new PRedisDriver();
        $redis->write('test', 'test', []);
        $redis->delete('test');

        $this->assertNull($redis->read('test'), 'Method read return test item.');
    }

    /**
     * Test for method clear
     */
    public function testWriteAndClear()
    {
        $redis = new PRedisDriver();
        $redis->write('test', 'test', []);
        $redis->write('test2', 'test2', []);
        $redis->write('test3', 'test3', []);
        $redis->clear([Cache::ALL => 'all']);

        $this->assertNull($redis->read('test'), 'Method read return test item.');
        $this->assertNull($redis->read('test2'), 'Method read return test2 item.');
        $this->assertNull($redis->read('test3'), 'Method read return test3 item.');
    }

    /**
     * Test for method write with expiration
     */
    public function testWriteWithExpiration()
    {
        $redis = new PRedisDriver();
        $redis->write('test', 'test', [Cache::EXPIRATION => 2]);

        $this->assertSame('test', $redis->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(3);
        $this->assertNull($redis->read('test'), 'Method read return test item when should return null.');
    }

    /**
     * Test fot method write with expiration and shift time option
     */
    public function testWriteWithShiftExpiration()
    {
        $redis = new PRedisDriver();
        $redis->write('test', 'test', [Cache::EXPIRATION => 3, Cache::SHIFT => true]);

        $this->assertSame('test', $redis->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(1);
        $this->assertSame('test', $redis->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(2);
        $this->assertSame('test', $redis->read('test'), 'Returned item from cache isn\'t same as excepted.');
        sleep(5);
        $this->assertNull($redis->read('test'), 'Method read return test item when should return null.');
    }

    /**
     * Test for method write with file option
     */
    public function testWriteAndExpirationWhenFileIsChanged()
    {
        $cache = new Cache(new PRedisDriver());
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