<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/** @covers \PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController */
class TablesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['server'] = 1;

        parent::loadResponseIntoContainerBuilder();
    }

    public function testGetForeignKeyConstrainsForTable(): void
    {
        $_GET['tables'] = ['table1', 'table2'];
        $_GET['db'] = 'test';

        /** @var TablesController $multiTableQueryController */
        $multiTableQueryController = $GLOBALS['containerBuilder']->get(TablesController::class);
        $request = $this->createStub(ServerRequest::class);
        $request->method('getQueryParam')->willReturnOnConsecutiveCalls($_GET['tables'], $_GET['db']);
        $multiTableQueryController($request);
        $this->assertSame(
            [
                'foreignKeyConstrains' => [
                    [
                        'TABLE_NAME' => 'table2',
                        'COLUMN_NAME' => 'idtable2',
                        'REFERENCED_TABLE_NAME' => 'table1',
                        'REFERENCED_COLUMN_NAME' => 'idtable1',
                    ],
                ],
            ],
            $this->getResponseJsonResult(),
        );
    }
}
