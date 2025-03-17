<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\Table\GetFieldController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

use function bin2hex;

#[CoversClass(GetFieldController::class)]
class GetFieldControllerTest extends AbstractTestCase
{
    public function testGetFieldController(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'table_with_blob';

        $request = self::createStub(ServerRequest::class);
        $request->method('getQueryParam')->willReturnMap([
            ['transform_key', '', 'file' ],
            ['sql_query', '', 'SELECT * FROM `test_db`.`table_with_blob`'],
            ['where_clause', '', '`table_with_blob`.`id` = 1'],
            ['where_clause_sign', '', Core::signSqlQuery('`table_with_blob`.`id` = 1')],
        ]);

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'test_db\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'table_with_blob\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [
                ['id', 'int(11)', null, 'NO', 'PRI', null, 'auto_increment', '', ''],
                ['file', 'blob', null, 'NO', '', null, '', '', ''],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $dummyDbi->addResult(
            'SHOW INDEXES FROM `test_db`.`table_with_blob`',
            [['table_with_blob', 'PRIMARY', 'id']],
            ['Table', 'Key_name', 'Column_name'],
        );
        $dummyDbi->addResult(
            'SELECT `file` FROM `table_with_blob` WHERE `table_with_blob`.`id` = 1;',
            [[bin2hex('FILE')]],
            ['file'],
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $response = (new GetFieldController(new ResponseRenderer(), $dbi, ResponseFactory::create()))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('46494c45', (string) $response->getBody());
    }
}
