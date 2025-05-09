<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SearchController::class)]
final class SearchControllerTest extends AbstractTestCase
{
    public function testRangeSearch(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);
        $dbiDummy->addResult(
            'SELECT MIN(`column`) AS `min`, MAX(`column`) AS `max` FROM `test_db`.`test_table`',
            [['1', '2']],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $_POST['range_search'] = '1';
        $_POST['column'] = 'column';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com')
            ->withParsedBody(['db' => 'test_db', 'table' => 'test_table']);

        $config = new Config();
        $relation = new Relation($dbi, $config);
        $template = new Template($config);
        $sql = new Sql(
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Transformations(),
            $template,
            new BookmarkRepository($dbi, $relation),
            $config,
        );

        $responseRenderer = new ResponseRenderer();
        $controller = new SearchController(
            $responseRenderer,
            $template,
            new Search($dbi),
            $relation,
            $dbi,
            new DbTableExists($dbi),
            $config,
            $sql,
        );
        $controller($request);

        self::assertSame(['column_data' => ['1', '2']], $responseRenderer->getJSONResult());
    }
}
