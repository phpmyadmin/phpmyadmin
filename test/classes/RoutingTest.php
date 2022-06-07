<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use FastRoute\Dispatcher;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Routing;

use function copy;
use function unlink;

use const CACHE_DIR;
use const ROOT_PATH;

/**
 * @covers \PhpMyAdmin\Routing
 */
class RoutingTest extends AbstractTestCase
{
    /**
     * Test for Routing::getDispatcher
     */
    public function testGetDispatcher(): void
    {
        $expected = [Dispatcher::FOUND, HomeController::class, []];
        $cacheFilename = CACHE_DIR . 'routes.cache.php';
        $validCacheFilename = ROOT_PATH . 'test/test_data/routes/routes-valid.cache.txt';
        $invalidCacheFilename = ROOT_PATH . 'test/test_data/routes/routes-invalid.cache.txt';
        $GLOBALS['cfg']['environment'] = null;

        $this->assertDirectoryIsWritable(CACHE_DIR);

        // Valid cache file.
        $this->assertTrue(copy($validCacheFilename, $cacheFilename));
        $dispatcher = Routing::getDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($expected, $dispatcher->dispatch('GET', '/'));
        $this->assertFileEquals($validCacheFilename, $cacheFilename);

        // Invalid cache file.
        $this->assertTrue(copy($invalidCacheFilename, $cacheFilename));
        $dispatcher = Routing::getDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($expected, $dispatcher->dispatch('GET', '/'));
        $this->assertFileNotEquals($invalidCacheFilename, $cacheFilename);

        // Create new cache file.
        $this->assertTrue(unlink($cacheFilename));
        $this->assertFileNotExists($cacheFilename);
        $dispatcher = Routing::getDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($expected, $dispatcher->dispatch('GET', '/'));
        $this->assertFileExists($cacheFilename);

        // Without a cache file.
        $GLOBALS['cfg']['environment'] = 'development';
        $dispatcher = Routing::getDispatcher();
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertSame($expected, $dispatcher->dispatch('GET', '/'));
    }
}
