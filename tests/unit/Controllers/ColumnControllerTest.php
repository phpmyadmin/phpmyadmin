<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\ColumnController;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ColumnController::class)]
final class ColumnControllerTest extends AbstractTestCase
{
    public function testColumnController(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withParsedBody(['db' => 'test_db', 'table' => 'test_table']);

        $dbi = $this->createDatabaseInterface();
        $responseRenderer = new ResponseRenderer();
        $controller = new ColumnController($responseRenderer, new Template(new Config()), $dbi);
        $controller($request);

        self::assertTrue($responseRenderer->hasSuccessState());
        self::assertSame('', $responseRenderer->getHTMLResult());
        self::assertSame(['columns' => ['id', 'name', 'datetimefield']], $responseRenderer->getJSONResult());
    }

    public function testWithMissingParameters(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $dbi = $this->createDatabaseInterface();
        $responseRenderer = new ResponseRenderer();
        $controller = new ColumnController($responseRenderer, new Template(new Config()), $dbi);
        $controller($request);

        self::assertFalse($responseRenderer->hasSuccessState());
        self::assertSame('', $responseRenderer->getHTMLResult());
        self::assertSame(['message' => Message::error()->getDisplay()], $responseRenderer->getJSONResult());
    }
}
