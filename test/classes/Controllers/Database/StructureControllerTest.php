<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Tracking\TrackingChecker;
use ReflectionClass;
use ReflectionException;

/** @covers \PhpMyAdmin\Controllers\Database\StructureController */
class StructureControllerTest extends AbstractTestCase
{
    private ResponseStub $response;

    private Relation $relation;

    private Replication $replication;

    private Template $template;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setTheme();

        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Expect the table will have 6 rows
        $table->expects($this->any())->method('getRealRowCountTable')
            ->will($this->returnValue(6));
        $table->expects($this->any())->method('countRecords')
            ->will($this->returnValue(6));

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $GLOBALS['dbi'] = $dbi;

        $this->template = new Template();
        $this->response = new ResponseStub();
        $this->relation = new Relation($dbi);
        $this->replication = new Replication($dbi);
    }

    /**
     * Tests for getValuesForInnodbTable()
     */
    public function testGetValuesForInnodbTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForInnodbTable');
        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        $property->setValue($controller, true);

        $GLOBALS['cfg']['MaxExactCount'] = 10;
        $currentTable = [
            'ENGINE' => 'InnoDB',
            'TABLE_ROWS' => 5,
            'Data_length' => 16384,
            'Index_length' => 0,
            'TABLE_NAME' => 'table',
        ];
        [$currentTable, , , $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 10],
        );

        $this->assertTrue($currentTable['COUNTED']);
        $this->assertEquals(6, $currentTable['TABLE_ROWS']);
        $this->assertEquals(16394, $sumSize);

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 10],
        );

        $this->assertFalse($currentTable['COUNTED']);
        $this->assertEquals(16394, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );

        $currentTable['ENGINE'] = 'InnoDB';
        [$currentTable, , , $sumSize] = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertTrue($currentTable['COUNTED']);
        $this->assertEquals(10, $sumSize);

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertFalse($currentTable['COUNTED']);
        $this->assertEquals(10, $sumSize);
    }

    /**
     * Tests for the getValuesForAriaTable()
     */
    public function testGetValuesForAriaTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForAriaTable');

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        $property->setValue($controller, true);
        $property = $class->getProperty('dbIsSystemSchema');
        $property->setValue($controller, true);

        $currentTable = ['Data_length' => 16384, 'Index_length' => 0, 'Name' => 'table', 'Data_free' => 300];
        [$currentTable, , , , , $overheadSize, $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        $this->assertEquals(6, $currentTable['Rows']);
        $this->assertEquals(16384, $sumSize);
        $this->assertEquals(300, $overheadSize);

        unset($currentTable['Data_free']);
        [$currentTable, , , , , $overheadSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        $this->assertEquals(0, $overheadSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
        [$currentTable, , , , , , $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        $this->assertEquals(0, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
        [$currentTable] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        $this->assertArrayNotHasKey('Row', $currentTable);
    }

    /**
     * Tests for hasTable()
     */
    public function testHasTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('hasTable');

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );

        // When parameter $db is empty
        $this->assertFalse(
            $method->invokeArgs($controller, [[], 'table']),
        );

        // Correct parameter
        $tables = ['db.table'];
        $this->assertTrue(
            $method->invokeArgs($controller, [$tables, 'table']),
        );

        // Table not in database
        $tables = ['db.tab1e'];
        $this->assertFalse(
            $method->invokeArgs($controller, [$tables, 'table']),
        );
    }

    /**
     * Tests for checkFavoriteTable()
     */
    public function testCheckFavoriteTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('checkFavoriteTable');

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );

        $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] = [['db' => 'db', 'table' => 'table']];

        $this->assertFalse(
            $method->invokeArgs($controller, ['']),
        );

        $this->assertTrue(
            $method->invokeArgs($controller, ['table']),
        );
    }

    /** @throws ReflectionException */
    public function testDisplayTableList(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('displayTableList');

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $GLOBALS['dbi'],
            $this->createStub(TrackingChecker::class),
        );
        // Showing statistics
        $class = new ReflectionClass(StructureController::class);
        $showStatsProperty = $class->getProperty('isShowStats');
        $showStatsProperty->setValue($controller, true);

        $tablesProperty = $class->getProperty('tables');

        $numTables = $class->getProperty('numTables');
        $numTables->setValue($controller, 1);

        //no tables
        $_REQUEST['db'] = 'my_unique_test_db';
        $tablesProperty->setValue($controller, []);
        $result = $method->invoke($controller, ['status' => false]);
        $this->assertStringContainsString($_REQUEST['db'], $result);
        $this->assertStringNotContainsString('id="overhead"', $result);

        //with table
        $_REQUEST['db'] = 'my_unique_test_db';
        $tablesProperty->setValue($controller, [
            [
                'TABLE_NAME' => 'my_unique_test_db',
                'ENGINE' => 'Maria',
                'TABLE_TYPE' => 'BASE TABLE',
                'TABLE_ROWS' => 0,
                'TABLE_COMMENT' => 'test',
                'Data_length' => 5000,
                'Index_length' => 100,
                'Data_free' => 10000,
            ],
        ]);
        $result = $method->invoke($controller, ['status' => false]);

        $this->assertStringContainsString($_REQUEST['db'], $result);
        $this->assertStringContainsString('id="overhead"', $result);
        $this->assertStringContainsString('9.8', $result);
    }

    /**
     * Tests for getValuesForMroongaTable()
     */
    public function testGetValuesForMroongaTable(): void
    {
        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        $GLOBALS['db'] = 'testdb';
        $GLOBALS['table'] = 'mytable';

        $GLOBALS['containerBuilder']->setParameter('db', $GLOBALS['db']);
        $GLOBALS['containerBuilder']->setParameter('table', $GLOBALS['table']);

        /** @var StructureController $structureController */
        $structureController = $GLOBALS['containerBuilder']->get(StructureController::class);

        $this->assertSame(
            [[], '', '', 0],
            $this->callFunction(
                $structureController,
                StructureController::class,
                'getValuesForMroongaTable',
                [[], 0],
            ),
        );

        // Enable stats
        $GLOBALS['cfg']['ShowStats'] = true;
        $this->callFunction(
            $structureController,
            StructureController::class,
            'getDatabaseInfo',
            [$this->createStub(ServerRequest::class)],
        );

        $this->assertSame(
            [['Data_length' => 45, 'Index_length' => 60], '105', 'B', 105],
            $this->callFunction(
                $structureController,
                StructureController::class,
                'getValuesForMroongaTable',
                [['Data_length' => 45, 'Index_length' => 60], 0],
            ),
        );

        $this->assertSame(
            [
                ['Data_length' => 45, 'Index_length' => 60],
                '105',
                'B',
                180, //105 + 75
            ],
            $this->callFunction(
                $structureController,
                StructureController::class,
                'getValuesForMroongaTable',
                [['Data_length' => 45, 'Index_length' => 60], 75],
            ),
        );
    }
}
