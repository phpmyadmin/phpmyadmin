<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Operations;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Replication;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use ReflectionClass;
use ReflectionException;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\Database\StructureController
 */
class StructureControllerTest extends AbstractTestCase
{
    /** @var ResponseStub */
    private $response;

    /** @var Relation */
    private $relation;

    /** @var Replication */
    private $replication;

    /** @var Template */
    private $template;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Operations */
    private $operations;

    /** @var FlashMessages */
    private $flash;

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
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

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
        $this->replication = new Replication();
        $this->relationCleanup = new RelationCleanup($dbi, $this->relation);
        $this->operations = new Operations($dbi, $this->relation);
        $this->flash = new FlashMessages();
    }

    /**
     * Tests for getValuesForInnodbTable()
     */
    public function testGetValuesForInnodbTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForInnodbTable');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

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
            [
                $currentTable,
                10,
            ]
        );

        self::assertTrue($currentTable['COUNTED']);
        self::assertSame(6, $currentTable['TABLE_ROWS']);
        self::assertSame(16394, $sumSize);

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                10,
            ]
        );

        self::assertFalse($currentTable['COUNTED']);
        self::assertSame(16394, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
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
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue($controller, true);
        $property = $class->getProperty('dbIsSystemSchema');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue($controller, true);

        $currentTable = [
            'Data_length' => 16384,
            'Index_length' => 0,
            'Name' => 'table',
            'Data_free' => 300,
        ];
        [$currentTable, , , , , $overheadSize, $sumSize] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                0,
                0,
                0,
                0,
                0,
                0,
            ]
        );
        self::assertSame(6, $currentTable['Rows']);
        self::assertSame(16384, $sumSize);
        self::assertSame(300, $overheadSize);

        unset($currentTable['Data_free']);
        [$currentTable, , , , , $overheadSize] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                0,
                0,
                0,
                0,
                0,
                0,
            ]
        );
        self::assertSame(0, $overheadSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );
        [$currentTable, , , , , , $sumSize] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                0,
                0,
                0,
                0,
                0,
                0,
            ]
        );
        self::assertSame(0, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );
        [$currentTable] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                0,
                0,
                0,
                0,
                0,
                0,
            ]
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
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );

        // When parameter $db is empty
        self::assertFalse($method->invokeArgs($controller, [[], 'table']));

        // Correct parameter
        $tables = ['db.table'];
        self::assertTrue($method->invokeArgs($controller, [$tables, 'table']));

        // Table not in database
        $tables = ['db.tab1e'];
        self::assertFalse($method->invokeArgs($controller, [$tables, 'table']));
    }

    /**
     * Tests for checkFavoriteTable()
     */
    public function testCheckFavoriteTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('checkFavoriteTable');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $GLOBALS['db'] = 'sakila';
        $GLOBALS['dbi'] = $this->dbi;

        $this->dummyDbi->removeDefaultResults();
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `sakila`.`country`',
            [
                ['country_id', 'smallint(5) unsigned', 'NO', 'PRI', null, 'auto_increment'],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra']
        );
        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `sakila`.`country`',
            [],
            ['Table', 'Non_unique', 'Key_name', 'Column_name']
        );

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );

        $recentFavoriteTables = RecentFavoriteTable::getInstance('favorite');
        self::assertSame([], $recentFavoriteTables->getTables());
        $recentFavoriteTables->remove('sakila', 'country');
        $recentFavoriteTables->add('sakila', 'country');
        self::assertSame([
            [
                'db' => 'sakila',
                'table' => 'country',
            ],
        ], $recentFavoriteTables->getTables());

        self::assertFalse($method->invokeArgs($controller, ['']));

        self::assertTrue($method->invokeArgs($controller, ['country']));
    }

    /**
     * @throws ReflectionException
     */
    public function testDisplayTableList(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('displayTableList');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi'],
            $this->flash
        );
        // Showing statistics
        $class = new ReflectionClass(StructureController::class);
        $showStatsProperty = $class->getProperty('isShowStats');
        if (PHP_VERSION_ID < 80100) {
            $showStatsProperty->setAccessible(true);
        }

        $showStatsProperty->setValue($controller, true);

        $tablesProperty = $class->getProperty('tables');
        if (PHP_VERSION_ID < 80100) {
            $tablesProperty->setAccessible(true);
        }

        $numTables = $class->getProperty('numTables');
        if (PHP_VERSION_ID < 80100) {
            $numTables->setAccessible(true);
        }

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
        global $containerBuilder;
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['db'] = 'testdb';
        $GLOBALS['table'] = 'mytable';

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);

        /** @var StructureController $structureController */
        $structureController = $containerBuilder->get(StructureController::class);

        self::assertSame([
            [],
            '',
            '',
            0,
        ], $this->callFunction(
            $structureController,
            StructureController::class,
            'getValuesForMroongaTable',
            [
                [],
                0,
            ]
        ));

        // Enable stats
        $GLOBALS['cfg']['ShowStats'] = true;
        $this->callFunction(
            $structureController,
            StructureController::class,
            'getDatabaseInfo',
            ['']
        );

        self::assertSame([
            [
                'Data_length' => 45,
                'Index_length' => 60,
            ],
            '105',
            'B',
            105,
        ], $this->callFunction(
            $structureController,
            StructureController::class,
            'getValuesForMroongaTable',
            [
                [
                    'Data_length' => 45,
                    'Index_length' => 60,
                ],
                0,
            ]
        ));

        self::assertSame([
            [
                'Data_length' => 45,
                'Index_length' => 60,
            ],
            '105',
            'B',
            180, //105 + 75
        ], $this->callFunction(
            $structureController,
            StructureController::class,
            'getValuesForMroongaTable',
            [
                [
                    'Data_length' => 45,
                    'Index_length' => 60,
                ],
                75,
            ]
        ));
    }
}
