<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\DatabaseController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\ListDatabase;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DatabaseController::class)]
class DatabaseControllerTest extends AbstractTestCase
{
    public function testDatabaseController(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $dbiDummy->addResult(
            'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`',
            [['test_db_1'], ['test_db_2']],
            ['SCHEMA_NAME'],
        );

        $responseRenderer = new ResponseRenderer();
        $controller = new DatabaseController($responseRenderer, $dbi);
        $controller($request);

        $output = $responseRenderer->getJSONResult();
        self::assertArrayHasKey('databases', $output);
        self::assertInstanceOf(ListDatabase::class, $output['databases']);
        self::assertSame(['test_db_1', 'test_db_2'], $output['databases']->getArrayCopy());

        $dbiDummy->assertAllQueriesConsumed();
    }
}
