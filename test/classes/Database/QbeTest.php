<?php
/**
 * Tests for PhpMyAdmin\Database\Qbe
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Qbe;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionClass;

/**
 * Tests for PhpMyAdmin\Database\Qbe class
 *
 * @package PhpMyAdmin-test
 */
class QbeTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = new Qbe(new Relation($GLOBALS['dbi']), new Template(), $GLOBALS['dbi'], 'pma_test');
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma_test';
        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $create_table = 'CREATE TABLE `table1` ('
            . '`id` int(11) NOT NULL,'
            . '`value` int(11) NOT NULL,'
            . 'PRIMARY KEY (`id`,`value`),'
            . 'KEY `value` (`value`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=latin1';

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->with('SHOW CREATE TABLE `pma_test`.`table1`', 0, 1)
            ->will($this->returnValue($create_table));

        $dbi->expects($this->any())
            ->method('getTableIndexes')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;
        $this->object->dbi = $dbi;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return mixed the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass(Qbe::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _getSortSelectCell
     *
     * @return void
     */
    public function testGetSortSelectCell()
    {
        $this->assertStringContainsString(
            'style="width:12ex" name="criteriaSort[1]"',
            $this->_callProtectedFunction(
                '_getSortSelectCell',
                [1]
            )
        );
        $this->assertStringNotContainsString(
            'selected="selected"',
            $this->_callProtectedFunction(
                '_getSortSelectCell',
                [1]
            )
        );
        $this->assertStringContainsString(
            'value="ASC" selected="selected">',
            $this->_callProtectedFunction(
                '_getSortSelectCell',
                [
                    1,
                    'ASC',
                ]
            )
        );
    }

    /**
     * Test for _getSortRow
     *
     * @return void
     */
    public function testGetSortRow()
    {
        $this->assertStringContainsString(
            '<th>Sort:</th>',
            $this->_callProtectedFunction(
                '_getSortRow',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaSort[0]"',
            $this->_callProtectedFunction(
                '_getSortRow',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaSort[1]"',
            $this->_callProtectedFunction(
                '_getSortRow',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaSort[2]"',
            $this->_callProtectedFunction(
                '_getSortRow',
                []
            )
        );
    }

    /**
     * Test for _getShowRow
     *
     * @return void
     */
    public function testGetShowRow()
    {
        $this->assertEquals(
            '<tr class="noclick"><th>Show:</th><td class="center"><input type'
            . '="checkbox" name="criteriaShow[0]"></td><td class="center">'
            . '<input type="checkbox" name="criteriaShow[1]"></td><td '
            . 'class="center"><input type="checkbox" name="criteriaShow[2]">'
            . '</td></tr>',
            $this->_callProtectedFunction(
                '_getShowRow',
                []
            )
        );
    }

    /**
     * Test for _getCriteriaInputboxRow
     *
     * @return void
     */
    public function testGetCriteriaInputboxRow()
    {
        $this->assertEquals(
            '<tr class="noclick"><th>Criteria:</th><td class="center">'
            . '<input type="hidden" name="prev_criteria[0]" value="">'
            . '<input type="text" name="criteria[0]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="center">'
            . '<input type="hidden" name="prev_criteria[1]" value="">'
            . '<input type="text" name="criteria[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="center">'
            . '<input type="hidden" name="prev_criteria[2]" value="">'
            . '<input type="text" name="criteria[2]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td></tr>',
            $this->_callProtectedFunction(
                '_getCriteriaInputboxRow',
                []
            )
        );
    }

    /**
     * Test for _getFootersOptions
     *
     * @return void
     */
    public function testGetFootersOptions()
    {
        $this->assertStringContainsString(
            'Add/Delete criteria rows',
            $this->_callProtectedFunction(
                '_getFootersOptions',
                ['row']
            )
        );
        $this->assertStringContainsString(
            'name="criteriaRowAdd"',
            $this->_callProtectedFunction(
                '_getFootersOptions',
                ['row']
            )
        );
        $this->assertStringContainsString(
            '<option value="0" selected="selected">0</option>',
            $this->_callProtectedFunction(
                '_getFootersOptions',
                ['row']
            )
        );
    }

    /**
     * Test for _getTableFooters
     *
     * @return void
     */
    public function testGetTableFooters()
    {
        $this->assertStringContainsString(
            'name="criteriaRowAdd"',
            $this->_callProtectedFunction(
                '_getTableFooters',
                []
            )
        );
        $this->assertStringContainsString(
            'name="criteriaColumnAdd"',
            $this->_callProtectedFunction(
                '_getTableFooters',
                []
            )
        );
        $this->assertStringContainsString(
            '<input class="btn btn-secondary" type="submit" name="modify" value="Update Query">',
            $this->_callProtectedFunction(
                '_getTableFooters',
                []
            )
        );
    }

    /**
     * Test for _getAndOrColCell
     *
     * @return void
     */
    public function testGetAndOrColCell()
    {
        $this->assertEquals(
            '<td class="center"><strong>Or:</strong><input type="radio" '
            . 'name="criteriaAndOrColumn[1]" value="or">&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and"><br>Ins<input type="checkbox" name="criteriaColumnInsert'
            . '[1]">&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]"></td>',
            $this->_callProtectedFunction(
                '_getAndOrColCell',
                [1]
            )
        );
    }

    /**
     * Test for _getModifyColumnsRow
     *
     * @return void
     */
    public function testGetModifyColumnsRow()
    {
        $this->assertEquals(
            '<tr class="noclick"><th>Modify:</th><td class="center"><strong>'
            . 'Or:</strong><input type="radio" name="criteriaAndOrColumn[0]" value'
            . '="or">&nbsp;&nbsp;<strong>And:</strong><input type="radio" name='
            . '"criteriaAndOrColumn[0]" value="and" checked="checked"><br>Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[0]">&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[0]"></td><td '
            . 'class="center"><strong>Or:</strong><input type="radio" name="'
            . 'criteriaAndOrColumn[1]" value="or">&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and" checked="checked"><br>Ins<input type="checkbox" name='
            . '"criteriaColumnInsert[1]">&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]"></td><td class="center"><br>Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[2]">&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[2]"></td>'
            . '</tr>',
            $this->_callProtectedFunction(
                '_getModifyColumnsRow',
                []
            )
        );
    }

    /**
     * Test for _getInsDelAndOrCell
     *
     * @return void
     */
    public function testGetInsDelAndOrCell()
    {
        $this->assertEquals(
            '<td class="value nowrap"><!-- Row controls --><table class="nospac'
            . 'ing nopadding"><tr><td class="value nowrap"><small>Ins:</small>'
            . '<input type="checkbox" name="criteriaRowInsert[3]"></td><td '
            . 'class="value"><strong>And:</strong></td><td><input type="radio" '
            . 'name="criteriaAndOrRow[3]" value="and"></td></tr><tr><td class="'
            . 'value nowrap"><small>Del:</small><input type="checkbox" '
            . 'name="criteriaRowDelete[3]"></td><td class="value"><strong>'
            . 'Or:</strong></td><td><input type="radio" name="criteriaAndOrRow[3]" '
            . 'value="or" checked="checked"></td></tr></table></td>',
            $this->_callProtectedFunction(
                '_getInsDelAndOrCell',
                [
                    3,
                    [
                        'and' => '',
                        'or' => ' checked="checked"',
                    ],
                ]
            )
        );
    }

    /**
     * Test for _getInputboxRow
     *
     * @return void
     */
    public function testGetInputboxRow()
    {
        $this->assertEquals(
            '<td class="center"><input type="text" name="Or2[0]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td><td class="center">'
            . '<input type="text" name="Or2[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20"></td><td class="center"><input '
            . 'type="text" name="Or2[2]" value="" class="textfield" style="width: '
            . '12ex" size="20"></td>',
            $this->_callProtectedFunction(
                '_getInputboxRow',
                [2]
            )
        );
    }

    /**
     * Test for _getInsDelAndOrCriteriaRows
     *
     * @return void
     */
    public function testGetInsDelAndOrCriteriaRows()
    {
        $this->assertEquals(
            '<tr class="noclick"><td class="value nowrap"><!-- Row controls'
            . ' --><table class="nospacing nopadding"><tr><td class="value '
            . 'nowrap"><small>Ins:</small><input type="checkbox" name="'
            . 'criteriaRowInsert[0]"></td><td class="value"><strong>And:'
            . '</strong></td><td><input type="radio" name="criteriaAndOrRow[0]" '
            . 'value="and"></td></tr><tr><td class="value nowrap"><small>Del:'
            . '</small><input type="checkbox" name="criteriaRowDelete[0]"></td>'
            . '<td class="value"><strong>Or:</strong></td><td><input type='
            . '"radio" name="criteriaAndOrRow[0]" value="or" checked="checked">'
            . '</td></tr></table></td><td class="center"><input type="text" '
            . 'name="Or0[0]" value="" class="textfield" style="width: 12ex" '
            . 'size="20"></td><td class="center"><input type="text" name="Or0[1]" '
            . 'value="" class="textfield" style="width: 12ex" size="20"></td><td '
            . 'class="center"><input type="text" name="Or0[2]" value="" class='
            . '"textfield" style="width: 12ex" size="20"></td></tr>',
            $this->_callProtectedFunction(
                '_getInsDelAndOrCriteriaRows',
                [
                    2,
                    3,
                ]
            )
        );
    }

    /**
     * Test for _getSelectClause
     *
     * @return void
     */
    public function testGetSelectClause()
    {
        $this->assertEquals(
            '',
            $this->_callProtectedFunction(
                '_getSelectClause',
                []
            )
        );
    }

    /**
     * Test for _getWhereClause
     *
     * @return void
     */
    public function testGetWhereClause()
    {
        $this->assertEquals(
            '',
            $this->_callProtectedFunction(
                '_getWhereClause',
                []
            )
        );
    }

    /**
     * Test for _getOrderByClause
     *
     * @return void
     */
    public function testGetOrderByClause()
    {
        $this->assertEquals(
            '',
            $this->_callProtectedFunction(
                '_getOrderByClause',
                []
            )
        );
    }

    /**
     * Test for _getIndexes
     *
     * @return void
     */
    public function testGetIndexes()
    {
        $this->assertEquals(
            [
                'unique' => [],
                'index' => [],
            ],
            $this->_callProtectedFunction(
                '_getIndexes',
                [
                    [
                        '`table1`',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                ]
            )
        );
    }

    /**
     * Test for _getLeftJoinColumnCandidates
     *
     * @return void
     */
    public function testGetLeftJoinColumnCandidates()
    {
        $this->assertEquals(
            [
                0 => 'column2',
            ],
            $this->_callProtectedFunction(
                '_getLeftJoinColumnCandidates',
                [
                    [
                        '`table1`',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                ]
            )
        );
    }

    /**
     * Test for _getMasterTable
     *
     * @return void
     */
    public function testGetMasterTable()
    {
        $this->assertEquals(
            0,
            $this->_callProtectedFunction(
                '_getMasterTable',
                [
                    [
                        'table1',
                        'table2',
                    ],
                    [
                        'column1',
                        'column2',
                        'column3',
                    ],
                    ['column2'],
                    ['qbe_test'],
                ]
            )
        );
    }

    /**
     * Test for _getWhereClauseTablesAndColumns
     *
     * @return void
     */
    public function testGetWhereClauseTablesAndColumns()
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            [
                'where_clause_tables' => [],
                'where_clause_columns' => [],
            ],
            $this->_callProtectedFunction(
                '_getWhereClauseTablesAndColumns',
                []
            )
        );
    }

    /**
     * Test for _getFromClause
     *
     * @return void
     */
    public function testGetFromClause()
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            '`table1`',
            $this->_callProtectedFunction(
                '_getFromClause',
                [['`table1`.`id`']]
            )
        );
    }

    /**
     * Test for _getSQLQuery
     *
     * @return void
     */
    public function testGetSQLQuery()
    {
        $_POST['criteriaColumn'] = [
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted',
        ];
        $this->assertEquals(
            'FROM `table1`' . "\n",
            $this->_callProtectedFunction(
                '_getSQLQuery',
                [['`table1`.`id`']]
            )
        );
    }
}
