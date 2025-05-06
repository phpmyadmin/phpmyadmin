<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Table\DeleteRowsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DeleteRowsController::class)]
class DeleteRowsControllerTest extends AbstractTestCase
{
    public function testDeleteRowsController(): void
    {
        UrlParams::$goto = '';
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        UrlParams::$params = [];
        $config = Config::getInstance();
        $config->selectedServer = $config->getSettings()->Servers[1]->asArray();
        $config->selectedServer['DisableIS'] = true;
        $_POST = [
            'db' => 'test_db',
            'table' => 'test_table',
            'selected' => [2 => '`test_table`.`id` = 3'],
            'fk_checks' => '1',
            'mult_btn' => 'Yes',
        ];

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult('DELETE FROM `test_table` WHERE `test_table`.`id` = 3 LIMIT 1;', true);
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SELECT * FROM `test_db`.`test_table` LIMIT 0, 25',
            [['1', 'abcd', '2011-01-20 02:00:02'], ['2', 'foo', '2010-01-20 02:00:02']],
            ['id', 'name', 'datetimefield'],
        );
        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = \'test_db\''
            . ' AND TABLE_NAME = \'test_table\' AND IS_UPDATABLE = \'YES\'',
            [],
            ['TABLE_NAME'],
        );
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['original_sql_query' => 'SELECT * FROM `test_db`.`test_table`']);

        $relation = new Relation($dbi, $config);
        $sql = new Sql(
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Transformations(),
            new Template($config),
            new BookmarkRepository($dbi, $relation),
            $config,
        );

        $response = new ResponseRenderer();
        (new DeleteRowsController($response, $dbi, $sql))($request);
        $actual = $response->getHTMLResult();
        self::assertStringContainsString(
            '<div class="alert alert-success border-top-0 border-start-0 border-end-0 rounded-bottom-0 mb-0"'
            . ' role="alert">' . "\n"
            . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_success">'
            . ' Your SQL query has been executed successfully.' . "\n"
            . '</div>',
            $actual,
        );
        self::assertStringContainsString('DELETE FROM `test_table` WHERE `test_table`.`id` = 3 LIMIT 1;', $actual);
        self::assertStringContainsString('Showing rows 0 -  1 (2 total, Query took', $actual);
        self::assertStringContainsString('SELECT * FROM `test_db`.`test_table`', $actual);
        self::assertStringContainsString(
            '<td data-decimals="0" data-type="string" data-originallength="4" class="data text pre_wrap">abcd</td>',
            $actual,
        );
    }
}
