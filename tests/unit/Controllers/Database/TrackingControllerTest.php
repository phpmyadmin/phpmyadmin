<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Database\TrackingController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(TrackingController::class)]
final class TrackingControllerTest extends AbstractTestCase
{
    public function testWithoutVersions(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING => 'tracking',
            RelationParameters::TRACKING_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        UrlParams::$params = [];
        Current::$database = 'test_db';
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult("SELECT * FROM `pmadb`.`tracking` WHERE `db_name` = 'test_db' AND `version` = '1' ORDER BY `version` DESC LIMIT 1", []);
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        $dbiDummy->addResult("SELECT table_name, MAX(version) as version FROM `pmadb`.`tracking` WHERE db_name = 'test_db' GROUP BY table_name ORDER BY table_name ASC", []);
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        // phpcs:enable

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = ($this->getTrackingController($dbiDummy))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Tracking-testWithoutVersions.html',
            (string) $response->getBody(),
        );
    }

    private function getTrackingController(DbiDummy $dbiDummy): TrackingController
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $template = new Template($config);

        return new TrackingController(
            new ResponseRenderer(),
            new Tracking(
                new SqlQueryForm($template, $dbi, new BookmarkRepository($dbi, $relation)),
                $template,
                $relation,
                $dbi,
                new TrackingChecker($dbi, $relation),
            ),
            $dbi,
            new DbTableExists($dbi),
            $config,
        );
    }
}
