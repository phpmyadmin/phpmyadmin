<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Routing;

use function file_exists;
use function file_put_contents;
use function sprintf;
use function unlink;
use function var_export;

use const CACHE_DIR;
use const TEST_PATH;

/**
 * @covers \PhpMyAdmin\Routing
 */
class RoutingTest extends AbstractTestCase
{
    public function testGetDispatcherWithDevEnv(): void
    {
        $GLOBALS['cfg']['environment'] = 'development';
        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    public function testGetDispatcherWithValidCacheFile(): void
    {
        $GLOBALS['cfg']['environment'] = 'production';
        $_SESSION['isRoutesCacheFileValid'] = true;

        self::assertDirectoryIsWritable(CACHE_DIR);

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        $routeDefinitionCallback = require TEST_PATH . 'libraries/routes.php';
        $routeDefinitionCallback($routeCollector);
        $routesData = sprintf('<?php return %s;', var_export($routeCollector->getData(), true));
        self::assertNotFalse(file_put_contents(Routing::ROUTES_CACHE_FILE, $routesData));

        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    public function testGetDispatcherWithInvalidCacheFile(): void
    {
        $GLOBALS['cfg']['environment'] = 'production';
        $_SESSION['isRoutesCacheFileValid'] = null;

        self::assertDirectoryIsWritable(CACHE_DIR);

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        $routeDefinitionCallback = require TEST_PATH . 'libraries/routes.php';
        $routeDefinitionCallback($routeCollector);
        $dispatchData = $routeCollector->getData();
        /** @psalm-suppress MixedArrayAccess */
        unset($dispatchData[0]['GET']['/']);
        $routesData = sprintf('<?php return %s;', var_export($dispatchData, true));
        self::assertNotFalse(file_put_contents(Routing::ROUTES_CACHE_FILE, $routesData));

        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    public function testGetDispatcherWithNoCacheFile(): void
    {
        $GLOBALS['cfg']['environment'] = 'production';
        $_SESSION['isRoutesCacheFileValid'] = null;

        self::assertDirectoryIsWritable(CACHE_DIR);
        if (file_exists(Routing::ROUTES_CACHE_FILE)) {
            self::assertTrue(unlink(Routing::ROUTES_CACHE_FILE));
        }

        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteNoParams(): void
    {
        self::assertSame('/', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGet(): void
    {
        $_GET['route'] = '/test';
        self::assertSame('/test', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRoutePost(): void
    {
        unset($_GET['route']);
        $_POST['route'] = '/testpost';
        self::assertSame('/testpost', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteGetIsOverPost(): void
    {
        $_GET['route'] = '/testget';
        $_POST['route'] = '/testpost';
        self::assertSame('/testget', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectDbStructure(): void
    {
        unset($_POST['route']);
        unset($_GET['route']);
        $_GET['db'] = 'testDB';
        self::assertSame('/database/structure', Routing::getCurrentRoute());
    }

    /**
     * Test for Routing::getCurrentRoute
     */
    public function testGetCurrentRouteRedirectSql(): void
    {
        $_GET['db'] = 'testDB';
        $_GET['table'] = 'tableTest';
        self::assertSame('/sql', Routing::getCurrentRoute());
    }
}
