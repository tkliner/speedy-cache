<?php declare(strict_types=1);

/**
 * This file is part of the Speedy Components (http://stagemedia.cz)
 * Copyright (c) 2017 Tomáš Kliner
 */

use PHPUnit\Framework\TestCase;
use Speedy\Cache\Cache;
use Speedy\Fixtures\TestDriver;

/**
 * Class CacheTest
 * @author      Tomáš Kliner <kliner.tomas@gmail.com>
 * @since       1.0.0
 */
class CacheTest extends TestCase
{
    /**
     * Test for method getDriver when driver was set
     */
    public function testGetDriver(): void
    {
        $driver = new TestDriver();
        $cache = new Cache($driver, 'test');

        $this->assertInstanceOf(TestDriver::class, $cache->getDriver(), 'Returned driver isn\'t instance of TestDriver.');
    }

    /**
     * @expectedException TypeError
     */
    public function testGetDriverWithoutSetDriver(): void
    {
        $cache = new Cache();
        $this->assertInstanceOf(TestDriver::class, $cache->getDriver(), 'Returned driver isn\'t instance of TestDriver.');
    }

    /**
     * Test for getKey method without prefix
     */
    public function testGenerateKeyWithoutPrefix(): void
    {
        $methodGetKey = self::getMethod('getKey');
        $driver = new TestDriver();
        $cache = new Cache($driver);
        $test = $methodGetKey->invokeArgs($cache, ['test']);

        $this->assertEquals(0, preg_match('#^__#', $test), 'Returned key contain prefix __ for key.');
    }

    /**
     * Test for getKey method with prefix
     */
    public function testGenerateKeyWithPrefix(): void
    {
        $methodGetKey = self::getMethod('getKey');
        $driver = new TestDriver();
        $cache = new Cache($driver, 'test');
        $test = $methodGetKey->invokeArgs($cache, ['test']);

        $this->assertEquals(1, preg_match('#^test__#', $test), 'Returned key contain prefix __ for key.');
    }

    /**
     * Test for add and get method when insert new item to test cache
     */
    public function testAddNewItem(): void
    {
        $driver = new TestDriver();
        $cache = new Cache($driver, 'test');
        $cache->add('test', 'some data');

        $this->assertCount(1, $driver->storage, 'More than one item in the storage array.');
        $this->assertSame('some data', $cache->get('test'), 'Returned data from cache is not same as inserted data.');
    }

    /**
     * Test for method remove
     */
    public function testRemoveItem(): void
    {
        $driver = new TestDriver();
        $cache = new Cache($driver, 'test');
        $cache->add('test', 'some data');

        $this->assertTrue($cache->remove('test'), 'Remove item from TestDriver failed.');
        $this->assertNull($cache->get('test'), 'Removed item from TestDriver was returned.');
    }

    /**
     * Test for method clean
     */
    public function testClean(): void
    {
        $driver = new TestDriver();
        $cache = new Cache($driver, 'test');
        $cache->add('test', 'some data');
        $cache->add('test2', 'some data');

        $cache->clean([Cache::ALL => 'all']);

        $this->assertCount(0, $driver->storage, 'Storage is not empty.');

    }

    /**
     * Test for method ProcessOptions with expiration option
     */
    public function testProcessOptionsExpiration()
    {
        $methodGetKey = self::getMethod('processOptions');
        $driver = new TestDriver();
        $cache = new Cache($driver);
        $options = $methodGetKey->invokeArgs($cache, [[Cache::EXPIRATION => '10 seconds']]);

        $this->assertArrayHasKey(Cache::EXPIRATION, $options, 'Returned array havn\'t expiration key.');
        $this->assertSame(10, $options[Cache::EXPIRATION], 'Returned expiration time isn\'t same as defined time.');
    }

    /**
     * Test for method ProcessOptions with file option
     */
    public function testProcessOptionsFile()
    {
        $methodGetKey = self::getMethod('processOptions');
        $driver = new TestDriver();
        $cache = new Cache($driver);
        $options = $methodGetKey->invokeArgs($cache, [[Cache::FILE => './testFile.yml']]);

        $this->assertArrayHasKey(Cache::CALLBACK, $options, 'Returned array havn\'t callback key.');
        $this->assertEquals('Speedy\Cache\Cache', $options[Cache::CALLBACK][0][0][0], 'Returned callback class is not same as defined.');
        $this->assertEquals('isFileChange', $options[Cache::CALLBACK][0][0][1], 'Returned callback method is not same as defined.');
        $this->assertEquals('./testFile.yml', $options[Cache::CALLBACK][0][1], 'Returned file is not same as defined.');
    }

    /**
     * Test for method ProcessOptions with all option
     */
    public function testProcessOptionsAll()
    {
        $methodGetKey = self::getMethod('processOptions');
        $driver = new TestDriver();
        $cache = new Cache($driver);
        $options = $methodGetKey->invokeArgs($cache, [[
            Cache::EXPIRATION => '10 seconds', Cache::FILE => './testFile.yml',
        ]]);

        $this->assertArrayHasKey(Cache::EXPIRATION, $options, 'Returned array havn\'t expiration key.');
        $this->assertSame(10, $options[Cache::EXPIRATION], 'Returned expiration time isn\'t same as defined time.');
        $this->assertArrayHasKey(Cache::CALLBACK, $options, 'Returned array havn\'t callback key.');
        $this->assertEquals('Speedy\Cache\Cache', $options[Cache::CALLBACK][0][0][0], 'Returned callback class is not same as defined.');
        $this->assertEquals('isFileChange', $options[Cache::CALLBACK][0][0][1], 'Returned callback method is not same as defined.');
        $this->assertEquals('./testFile.yml', $options[Cache::CALLBACK][0][1], 'Returned file is not same as defined.');
    }

    /**
     * Workaround for protected method
     *
     * @param $name
     *
     * @return ReflectionMethod
     */
    protected static function getMethod($name): ReflectionMethod
    {
        $class = new ReflectionClass(Cache::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

}