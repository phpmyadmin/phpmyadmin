<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\SpatialController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SpatialController::class)]
class SpatialControllerTest extends AbstractTestCase
{
    public function testAddSpatialKeyToSingleField(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$message = null;
        Current::$sqlQuery = '';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);
        $controllerStub = self::createMock(StructureController::class);
        $controllerStub->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $controller = new SpatialController(new ResponseRenderer(), $controllerStub, $indexes);
        $controller($request);

        self::assertEquals(Message::success(), Current::$message);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', Current::$sqlQuery);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testAddSpatialKeyToMultipleFields(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$message = null;
        Current::$sqlQuery = '';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', true);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field1', 'test_field2']]]);
        $controllerStub = self::createMock(StructureController::class);
        $controllerStub->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $controller = new SpatialController(new ResponseRenderer(), $controllerStub, $indexes);
        $controller($request);

        self::assertEquals(Message::success(), Current::$message);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field1`, `test_field2`);', Current::$sqlQuery);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testNoColumnsSelected(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$message = null;
        Current::$sqlQuery = '';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], null]]);
        $controllerStub = self::createMock(StructureController::class);
        $controllerStub->expects(self::never())->method('__invoke');
        $response = new ResponseRenderer();

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $controller = new SpatialController($response, $controllerStub, $indexes);
        $controller($request);

        self::assertFalse($response->hasSuccessState());
        self::assertSame(['message' => 'No column selected.'], $response->getJSONResult());
        /** @psalm-suppress RedundantCondition */
        self::assertNull(Current::$message);
        /** @psalm-suppress RedundantCondition */
        self::assertEmpty(Current::$sqlQuery);
    }

    public function testAddSpatialKeyWithError(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$message = null;
        Current::$sqlQuery = '';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', false);
        $dbiDummy->addErrorCode('#1210 - Incorrect arguments to SPATIAL INDEX');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['selected_fld', [], ['test_field']]]);
        $controllerStub = self::createMock(StructureController::class);
        $controllerStub->expects(self::once())->method('__invoke')->with($request)
            ->willReturn(ResponseFactory::create()->createResponse());

        $indexes = new Indexes(DatabaseInterface::getInstance());
        $controller = new SpatialController(new ResponseRenderer(), $controllerStub, $indexes);
        $controller($request);

        self::assertEquals(
            Message::error('#1210 - Incorrect arguments to SPATIAL INDEX'),
            Current::$message,
        );
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('ALTER TABLE `test_table` ADD SPATIAL(`test_field`);', Current::$sqlQuery);
        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllErrorCodesConsumed();
    }
}
