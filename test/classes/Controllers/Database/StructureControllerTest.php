<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * StructureControllerTest class
 *
 * this class is for testing StructureController class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\StructureController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Replication;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use ReflectionClass;

/**
 * StructureControllerTest class
 *
 * this class is for testing StructureController class
 *
 * @package PhpMyAdmin-test
 */
class StructureControllerTest extends PmaTestCase
{
    /**
     * @var \PhpMyAdmin\Tests\Stubs\Response
     */
    private $response;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var Replication
     */
    private $replication;

    /**
     * @var Template
     */
    private $template;

    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = "table";
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
    }

    /**
     * Tests for getValuesForInnodbTable()
     *
     * @return void
     * @test
     */
    public function testGetValuesForInnodbTable()
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForInnodbTable');
        $method->setAccessible(true);
        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
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
        list($currentTable,,, $sumSize) = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                10,
            ]
        );

        $this->assertEquals(
            true,
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
        list($currentTable,,, $sumSize) = $method->invokeArgs(
            $controller,
            [
                $currentTable,
                10,
            ]
        );

        $this->assertEquals(
            false,
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            16394,
            $sumSize
        );
        // Not showing statistics
        $is_show_stats = false;
        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );

        $currentTable['ENGINE'] = 'InnoDB';
        list($currentTable,,, $sumSize)
            = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertEquals(
            true,
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            10,
            $sumSize
        );

        $currentTable['ENGINE'] = 'MYISAM';
        list($currentTable,,, $sumSize)
            = $method->invokeArgs($controller, [$currentTable, 10]);
        $this->assertEquals(
            false,
            $currentTable['COUNTED']
        );
        $this->assertEquals(
            10,
            $sumSize
        );
    }

    /**
     * Tests for the getValuesForAriaTable()
     *
     * @return void
     * @test
     */
    public function testGetValuesForAriaTable()
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getValuesForAriaTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
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
        list($currentTable,,,,, $overheadSize, $sumSize) = $method->invokeArgs(
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
        list($currentTable,,,,, $overheadSize,)  = $method->invokeArgs(
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
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );
        list($currentTable,,,,,, $sumSize) = $method->invokeArgs(
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
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );
        list($currentTable,,,,,,) = $method->invokeArgs(
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
     *
     * @return void
     * @test
     */
    public function testHasTable()
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('hasTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );

        // When parameter $db is empty
        $this->assertEquals(
            false,
            $method->invokeArgs($controller, [[], 'table'])
        );

        // Correct parameter
        $tables = [
            'db.table',
        ];
        $this->assertEquals(
            true,
            $method->invokeArgs($controller, [$tables, 'table'])
        );

        // Table not in database
        $tables = [
            'db.tab1e',
        ];
        $this->assertEquals(
            false,
            $method->invokeArgs($controller, [$tables, 'table'])
        );
    }

    /**
     * Tests for checkFavoriteTable()
     *
     * @return void
     * @test
     */
    public function testCheckFavoriteTable()
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('checkFavoriteTable');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );

        $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] = [
            [
                'db' => 'db',
                'table' => 'table',
            ],
        ];

        $this->assertEquals(
            false,
            $method->invokeArgs($controller, [''])
        );

        $this->assertEquals(
            true,
            $method->invokeArgs($controller, ['table'])
        );
    }

    /**
     * Tests for synchronizeFavoriteTables()
     *
     * @return void
     * @test
     */
    public function testSynchronizeFavoriteTables()
    {
        $favoriteInstance = $this->getMockBuilder(RecentFavoriteTable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $favoriteInstance->expects($this->at(1))->method('getTables')
            ->will($this->returnValue([]));
        $favoriteInstance->expects($this->at(2))
            ->method('getTables')
            ->will($this->returnValue([
                [
                    'db' => 'db',
                    'table' => 'table',
                ],
            ]));

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('synchronizeFavoriteTables');
        $method->setAccessible(true);

        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
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

        $this->assertEquals(json_encode($favoriteTable), $json['favoriteTables']??'');
        $this->assertArrayHasKey('list', $json);
    }

    /**
     * Tests for handleRealRowCountRequestAction()
     *
     * @return void
     * @test
     */
    public function testHandleRealRowCountRequestAction()
    {
        $controller = new StructureController(
            $this->response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $this->relation,
            $this->replication
        );
        // Showing statistics
        $class = new ReflectionClass(StructureController::class);
        $property = $class->getProperty('tables');
        $property->setAccessible(true);

        $json = $controller->handleRealRowCountRequestAction([
            'table' => 'table',
        ]);
        $this->assertEquals(
            6,
            $json['real_row_count']
        );

        // Fall into another branch
        $property->setValue($controller, [['TABLE_NAME' => 'table']]);
        $json = $controller->handleRealRowCountRequestAction([
            'table' => 'table',
            'real_row_count_all' => 'abc',
        ]);

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
}
