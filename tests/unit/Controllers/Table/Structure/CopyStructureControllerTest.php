<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\CopyStructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CopyStructureController::class)]
final class CopyStructureControllerTest extends AbstractTestCase
{
    public function testReturnErrorWhenNoDatabaseSet(): void
    {
        Current::$database = '';
        Current::$table = 'orders';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => '', 'table' => 'orders']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertFalse($response->hasSuccessState());
        self::assertStringContainsString('No databases selected', $response->getJSONResult()['message']);
    }

    public function testReturnErrorWhenNoTableSet(): void
    {
        Current::$database = 'test_db';
        Current::$table = '';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => '']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertFalse($response->hasSuccessState());
        self::assertStringContainsString('No table selected', $response->getJSONResult()['message']);
    }

    public function testReturnErrorWhenDatabaseNameInvalid(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'orders';

        // Controller calls selectDb(Current::$database) before the DatabaseName check
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        // 'db' param is empty → DatabaseName::tryFrom returns null
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => '', 'table' => 'orders']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertFalse($response->hasSuccessState());
        self::assertStringContainsString('No databases selected', $response->getJSONResult()['message']);

        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testReturnsSqlForTable(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'orders';

        $createSql = "CREATE TABLE `orders` (\n  `id` int(11) NOT NULL\n) ENGINE=InnoDB";

        $dbiDummy = $this->createDbiDummy();
        // 1st: controller calls selectDb(Current::$database)
        // 2nd: DbTableExists::selectDatabase() calls selectDb($databaseName)
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addSelectDb('test_db');
        // DbTableExists::hasTable issues SELECT 1 FROM `db`.`table` LIMIT 1
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`orders` LIMIT 1;', [['1']]);
        // showCreate()
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`orders`',
            [['orders', $createSql]],
            ['Table', 'Create Table'],
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'orders'])
            ->withParsedBody(['db' => 'test_db', 'table' => 'orders']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertTrue($response->hasSuccessState());
        self::assertSame($createSql, $response->getJSONResult()['sql']);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testReturnsSqlForView(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'v_orders';

        $viewSql = 'CREATE VIEW `v_orders` AS SELECT * FROM `orders`';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`v_orders` LIMIT 1;', [['1']]);
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`v_orders`',
            [['v_orders', $viewSql]],
            ['Table', 'Create Table'],
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'v_orders'])
            ->withParsedBody(['db' => 'test_db', 'table' => 'v_orders']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertTrue($response->hasSuccessState());
        self::assertSame($viewSql, $response->getJSONResult()['sql']);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testReturnErrorWhenTableDoesNotExist(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'ghost_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addSelectDb('test_db');
        // hasTable SELECT fails (table not found)
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`ghost_table` LIMIT 1;', false);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'ghost_table'])
            ->withParsedBody(['db' => 'test_db', 'table' => 'ghost_table']);

        (new CopyStructureController($response, $dbi, new DbTableExists($dbi)))($request);

        self::assertFalse($response->hasSuccessState());
        self::assertStringContainsString('No table selected', $response->getJSONResult()['message']);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }
}
