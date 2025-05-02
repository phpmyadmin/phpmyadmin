<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Tracking\TrackingChecker;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionException;

#[CoversClass(StructureController::class)]
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

        Config::getInstance()->selectedServer['DisableIS'] = false;
        Current::$table = 'table';
        Current::$database = 'db';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Expect the table will have 6 rows
        $table->expects(self::any())->method('getRealRowCountTable')
            ->willReturn(6);
        $table->expects(self::any())->method('countRecords')
            ->willReturn(6);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())->method('getTable')
            ->willReturn($table);

        DatabaseInterface::$instance = $dbi;

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
        $dbi = DatabaseInterface::getInstance();
        $config = Config::getInstance();
        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        $property->setValue($controller, true);

        $config->settings['MaxExactCount'] = 10;
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

        self::assertTrue($currentTable['COUNTED']);
        self::assertSame(6, $currentTable['TABLE_ROWS']);
        self::assertSame(16394, $sumSize);

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 10],
        );

        self::assertFalse($currentTable['COUNTED']);
        self::assertSame(16394, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
        );

        $currentTable['ENGINE'] = 'InnoDB';
        [$currentTable, , , $sumSize] = $method->invokeArgs($controller, [$currentTable, 10]);
        self::assertTrue($currentTable['COUNTED']);
        self::assertSame(10, $sumSize);

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs($controller, [$currentTable, 10]);
        self::assertFalse($currentTable['COUNTED']);
        self::assertSame(10, $sumSize);
    }

    /**
     * Tests for the getValuesForAriaTable()
     */
    public function testGetValuesForAriaTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForAriaTable');

        $dbi = DatabaseInterface::getInstance();
        $config = Config::getInstance();
        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
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
        self::assertSame(6, $currentTable['Rows']);
        self::assertSame(16384, $sumSize);
        self::assertSame(300, $overheadSize);

        unset($currentTable['Data_free']);
        [$currentTable, , , , , $overheadSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        self::assertSame(0, $overheadSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
        );
        [$currentTable, , , , , , $sumSize] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        self::assertSame(0, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
        );
        [$currentTable] = $method->invokeArgs(
            $controller,
            [$currentTable, 0, 0, 0, 0, 0, 0],
        );
        self::assertArrayNotHasKey('Row', $currentTable);
    }

    /**
     * Tests for hasTable()
     */
    public function testHasTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('hasTable');

        $dbi = DatabaseInterface::getInstance();
        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            Config::getInstance(),
        );

        // When parameter $db is empty
        self::assertFalse(
            $method->invokeArgs($controller, [[], 'table']),
        );

        // Correct parameter
        $tables = ['db.table'];
        self::assertTrue(
            $method->invokeArgs($controller, [$tables, 'table']),
        );

        // Table not in database
        $tables = ['db.tab1e'];
        self::assertFalse(
            $method->invokeArgs($controller, [$tables, 'table']),
        );
    }

    /** @throws ReflectionException */
    public function testDisplayTableList(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('displayTableList');

        $dbi = DatabaseInterface::getInstance();
        $controller = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            Config::getInstance(),
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
        self::assertStringContainsString($_REQUEST['db'], $result);
        self::assertStringNotContainsString('id="overhead"', $result);

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

        self::assertStringContainsString($_REQUEST['db'], $result);
        self::assertStringContainsString('id="overhead"', $result);
        self::assertStringContainsString('9.8', $result);
    }

    /**
     * Tests for getValuesForMroongaTable()
     */
    public function testGetValuesForMroongaTable(): void
    {
        Current::$database = 'testdb';
        Current::$table = 'mytable';

        $dbi = DatabaseInterface::getInstance();
        $config = Config::getInstance();
        $structureController = new StructureController(
            $this->response,
            $this->template,
            $this->relation,
            $this->replication,
            $dbi,
            self::createStub(TrackingChecker::class),
            self::createStub(PageSettings::class),
            new DbTableExists($dbi),
            $config,
        );

        self::assertSame(
            [[], '', '', 0],
            $this->callFunction(
                $structureController,
                StructureController::class,
                'getValuesForMroongaTable',
                [[], 0],
            ),
        );

        // Enable stats
        $config->settings['ShowStats'] = true;
        $this->callFunction(
            $structureController,
            StructureController::class,
            'getDatabaseInfo',
            [self::createStub(ServerRequest::class)],
        );

        self::assertSame(
            [['Data_length' => 45, 'Index_length' => 60], '105', 'B', 105],
            $this->callFunction(
                $structureController,
                StructureController::class,
                'getValuesForMroongaTable',
                [['Data_length' => 45, 'Index_length' => 60], 0],
            ),
        );

        self::assertSame(
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
