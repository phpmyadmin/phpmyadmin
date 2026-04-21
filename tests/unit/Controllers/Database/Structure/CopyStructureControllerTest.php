<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\Structure\CopyStructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

use function strpos;

#[CoversClass(CopyStructureController::class)]
final class CopyStructureControllerTest extends AbstractTestCase
{
    public function testReturnErrorWhenNoDatabaseSet(): void
    {
        Current::$database = '';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => '']);

        $response = (new CopyStructureController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($responseRenderer->hasSuccessState());
        $message = (string) $responseRenderer->getJSONResult()['message'];
        self::assertStringContainsString('No databases selected', $message);
    }

    public function testReturnErrorWhenDatabaseNameInvalid(): void
    {
        Current::$database = 'test_db';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = new ResponseStub();
        // empty 'db' param → DatabaseName::tryFrom returns null
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => '']);

        $response = (new CopyStructureController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($responseRenderer->hasSuccessState());
        $message = (string) $responseRenderer->getJSONResult()['message'];
        self::assertStringContainsString('No databases selected', $message);
    }

    public function testReturnsSqlForTablesOnly(): void
    {
        Current::$database = 'test_db';

        $createSql = "CREATE TABLE `orders` (\n  `id` int(11) NOT NULL\n) ENGINE=InnoDB";

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        // getTables() call
        $dbiDummy->addResult(
            'SHOW TABLES FROM `test_db`;',
            [['orders']],
        );
        // showCreate() for orders
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`orders`',
            [['orders', $createSql]],
            ['Table', 'Create Table'],
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        // Pre-seed the TABLE_TYPE cache so isView() returns false without an extra query
        $dbi->getCache()->cacheTableValue('test_db', 'orders', 'TABLE_TYPE', 'BASE TABLE');

        $responseRenderer = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db'])
            ->withParsedBody(['db' => 'test_db']);

        $response = (new CopyStructureController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($responseRenderer->hasSuccessState());
        $sql = (string) $responseRenderer->getJSONResult()['sql'];
        self::assertStringContainsString('-- Database: test_db', $sql);
        self::assertStringContainsString($createSql, $sql);
        self::assertStringNotContainsString('-- Views', $sql);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testReturnsSqlWithViewsSeparated(): void
    {
        Current::$database = 'test_db';

        $tableSql = "CREATE TABLE `products` (\n  `id` int(11) NOT NULL\n) ENGINE=InnoDB";
        $viewSql = 'CREATE VIEW `v_products` AS SELECT * FROM `products`';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult(
            'SHOW TABLES FROM `test_db`;',
            [['products'], ['v_products']],
        );
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`products`',
            [['products', $tableSql]],
            ['Table', 'Create Table'],
        );
        $dbiDummy->addResult(
            'SHOW CREATE TABLE `test_db`.`v_products`',
            [['v_products', $viewSql]],
            ['Table', 'Create Table'],
        );

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $dbi->getCache()->cacheTableValue('test_db', 'products', 'TABLE_TYPE', 'BASE TABLE');
        $dbi->getCache()->cacheTableValue('test_db', 'v_products', 'TABLE_TYPE', 'VIEW');

        $responseRenderer = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db'])
            ->withParsedBody(['db' => 'test_db']);

        $response = (new CopyStructureController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($responseRenderer->hasSuccessState());
        $sql = (string) $responseRenderer->getJSONResult()['sql'];
        self::assertStringContainsString('-- Database: test_db', $sql);
        self::assertStringContainsString($tableSql, $sql);
        self::assertStringContainsString('-- Views', $sql);
        self::assertStringContainsString($viewSql, $sql);
        // Views section must come after tables
        self::assertGreaterThan(
            strpos($sql, $tableSql),
            strpos($sql, '-- Views'),
        );

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testReturnsSqlForEmptyDatabase(): void
    {
        Current::$database = 'empty_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('empty_db');
        $dbiDummy->addResult('SHOW TABLES FROM `empty_db`;', []);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = new ResponseStub();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'empty_db'])
            ->withParsedBody(['db' => 'empty_db']);

        $response = (new CopyStructureController($responseRenderer, $dbi, new DbTableExists($dbi)))($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($responseRenderer->hasSuccessState());
        $sql = (string) $responseRenderer->getJSONResult()['sql'];
        self::assertStringContainsString('-- Database: empty_db', $sql);
        self::assertStringNotContainsString('CREATE TABLE', $sql);
        self::assertStringNotContainsString('-- Views', $sql);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }
}
