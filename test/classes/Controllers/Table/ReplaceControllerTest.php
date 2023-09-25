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
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\ChangeController;
use PhpMyAdmin\Controllers\Table\ReplaceController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
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
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $GLOBALS['server'] = 1;
        $GLOBALS['showtable'] = null;
        $GLOBALS['db'] = 'my_db';
        $GLOBALS['table'] = 'test_tbl';

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
        $_POST['db'] = $GLOBALS['db'];
        $_POST['table'] = $GLOBALS['table'];
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
        $replaceController = new ReplaceController(
            $response,
            $template,
            new InsertEdit($dbi, $relation, $transformations, new FileListing(), $template),
            $transformations,
            $relation,
            $dbi,
        );

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
        $GLOBALS['containerBuilder'] = $this->createStub(ContainerBuilder::class);
        $GLOBALS['containerBuilder']->method('get')->willReturn($sqlController);

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

    public function testIsInsertRow(): void
    {
        $GLOBALS['urlParams'] = [];
        $GLOBALS['goto'] = 'index.php?route=/sql';
        $_POST['sql_query'] = 'SELECT 1';
        $config = Config::getInstance();
        $config->settings['InsertRows'] = 2;
        $config->selectedServer['host'] = 'host.tld';
        $config->selectedServer['verbose'] = '';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $transformations = new Transformations();
        $template = new Template();
        $response = new ResponseRenderer();
        $insertEdit = new InsertEdit($dbi, $relation, $transformations, new FileListing(), $template);
        $replaceController = new ReplaceController(
            $response,
            $template,
            $insertEdit,
            $transformations,
            $relation,
            $dbi,
        );

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'my_db', 'table' => 'test_tbl'])
            ->withParsedBody(['insert_rows' => '5', 'sql_query' => 'SELECT 1']);

        $pageSettings = $this->createStub(PageSettings::class);
        $changeController = new ChangeController(
            $response,
            $template,
            $insertEdit,
            $relation,
            $pageSettings,
            new DbTableExists($dbi),
        );
        $GLOBALS['containerBuilder'] = $this->createStub(ContainerBuilder::class);
        $GLOBALS['containerBuilder']->method('get')->willReturn($changeController);

        $dummyDbi->addSelectDb('my_db');
        $dummyDbi->addSelectDb('my_db');
        $dummyDbi->addResult('SHOW TABLES LIKE \'test_tbl\';', [['test_tbl']]);
        $dummyDbi->addResult('SELECT * FROM `my_db`.`test_tbl` LIMIT 1;', []);
        $dummyDbi->addSelectDb('my_db');

        $replaceController($request);
        $output = $response->getHTMLResult();
        $this->dummyDbi->assertAllSelectsConsumed();
        $this->assertEquals(5, $config->settings['InsertRows']);
        $this->assertStringContainsString(
            '<form id="continueForm" method="post" '
            . 'action="index.php?route=/table/replace&lang=en" name="continueForm">',
            $output,
        );
        $this->assertStringContainsString(
            'Continue insertion with         <input type="number" '
            . 'name="insert_rows" id="insert_rows" value="5" min="1">',
            $output,
        );
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
            $this->createStub(ResponseRenderer::class),
            $this->createStub(Template::class),
            $this->createStub(InsertEdit::class),
            $this->createStub(Transformations::class),
            $this->createStub(Relation::class),
            $this->createStub(DatabaseInterface::class),
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
