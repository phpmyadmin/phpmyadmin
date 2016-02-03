<?php
/**
 * Tests for DbQbe.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';
require_once 'test/PMATestCase.php';

use PMA\libraries\DbQbe;

/**
 * Tests for PMA\libraries\DbQbe class
 *
 *  @package PhpMyAdmin-test
 */
class DbQbeTest extends PMATestCase
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
    protected function setUp()
    {
        $this->object = new DbQbe('pma_test');
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'pma_test';
        //mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA\libraries\DbQbe');
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
        $this->assertEquals(
            '<td class="center"><select style="width: 12ex" name="criteriaSort[1]" '
            . 'size="1"><option value="">&nbsp;</option><option value="ASC">'
            . 'Ascending</option><option value="DESC">Descending</option>'
            . '</select></td>',
            $this->_callProtectedFunction(
                '_getSortSelectCell',
                array(1)
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
        $this->assertEquals(
            '<tr class="even noclick"><th>Sort:</th><td class="center">'
            . '<select style="width: 12ex" name="criteriaSort[0]" size="1">'
            . '<option value="">&nbsp;</option><option value="ASC">Ascending'
            . '</option><option value="DESC">Descending</option></select></td>'
            . '<td class="center"><select style="width: 12ex" '
            . 'name="criteriaSort[1]" size="1"><option value="">&nbsp;</option>'
            . '<option value="ASC">Ascending</option><option value="DESC">'
            . 'Descending</option></select></td><td class="center">'
            . '<select style="width: 12ex" name="criteriaSort[2]" size="1">'
            . '<option value="">&nbsp;</option><option value="ASC">Ascending'
            . '</option><option value="DESC">Descending</option></select></td></tr>',
            $this->_callProtectedFunction(
                '_getSortRow',
                array()
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
            '<tr class="odd noclick"><th>Show:</th><td class="center"><input type'
            . '="checkbox" name="criteriaShow[0]" /></td><td class="center">'
            . '<input type="checkbox" name="criteriaShow[1]" /></td><td '
            . 'class="center"><input type="checkbox" name="criteriaShow[2]" />'
            . '</td></tr>',
            $this->_callProtectedFunction(
                '_getShowRow',
                array()
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
            '<tr class="even noclick"><th>Criteria:</th><td class="center">'
            . '<input type="hidden" name="prev_criteria[0]" value="" />'
            . '<input type="text" name="criteria[0]" value="" class="textfield" '
            . 'style="width: 12ex" size="20" /></td><td class="center">'
            . '<input type="hidden" name="prev_criteria[1]" value="" />'
            . '<input type="text" name="criteria[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20" /></td><td class="center">'
            . '<input type="hidden" name="prev_criteria[2]" value="" />'
            . '<input type="text" name="criteria[2]" value="" class="textfield" '
            . 'style="width: 12ex" size="20" /></td></tr>',
            $this->_callProtectedFunction(
                '_getCriteriaInputboxRow',
                array()
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
        $this->assertEquals(
            '<div class="floatleft">Add/Delete criteria rows:<select size="1" '
            . 'name="criteriaRowAdd"><option value="-3">-3</option><option '
            . 'value="-2">-2</option><option value="-1">-1</option><option '
            . 'value="0" selected="selected">0</option><option value="1">1'
            . '</option><option value="2">2</option><option value="3">3</option>'
            . '</select></div>',
            $this->_callProtectedFunction(
                '_getFootersOptions',
                array('row')
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
        $this->assertEquals(
            '<fieldset class="tblFooters"><div class="floatleft">Add/Delete criteria'
            . ' rows:<select size="1" name="criteriaRowAdd"><option value="-3">-3'
            . '</option><option value="-2">-2</option><option value="-1">-1</option>'
            . '<option value="0" selected="selected">0</option><option value="1">1'
            . '</option><option value="2">2</option><option value="3">3</option>'
            . '</select></div><div class="floatleft">Add/Delete columns:<select '
            . 'size="1" name="criteriaColumnAdd"><option value="-3">-3</option>'
            . '<option value="-2">-2</option><option value="-1">-1</option>'
            . '<option value="0" selected="selected">0</option><option value="1">1'
            . '</option><option value="2">2</option><option value="3">3</option>'
            . '</select></div><div class="floatleft"><input type="submit" '
            . 'name="modify"value="Update Query" /></div></fieldset>',
            $this->_callProtectedFunction(
                '_getTableFooters',
                array()
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
            . 'name="criteriaAndOrColumn[1]" value="or" />&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and" /><br />Ins<input type="checkbox" name="criteriaColumnInsert'
            . '[1]" />&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]" /></td>',
            $this->_callProtectedFunction(
                '_getAndOrColCell',
                array(1)
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
            '<tr class="even noclick"><th>Modify:</th><td class="center"><strong>'
            . 'Or:</strong><input type="radio" name="criteriaAndOrColumn[0]" value'
            . '="or" />&nbsp;&nbsp;<strong>And:</strong><input type="radio" name='
            . '"criteriaAndOrColumn[0]" value="and" checked="checked" /><br />Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[0]" />&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[0]" /></td><td '
            . 'class="center"><strong>Or:</strong><input type="radio" name="'
            . 'criteriaAndOrColumn[1]" value="or" />&nbsp;&nbsp;<strong>And:'
            . '</strong><input type="radio" name="criteriaAndOrColumn[1]" value='
            . '"and" checked="checked" /><br />Ins<input type="checkbox" name='
            . '"criteriaColumnInsert[1]" />&nbsp;&nbsp;Del<input type="checkbox" '
            . 'name="criteriaColumnDelete[1]" /></td><td class="center"><br />Ins'
            . '<input type="checkbox" name="criteriaColumnInsert[2]" />&nbsp;&nbsp;'
            . 'Del<input type="checkbox" name="criteriaColumnDelete[2]" /></td>'
            . '</tr>',
            $this->_callProtectedFunction(
                '_getModifyColumnsRow',
                array()
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
        $GLOBALS['cell_align_right'] = 'cellAlign';
        $this->assertEquals(
            '<td class="cellAlign nowrap"><!-- Row controls --><table class="nospac'
            . 'ing nopadding"><tr><td class="cellAlign nowrap"><small>Ins:</small>'
            . '<input type="checkbox" name="criteriaRowInsert[3]" /></td><td '
            . 'class="cellAlign"><strong>And:</strong></td><td><input type="radio" '
            . 'name="criteriaAndOrRow[3]" value="and" /></td></tr><tr><td class="'
            . 'cellAlign nowrap"><small>Del:</small><input type="checkbox" '
            . 'name="criteriaRowDelete[3]" /></td><td class="cellAlign"><strong>'
            . 'Or:</strong></td><td><input type="radio" name="criteriaAndOrRow[3]" '
            . 'value="or" checked="checked" /></td></tr></table></td>',
            $this->_callProtectedFunction(
                '_getInsDelAndOrCell',
                array(3, array('and' => '', 'or' => ' checked="checked"'))
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
            . '"textfield" style="width: 12ex" size="20" /></td><td class="center">'
            . '<input type="text" name="Or2[1]" value="" class="textfield" '
            . 'style="width: 12ex" size="20" /></td><td class="center"><input '
            . 'type="text" name="Or2[2]" value="" class="textfield" style="width: '
            . '12ex" size="20" /></td>',
            $this->_callProtectedFunction(
                '_getInputboxRow',
                array(2)
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
        $GLOBALS['cell_align_right'] = 'cellAlign';
        $this->assertEquals(
            '<tr class="odd noclick"><td class="cellAlign nowrap"><!-- Row controls'
            . ' --><table class="nospacing nopadding"><tr><td class="cellAlign '
            . 'nowrap"><small>Ins:</small><input type="checkbox" name="'
            . 'criteriaRowInsert[0]" /></td><td class="cellAlign"><strong>And:'
            . '</strong></td><td><input type="radio" name="criteriaAndOrRow[0]" '
            . 'value="and" /></td></tr><tr><td class="cellAlign nowrap"><small>Del:'
            . '</small><input type="checkbox" name="criteriaRowDelete[0]" /></td>'
            . '<td class="cellAlign"><strong>Or:</strong></td><td><input type='
            . '"radio" name="criteriaAndOrRow[0]" value="or" checked="checked" />'
            . '</td></tr></table></td><td class="center"><input type="text" '
            . 'name="Or0[0]" value="" class="textfield" style="width: 12ex" '
            . 'size="20" /></td><td class="center"><input type="text" name="Or0[1]" '
            . 'value="" class="textfield" style="width: 12ex" size="20" /></td><td '
            . 'class="center"><input type="text" name="Or0[2]" value="" class='
            . '"textfield" style="width: 12ex" size="20" /></td></tr>',
            $this->_callProtectedFunction(
                '_getInsDelAndOrCriteriaRows',
                array(2,3)
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
                array()
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
                array()
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
                array()
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
            array(
                'unique' => array(),
                'index' => array()
            ),
            $this->_callProtectedFunction(
                '_getIndexes',
                array(
                    array('`table1`','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2')
                )
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
            array(
                0 => 'column2'
            ),
            $this->_callProtectedFunction(
                '_getLeftJoinColumnCandidates',
                array(
                    array('`table1`','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2')
                )
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
                array(
                    array('table1','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2'),
                    array('qbe_test')
                )
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
        $_POST['criteriaColumn'] = array(
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted'
        );
        $this->assertEquals(
            array(
                'where_clause_tables' => array(),
                'where_clause_columns' => array()
            ),
            $this->_callProtectedFunction(
                '_getWhereClauseTablesAndColumns',
                array()
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
        $_POST['criteriaColumn'] = array(
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted'
        );
        $this->assertEquals(
            '`table1`',
            $this->_callProtectedFunction(
                '_getFromClause',
                array(array('`table1`.`id`'))
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
        $_POST['criteriaColumn'] = array(
            'table1.id',
            'table1.value',
            'table1.name',
            'table1.deleted'
        );
        $this->assertEquals(
            'FROM `table1`
',
            $this->_callProtectedFunction(
                '_getSQLQuery',
                array(array('`table1`.`id`'))
            )
        );
    }
}
