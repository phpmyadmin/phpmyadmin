<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * TableStructureController_Test class
 *
 * this class is for testing StructureController class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Transformations;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * TableStructureController_Test class
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
    private $_response;

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
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $table = $this->getMockBuilder('PhpMyAdmin\Table')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));

        $GLOBALS['dbi'] = $dbi;

        $this->_response = new ResponseStub();
        $this->template = new Template();
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

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            null,
            new Relation($GLOBALS['dbi'], $this->template),
            new Transformations(),
            new CreateAddField($GLOBALS['dbi'])
        );

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

                            return [
                                'Key_name'    => 'PRIMARY',
                                'Column_name' => 'column',
                            ];
                        } else {
                            return null;
                        }
                    }
                )
            );

        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Table\StructureController');
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            null,
            new Relation($GLOBALS['dbi'], $this->template),
            new Transformations(),
            new CreateAddField($GLOBALS['dbi'])
        );

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
        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Table\StructureController');
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            null,
            new Relation($GLOBALS['dbi'], $this->template),
            new Transformations(),
            new CreateAddField($GLOBALS['dbi'])
        );

        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, [[]])
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
        $class = new ReflectionClass('\PhpMyAdmin\Controllers\Table\StructureController');
        $method = $class->getMethod('getMultipleFieldCommandType');
        $method->setAccessible(true);

        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            null,
            new Relation($GLOBALS['dbi'], $this->template),
            new Transformations(),
            new CreateAddField($GLOBALS['dbi'])
        );

        $this->assertEquals(
            null,
            $method->invoke($ctrl)
        );

        $_POST['submit_mult_drop_x'] = true;
        $this->assertEquals(
            'drop',
            $method->invoke($ctrl)
        );
        unset($_POST['submit_mult_drop_x']);

        $_POST['submit_mult'] = 'create';
        $this->assertEquals(
            'create',
            $method->invoke($ctrl)
        );
        unset($_POST['submit_mult']);

        $_POST['mult_btn'] = __('Yes');
        $this->assertEquals(
            'row_delete',
            $method->invoke($ctrl)
        );

        $_POST['selected'] = [
            'a',
            'b',
        ];
        $method->invoke($ctrl);
        $this->assertEquals(
            $_POST['selected'],
            $_POST['selected_fld']
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue(true));

        $class = new ReflectionClass('PhpMyAdmin\Controllers\Table\StructureController');
        $method = $class->getMethod('getDataForSubmitMult');
        $method->setAccessible(true);

        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            null,
            null,
            null,
            null,
            null,
            null,
            new Relation($GLOBALS['dbi'], $this->template),
            new Transformations(),
            new CreateAddField($GLOBALS['dbi'])
        );

        $submit_mult = "index";
        $db = "PMA_db";
        $table = "PMA_table";
        $selected = [
            "table1",
            "table2",
        ];
        $action = 'db_delete_row';
        $containerBuilder = new ContainerBuilder();

        list($what, $query_type, $is_unset_submit_mult, $mult_btn, $centralColsError)
            = $method->invokeArgs(
                $ctrl,
                [
                    $submit_mult,
                    $selected,
                    $action,
                    $containerBuilder,
                ]
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
                [
                    $submit_mult,
                    $selected,
                    $action,
                    $containerBuilder,
                ]
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
