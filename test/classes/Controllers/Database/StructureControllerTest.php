<?php
/**
 * StructureControllerTest class
 *
 * this class is for testing StructureController class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Operations;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Replication;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use function define;
use function defined;
use function json_encode;

/**
 * StructureControllerTest class
 *
 * this class is for testing StructureController class
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

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
        parent::setTheme();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'Other');
        }

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
    }

    /**
     * Tests for getValuesForInnodbTable()
     */
    public function testGetValuesForInnodbTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForInnodbTable');
        $method->setAccessible(true);
        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        $property->setAccessible(true);
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

        $this->assertTrue(
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            6,
            $currentTable['TABLE_ROWS']
        );
        $this->assertEquals(
            16394,
            $sumSize
        );

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize] = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                10,
            ]
        );

        $this->assertFalse(
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            16394,
            $sumSize
        );

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );

        $currentTable['ENGINE'] = 'InnoDB';
        [$currentTable, , , $sumSize]
            = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertTrue(
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            10,
            $sumSize
        );

        $currentTable['ENGINE'] = 'MYISAM';
        [$currentTable, , , $sumSize]
            = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertFalse(
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            10,
            $sumSize
        );
    }

    /**
     * Tests for the getValuesForAriaTable()
     */
    public function testGetValuesForAriaTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForAriaTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );
        // Showing statistics
        $property = $class->getProperty('isShowStats');
        $property->setAccessible(true);
        $property->setValue($controller, true);
        $property = $class->getProperty('dbIsSystemSchema');
        $property->setAccessible(true);
        $property->setValue($controller, true);

        $currentTable = [
            'Data_length'  => 16384,
            'Index_length' => 0,
            'Name'         => 'table',
            'Data_free'    => 300,
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
        $this->assertEquals(
            6,
            $currentTable['Rows']
        );
        $this->assertEquals(
            16384,
            $sumSize
        );
        $this->assertEquals(
            300,
            $overheadSize
        );

        unset($currentTable['Data_free']);
        [$currentTable, , , , , $overheadSize]  = $method->invokeArgs(
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
        $this->assertEquals(0, $overheadSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
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
        $this->assertEquals(0, $sumSize);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
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
        $this->assertArrayNotHasKey('Row', $currentTable);
    }

    /**
     * Tests for hasTable()
     */
    public function testHasTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('hasTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );

        // When parameter $db is empty
        $this->assertFalse(
            $method->invokeArgs($controller, [[], 'table'])
        );

        // Correct parameter
        $tables = ['db.table'];
        $this->assertTrue(
            $method->invokeArgs($controller, [$tables, 'table'])
        );

        // Table not in database
        $tables = ['db.tab1e'];
        $this->assertFalse(
            $method->invokeArgs($controller, [$tables, 'table'])
        );
    }

    /**
     * Tests for checkFavoriteTable()
     */
    public function testCheckFavoriteTable(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('checkFavoriteTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );

        $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] = [
            [
                'db' => 'db',
                'table' => 'table',
            ],
        ];

        $this->assertFalse(
            $method->invokeArgs($controller, [''])
        );

        $this->assertTrue(
            $method->invokeArgs($controller, ['table'])
        );
    }

    /**
     * Tests for synchronizeFavoriteTables()
     */
    public function testSynchronizeFavoriteTables(): void
    {
        $favoriteInstance = $this->getFavoriteTablesMock();

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('synchronizeFavoriteTables');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );

        // The user hash for test
        $user = 'abcdefg';
        $favoriteTable = [
            $user => [
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
            ],
        ];

        $json = $method->invokeArgs($controller, [$favoriteInstance, $user, $favoriteTable]);

        $this->assertEquals(json_encode($favoriteTable), $json['favoriteTables'] ?? '');
        $this->assertArrayHasKey('list', $json);
    }

    /**
     * @return MockObject|RecentFavoriteTable
     */
    private function getFavoriteTablesMock()
    {
        $favoriteInstance = $this->getMockBuilder(RecentFavoriteTable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $favoriteInstance->expects($this->at(1))->method('getTables')
            ->willReturn([]);
        $favoriteInstance->expects($this->at(2))
            ->method('getTables')
            ->willReturn([
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
            ]);

        return $favoriteInstance;
    }

    /**
     * Tests for handleRealRowCountRequestAction()
     */
    public function testHandleRealRowCountRequestAction(): void
    {
        global $is_db;

        $is_db = true;

        $this->response->setAjax(true);
        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );
        // Showing statistics
        $class = new ReflectionClass(StructureController::class);
        $property = $class->getProperty('tables');
        $property->setAccessible(true);

        $_REQUEST['table'] = 'table';
        $controller->handleRealRowCountRequestAction();
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            6,
            $json['real_row_count']
        );

        // Fall into another branch
        $property->setValue($controller, [['TABLE_NAME' => 'table']]);
        $_REQUEST['real_row_count_all'] = 'abc';
        $controller->handleRealRowCountRequestAction();
        $json = $this->response->getJSONResult();

        $expectedResult = [
            [
                'table' => 'table',
                'row_count' => 6,
            ],
        ];
        $this->assertEquals(
            json_encode($expectedResult),
            $json['real_row_count_all']
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testDisplayTableList(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('displayTableList');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication,
            $this->relationCleanup,
            $this->operations,
            $GLOBALS['dbi']
        );
        // Showing statistics
        $class = new ReflectionClass(StructureController::class);
        $showStatsProperty = $class->getProperty('isShowStats');
        $showStatsProperty->setAccessible(true);
        $showStatsProperty->setValue($controller, true);

        $tablesProperty = $class->getProperty('tables');
        $tablesProperty->setAccessible(true);

        $numTables = $class->getProperty('numTables');
        $numTables->setAccessible(true);
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
}
