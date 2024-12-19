<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
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
            self::createStub(ResponseRenderer::class),
            self::createStub(Config::class),
            self::createStub(ThemeManager::class),
            self::createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame('./index.php?route=/database/structure&db=test_db', $response->getHeaderLine('Location'));
        self::assertSame('', (string) $response->getBody());
    }

    public function testRedirectToTablePage(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);
        $controller = new HomeController(
            self::createStub(ResponseRenderer::class),
            self::createStub(Config::class),
            self::createStub(ThemeManager::class),
            self::createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame('./index.php?route=/sql&db=test_db&table=test_table', $response->getHeaderLine('Location'));
        self::assertSame('', (string) $response->getBody());
    }
}
