<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\SearchController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SearchController::class)]
final class SearchControllerTest extends AbstractTestCase
{
    public function testController(): void
    {
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['route' => '/database/search', 'db' => 'test_db']);

        $response = ($this->getSearchController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Search-testController.html',
            (string) $response->getBody(),
        );
    }

    private function getSearchController(DbiDummy $dbiDummy): SearchController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);

        return new SearchController(
            new ResponseRenderer(),
            new Template($config),
            $dbi,
            new DbTableExists($dbi),
            $config,
        );
    }
}
