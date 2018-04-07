<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * DatabaseStructureControllerTest class
 *
 * this class is for testing DatabaseStructureController class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\DatabaseStructureController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use ReflectionClass;

/**
 * DatabaseStructureControllerTest class
 *
 * this class is for testing DatabaseStructureController class
 *
 * @package PhpMyAdmin-test
 */
class DatabaseStructureControllerTest extends PmaTestCase
{
    /**
     * @var \PhpMyAdmin\Tests\Stubs\Response
     */
    private $_response;

    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['table'] = "table";
        $GLOBALS['db'] = 'db';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        //$_SESSION

        if (!defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'Other');
        }

        $table = $this->getMockBuilder('PhpMyAdmin\Table')
            ->disableOriginalConstructor()
            ->getMock();
        // Expect the table will have 6 rows
        $table->expects($this->any())->method('getRealRowCountTable')
            ->will($this->returnValue(6));
        $table->expects($this->any())->method('countRecords')
            ->will($this->returnValue(6));

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $this->_response = new ResponseStub();
        $container->set('PhpMyAdmin\Response', $this->_response);
        $container->alias('response', 'PhpMyAdmin\Response');
    }

    /**
     * Tests for getValuesForInnodbTable()
     *
     * @return void
     * @test
     */
    public function testGetValuesForInnodbTable()
    {
        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $response = new ResponseStub();
        $container->set('PhpMyAdmin\Response', $response);
        $container->alias('response', 'PhpMyAdmin\Response');

        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $method = $class->getMethod('getValuesForInnodbTable');
        $method->setAccessible(true);
        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );
        // Showing statistics
        $property = $class->getProperty('_is_show_stats');
        $property->setAccessible(true);
        $property->setValue($ctrl, true);

        $GLOBALS['cfg']['MaxExactCount'] = 10;
        $current_table = array(
            'ENGINE' => 'InnoDB',
            'TABLE_ROWS' => 5,
            'Data_length' => 16384,
            'Index_length' => 0,
            'TABLE_NAME' => 'table'
        );
        list($current_table,,, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 10));

        $this->assertEquals(
            true,
            $current_table['COUNTED']
        );
        $this->assertEquals(
            6,
            $current_table['TABLE_ROWS']
        );
        $this->assertEquals(
            16394,
            $sum_size
        );

        $current_table['ENGINE'] = 'MYISAM';
        list($current_table,,, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 10));

        $this->assertEquals(
            false,
            $current_table['COUNTED']
        );
        $this->assertEquals(
            16394,
            $sum_size
        );
        // Not showing statistics
        $is_show_stats = false;
        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );

        $current_table['ENGINE'] = 'InnoDB';
        list($current_table,,, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 10));
        $this->assertEquals(
            true,
            $current_table['COUNTED']
        );
        $this->assertEquals(
            10,
            $sum_size
        );

        $current_table['ENGINE'] = 'MYISAM';
        list($current_table,,, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 10));
        $this->assertEquals(
            false,
            $current_table['COUNTED']
        );
        $this->assertEquals(
            10,
            $sum_size
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
        $container = Container::getDefaultContainer();
        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $method = $class->getMethod('getValuesForAriaTable');
        $method->setAccessible(true);

        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );
        // Showing statistics
        $property = $class->getProperty('_is_show_stats');
        $property->setAccessible(true);
        $property->setValue($ctrl, true);
        $property = $class->getProperty('_db_is_system_schema');
        $property->setAccessible(true);
        $property->setValue($ctrl, true);

        $current_table = array(
            'Data_length'  => 16384,
            'Index_length' => 0,
            'Name'         => 'table',
            'Data_free'    => 300,
        );
        list($current_table,,,,, $overhead_size, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 0, 0, 0, 0, 0, 0,));
        $this->assertEquals(
            6,
            $current_table['Rows']
        );
        $this->assertEquals(
            16384,
            $sum_size
        );
        $this->assertEquals(
            300,
            $overhead_size
        );

        unset($current_table['Data_free']);
        list($current_table,,,,, $overhead_size,)
            = $method->invokeArgs($ctrl, array($current_table, 0, 0, 0, 0, 0, 0,));
        $this->assertEquals(0, $overhead_size);

        $is_show_stats = false;
        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );
        list($current_table,,,,,, $sum_size)
            = $method->invokeArgs($ctrl, array($current_table, 0, 0, 0, 0, 0, 0));
        $this->assertEquals(0, $sum_size);

        $db_is_system_schema = false;
        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );
        list($current_table,,,,,,)
            = $method->invokeArgs($ctrl, array($current_table, 0, 0, 0, 0, 0, 0,));
        $this->assertArrayNotHasKey('Row', $current_table);
    }

    /**
     * Tests for hasTable()
     *
     * @return void
     * @test
     */
    public function testHasTable()
    {
        $container = Container::getDefaultContainer();
        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $method = $class->getMethod('hasTable');
        $method->setAccessible(true);

        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );

        // When parameter $db is empty
        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, array(array(), 'table'))
        );

        // Correct parameter
        $tables = array(
            'db.table'
        );
        $this->assertEquals(
            true,
            $method->invokeArgs($ctrl, array($tables, 'table'))
        );

        // Table not in database
        $tables = array(
            'db.tab1e'
        );
        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, array($tables, 'table'))
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
        $container = Container::getDefaultContainer();
        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $method = $class->getMethod('checkFavoriteTable');
        $method->setAccessible(true);

        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );

        $_SESSION['tmpval']['favorite_tables'][$GLOBALS['server']] = array(
            array('db' => 'db', 'table' => 'table')
        );

        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, array(''))
        );

        $this->assertEquals(
            true,
            $method->invokeArgs($ctrl, array('table'))
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
        $container = Container::getDefaultContainer();
        $fav_instance = $this->getMockBuilder('PhpMyAdmin\RecentFavoriteTable')
            ->disableOriginalConstructor()
            ->getMock();
        $fav_instance->expects($this->at(1))->method('getTables')
            ->will($this->returnValue(array()));
        $fav_instance->expects($this->at(2))
            ->method('getTables')
            ->will(
                $this->returnValue(
                    array(
                        array('db' => 'db', 'table' => 'table'),
                    )
                )
            );

        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $method = $class->getMethod('synchronizeFavoriteTables');
        $method->setAccessible(true);

        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );

        // The user hash for test
        $user = 'abcdefg';
        $favorite_table = array(
            $user => array(
                array('db' => 'db', 'table' => 'table')
            ),
        );

        $method->invokeArgs($ctrl, array($fav_instance, $user, $favorite_table));
        $json = $this->_response->getJSONResult();

        $this->assertEquals(json_encode($favorite_table), $json['favorite_tables']);
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
        $container = Container::getDefaultContainer();
        $_REQUEST['table'] = 'table';

        $ctrl = new DatabaseStructureController(
            $container->get('response'),
            $container->get('dbi'),
            $container->get('db')
        );
        // Showing statistics
        $class = new ReflectionClass('PhpMyAdmin\Controllers\Database\DatabaseStructureController');
        $property = $class->getProperty('_tables');
        $property->setAccessible(true);

        $ctrl->handleRealRowCountRequestAction();
        $json = $this->_response->getJSONResult();
        $this->assertEquals(
            6,
            $json['real_row_count']
        );

        // Fall into another branch
        $_REQUEST['real_row_count_all'] = 'abc';
        $property->setValue(
            $ctrl,
            array(
                array(
                    'TABLE_NAME' => 'table'
                )
            )
        );
        $ctrl->handleRealRowCountRequestAction();
        $json = $this->_response->getJSONResult();

        $expected_result = array(
            array(
                'table' => 'table',
                'row_count' => 6
            )
        );
        $this->assertEquals(
            json_encode($expected_result),
            $json['real_row_count_all']
        );
    }
}
