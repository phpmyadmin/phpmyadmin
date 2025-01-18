<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\TrackingController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TrackingController::class)]
class TrackingControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testTrackingController(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->selectedServer['tracking_default_statements'] = 'CREATE TABLE,ALTER TABLE,DROP TABLE';

        $this->dummyDbi->addSelectDb('test_db');

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table'])
            ->withParsedBody(['version' => '', 'table' => '']);

        $responseRenderer = new ResponseRenderer();
        $template = new Template();
        $trackingChecker = self::createStub(TrackingChecker::class);
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        $response = (new TrackingController(
            $responseRenderer,
            new Tracking(
                new SqlQueryForm($template, $this->dbi, $bookmarkRepository),
                $template,
                $relation,
                $this->dbi,
                $trackingChecker,
            ),
            $trackingChecker,
            new DbTableExists($this->dbi),
            ResponseFactory::create(),
        ))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        $main = $template->render('table/tracking/main', [
            'url_params' => [
                'db' => Current::$database,
                'table' => Current::$table,
                'goto' => 'index.php?route=/table/tracking&server=2&lang=en',
                'back' => 'index.php?route=/table/tracking&server=2&lang=en',
            ],
            'db' => Current::$database,
            'table' => Current::$table,
            'selectable_tables_entries' => [],
            'selected_table' => null,
            'last_version' => 0,
            'versions' => [],
            'type' => 'table',
            'default_statements' => $config->selectedServer['tracking_default_statements'],
        ]);
        $expected = $template->render('table/tracking/index', [
            'active_message' => '',
            'action_message' => '',
            'delete_version' => '',
            'create_version' => '',
            'deactivate_tracking' => '',
            'activate_tracking' => '',
            'message' => '',
            'sql_dump' => '',
            'schema_snapshot' => '',
            'tracking_report_rows' => '',
            'tracking_report' => '',
            'main' => $main,
        ]);

        self::assertSame($expected, $responseRenderer->getHTMLResult());
    }
}
