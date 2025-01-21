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
    public static function dataProviderCacheKeyValues(): array
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
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
        self::assertTrue(Cache::remove($cacheKey));
        self::assertFalse(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCachePurge(string $cacheKey, $valueToCache): void
    {
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
        self::assertTrue(Cache::purge());
        self::assertFalse(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheSet(string $cacheKey, $valueToCache): void
    {
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheGet(string $cacheKey, $valueToCache): void
    {
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
        self::assertSame(Cache::get($cacheKey), $valueToCache);
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheGetDefaultValue(string $cacheKey, $valueToCache): void
    {
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
        self::assertSame(Cache::get($cacheKey, null), $valueToCache);
        self::assertTrue(Cache::remove($cacheKey));
        self::assertFalse(Cache::has($cacheKey));
        self::assertNull(Cache::get($cacheKey, null));
        $defaultValue = new stdClass();
        self::assertSame($defaultValue, Cache::get($cacheKey, $defaultValue));
        self::assertFalse(Cache::get($cacheKey, false));
    }

    /**
     * @param mixed $valueToCache
     *
     * @dataProvider dataProviderCacheKeyValues
     */
    public function testCacheRemove(string $cacheKey, $valueToCache): void
    {
        self::assertFalse(Cache::has($cacheKey));
        self::assertTrue(Cache::set($cacheKey, $valueToCache));
        self::assertTrue(Cache::has($cacheKey));
        self::assertTrue(Cache::remove($cacheKey));
        self::assertFalse(Cache::has($cacheKey));
    }
}
