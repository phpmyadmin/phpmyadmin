<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\RecentFavoriteController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Template;
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
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $_SESSION['tmpval'] = [
            'recentTables' => [2 => [['db' => 'test_db', 'table' => 'test_table']]],
            'favoriteTables' => [2 => [['db' => 'test_db', 'table' => 'test_table']]],
        ];

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $recent = RecentFavoriteTable::getInstance('recent');
        $favorite = RecentFavoriteTable::getInstance('favorite');

        self::assertSame([['db' => 'test_db', 'table' => 'test_table']], $recent->getTables());
        self::assertSame([['db' => 'test_db', 'table' => 'test_table']], $favorite->getTables());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer, new Template()))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/sql&db=test_db&table=test_table&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );

        self::assertSame([['db' => 'test_db', 'table' => 'test_table']], $recent->getTables());
        self::assertSame([['db' => 'test_db', 'table' => 'test_table']], $favorite->getTables());
    }

    public function testRecentFavoriteControllerWithInvalidDbAndTable(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $_SESSION['tmpval'] = [
            'recentTables' => [2 => [['db' => 'invalid_db', 'table' => 'invalid_table']]],
            'favoriteTables' => [2 => [['db' => 'invalid_db', 'table' => 'invalid_table']]],
        ];

        $dbiDummy = $this->createDbiDummy();
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        $dbiDummy->addResult('SHOW COLUMNS FROM `invalid_db`.`invalid_table`', false);
        $dbiDummy->addResult('SHOW COLUMNS FROM `invalid_db`.`invalid_table`', false);

        $recent = RecentFavoriteTable::getInstance('recent');
        $favorite = RecentFavoriteTable::getInstance('favorite');

        self::assertSame([['db' => 'invalid_db', 'table' => 'invalid_table']], $recent->getTables());
        self::assertSame([['db' => 'invalid_db', 'table' => 'invalid_table']], $favorite->getTables());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'invalid_db', 'table' => 'invalid_table']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer, new Template()))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/sql&db=invalid_db&table=invalid_table&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );

        self::assertSame([], $recent->getTables());
        self::assertSame([], $favorite->getTables());
    }

    public function testRecentFavoriteControllerWithInvalidDbAndTableName(): void
    {
        $GLOBALS['server'] = 2;
        $GLOBALS['text_dir'] = 'ltr';

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => '', 'table' => '']);

        $responseRenderer = new ResponseRenderer();
        (new RecentFavoriteController($responseRenderer, new Template()))($request);

        $response = $responseRenderer->getResponse();
        self::assertSame(302, $response->getStatusCode());
        self::assertStringEndsWith(
            'index.php?route=/&message=Invalid+database+or+table+name.&server=2&lang=en',
            $response->getHeaderLine('Location'),
        );
    }
}
