<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\Database\MultiTableQuery\TablesController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TablesController::class)]
class TablesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testGetForeignKeyConstrainsForTable(): void
    {
        $_GET['tables'] = ['table1', 'table2'];
        $_GET['db'] = 'test';

        $responseRenderer = new ResponseRenderer();
        $multiTableQueryController = new TablesController($responseRenderer, $this->dbi);

        $request = self::createStub(ServerRequest::class);
        $request->method('getQueryParam')->willReturn($_GET['tables'], $_GET['db']);
        $multiTableQueryController($request);
        self::assertSame(
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
            $responseRenderer->getJSONResult(),
        );
    }
}
