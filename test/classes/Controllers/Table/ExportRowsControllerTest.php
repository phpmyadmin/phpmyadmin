<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\ExportController;
use PhpMyAdmin\Controllers\Table\ExportRowsController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Table\ExportRowsController
 */
class ExportRowsControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 2;
        $GLOBALS['active_page'] = null;
        $GLOBALS['single_table'] = null;
        $GLOBALS['where_clause'] = null;
        $_POST = [];
    }

    public function testExportRowsController(): void
    {
        $_POST['rows_to_delete'] = 'row';

        $controller = $this->createMock(ExportController::class);
        $controller->expects($this->once())->method('__invoke');

        (new ExportRowsController(new ResponseRenderer(), new Template(), $controller))();

        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame('index.php?route=/table/export&server=2&lang=en', $GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertTrue($GLOBALS['single_table']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame([], $GLOBALS['where_clause']);
    }

    public function testWithoutRowsToDelete(): void
    {
        $_POST['goto'] = 'goto';

        $controller = $this->createMock(ExportController::class);
        $controller->expects($this->never())->method('__invoke');

        $response = new ResponseRenderer();
        (new ExportRowsController($response, new Template(), $controller))();

        $this->assertSame(['message' => 'No row selected.'], $response->getJSONResult());
        $this->assertFalse($response->hasSuccessState());
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertNull($GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertNull($GLOBALS['single_table']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertNull($GLOBALS['where_clause']);
    }

    public function testWithRowsToDelete(): void
    {
        $_POST['goto'] = 'goto';
        $_POST['rows_to_delete'] = ['key1' => 'row1', 'key2' => 'row2'];

        $controller = $this->createMock(ExportController::class);
        $controller->expects($this->once())->method('__invoke');

        (new ExportRowsController(new ResponseRenderer(), new Template(), $controller))();

        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame('index.php?route=/table/export&server=2&lang=en', $GLOBALS['active_page']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertTrue($GLOBALS['single_table']);
        /** @psalm-suppress InvalidArrayOffset */
        $this->assertSame(['row1', 'row2'], $GLOBALS['where_clause']);
    }
}
