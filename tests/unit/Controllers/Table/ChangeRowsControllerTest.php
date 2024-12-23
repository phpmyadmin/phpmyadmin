<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ChangeRowsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ChangeRowsController::class)]
class ChangeRowsControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$server = 2;
        $GLOBALS['where_clause'] = null;
        $_POST = [];
    }

    public function testChangeRowsController(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['rows_to_delete' => 'row']);

        $mock = self::createMock(ChangeController::class);
        $mock->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        (new ChangeRowsController(new ResponseRenderer(), $mock))($request);

        /** @psalm-suppress InvalidArrayOffset */
        self::assertSame([], $GLOBALS['where_clause']);
    }

    public function testWithoutRowsToDelete(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['goto' => 'goto']);

        $mock = self::createMock(ChangeController::class);
        $mock->expects(self::never())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        $response = new ResponseRenderer();
        (new ChangeRowsController($response, $mock))($request);

        self::assertSame(['message' => 'No row selected.'], $response->getJSONResult());
        self::assertFalse($response->hasSuccessState());
        /** @psalm-suppress InvalidArrayOffset */
        self::assertNull($GLOBALS['where_clause']);
    }

    public function testWithRowsToDelete(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['goto' => 'goto', 'rows_to_delete' => ['key1' => 'row1', 'key2' => 'row2']]);

        $mock = self::createMock(ChangeController::class);
        $mock->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        (new ChangeRowsController(new ResponseRenderer(), $mock))($request);

        /** @psalm-suppress InvalidArrayOffset */
        self::assertSame(['row1', 'row2'], $GLOBALS['where_clause']);
    }
}
