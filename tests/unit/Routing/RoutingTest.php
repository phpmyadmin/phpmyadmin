<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing;

use FastRoute\DataGenerator\GroupCountBased as DataGeneratorGroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParserStd;
use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Routing\Routes;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;

use function file_exists;
use function file_put_contents;
use function sprintf;
use function unlink;
use function var_export;

use const CACHE_DIR;

#[CoversClass(Routing::class)]
final class RoutingTest extends AbstractTestCase
{
    public function testGetDispatcherWithDevEnv(): void
    {
        Config::getInstance()->set('environment', 'development');
        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    public function testGetDispatcherWithValidCacheFile(): void
    {
        Config::getInstance()->set('environment', 'production');
        $_SESSION['isRoutesCacheFileValid'] = true;

        self::assertDirectoryIsWritable(CACHE_DIR);

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);
        $routesData = sprintf('<?php return %s;', var_export($routeCollector->getData(), true));
        self::assertNotFalse(file_put_contents(Routing::ROUTES_CACHE_FILE, $routesData));

        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    public function testGetDispatcherWithInvalidCacheFile(): void
    {
        Config::getInstance()->set('environment', 'production');
        $_SESSION['isRoutesCacheFileValid'] = null;

        self::assertDirectoryIsWritable(CACHE_DIR);

        $routeCollector = new RouteCollector(new RouteParserStd(), new DataGeneratorGroupCountBased());
        Routes::collect($routeCollector);
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
        Config::getInstance()->set('environment', 'production');
        $_SESSION['isRoutesCacheFileValid'] = null;

        self::assertDirectoryIsWritable(CACHE_DIR);
        if (file_exists(Routing::ROUTES_CACHE_FILE)) {
            self::assertTrue(unlink(Routing::ROUTES_CACHE_FILE));
        }

        $expected = [Dispatcher::FOUND, HomeController::class, []];
        self::assertSame($expected, Routing::getDispatcher()->dispatch('GET', '/'));
    }

    /**
     * @param string $phpSelf  The PHP_SELF value
     * @param string $request  The REQUEST_URI value
     * @param string $pathInfo The PATH_INFO value
     * @param string $expected Expected result
     */
    #[DataProvider('providerForTestCleanupPathInfo')]
    public function testCleanupPathInfo(string $phpSelf, string $request, string $pathInfo, string $expected): void
    {
        $_SERVER['PHP_SELF'] = $phpSelf;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['PATH_INFO'] = $pathInfo;
        $actual = Routing::getCleanPathInfo();
        self::assertSame($expected, $actual);
    }

    /** @return array<array{string, string, string, string}> */
    public static function providerForTestCleanupPathInfo(): array
    {
        return [
            [
                '/phpmyadmin/index.php/; cookieinj=value/',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php',
            ],
            ['', '/phpmyadmin/index.php/;%20cookieinj=value///', '/; cookieinj=value/', '/phpmyadmin/index.php'],
            ['', '//example.com/../phpmyadmin/index.php', '', '/phpmyadmin/index.php'],
            ['', '//example.com/../../.././phpmyadmin/index.php', '', '/phpmyadmin/index.php'],
            ['', '/page.php/malicouspathinfo?malicouspathinfo', 'malicouspathinfo', '/page.php'],
            ['/phpmyadmin/./index.php', '/phpmyadmin/./index.php', '', '/phpmyadmin/index.php'],
            ['/phpmyadmin/index.php', '/phpmyadmin/index.php', '', '/phpmyadmin/index.php'],
            ['', '/phpmyadmin/index.php', '', '/phpmyadmin/index.php'],
        ];
    }

    public function testCallSetupControllerWithInvalidRoute(): void
    {
        $template = new Template();
        $container = self::createStub(ContainerInterface::class);
        $container->method('get')->willReturn($template);
        ContainerBuilder::$container = $container;

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withAttribute('route', '/setup/invalid-route');
        $response = Routing::callSetupController($request, ResponseFactory::create());

        $expected = $template->render('error/generic', [
            'lang' => 'en',
            'error_message' => 'Error 404! The page <code>/setup/invalid-route</code> was not found.',
        ]);

        self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
        self::assertSame($expected, (string) $response->getBody());

        ContainerBuilder::$container = null;
    }
}
