<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\Structure\AddPrefixTableController;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Message;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking\TrackingChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(AddPrefixTableController::class)]
final class AddPrefixTableControllerTest extends AbstractTestCase
{
    public function testAddPrefix(): void
    {
        (new ReflectionProperty(RecentFavoriteTables::class, 'instances'))->setValue(null, []);
        $_SESSION['tmpval'] = [];
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $_REQUEST['db'] = Current::$database = 'test_db';
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `table` RENAME `test_table`', true);
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dbiDummy->addResult('SELECT COUNT(*) FROM `test_db`.`test_table`', [['3']]);
        $dbiDummy->addResult(
            "SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'test_db' LIMIT 1",
            [['utf8mb4_uca1400_ai_ci']],
            ['DEFAULT_COLLATION_NAME'],
        );
        $dbiDummy->addResult('SELECT @@default_storage_engine;', [['InnoDB']]);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['db' => 'test_db', 'add_prefix' => 'test_', 'selected' => ['table']]);

        $response = ($this->getAddPrefixTableController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame("ALTER TABLE `table` RENAME `test_table`;\n", Current::$sqlQuery);
        self::assertEquals(Message::success(), Current::$message);
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/AddPrefixTable-testAddPrefix.html',
            (string) $response->getBody(),
        );
    }

    private function getAddPrefixTableController(DbiDummy $dbiDummy): AddPrefixTableController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);
        $structureController = new StructureController(
            $responseRenderer,
            $template,
            $relation,
            new Replication($dbi),
            $dbi,
            new TrackingChecker($dbi, $relation),
            new PageSettings(new UserPreferences($dbi, $relation, $template, $config, new Clock()), $responseRenderer),
            new DbTableExists($dbi),
            $config,
        );

        return new AddPrefixTableController($dbi, $structureController);
    }
}
