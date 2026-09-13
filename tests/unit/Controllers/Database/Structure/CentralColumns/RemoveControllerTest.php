<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure\CentralColumns;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Database\Structure\CentralColumns\RemoveController;
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

#[CoversClass(RemoveController::class)]
final class RemoveControllerTest extends AbstractTestCase
{
    public function testRemoveUsesCurrentDatabase(): void
    {
        (new ReflectionProperty(RecentFavoriteTables::class, 'instances'))->setValue(null, []);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, RelationParameters::fromArray([
            RelationParameters::CENTRAL_COLUMNS_WORK => true,
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::CENTRAL_COLUMNS => 'pma_central_columns',
        ]));
        $_SESSION['tmpval'] = [];
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $_REQUEST['db'] = Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        // CentralColumns::deleteColumnsFromList() must target Current::$database
        $dbiDummy->addResult(
            'SELECT col_name FROM `phpmyadmin`.`pma_central_columns`'
                . " WHERE db_name = 'test_db' AND col_name IN ('id','name','datetimefield');",
            [['id'], ['name'], ['datetimefield']],
            ['col_name'],
        );
        $dbiDummy->addResult(
            'DELETE FROM `phpmyadmin`.`pma_central_columns`'
                . " WHERE db_name = 'test_db' AND col_name IN ('id','name','datetimefield');",
            true,
        );
        // StructureController renders the database structure page afterwards
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
            ->withParsedBody(['db' => 'test_db', 'selected_tbl' => ['test_table']]);

        $response = ($this->getRemoveController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertEquals(Message::success('Success!'), Current::$message);
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
    }

    private function getRemoveController(DbiDummy $dbiDummy): RemoveController
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

        return new RemoveController($responseRenderer, $dbi, $structureController);
    }
}
