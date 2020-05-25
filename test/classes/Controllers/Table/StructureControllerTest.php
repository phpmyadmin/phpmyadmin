<?php
/**
 * TableStructureController_Test class
 *
 * this class is for testing StructureController class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use PhpMyAdmin\Transformations;
use ReflectionClass;

/**
 * TableStructureController_Test class
 *
 * this class is for testing StructureController class
 */
class StructureControllerTest extends AbstractTestCase
{
    /** @var ResponseStub */
    private $_response;

    /** @var Template */
    private $template;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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
     *
     * @test
     */
    public function testGetKeyForTablePrimaryOne()
    {
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->returnValue(null));

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation)
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
     *
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

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation)
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
     *
     * @test
     */
    public function testAdjustColumnPrivileges()
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->_response,
            $GLOBALS['dbi'],
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation)
        );

        $this->assertEquals(
            false,
            $method->invokeArgs($ctrl, [[]])
        );
    }
}
