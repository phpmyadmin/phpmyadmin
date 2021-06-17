<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use stdClass;

/**
 * @covers \PhpMyAdmin\Cache
 */
class CacheTest extends AbstractTestCase
{
    public function setUp(): void
    {
        Cache::purge();
    }

    /**
     * @return array[]
     */
    public function dataProviderCacheKeyValues(): array
    {
        return [
            'normal key and false value' => [
                'mykey',
                false,
            ],
            'normal key and null value' => [
                'mykey',
                null,
            ],
            'normal key and object value' => [
                'mykey',
                new stdClass(),
            ],
        ];
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheHas(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::remove($cacheKey));
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCachePurge(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::purge());
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheSet(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheGet(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(Cache::get($cacheKey), $valueToCache);
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheGetDefaultValue(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(Cache::get($cacheKey, null), $valueToCache);
        $this->assertTrue(Cache::remove($cacheKey));
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertNull(Cache::get($cacheKey, null));
        $defaultValue = new stdClass();
        $this->assertSame($defaultValue, Cache::get($cacheKey, $defaultValue));
        $this->assertFalse(Cache::get($cacheKey, false));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheRemove(string $cacheKey, $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertTrue(Cache::set($cacheKey, $valueToCache));
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::remove($cacheKey));
        $this->assertFalse(Cache::has($cacheKey));
    }
}
