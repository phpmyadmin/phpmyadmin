<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console\Bookmark;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Console\Bookmark\AddController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(AddController::class)]
class AddControllerTest extends AbstractTestCase
{
    public function testWithInvalidParams(): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, null],
            ['label', null, null],
            ['bookmark_query', null, null],
            ['shared', null, null],
        ]);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, new Template(), $bookmarkRepository);
        $controller($request);
        $this->assertSame(['message' => 'Incomplete params'], $response->getJSONResult());
    }

    public function testWithoutRelationParameters(): void
    {
        Config::getInstance()->selectedServer['user'] = 'user';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test'],
            ['label', null, 'test'],
            ['bookmark_query', null, 'test'],
            ['shared', null, 'test'],
        ]);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, new Template(), $bookmarkRepository);
        $controller($request);
        $this->assertSame(['message' => 'Failed'], $response->getJSONResult());
    }

    public function testWithValidParameters(): void
    {
        Config::getInstance()->selectedServer['user'] = 'test_user';
        $GLOBALS['server'] = 1;
        $relationParameters = RelationParameters::fromArray([
            'user' => 'test_user',
            'db' => 'pmadb',
            'bookmarkwork' => true,
            'bookmark' => 'bookmark',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'INSERT INTO `pmadb`.`bookmark` (id, dbase, user, query, label)'
            . ' VALUES (NULL, \'test_db\', \'\', \'test_query\', \'test_label\')',
            true,
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['label', null, 'test_label'],
            ['bookmark_query', null, 'test_query'],
            ['shared', null, 'true'],
        ]);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, new Template(), $bookmarkRepository);
        $controller($request);
        $this->assertSame(
            [
                'message' => 'Succeeded',
                'data' => [
                    'bkm_database' => 'test_db',
                    'bkm_user' => 'test_user',
                    'bkm_sql_query' => 'test_query',
                    'bkm_label' => 'test_label',
                ],
                'isShared' => true,
            ],
            $response->getJSONResult(),
        );
        $dbiDummy->assertAllQueriesConsumed();
    }
}
