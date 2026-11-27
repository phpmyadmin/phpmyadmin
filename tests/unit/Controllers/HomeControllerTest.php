<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HomeController::class)]
final class HomeControllerTest extends AbstractTestCase
{
    public function testRedirectToDatabasePage(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);
        $controller = new HomeController(
            new ResponseRenderer(),
            self::createStub(Config::class),
            self::createStub(ThemeManager::class),
            self::createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            './index.php?route=/database/structure&db=test_db&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame('', (string) $response->getBody());
    }

    public function testRedirectToTablePage(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);
        $controller = new HomeController(
            new ResponseRenderer(),
            self::createStub(Config::class),
            self::createStub(ThemeManager::class),
            self::createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            './index.php?route=/sql&db=test_db&table=test_table&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame('', (string) $response->getBody());
    }

    public function testHomeController(): void
    {
        Current::$server = 1;
        $config = Config::$instance = new Config();
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('mysql');
        $dbiDummy->addResult("SET collation_connection = 'latin1_swedish_ci';", true);
        $dbiDummy->addResult(
            "SHOW SESSION VARIABLES LIKE 'character_set_server';",
            [['character_set_server', 'utf8mb4']],
            ['Variable_name', 'Value'],
        );
        $dbiDummy->addResult('SELECT @@hostname;', [['test-hostname']]);
        $dbi = DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        $dbi->setCollation('latin1_swedish_ci');

        $controller = new HomeController(
            new ResponseRenderer(),
            $config,
            new ThemeManager(),
            $dbi,
            ResponseFactory::create(),
        );

        $response = $controller(ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/'));

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringMatchesFormatFile(
            __DIR__ . '/Fixtures/Home-testHomeController.html',
            (string) $response->getBody(),
        );
    }
}
