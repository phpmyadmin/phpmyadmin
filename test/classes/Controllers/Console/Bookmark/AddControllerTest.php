<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Console\Bookmark;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\Console\Bookmark\AddController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use ReflectionClass;

/** @covers \PhpMyAdmin\Controllers\Console\Bookmark\AddController */
class AddControllerTest extends AbstractTestCase
{
    public function testWithInvalidParams(): void
    {
        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, null],
            ['label', null, null],
            ['bookmark_query', null, null],
            ['shared', null, null],
        ]);
        $controller = new AddController($response, new Template(), $dbi);
        $controller($request);
        $this->assertSame(['message' => 'Incomplete params'], $response->getJSONResult());
    }

    public function testWithoutRelationParameters(): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'user';
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([]);
        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test'],
            ['label', null, 'test'],
            ['bookmark_query', null, 'test'],
            ['shared', null, 'test'],
        ]);
        $controller = new AddController($response, new Template(), $dbi);
        $controller($request);
        $this->assertSame(['message' => 'Failed'], $response->getJSONResult());
    }

    public function testWithValidParameters(): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'test_user';
        $GLOBALS['server'] = 1;
        $relationParameters = RelationParameters::fromArray([
            'user' => 'test_user',
            'db' => 'pmadb',
            'bookmarkwork' => true,
            'bookmark' => 'bookmark',
        ]);
        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue(
            [$GLOBALS['server'] => $relationParameters],
        );

        $dbiDummy = $this->createDbiDummy();
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $dbiDummy->addResult('INSERT INTO `pmadb`.`bookmark` (id, dbase, user, query, label) VALUES (NULL, \'test_db\', \'\', \'test_query\', \'test_label\')', []);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, 'test_db'],
            ['label', null, 'test_label'],
            ['bookmark_query', null, 'test_query'],
            ['shared', null, 'true'],
        ]);
        $controller = new AddController($response, new Template(), $dbi);
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
