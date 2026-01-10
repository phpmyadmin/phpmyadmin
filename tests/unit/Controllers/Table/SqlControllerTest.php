<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\SqlController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlController::class)]
#[CoversClass(SqlQueryForm::class)]
class SqlControllerTest extends AbstractTestCase
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

    public function testSqlController(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$lang = 'en';
        $config = Config::getInstance();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();

        $this->dummyDbi->addSelectDb('test_db');
        $this->dummyDbi->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);

        $template = new Template($config);
        $userPreferences = new UserPreferences($this->dbi, new Relation($this->dbi, $config), $template, $config);
        $pageSettings = new PageSettings($userPreferences);
        $pageSettings->init('Sql');
        $fields = $this->dbi->getColumns('test_db', 'test_table');

        $expected = $pageSettings->getHTML();
        $expected .= $template->render('sql/query', [
            'legend' => 'Run SQL query/queries on table <a href="'
                . 'index.php?route=/sql&server=2&lang=en&db=test_db&table=test_table&server=2&lang=en'
                . '">test_db.test_table</a>: ' . MySQLDocumentation::show('SELECT'),
            'textarea_cols' => 40,
            'textarea_rows' => 15,
            'textarea_auto_select' => false,
            'columns_list' => [
                'id' => $fields['id'],
                'name' => $fields['name'],
                'datetimefield' => $fields['datetimefield'],
            ],
            'codemirror_enable' => true,
            'has_bookmark' => false,
            'delimiter' => ';',
            'retain_query_box' => false,
            'is_upload' => true,
            'db' => 'test_db',
            'table' => 'test_table',
            'goto' => 'index.php?route=/table/sql&server=2&lang=en',
            'query' => 'SELECT * FROM `test_table` WHERE 1',
            'display_tab' => 'full',
            'bookmarks' => [],
            'can_convert_kanji' => false,
            'is_foreign_key_check' => true,
        ]);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = new ResponseRenderer();
        $relation = new Relation($this->dbi);
        $bookmarkRepository = new BookmarkRepository($this->dbi, $relation);
        (new SqlController(
            $response,
            new SqlQueryForm($template, $this->dbi, $bookmarkRepository),
            $pageSettings,
            new DbTableExists($this->dbi),
        ))($request);
        self::assertSame($expected, $response->getHTMLResult());
    }
}
