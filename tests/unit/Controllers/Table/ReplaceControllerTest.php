<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Database\SqlController as DatabaseSqlController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\Controllers\Table\SqlController as TableSqlController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(ReplaceController::class)]
class ReplaceControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        Current::$database = 'my_db';
        Current::$table = 'test_tbl';

        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';
        $config->selectedServer['DisableIS'] = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::TABLE_COORDS => 'table_name',
            RelationParameters::DISPLAY_WORK => true,
            RelationParameters::DATABASE => 'information_schema',
            RelationParameters::TABLE_INFO => 'table_info',
            RelationParameters::REL_WORK => true,
            RelationParameters::RELATION => 'relation',
            RelationParameters::MIME_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::PDF_PAGES => 'pdf_pages',
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::BOOKMARK => 'bookmark',
            RelationParameters::UI_PREFS_WORK => true,
            RelationParameters::TABLE_UI_PREFS => 'table_uiprefs',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    public function testReplace(): void
    {
        UrlParams::$params = [];
        $_POST['db'] = Current::$database;
        $_POST['table'] = Current::$table;
        $_POST['ajax_request'] = 'true';
        $_POST['relational_display'] = 'K';

        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', null, ''],
            ['clause_is_unique', null, 1],
            ['where_clause', [], ['`test`.`ser` = 2', '`test`.`ser` = 1']],
            ['rel_fields_list', '', ''],
            ['do_transformations', null, true],
            ['transform_fields_list', '', '0%5Bvc%5D=sss%20s%20s&1%5Bvc%5D=zzff%20s%20sf%0A'],
            ['submit_type', '', 'save'],
            ['fields', [], ['multi_edit' => [['zzff s sf'], ['sss s s']]]],
            ['fields_name', [], ['multi_edit' => [['vc'], ['vc']]]],
            ['fields_null', [], ['multi_edit' => [[], []]]],
        ]);

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $relation = new Relation($dbi);
        $transformations = new Transformations($dbi, $relation);
        $template = new Template();
        $response = new ResponseRenderer();

        $pageSettings = self::createStub(PageSettings::class);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $config = Config::getInstance();
        $sqlController = new SqlController(
            $response,
            new Sql(
                $dbi,
                $relation,
                new RelationCleanup($dbi, $relation),
                $transformations,
                $template,
                $bookmarkRepository,
                $config,
            ),
            $pageSettings,
            $bookmarkRepository,
            $config,
        );

        $replaceController = new ReplaceController(
            $response,
            new InsertEdit($dbi, $relation, $transformations, new FileListing(), $template, $config),
            $transformations,
            $relation,
            $dbi,
            $sqlController,
            self::createStub(DatabaseSqlController::class),
            self::createStub(ChangeController::class),
            self::createStub(TableSqlController::class),
        );

        UrlParams::$goto = 'index.php?route=/sql';
        $dummyDbi->addSelectDb('my_db');
        $dummyDbi->addSelectDb('my_db');
        $dummyDbi->addResult(
            "SELECT COLUMN_NAME, CASE WHEN INSTR(EXTRA, 'DEFAULT_GENERATED') THEN COLUMN_DEFAULT "
                . "ELSE CONCAT('''', COLUMN_DEFAULT, '''') END AS COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS "
                . "WHERE TABLE_NAME = 'test_tbl' AND TABLE_SCHEMA = 'my_db'",
            [],
        );
        $replaceController($request);
        $output = $response->getHTMLResult();
        $this->dummyDbi->assertAllSelectsConsumed();
        self::assertStringContainsString(
            'class="icon ic_s_success"> Showing rows 0 -  1 (2 total, Query took',
            $output,
        );
        self::assertStringContainsString('SELECT * FROM `test_tbl`', $output);
    }

    /**
     * Test for getParamsForUpdateOrInsert
     */
    public function testGetParamsForUpdateOrInsert(): void
    {
        $request1 = self::createStub(ServerRequest::class);
        $request1->method('getParsedBodyParam')->willReturnMap([
            ['where_clause', null, 'LIMIT 1'],
            ['submit_type', null, 'showinsert'],
        ]);

        $replaceController = new ReplaceController(
            self::createStub(ResponseRenderer::class),
            self::createStub(InsertEdit::class),
            self::createStub(Transformations::class),
            self::createStub(Relation::class),
            self::createStub(DatabaseInterface::class),
            self::createStub(SqlController::class),
            self::createStub(DatabaseSqlController::class),
            self::createStub(ChangeController::class),
            self::createStub(TableSqlController::class),
        );

        /** @var array $result */
        $result = $this->callFunction(
            $replaceController,
            ReplaceController::class,
            'getParamsForUpdateOrInsert',
            [$request1],
        );

        self::assertSame(
            [['LIMIT 1'], true, true],
            $result,
        );

        // case 2 (else)
        $request2 = self::createStub(ServerRequest::class);
        $request2->method('getParsedBodyParam')->willReturnMap([
            ['fields', null, ['multi_edit' => ['a' => 'b', 'c' => 'd']]],
        ]);

        /** @var array $result */
        $result = $this->callFunction(
            $replaceController,
            ReplaceController::class,
            'getParamsForUpdateOrInsert',
            [$request2],
        );

        self::assertSame(
            [['a', 'c'], false, true],
            $result,
        );
    }
}
