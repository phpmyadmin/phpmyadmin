<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
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
            $this->createStub(ResponseRenderer::class),
            $this->createStub(Template::class),
            $this->createStub(Config::class),
            $this->createStub(ThemeManager::class),
            $this->createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);
        $this->assertNotNull($response);
        $this->assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertSame('./index.php?route=/database/structure&db=test_db', $response->getHeaderLine('Location'));
        $this->assertSame('', (string) $response->getBody());
    }

    public function testRedirectToTablePage(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);
        $controller = new HomeController(
            $this->createStub(ResponseRenderer::class),
            $this->createStub(Template::class),
            $this->createStub(Config::class),
            $this->createStub(ThemeManager::class),
            $this->createStub(DatabaseInterface::class),
            ResponseFactory::create(),
        );
        $response = $controller($request);
        $this->assertNotNull($response);
        $this->assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertSame('./index.php?route=/sql&db=test_db&table=test_table', $response->getHeaderLine('Location'));
        $this->assertSame('', (string) $response->getBody());
    }
}
