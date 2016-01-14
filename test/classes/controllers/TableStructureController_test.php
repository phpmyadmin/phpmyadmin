<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TableStructureController_Test class
 *
 * this class is for testing TableStructureController class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\DI\Container;

require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/di/Container.class.php';
require_once 'test/libraries/stubs/ResponseStub.php';
require_once 'libraries/controllers/TableStructureController.class.php';

/**
 * TableStructureController_Test class
 *
 * this class is for testing TableStructureController class
 *
 * @package PhpMyAdmin-test
 */
class TableStructureController_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var \PMA\Test\Stubs\PMA_Response
     */
    private $response;

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
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $table = $this->getMockBuilder('PMA_Table')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $this->response = new \PMA\Test\Stubs\PMA_Response();
        $container->set('PMA_Response', $this->response);
        $container->alias('response', 'PMA_Response');
    }

    /**
     * Tests for getKeyForTablePrimary()
     *
     * Case one: there are no primary key in the table
     *
     * @return void
     * @test
     */
    public function testGetKeyForTablePrimaryOne()
    {
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->returnValue(null));

        $class = new ReflectionClass('\PMA\Controllers\TableStructureController');
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');
        // No primary key in db.table2
        $this->assertEquals(
            '',
            $method->invoke($ctrl)
        );
    }

    /**
     * Tests for getKeyForTablePrimary()
     *
     * Case two: there are a primary key in the table
     *
     * @return void
     * @test
     */
    public function testGetKeyForTablePrimaryTwo()
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnCallback(
                    function () {
                        static $callCount = 0;
                        if ($callCount == 0) {
                            $callCount++;

                            return array(
                                'Key_name'    => 'PRIMARY',
                                'Column_name' => 'column',
                            );
                        } else {
                            return null;
                        }
                    }
                )
            );

        $class = new ReflectionClass('\PMA\Controllers\TableStructureController');
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');
        // With db.table, it has a primary key `column`
        $this->assertEquals(
            'column, ',
            $method->invoke($ctrl)
        );
    }

    /**
     * Tests for adjustColumnPrivileges()
     *
     * @return void
     * @test
     */
    public function testAdjustColumnPrivileges()
    {
        $class = new ReflectionClass('\PMA\Controllers\TableStructureController');
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');

        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, array(null))
        );
    }

    /**
     * Tests for adjustColumnPrivileges()
     * For the Drizzle environment
     *
     * @return void
     * @test
     */
    public function testAdjustColumnPrivilegesDrizzle()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped("Cannot redefine PMA_DRIZZLE constant");
        }

        $class = new ReflectionClass('\PMA\Controllers\TableStructureController');
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');

        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, array(null))
        );

        runkit_constant_redefine('PMA_DRIZZLE', false);
        $adjust_privileges = array(
            'col1' => 'col2'
        );
        $GLOBALS['col_priv'] = true;
        $GLOBALS['is_reload_priv'] = true;
        $this->assertEquals(
            true,
            $method->invokeArgs($ctrl, array($adjust_privileges))
        );
    }

    /**
     * Tests for getMultipleFieldCommandType()
     *
     * @return void
     * @test
     */
    public function testGetMultipleFieldCommandType()
    {
        $class = new ReflectionClass('\PMA\Controllers\TableStructureController');
        $method = $class->getMethod('getMultipleFieldCommandType');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');

        $this->assertEquals(
            null,
            $method->invoke($ctrl)
        );

        $_REQUEST['submit_mult_drop_x'] = true;
        $this->assertEquals(
            'drop',
            $method->invoke($ctrl)
        );
        unset($_REQUEST['submit_mult_drop_x']);

        $_REQUEST['submit_mult'] = 'create';
        $this->assertEquals(
            'create',
            $method->invoke($ctrl)
        );
        unset($_REQUEST['submit_mult']);

        $_REQUEST['mult_btn'] = __('Yes');
        $this->assertEquals(
            'row_delete',
            $method->invoke($ctrl)
        );

        $_REQUEST['selected'] = array('a', 'b');
        $method->invoke($ctrl);
        $this->assertEquals(
            $_REQUEST['selected'],
            $_REQUEST['selected_fld']
        );
    }

    /**
     * Test for getDataForSubmitMult()
     *
     * @return void
     * @test
     */
    public function testPMAGetDataForSubmitMult()
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $class = new ReflectionClass('PMA\Controllers\TableStructureController');
        $method = $class->getMethod('getDataForSubmitMult');
        $method->setAccessible(true);

        $container = Container::getDefaultContainer();
        $container->set('dbi', $dbi);
        $container->factory('PMA\Controllers\TableStructureController');
        $container->alias(
            'TableStructureController', 'PMA\Controllers\TableStructureController'
        );
        $ctrl = $container->get('TableStructureController');

        $submit_mult = "index";
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = array(
            "table1", "table2"
        );
        $action = 'db_delete_row';

        list($what, $query_type, $is_unset_submit_mult, $mult_btn, $centralColsError)
            = $method->invokeArgs(
                $ctrl,
                array($submit_mult, $db, $table, $selected, $action)
            );

        //validate 1: $what
        $this->assertEquals(
            null,
            $what
        );

        //validate 2: $query_type
        $this->assertEquals(
            'index_fld',
            $query_type
        );

        //validate 3: $is_unset_submit_mult
        $this->assertEquals(
            true,
            $is_unset_submit_mult
        );

        //validate 4:
        $this->assertEquals(
            __('Yes'),
            $mult_btn
        );

        //validate 5: $centralColsError
        $this->assertEquals(
            null,
            $centralColsError
        );

        $submit_mult = "unique";

        list($what, $query_type, $is_unset_submit_mult, $mult_btn, $centralColsError)
            = $method->invokeArgs(
                $ctrl,
                array($submit_mult, $db, $table, $selected, $action)
            );

        //validate 1: $what
        $this->assertEquals(
            null,
            $what
        );

        //validate 2: $query_type
        $this->assertEquals(
            'unique_fld',
            $query_type
        );

        //validate 3: $is_unset_submit_mult
        $this->assertEquals(
            true,
            $is_unset_submit_mult
        );

        //validate 4: $mult_btn
        $this->assertEquals(
            __('Yes'),
            $mult_btn
        );

        //validate 5: $centralColsError
        $this->assertEquals(
            null,
            $centralColsError
        );
    }
}
