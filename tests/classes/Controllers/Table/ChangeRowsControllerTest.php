<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ChangeRowsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
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
        $GLOBALS['server'] = 2;
        $GLOBALS['active_page'] = null;
        $GLOBALS['where_clause'] = null;
        $_POST = [];
    }

    public function testChangeRowsController(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['rows_to_delete' => 'row']);

        $mock = $this->createMock(ChangeController::class);
        $mock->expects($this->once())->method('__invoke')->with($request);

        (new ChangeRowsController(new ResponseRenderer(), new Template(), $mock))($request);

        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame('index.php?route=/table/change&server=2&lang=en', $GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame([], $GLOBALS['where_clause']);
    }

    public function testWithoutRowsToDelete(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['goto' => 'goto']);

        $mock = $this->createMock(ChangeController::class);
        $mock->expects($this->never())->method('__invoke')->with($request);

        $response = new ResponseRenderer();
        (new ChangeRowsController($response, new Template(), $mock))($request);

        $this->assertSame(['message' => 'No row selected.'], $response->getJSONResult());
        $this->assertFalse($response->hasSuccessState());
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertNull($GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertNull($GLOBALS['where_clause']);
    }

    public function testWithRowsToDelete(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['goto' => 'goto', 'rows_to_delete' => ['key1' => 'row1', 'key2' => 'row2']]);

        $mock = $this->createMock(ChangeController::class);
        $mock->expects($this->once())->method('__invoke')->with($request);

        (new ChangeRowsController(new ResponseRenderer(), new Template(), $mock))($request);

        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame('index.php?route=/table/change&server=2&lang=en', $GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame(['row1', 'row2'], $GLOBALS['where_clause']);
    }
}
