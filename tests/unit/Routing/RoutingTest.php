<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Routing;

use FastRoute\Dispatcher;
use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;

use function copy;
use function unlink;

use const CACHE_DIR;
use const TEST_PATH;

#[CoversClass(Routing::class)]
final class RoutingTest extends AbstractTestCase
{
    /**
     * Test for Routing::getDispatcher
     */
    public function testGetDispatcher(): void
    {
        $expected = [Dispatcher::FOUND, HomeController::class, []];
        $cacheFilename = CACHE_DIR . 'routes.cache.php';
        $validCacheFilename = TEST_PATH . 'tests/test_data/routes/routes-valid.cache.txt';
        $invalidCacheFilename = TEST_PATH . 'tests/test_data/routes/routes-invalid.cache.txt';
        $config = Config::getInstance();
        $config->settings['environment'] = null;

        self::assertDirectoryIsWritable(CACHE_DIR);

        // Valid cache file.
        self::assertTrue(copy($validCacheFilename, $cacheFilename));
        $dispatcher = Routing::getDispatcher();
        self::assertSame($expected, $dispatcher->dispatch('GET', '/'));
        self::assertFileEquals($validCacheFilename, $cacheFilename);

        // Invalid cache file.
        self::assertTrue(copy($invalidCacheFilename, $cacheFilename));
        $dispatcher = Routing::getDispatcher();
        self::assertSame($expected, $dispatcher->dispatch('GET', '/'));
        self::assertFileNotEquals($invalidCacheFilename, $cacheFilename);

        // Create new cache file.
        self::assertTrue(unlink($cacheFilename));

        self::assertFileDoesNotExist($cacheFilename);

        $dispatcher = Routing::getDispatcher();
        self::assertSame($expected, $dispatcher->dispatch('GET', '/'));
        self::assertFileExists($cacheFilename);

        // Without a cache file.
        $config->settings['environment'] = 'development';
        $dispatcher = Routing::getDispatcher();
        self::assertSame($expected, $dispatcher->dispatch('GET', '/'));
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
            'dir' => 'ltr',
            'error_message' => 'Error 404! The page <code>/setup/invalid-route</code> was not found.',
        ]);

        self::assertSame(StatusCodeInterface::STATUS_NOT_FOUND, $response->getStatusCode());
        self::assertSame($expected, (string) $response->getBody());

        ContainerBuilder::$container = null;
    }
}
