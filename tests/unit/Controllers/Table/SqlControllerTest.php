<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\SqlController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlController::class)]
#[CoversClass(SqlQueryForm::class)]
final class SqlControllerTest extends AbstractTestCase
{
    public function testSqlController(): void
    {
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$lang = 'en';

        $config = Config::$instance = new Config();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();

        $dbiDummy = $this->createDbiDummy();
        $dbi = DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);

        $template = new Template($config);
        $relation = new Relation($dbi, $config);
        $userPreferences = new UserPreferences(
            $dbi,
            $relation,
            $template,
            $config,
            new Clock(),
        );
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $bookmarkRepository = new BookmarkRepository($dbi, $relation, $config);
        $responseRenderer = new ResponseRenderer();
        $response = (new SqlController(
            $responseRenderer,
            new SqlQueryForm($template, $dbi, $bookmarkRepository),
            new PageSettings($userPreferences, $responseRenderer),
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Sql-testSqlController.html',
            (string) $response->getBody(),
        );
    }
}
