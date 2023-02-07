<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use FastRoute\Dispatcher;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Routing;

use function copy;
use function method_exists;
use function unlink;

use const CACHE_DIR;
use const TEST_PATH;

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
        $validCacheFilename = TEST_PATH . 'test/test_data/routes/routes-valid.cache.txt';
        $invalidCacheFilename = TEST_PATH . 'test/test_data/routes/routes-invalid.cache.txt';
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

        if (method_exists($this, 'assertFileDoesNotExist')) {
            $this->assertFileDoesNotExist($cacheFilename);
        } else {
            /** @psalm-suppress DeprecatedMethod */
            $this->assertFileNotExists($cacheFilename);
        }

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

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteNoParams(): void
    {
        $this->assertSame('/', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGet(): void
    {
        $_GET['route'] = '/test';
        $this->assertSame('/test', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRoutePost(): void
    {
        unset($_GET['route']);
        $_POST['route'] = '/testpost';
        $this->assertSame('/testpost', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGetIsOverPost(): void
    {
        $_GET['route'] = '/testget';
        $_POST['route'] = '/testpost';
        $this->assertSame('/testget', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectDbStructure(): void
    {
        unset($_POST['route']);
        unset($_GET['route']);
        $_GET['db'] = 'testDB';
        $this->assertSame('/database/structure', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectSql(): void
    {
        $_GET['db'] = 'testDB';
        $_GET['table'] = 'tableTest';
        $this->assertSame('/sql', Routing::getCurrentRoute());
    }
}
