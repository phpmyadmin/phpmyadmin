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
    private $response;

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
        parent::setTheme();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $indexes = [
            [
                'Schema' => 'Schema1',
                'Key_name' => 'Key_name1',
                'Column_name' => 'Column_name1',
            ],
            [
                'Schema' => 'Schema2',
                'Key_name' => 'Key_name2',
                'Column_name' => 'Column_name2',
            ],
            [
                'Schema' => 'Schema3',
                'Key_name' => 'Key_name3',
                'Column_name' => 'Column_name3',
            ],
        ];

        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('getTable')
            ->will($this->returnValue($table));
        $dbi->expects($this->any())->method('getTableIndexes')
            ->will($this->returnValue($indexes));

        $GLOBALS['dbi'] = $dbi;

        $this->response = new ResponseStub();
        $this->template = new Template();
    }

    /**
     * Tests for getKeyForTablePrimary()
     *
     * Case one: there are no primary key in the table
     */
    public function testGetKeyForTablePrimaryOne(): void
    {
        $GLOBALS['dbi']->expects($this->any())->method('fetchAssoc')
            ->will($this->returnValue(null));

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation),
            $GLOBALS['dbi']
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
     */
    public function testGetKeyForTablePrimaryTwo(): void
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchAssoc')
            ->will(
                $this->returnCallback(
                    static function () {
                        static $callCount = 0;
                        if ($callCount == 0) {
                            $callCount++;

                            return [
                                'Key_name'    => 'PRIMARY',
                                'Column_name' => 'column',
                            ];
                        }

                        return null;
                    }
                )
            );

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('getKeyForTablePrimary');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation),
            $GLOBALS['dbi']
        );

        // With db.table, it has a primary key `column`
        $this->assertEquals(
            'column, ',
            $method->invoke($ctrl)
        );
    }

    /**
     * Tests for adjustColumnPrivileges()
     */
    public function testAdjustColumnPrivileges(): void
    {
        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('adjustColumnPrivileges');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation),
            $GLOBALS['dbi']
        );

        $this->assertFalse(
            $method->invokeArgs($ctrl, [[]])
        );
    }

    /**
     * Tests for displayHtmlForColumnChange()
     */
    public function testDisplayHtmlForColumnChange(): void
    {
        $_REQUEST['field'] = '_id';
        $GLOBALS['db'] = 'testdb';
        $GLOBALS['table'] = 'mytable';

        $this->setGlobalDbi();

        $class = new ReflectionClass(StructureController::class);
        $method = $class->getMethod('displayHtmlForColumnChange');
        $method->setAccessible(true);

        $relation = new Relation($GLOBALS['dbi'], $this->template);
        $ctrl = new StructureController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $relation,
            new Transformations(),
            new CreateAddField($GLOBALS['dbi']),
            new RelationCleanup($GLOBALS['dbi'], $relation),
            $GLOBALS['dbi']
        );

        $method->invokeArgs($ctrl, [null]);
        $this->assertStringContainsString(
            '<input id="field_0_1"' . "\n"
            . '        type="text"' . "\n"
            . '    name="field_name[0]"' . "\n"
            . '    maxlength="64"' . "\n"
            . '    class="textfield"' . "\n"
            . '    title="Column"' . "\n"
            . '    size="10"' . "\n"
            . '    value="_id">' . "\n",
            $this->response->getHTMLResult()
        );
    }
}
