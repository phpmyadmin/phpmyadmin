<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

#[CoversClass(Cache::class)]
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

    #[DataProvider('dataProviderCacheKeyValues')]
    public function testCacheHas(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::remove($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[DataProvider('dataProviderCacheKeyValues')]
    public function testCachePurge(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::purge();
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[DataProvider('dataProviderCacheKeyValues')]
    public function testCacheSet(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
    }

    #[DataProvider('dataProviderCacheKeyValues')]
    public function testCacheGet(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertSame(Cache::get($cacheKey), $valueToCache);
    }

    #[DataProvider('dataProviderCacheKeyValues')]
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

    #[DataProvider('dataProviderCacheKeyValues')]
    public function testCacheRemove(string $cacheKey, mixed $valueToCache): void
    {
        $this->assertFalse(Cache::has($cacheKey));
        Cache::set($cacheKey, $valueToCache);
        $this->assertTrue(Cache::has($cacheKey));
        Cache::remove($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
