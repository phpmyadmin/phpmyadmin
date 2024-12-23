<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\RecentFavoriteController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ReflectionProperty;

#[CoversClass(RecentFavoriteController::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class RecentFavoriteControllerTest extends AbstractTestCase
{
    public function testRecentFavoriteControllerWithValidDbAndTable(): void
    {
        Current::$server = 2;
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $_SESSION['tmpval'] = [
            'recentTables' => [2 => [['db' => 'test_db', 'table' => 'test_table']]],
            'favoriteTables' => [2 => [['db' => 'test_db', 'table' => 'test_table']]],
        ];

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']], ['1']);
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']], ['1']);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $recent = RecentFavoriteTables::getInstance(TableType::Recent);
        $favorite = RecentFavoriteTables::getInstance(TableType::Favorite);

        $table = new RecentFavoriteTable(DatabaseName::from('test_db'), TableName::from('test_table'));
        self::assertEquals([$table], $recent->getTables());
        self::assertEquals([$table], $favorite->getTables());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/sql&db=test_db&table=test_table&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );

        self::assertEquals([$table], $recent->getTables());
        self::assertEquals([$table], $favorite->getTables());
    }

    public function testRecentFavoriteControllerWithInvalidDbAndTable(): void
    {
        Current::$server = 2;
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $_SESSION['tmpval'] = [
            'recentTables' => [2 => [['db' => 'invalid_db', 'table' => 'invalid_table']]],
            'favoriteTables' => [2 => [['db' => 'invalid_db', 'table' => 'invalid_table']]],
        ];

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT 1 FROM `invalid_db`.`invalid_table` LIMIT 1;', false);
        $dbiDummy->addResult('SELECT 1 FROM `invalid_db`.`invalid_table` LIMIT 1;', false);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $recent = RecentFavoriteTables::getInstance(TableType::Recent);
        $favorite = RecentFavoriteTables::getInstance(TableType::Favorite);

        $table = new RecentFavoriteTable(DatabaseName::from('invalid_db'), TableName::from('invalid_table'));
        self::assertEquals([$table], $recent->getTables());
        self::assertEquals([$table], $favorite->getTables());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'invalid_db', 'table' => 'invalid_table']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/sql&db=invalid_db&table=invalid_table&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );

        self::assertSame([], $recent->getTables());
        self::assertSame([], $favorite->getTables());
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testRecentFavoriteControllerWithInvalidDbAndTableName(): void
    {
        Current::$server = 2;

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => '', 'table' => '']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/&message=Invalid+database+or+table+name.&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );
    }
}
