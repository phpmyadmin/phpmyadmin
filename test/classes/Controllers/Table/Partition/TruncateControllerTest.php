<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Partition;

use PhpMyAdmin\Controllers\Table\Partition\TruncateController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Partitioning\Maintenance;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Table\Partition\TruncateController */
class TruncateControllerTest extends AbstractTestCase
{
    /** @dataProvider providerForTestInvalidDatabaseAndTable */
    public function testInvalidDatabaseAndTable(
        string|null $partition,
        string|null $db,
        string|null $table,
        string $message,
    ): void {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['partition_name', null, $partition]]);
        $request->method('getParam')->willReturnMap([['db', null, $db], ['table', null, $table]]);
        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();

        $controller = new TruncateController($response, new Template(), new Maintenance($dbi));
        $controller($request);

        $this->assertSame(Message::error($message)->getDisplay(), $response->getHTMLResult());
    }

    /** @return array<int, array{string|null, string|null, string|null, non-empty-string}> */
    public static function providerForTestInvalidDatabaseAndTable(): iterable
    {
        return [
            [null, null, null, 'The partition name must be a non-empty string.'],
            ['', null, null, 'The partition name must be a non-empty string.'],
            ['partitionName', null, null, 'The database name must be a non-empty string.'],
            ['partitionName', '', null, 'The database name must be a non-empty string.'],
            ['partitionName', 'databaseName', null, 'The table name must be a non-empty string.'],
            ['partitionName', 'databaseName', '', 'The table name must be a non-empty string.'],
        ];
    }
}
