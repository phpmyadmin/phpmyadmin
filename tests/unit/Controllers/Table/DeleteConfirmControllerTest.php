<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\DeleteConfirmController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteConfirmController::class)]
class DeleteConfirmControllerTest extends AbstractTestCase
{
    public function testDeleteConfirmController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$sqlQuery = 'SELECT * FROM `test_db`.`test_table`';
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'rows_to_delete' => ['`test_table`.`id` = 2', '`test_table`.`id` = 3'],
            'sql_query' => 'SELECT * FROM `test_db`.`test_table`',
        ];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseRenderer();
        $template = new Template();
        $expected = $template->render('table/delete/confirm', [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => ['`test_table`.`id` = 2', '`test_table`.`id` = 3'],
            'sql_query' => 'SELECT * FROM `test_db`.`test_table`',
            'is_foreign_key_check' => true,
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody([
                'rows_to_delete' => ['`test_table`.`id` = 2', '`test_table`.`id` = 3'],
                'sql_query' => 'SELECT * FROM `test_db`.`test_table`',
            ]);

        (new DeleteConfirmController($response, new DbTableExists($dbi)))($request);
        self::assertSame($expected, $response->getHTMLResult());
    }
}
