<?php
/**
 * Tests for DBQbe.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/DBQbe.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/relation.lib.php';

class PMA_DBQbe_test extends PHPUnit_Framework_TestCase
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
        if (! function_exists('PMA_DBI_query')) {
            function PMA_DBI_query()
            {
                return array('table1', 'table2');
            }
        }
        if (! function_exists('PMA_DBI_num_rows')) {
            function PMA_DBI_num_rows()
            {
                return 2;
            }
        }
        if (! function_exists('PMA_DBI_fetch_row')) {
            function PMA_DBI_fetch_row()
            {
                return false;
            }
        }
        if (! function_exists('PMA_DBI_get_columns')) {
            function PMA_DBI_get_columns()
            {
                return array('column1', 'column2');
            }
        }
        if (! function_exists('PMA_DBI_free_result')) {
            function PMA_DBI_free_result()
            {
                return true;
            }
        }
        if (! defined('PMA_DBI_QUERY_STORE')) {
            define('PMA_DBI_QUERY_STORE', 1);
        }
        $this->object = $this->getMockForAbstractClass('PMA_DBQbe', array('pma'));
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
     * Call protected functions by making the visibitlity to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_DBQbe');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for getCommonFunctions
     */
    public function testGetCommonFunctions(){
        $this->assertTrue($this->object->getCommonFunctions() instanceof PMA_CommonFunctions);
    }

    /**
     * Test for _getSortSelectCell
     */
    public function testGetSortSelectCell(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getSortSelectCell',
                array(1)
            ),
            '<td class="center"><select style="width: 12ex" name="criteriaSort[1]" size="1"><option value="">&nbsp;</option><option value="ASC">Ascending</option><option value="DESC">Descending</option></select></td>'
        );
    }

    /**
     * Test for _getSortRow
     */
    public function testGetSortRow(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getSortRow',
                array()
            ),
            '<tr class="even noclick"><th>Sort:</th><td class="center"><select style="width: 12ex" name="criteriaSort[0]" size="1"><option value="">&nbsp;</option><option value="ASC">Ascending</option><option value="DESC">Descending</option></select></td><td class="center"><select style="width: 12ex" name="criteriaSort[1]" size="1"><option value="">&nbsp;</option><option value="ASC">Ascending</option><option value="DESC">Descending</option></select></td><td class="center"><select style="width: 12ex" name="criteriaSort[2]" size="1"><option value="">&nbsp;</option><option value="ASC">Ascending</option><option value="DESC">Descending</option></select></td></tr>'
        );
    }

    /**
     * Test for _getShowRow
     */
    public function testGetShowRow(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getShowRow',
                array()
            ),
            '<tr class="odd noclick"><th>Show:</th><td class="center"><input type="checkbox" name="criteriaShow[0]" /></td><td class="center"><input type="checkbox" name="criteriaShow[1]" /></td><td class="center"><input type="checkbox" name="criteriaShow[2]" /></td></tr>'
        );
    }

    /**
     * Test for _getCriteriaInputboxRow
     */
    public function testGetCriteriaInputboxRow(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getCriteriaInputboxRow',
                array()
            ),
            '<tr class="even noclick"><th>Criteria:</th><td class="center"><input type="hidden" name="prev_criteria[0]" value="" /><input type="text" name="criteria[0]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="hidden" name="prev_criteria[1]" value="" /><input type="text" name="criteria[1]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="hidden" name="prev_criteria[2]" value="" /><input type="text" name="criteria[2]" value="" class="textfield" style="width: 12ex" size="20" /></td></tr>'
        );
    }

    /**
     * Test for _getFootersOptions
     */
    public function testGetFootersOptions(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getFootersOptions',
                array('row')
            ),
            '<div class="floatleft">Add/Delete criteria rows:<select size="1" name="criteriaRowAdd"><option value="-3">-3</option><option value="-2">-2</option><option value="-1">-1</option><option value="0" selected="selected">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></div>'
        );
    }

    /**
     * Test for _getTableFooters
     */
    public function testGetTableFooters(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getTableFooters',
                array()
            ),
            '<fieldset class="tblFooters"><div class="floatleft">Add/Delete criteria rows:<select size="1" name="criteriaRowAdd"><option value="-3">-3</option><option value="-2">-2</option><option value="-1">-1</option><option value="0" selected="selected">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></div><div class="floatleft">Add/Delete columns:<select size="1" name="criteriaColumnAdd"><option value="-3">-3</option><option value="-2">-2</option><option value="-1">-1</option><option value="0" selected="selected">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></div><div class="floatleft"><input type="submit" name="modify"value="Update Query" /></div></fieldset>'
        );
    }

    /**
     * Test for _getAndOrColCell
     */
    public function testGetAndOrColCell(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getAndOrColCell',
                array(1)
            ),
            '<td class="center"><strong>Or:</strong><input type="radio" name="criteriaAndOrColumn[1]" value="or" />&nbsp;&nbsp;<strong>And:</strong><input type="radio" name="criteriaAndOrColumn[1]" value="and" /><br />Ins<input type="checkbox" name="criteriaColumnInsert[1]" />&nbsp;&nbsp;Del<input type="checkbox" name="criteriaColumnDelete[1]" /></td>'
        );
    }

    /**
     * Test for _getModifyColumnsRow
     */
    public function testGetModifyColumnsRow(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getModifyColumnsRow',
                array()
            ),
            '<tr class="even noclick"><th>Modify:</th><td class="center"><strong>Or:</strong><input type="radio" name="criteriaAndOrColumn[0]" value="or" />&nbsp;&nbsp;<strong>And:</strong><input type="radio" name="criteriaAndOrColumn[0]" value="and" checked="checked" /><br />Ins<input type="checkbox" name="criteriaColumnInsert[0]" />&nbsp;&nbsp;Del<input type="checkbox" name="criteriaColumnDelete[0]" /></td><td class="center"><strong>Or:</strong><input type="radio" name="criteriaAndOrColumn[1]" value="or" />&nbsp;&nbsp;<strong>And:</strong><input type="radio" name="criteriaAndOrColumn[1]" value="and" checked="checked" /><br />Ins<input type="checkbox" name="criteriaColumnInsert[1]" />&nbsp;&nbsp;Del<input type="checkbox" name="criteriaColumnDelete[1]" /></td><td class="center"><strong>Or:</strong><input type="radio" name="criteriaAndOrColumn[2]" value="or" />&nbsp;&nbsp;<strong>And:</strong><input type="radio" name="criteriaAndOrColumn[2]" value="and" checked="checked" /><br />Ins<input type="checkbox" name="criteriaColumnInsert[2]" />&nbsp;&nbsp;Del<input type="checkbox" name="criteriaColumnDelete[2]" /></td></tr>'
        );
    }

    /**
     * Test for _getInsDelAndOrCell
     */
    public function testGetInsDelAndOrCell(){
        $GLOBALS['cell_align_right'] = 'cellAlign';
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getInsDelAndOrCell',
                array(3, 'checked')
            ),
            '<td class="cellAlign nowrap"><!-- Row controls --><table class="nospacing nopadding"><tr><td class="cellAlign nowrap"><small>Ins:</small><input type="checkbox" name="criteriaRowInsert[3]" /></td><td class="cellAlign"><strong>And:</strong></td><td><input type="radio" name="criteriaAndOrRow[3]" value="and"c /></td></tr><tr><td class="cellAlign nowrap"><small>Del:</small><input type="checkbox" name="criteriaRowDelete[3]" /></td><td class="cellAlign"><strong>Or:</strong></td><td><input type="radio" name="criteriaAndOrRow[3]" value="or"c /></td></tr></table></td>'
        );
    }

    /**
     * Test for _getInputboxRow
     */
    public function testGetInputboxRow(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getInputboxRow',
                array(2,3)
            ),
            '<td class="center"><input type="text" name="Or2[0]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="text" name="Or2[1]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="text" name="Or2[2]" value="" class="textfield" style="width: 12ex" size="20" /></td>'
        );
    }

    /**
     * Test for _getInsDelAndOrCriteriaRows
     */
    public function testGetInsDelAndOrCriteriaRows(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getInsDelAndOrCriteriaRows',
                array(2,3)
            ),
            '<tr class="odd noclick"><td class="cellAlign nowrap"><!-- Row controls --><table class="nospacing nopadding"><tr><td class="cellAlign nowrap"><small>Ins:</small><input type="checkbox" name="criteriaRowInsert[0]" /></td><td class="cellAlign"><strong>And:</strong></td><td><input type="radio" name="criteriaAndOrRow[0]" value="and" /></td></tr><tr><td class="cellAlign nowrap"><small>Del:</small><input type="checkbox" name="criteriaRowDelete[0]" /></td><td class="cellAlign"><strong>Or:</strong></td><td><input type="radio" name="criteriaAndOrRow[0]" value="or" checked="checked" /></td></tr></table></td><td class="center"><input type="text" name="Or0[0]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="text" name="Or0[1]" value="" class="textfield" style="width: 12ex" size="20" /></td><td class="center"><input type="text" name="Or0[2]" value="" class="textfield" style="width: 12ex" size="20" /></td></tr>'
        );
    }

    /**
     * Test for _getSelectClause
     */
    public function testGetSelectClause(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getSelectClause',
                array()
            ),
            ''
        );
    }

    /**
     * Test for _getWhereClause
     */
    public function testGetWhereClause(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getWhereClause',
                array()
            ),
            ''
        );
    }

    /**
     * Test for _getOrderByClause
     */
    public function testGetOrderByClause(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getOrderByClause',
                array()
            ),
            ''
        );
    }

    /**
     * Test for _getIndexes
     */
    public function testGetIndexes(){
        if (! function_exists('PMA_DBI_get_table_indexes')) {
            function PMA_DBI_get_table_indexes()
            {
                return array(2,3);
            }
        }
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getIndexes',
                array(
                    array('table1','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2')
                )
            ),
            array(
                'unique' => array(),
                'index' => array()
            )
        );
    }

    /**
     * Test for _getLeftJoinColumnCandidates
     */
    public function test_getLeftJoinColumnCandidates(){
        if (! function_exists('PMA_DBI_select_db')) {
            function PMA_DBI_select_db()
            {
                return 'pma';
            }
        }
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getLeftJoinColumnCandidates',
                array(
                    array('table1','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2')
                )
            ),
            array(
                0 => 'column2'
            )
        );
    }

    /**
     * Test for _getMasterTable
     */
    public function test_getMasterTable(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getMasterTable',
                array(
                    array('table1','table2'),
                    array('column1', 'column2', 'column3'),
                    array('column2'),
                    array('pma')
                )
            ),
            0
        );
    }

    /**
     * Test for _getWhereClauseTablesAndColumns
     */
    public function test_getWhereClauseTablesAndColumns(){
        $_POST['criteriaColumn'] = array('table1.id', 'table1.value', 'table1.name', 'table1.deleted');
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getWhereClauseTablesAndColumns',
                array()
            ),
            array(
                'where_clause_tables' => array(),
                'where_clause_columns' => array()
            )
        );
    }

    /**
     * Test for _getFromClause
     */
    public function testGetFromClause(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getFromClause',
                array('relation')
            ),
            '`table1`'
        );
    }

    /**
     * Test for _getSQLQuery
     */
    public function test_getSQLQuery(){
        $this->assertEquals(
            $this->_callProtectedFunction(
                '_getSQLQuery',
                array('relation')
            ),
            'FROM `table1`
'
        );
    }
}
