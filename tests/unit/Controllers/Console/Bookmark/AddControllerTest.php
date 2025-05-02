<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console\Bookmark;

use InvalidArgumentException;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Console\Bookmark\AddController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
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
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => null,
                'label' => null,
                'bookmark_query' => null,
                'shared' => null,
            ]);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, $bookmarkRepository, new Config());
        $this->expectException(InvalidArgumentException::class);
        $controller($request);
    }

    public function testWithoutRelationParameters(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'user';
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $request = self::createStub(ServerRequest::class);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, $bookmarkRepository, $config);
        $controller($request);
        self::assertSame(['message' => 'Failed'], $response->getJSONResult());
    }

    public function testWithValidParameters(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['user'] = 'test_user';
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
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'db' => 'test_db',
                'label' => 'test_label',
                'bookmark_query' => 'test_query',
                'shared' => 'true',
            ]);
        $relation = new Relation($dbi);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation);
        $controller = new AddController($response, $bookmarkRepository, $config);
        $controller($request);
        self::assertSame(
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
