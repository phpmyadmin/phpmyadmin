<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use stdClass;

/** @covers \PhpMyAdmin\Cache */
class CacheTest extends AbstractTestCase
{
    public function setUp(): void
    {
        Cache::purge();
    }

    /** @return mixed[][] */
    public static function dataProviderCacheKeyValues(): array
    {
        return [
            'normal key and false value' => ['mykey', false],
            'normal key and null value' => ['mykey', null],
            'normal key and object value' => ['mykey', new stdClass()],
        ];
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCacheHas(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::remove($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCachePurge(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::purge();
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCacheSet(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCacheGet(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(Cache::get($cacheKey), $valueToCache);
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCacheGetDefaultValue(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(Cache::get($cacheKey, null), $valueToCache);
        Cache::remove($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
        $this->assertNull(Cache::get($cacheKey, null));
        $defaultValue = new stdClass();
        $this->assertSame($defaultValue, Cache::get($cacheKey, $defaultValue));
        $this->assertFalse(Cache::get($cacheKey, false));
    }

    /** @dataProvider dataProviderCacheKeyValues */
    public function testCacheRemove(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::remove($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
