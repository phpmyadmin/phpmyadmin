<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\CheckUserPrivileges;
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
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
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
            'table_coords' => 'table_name',
            'displaywork' => true,
            'db' => 'information_schema',
            'table_info' => 'table_info',
            'relwork' => true,
            'relation' => 'relation',
            'mimework' => true,
            'commwork' => true,
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages',
            'bookmarkwork' => true,
            'bookmark' => 'bookmark',
            'uiprefswork' => true,
            'table_uiprefs' => 'table_uiprefs',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    public function testReplace(): void
    {
        $GLOBALS['urlParams'] = [];
        $_POST['db'] = Current::$database;
        $_POST['table'] = Current::$table;
        $_POST['ajax_request'] = 'true';
        $_POST['relational_display'] = 'K';
        $_POST['goto'] = 'index.php?route=/sql';

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', null, ''],
            ['clause_is_unique', null, 1],
            ['where_clause', [], ['`test`.`ser` = 2', '`test`.`ser` = 1']],
            ['rel_fields_list', '', ''],
            ['do_transformations', null, true],
            ['transform_fields_list', '', '0%5Bvc%5D=sss%20s%20s&1%5Bvc%5D=zzff%20s%20sf%0A'],
            ['submit_type', '', 'save'],
            ['fields', [], ['multi_edit' => [0 => ['zzff s sf'], 1 => ['sss s s']]]],
            ['fields_name', [], ['multi_edit' => [0 => ['vc'], 1 => ['vc']]]],
            ['fields_null', [], ['multi_edit' => [0 => [], 1 => []]]],
        ]);

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $relation = new Relation($dbi);
        $transformations = new Transformations();
        $template = new Template();
        $response = new ResponseRenderer();

        $pageSettings = $this->createStub(PageSettings::class);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $sqlController = new SqlController(
            $response,
            $template,
            new Sql(
                $dbi,
                $relation,
                new RelationCleanup($dbi, $relation),
                new Operations($dbi, $relation),
                $transformations,
                $template,
                $bookmarkRepository,
            ),
            new CheckUserPrivileges($dbi),
            $dbi,
            $pageSettings,
            $bookmarkRepository,
        );

        $replaceController = new ReplaceController(
            $response,
            $template,
            new InsertEdit($dbi, $relation, $transformations, new FileListing(), $template),
            $transformations,
            $relation,
            $dbi,
            $sqlController,
            self::createStub(DatabaseSqlController::class),
            self::createStub(ChangeController::class),
            self::createStub(TableSqlController::class),
        );

        $GLOBALS['goto'] = 'index.php?route=/sql';
        $dummyDbi->addSelectDb('my_db');
        $dummyDbi->addSelectDb('my_db');
        $replaceController($request);
        $output = $response->getHTMLResult();
        $this->dummyDbi->assertAllSelectsConsumed();
        $this->assertStringContainsString(
            'class="icon ic_s_success"> Showing rows 0 -  1 (2 total, Query took',
            $output,
        );
        $this->assertStringContainsString('SELECT * FROM `test_tbl`', $output);
    }

    /**
     * Test for getParamsForUpdateOrInsert
     */
    public function testGetParamsForUpdateOrInsert(): void
    {
        $request1 = $this->createStub(ServerRequest::class);
        $request1->method('getParsedBodyParam')->willReturnMap([
            ['where_clause', null, 'LIMIT 1'],
            ['submit_type', null, 'showinsert'],
        ]);

        $replaceController = new ReplaceController(
            self::createStub(ResponseRenderer::class),
            self::createStub(Template::class),
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

        $this->assertEquals(
            [['LIMIT 1'], true, true],
            $result,
        );

        // case 2 (else)
        $request2 = $this->createStub(ServerRequest::class);
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

        $this->assertEquals(
            [['a', 'c'], false, true],
            $result,
        );
    }
}
