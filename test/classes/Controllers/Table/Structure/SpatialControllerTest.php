<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\SpatialController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Table\Structure\SpatialController */
class SpatialControllerTest extends AbstractTestCase
{
    public function testAddSpatialKeyToSingleField(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['message'] = null;
        $GLOBALS['sql_query'] = null;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', []);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);
        $controllerStub = $this->createMock(StructureController::class);
        $controllerStub->expects($this->once())->method('__invoke')->with($request);

        $indexes = new Indexes(new ResponseRenderer(), new Template(), $GLOBALS['dbi']);
        $controller = new SpatialController(new ResponseRenderer(), new Template(), $controllerStub, $indexes);
        $controller($request);

        $this->assertEquals(Message::success(), $GLOBALS['message']);
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testAddSpatialKeyToMultipleFields(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['message'] = null;
        $GLOBALS['sql_query'] = null;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', []);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field1', 'test_field2']]]);
        $controllerStub = $this->createMock(StructureController::class);
        $controllerStub->expects($this->once())->method('__invoke')->with($request);

        $indexes = new Indexes(new ResponseRenderer(), new Template(), $GLOBALS['dbi']);
        $controller = new SpatialController(new ResponseRenderer(), new Template(), $controllerStub, $indexes);
        $controller($request);

        $this->assertEquals(Message::success(), $GLOBALS['message']);
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testNoColumnsSelected(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['message'] = null;
        $GLOBALS['sql_query'] = null;

        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], null]]);
        $controllerStub = $this->createMock(StructureController::class);
        $controllerStub->expects($this->never())->method('__invoke');
        $response = new ResponseRenderer();

        $indexes = new Indexes(new ResponseRenderer(), new Template(), $GLOBALS['dbi']);
        $controller = new SpatialController($response, new Template(), $controllerStub, $indexes);
        $controller($request);

        $this->assertFalse($response->hasSuccessState());
        $this->assertSame(['message' => 'No column selected.'], $response->getJSONResult());
        /** @psalm-suppress RedundantCondition */
        $this->assertNull($GLOBALS['message']);
        /** @psalm-suppress RedundantCondition */
        $this->assertNull($GLOBALS['sql_query']);
    }

    public function testAddSpatialKeyWithError(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $GLOBALS['message'] = null;
        $GLOBALS['sql_query'] = null;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', false);
        $dbiDummy->addErrorCode('#1210 - Incorrect arguments to SPATIAL INDEX');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);
        $controllerStub = $this->createMock(StructureController::class);
        $controllerStub->expects($this->once())->method('__invoke')->with($request);

        $indexes = new Indexes(new ResponseRenderer(), new Template(), $GLOBALS['dbi']);
        $controller = new SpatialController(new ResponseRenderer(), new Template(), $controllerStub, $indexes);
        $controller($request);

        $this->assertEquals(
            Message::error('#1210 - Incorrect arguments to SPATIAL INDEX'),
            $GLOBALS['message'],
        );
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', $GLOBALS['sql_query']);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllErrorCodesConsumed();
    }
}
