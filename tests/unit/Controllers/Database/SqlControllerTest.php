<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\SqlController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlController::class)]
final class SqlControllerTest extends AbstractTestCase
{
    public function testController(): void
    {
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = ($this->getSqlController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringMatchesFormatFile(
            __DIR__ . '/Fixtures/Sql-testController.html',
            (string) $response->getBody(),
        );
    }

    private function getSqlController(DbiDummy $dbiDummy): SqlController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $responseRenderer = new ResponseRenderer();
        $relation = new Relation($dbi, $config);
        $template = new Template($config);

        return new SqlController(
            $responseRenderer,
            new SqlQueryForm($template, $dbi, new BookmarkRepository($dbi, $relation)),
            new PageSettings(new UserPreferences($dbi, $relation, $template, $config, new Clock()), $responseRenderer),
            new DbTableExists($dbi),
        );
    }
}
