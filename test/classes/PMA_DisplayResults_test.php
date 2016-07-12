<?php
/**
 * Tests for displaying results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/DisplayResults.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Util.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/string.lib.php';
require_once 'libraries/String.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Link.class.php';
require_once 'libraries/DatabaseInterface.class.php';

/**
 * Test cases for displaying results.
 *
 * @package PhpMyAdmin-test
 */
class PMA_DisplayResults_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['server'] = 0;
        $this->object = new PMA_DisplayResults('as', '', '', '');
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['collation_connection'] = 'utf-8';
        include_once 'libraries/Response.class.php';

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fieldFlags')
            ->will($this->returnArgument(1));

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
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_DisplayResults');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _isSelect function
     *
     * @return void
     */
    public function testisSelect()
    {
        $analyzed_sql = array(array());
        $analyzed_sql[0]['select_expr'] = array();
        $analyzed_sql[0]['queryflags']['select_from'] = 'pma';
        $analyzed_sql[0]['table_ref'] = array('table_ref');

        $this->assertTrue(
            $this->_callPrivateFunction(
                '_isSelect',
                array($analyzed_sql)
            )
        );
    }

    /**
     * Test for navigation buttons
     *
     * @param string  $caption        iconic caption for button
     * @param string  $title          text for button
     * @param integer $pos            position for next query
     * @param string  $html_sql_query query ready for display
     * @param string  $output         output from the _getTableNavigationButton
     *                                method
     *
     * @return void
     *
     * @dataProvider providerForTestGetTableNavigationButton
     */
    public function testGetTableNavigationButton(
        $caption, $title, $pos, $html_sql_query, $output
    ) {
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $_SESSION[' PMA_token '] = 'token';

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getTableNavigationButton',
                array(&$caption, $title, $pos, $html_sql_query, true)
            )
        );
    }

    /**
     * Provider for testGetTableNavigationButton
     *
     * @return array array data for testGetTableNavigationButton
     */
    public function providerForTestGetTableNavigationButton()
    {
        return array(
            array(
                'btn',
                'Submit',
                1,
                'SELECT * FROM `pma_bookmark` WHERE 1',
                '<td><form action="sql.php" method="post" >'
                . '<input type="hidden" name="db" value="as" />'
                . '<input type="hidden" name="lang" value="en" />'
                . '<input type="hidden" name="collation_connection" value="utf-8" />'
                . '<input type="hidden" name="token" value="token" />'
                . '<input type="hidden" name="sql_query" value="SELECT * '
                . 'FROM `pma_bookmark` WHERE 1" />'
                . '<input type="hidden" name="pos" value="1" />'
                . '<input type="hidden" name="is_browse_distinct" value="" />'
                . '<input type="hidden" name="goto" value="" />'
                . '<input type="submit" name="navig" class="ajax" '
                . 'value="btn"  title="Submit" /></form></td>'
            )
        );
    }

    /**
     * Test for table navigation
     *
     * @param integer $pos_next  the offset for the "next" page
     * @param integer $pos_prev  the offset for the "previous" page
     * @param boolean $is_innodb the table type is innoDb or not
     * @param string  $output    output from the _getTableNavigation method
     *
     * @return void
     *
     * @dataProvider providerForTestGetTableNavigation
     */
    public function testGetTableNavigation(
        $pos_next, $pos_prev, $is_innodb, $output
    ) {
        $_SESSION['tmpval']['max_rows'] = '20';
        $_SESSION['tmpval']['pos'] = true;
        $GLOBALS['num_rows'] = '20';
        $GLOBALS['unlim_num_rows'] = '50';
        $GLOBALS['cfg']['ShowAll'] = true;
        $_SESSION['tmpval']['repeat_cells'] = '1';

        /**
         * FIXME Counting words of a generated large HTML is not a good way
         * of testing IMO. Introduce more granular assertions that assert for
         * existence of important content inside the generated HTML.
         */
        /*
        $this->assertEquals(
            $output,
            str_word_count(
                $this->_callPrivateFunction(
                    '_getTableNavigation',
                    array(
                        $pos_next, $pos_prev, $is_innodb
                    )
                )
            )
        );
        */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Provider for testing table navigation
     *
     * @return array data for testGetTableNavigation
     */
    public function providerForTestGetTableNavigation()
    {
        return array(
            array(
                21,
                41,
                false,
                '310'
            )
        );
    }

    /**
     * Data provider for testGetClassesForColumn
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetClassesForColumn()
    {
        return array(
            array(
                'grid_edit',
                'not_null',
                '',
                '',
                '',
                'data grid_edit not_null   '
            )
        );
    }

    /**
     * Test for _getClassesForColumn
     *
     * @param string  $grid_edit_class  the class for all editable columns
     * @param string  $not_null_class   the class for not null columns
     * @param string  $relation_class   the class for relations in a column
     * @param string  $hide_class       the class for visibility of a column
     * @param string  $field_type_class the class related to type of the field
     * @param string  $output           output of__getResettedClassForInlineEdit
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetClassesForColumn
     */
    public function testGetClassesForColumn(
        $grid_edit_class, $not_null_class, $relation_class,
        $hide_class, $field_type_class, $output
    ) {
        $GLOBALS['cfg']['BrowsePointerEnable'] = true;
        $GLOBALS['cfg']['BrowseMarkerEnable'] = true;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getClassesForColumn',
                array(
                    $grid_edit_class, $not_null_class, $relation_class,
                    $hide_class, $field_type_class
                )
            )
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 1
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase1()
    {
        $this->assertEquals(
            'datetimefield',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::DATETIME_FIELD)
            )
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 2
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase2()
    {
        $this->assertEquals(
            'datefield',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::DATE_FIELD)
            )
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 3
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase3()
    {
        $this->assertEquals(
            'text',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::STRING_FIELD)
            )
        );
    }

    /**
     * Data provider for testGetCheckBoxesForMultipleRowOperations
     *
     * @return array parameters and output
     */
    public function dataProviderForGetCheckBoxesForMultipleRowOperations()
    {
        return array(
            array(
                '_left',
                array('edit_lnk' => null, 'del_lnk' => null),
                //array('edit_lnk' => 'nn', 'del_lnk' => 'nn'),
                '<td class="odd" class="center"><input type='
                . '"checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" '
                . 'class="multi_checkbox" value="%60cars%60.%60id%60+%3D+3"  />'
                . '<input type="hidden" class="condition_array" value="{&quot;'
                . '`cars`.`id`&quot;:&quot;= 3&quot;}" />    </td><td class="even"'
                . ' class="center"><input type="checkbox" '
                . 'id="id_rows_to_delete1_left" name="rows_to_delete[1]" class='
                . '"multi_checkbox" value="%60cars%60.%60id%60+%3D+9"  /><input '
                . 'type="hidden" class="condition_array" value="{&quot;`cars`.'
                . '`id`&quot;:&quot;= 9&quot;}" />    </td>'
            )
        );
    }

    /**
     * Test for _getCheckBoxesForMultipleRowOperations
     *
     * @param string $dir          _left / _right
     * @param array  $displayParts which parts to display
     * @param string $output       output of _getCheckBoxesForMultipleRowOperations
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCheckBoxesForMultipleRowOperations
     */
    public function testGetCheckBoxesForMultipleRowOperations(
        $dir, $displayParts, $output
    ) {
        $vertical_display = array(
            'row_delete' => array(
                '<td class="odd" class="center"><input '
                . 'type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" '
                . 'name="rows_to_delete[0]" class="multi_checkbox" value="%60cars'
                . '%60.%60id%60+%3D+3"  /><input type="hidden" class="condition_'
                . 'array" value="{&quot;`cars`.`id`&quot;:&quot;= 3&quot;}" />    '
                . '</td>',
                '<td class="even" class="center"><input '
                . 'type="checkbox" id="id_rows_to_delete1[%_PMA_CHECKBOX_DIR_%]" '
                . 'name="rows_to_delete[1]" class="multi_checkbox" value="%60cars'
                . '%60.%60id%60+%3D+9"  /><input type="hidden" class="condition_'
                . 'array" value="{&quot;`cars`.`id`&quot;:&quot;= 9&quot;}" />    '
                . '</td>'
            )
        );

        $this->object->__set('vertical_display', $vertical_display);

        $_SESSION['tmpval']['repeat_cells'] = 0;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckBoxesForMultipleRowOperations',
                array($dir, $displayParts)
            )
        );
    }

    /**
     * Test for _getOffsets - case 1
     *
     * @return void
     */
    public function testGetOffsetsCase1()
    {
        $_SESSION['tmpval']['max_rows'] = PMA_DisplayResults::ALL_ROWS;
        $this->assertEquals(
            array(0, 0),
            $this->_callPrivateFunction('_getOffsets', array())
        );
    }

    /**
     * Test for _getOffsets - case 2
     *
     * @return void
     */
    public function testGetOffsetsCase2()
    {
        $_SESSION['tmpval']['max_rows'] = 5;
        $_SESSION['tmpval']['pos'] = 4;
        $this->assertEquals(
            array(9, 0),
            $this->_callPrivateFunction('_getOffsets', array())
        );
    }

    /**
     * Data provider for testGetSortParams
     *
     * @return array parameters and output
     */
    public function dataProviderForGetSortParams()
    {
        return array(
            array('', array(array(''), array(''), array(''))),
            array(
                '`a_sales`.`customer_id` ASC',
                array(
                    array('`a_sales`.`customer_id` ASC'),
                    array('`a_sales`.`customer_id`'),
                    array('ASC')
                )
            ),
            array(
                '`a_sales`.`customer_id` ASC, `b_sales`.`customer_id` DESC',
                array(
                    array(
                        '`a_sales`.`customer_id` ASC',
                        '`b_sales`.`customer_id` DESC'
                    ),
                    array('`a_sales`.`customer_id`', '`b_sales`.`customer_id`'),
                    array('ASC', 'DESC')
                )
            ),
        );
    }

    /**
     * Test for _getSortParams
     *
     * @param string $order_by_clause the order by clause of the sql query
     * @param string $output          output of _getSortParams
     *
     * @return void
     *
     * @dataProvider dataProviderForGetSortParams
     */
    public function testGetSortParams($order_by_clause, $output)
    {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getSortParams', array($order_by_clause)
            )
        );
    }

    /**
     * Data provider for testGetCheckboxForMultiRowSubmissions
     *
     * @return array parameters and output
     */
    public function dataProviderForGetCheckboxForMultiRowSubmissions()
    {
        return array(
            array(
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60'
                . '.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show='
                . 'The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3D'
                . 'new%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message'
                . '_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_'
                . 'structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;'
                . 'token=d1aecb47ef7c081e068e7008b38a5d76',
                array(
                    'edit_lnk' => 'ur',
                    'del_lnk' => 'dr',
                    'sort_lnk' => '0',
                    'nav_bar' => '1',
                    'ins_row' => '1',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1'
                ),
                0,
                '%60new%60.%60id%60+%3D+1',
                array('`new`.`id`' => '= 1'),
                '[%_PMA_CHECKBOX_DIR_%]',
                'odd',
                '<td class="odd" class="center"><input type'
                . '="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name='
                . '"rows_to_delete[0]" class="multi_checkbox checkall" value="%60'
                . 'new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_'
                . 'array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    '
                . '</td>'
            )
        );
    }

    /**
     * Test for _getCheckboxForMultiRowSubmissions
     *
     * @param string $del_url           delete url
     * @param array  $displayParts      array with explicit indexes for all
     *                                  the display elements
     * @param string $row_no            the row number
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $id_suffix         suffix for the id
     * @param string $class             css classes for the td element
     * @param string $output            output of _getCheckboxForMultiRowSubmissions
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCheckboxForMultiRowSubmissions
     */
    public function testGetCheckboxForMultiRowSubmissions(
        $del_url, $displayParts, $row_no, $where_clause_html, $condition_array,
        $id_suffix, $class, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckboxForMultiRowSubmissions',
                array(
                    $del_url, $displayParts, $row_no, $where_clause_html,
                    $condition_array, $id_suffix, $class
                )
            )
        );
    }

    /**
     * Data provider for testGetEditLink
     *
     * @return array parameters and output
     */
    public function dataProviderForGetEditLink()
    {
        return array(
            array(
                'tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60'
                . 'customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query='
                . 'SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_'
                . 'action=update&amp;token=bbd5003198a3bd856b21d9607d6c6a1e',
                'odd edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt='
                . '"Edit" class="icon ic_b_edit" /> Edit</span>',
                '`customer`.`id` = 1',
                '%60customer%60.%60id%60+%3D+1',
                '<td class="odd edit_row_anchor center"  >'
                . '<span class="nowrap">' . "\n"
                . '<a href="tbl_change.php?db=Data&amp;table=customer&amp;where_'
                . 'clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;'
                . 'sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;'
                . 'default_action=update&amp;token=bbd5003198a3bd856b21d9607d6c6a1e"'
                . ' ><span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value ="%60customer'
                . '%60.%60id%60+%3D+1" /></span></td>'
            )
        );
    }

    /**
     * Test for _getEditLink
     *
     * @param string $edit_url          edit url
     * @param string $class             css classes for td element
     * @param string $edit_str          text for the edit link
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param string $output            output of _getEditLink
     *
     * @return void
     *
     * @dataProvider dataProviderForGetEditLink
     */
    public function testGetEditLink(
        $edit_url, $class, $edit_str, $where_clause, $where_clause_html, $output
    ) {
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getEditLink',
                array(
                    $edit_url, $class, $edit_str, $where_clause, $where_clause_html
                )
            )
        );
    }

    /**
     * Data provider for testGetCopyLink
     *
     * @return array parameters and output
     */
    public function dataProviderForGetCopyLink()
    {
        return array(
            array(
                'tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60cust'
                . 'omer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query='
                . 'SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_'
                . 'action=insert&amp;token=f597309d3a066c3c81a6cb015a79636d',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt'
                . '="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '`customer`.`id` = 1',
                '%60customer%60.%60id%60+%3D+1',
                'odd',
                '<td class="odd center"  ><span class='
                . '"nowrap">' . "\n"
                . '<a href="tbl_change.php?db=Data&amp;table=customer&amp;where_'
                . 'clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;'
                . 'sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;'
                . 'default_action=insert&amp;token=f597309d3a066c3c81a6cb015a79636d"'
                . ' ><span class="nowrap"><img src="themes/dot.gif" title="Copy" '
                . 'alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value="%60customer%60'
                . '.%60id%60+%3D+1" /></span></td>'
            )
        );
    }

    /**
     * Test for _getCopyLink
     *
     * @param string $copy_url          copy url
     * @param string $copy_str          text for the copy link
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param string $class             css classes for the td element
     * @param string $output            output of _getCopyLink
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCopyLink
     */
    public function testGetCopyLink(
        $copy_url, $copy_str, $where_clause, $where_clause_html, $class, $output
    ) {
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCopyLink',
                array(
                    $copy_url, $copy_str, $where_clause, $where_clause_html, $class
                )
            )
        );
    }

    /**
     * Data provider for testGetDeleteLink
     *
     * @return array parameters and output
     */
    public function dataProviderForGetDeleteLink()
    {
        return array(
            array(
                'sql.php?db=Data&amp;table=customer&amp;sql_query=DELETE+FROM+%60'
                . 'Data%60.%60customer%60+WHERE+%60customer%60.%60id%60+%3D+1&amp;'
                . 'message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb'
                . '%3DData%26table%3Dcustomer%26sql_query%3DSELECT%2B%252A%2BFROM'
                . '%2B%2560customer%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen'
                . '%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Df597309d3a066c3'
                . 'c81a6cb015a79636d&amp;token=f597309d3a066c3c81a6cb015a79636d',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" '
                . 'alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `Data`.`customer` WHERE `customer`.`id` = 1',
                'odd',
                '<td class="odd center"  >' . "\n"
                . '<a href="sql.php?db=Data&amp;table=customer&amp;sql_query=DELETE'
                . '+FROM+%60Data%60.%60customer%60+WHERE+%60customer%60.%60id%60+%3D'
                . '+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php'
                . '%3Fdb%3DData%26table%3Dcustomer%26sql_query%3DSELECT%2B%252A%2B'
                . 'FROM%2B%2560customer%2560%26message_to_show%3DThe%2Brow%2Bhas%2B'
                . 'been%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Df597309d3a06'
                . '6c3c81a6cb015a79636d&amp;token=f597309d3a066c3c81a6cb015a79636d" '
                . 'class="delete_row requireConfirm"><span class="nowrap"><img src="themes/dot.'
                . 'gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> '
                . 'Delete</span></a>' . "\n"
                . '<div class="hide">DELETE FROM `Data`.`customer` WHERE '
                . '`customer`.`id` = 1</div></td>'
            )
        );
    }

    /**
     * Test for _getDeleteLink
     *
     * @param string $del_url delete url
     * @param string $del_str text for the delete link
     * @param string $js_conf text for the JS confirmation
     * @param string $class   css classes for the td element
     * @param string $output  output of _getDeleteLink
     *
     * @return void
     *
     * @dataProvider dataProviderForGetDeleteLink
     */
    public function testGetDeleteLink(
        $del_url, $del_str, $js_conf, $class, $output
    ) {
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getDeleteLink',
                array(
                    $del_url, $del_str, $js_conf, $class
                )
            )
        );
    }

    /**
     * Data provider for testGetCheckboxAndLinks
     *
     * @return array parameters and output
     */
    public function dataProviderForGetCheckboxAndLinks()
    {
        return array(
            array(
                PMA_DisplayResults::POSITION_LEFT,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data'
                . '%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show='
                . 'The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3D'
                . 'new%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26'
                . 'message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3D'
                . 'tbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8'
                . '&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                array(
                    'edit_lnk' => 'ur',
                    'del_lnk' => 'dr',
                    'sort_lnk' => '0',
                    'nav_bar' => '1',
                    'ins_row' => '1',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1'
                ),
                0,
                '`new`.`id` = 1',
                '%60new%60.%60id%60+%3D+1',
                array(
                    '`new`.`id`' => '= 1',
                ),
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.'
                . '%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+'
                . 'FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;'
                . 'token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.'
                . '%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+'
                . 'FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;'
                . 'token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" '
                . 'alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" '
                . 'alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td  class="center"><input type="checkbox" id="id_rows_to_delete0_'
                . 'left" name="rows_to_delete[0]" class="multi_checkbox checkall" '
                . 'value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class='
                . '"condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;'
                . '}" />    </td><td class="edit_row_anchor center"  ><span class='
                . '"nowrap">' . "\n"
                . '<a href="tbl_change.php?db=data&amp;table=new&amp;where_'
                . 'clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;'
                . 'sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default'
                . '_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" >'
                . '<span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value ="%60new%60.%60'
                . 'id%60+%3D+1" /></span></td><td class="center"  ><span class'
                . '="nowrap">' . "\n"
                . '<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause'
                . '=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query='
                . 'SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action='
                . 'insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class'
                . '="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" '
                . 'class="icon ic_b_insrow" /> Copy</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value="%60new%60.%60id'
                . '%60+%3D+1" /></span></td><td class="center"  >' . "\n"
                . '<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+'
                . 'FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;'
                . 'message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3F'
                . 'db%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B'
                . '%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2B'
                . 'deleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446d'
                . 'fa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" '
                . 'class="delete_row requireConfirm"><span class="nowrap"><img src="themes/dot.'
                . 'gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> '
                . 'Delete</span></a>' . "\n"
                . '<div class="hide">DELETE FROM `data`.`new` WHERE `new`.`id` = 1'
                . '</div></td>'
            ),
            array(
                PMA_DisplayResults::POSITION_RIGHT,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60'
                . '.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show='
                . 'The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3D'
                . 'new%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message'
                . '_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_'
                . 'structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;'
                . 'token=ae4c6d18375f446dfa068420c1f6a4e8',
                array(
                    'edit_lnk' => 'ur',
                    'del_lnk' => 'dr',
                    'sort_lnk' => '0',
                    'nav_bar' => '1',
                    'ins_row' => '1',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1'
                ),
                0,
                '`new`.`id` = 1',
                '%60new%60.%60id%60+%3D+1',
                array(
                    '`new`.`id`' => '= 1',
                ),
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.'
                . '%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+'
                . 'FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;'
                . 'token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.'
                . '%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+'
                . 'FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;'
                . 'token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" '
                . 'alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" '
                . 'alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td class="center"  >' . "\n"
                . '<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+'
                . 'FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;'
                . 'message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb'
                . '%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%25'
                . '60new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted'
                . '%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c'
                . '1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" class="delete'
                . '_row requireConfirm"><span class="nowrap"><img src="themes/dot.gif" title='
                . '"Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span></a>'
                . "\n" . '<div class="hide">DELETE FROM `data`.`new` WHERE `new`.'
                . '`id` = 1</div></td><td class="center"  ><span class="nowrap">'
                . "\n" . '<a href="tbl_change.php?db=data&amp;table=new&amp;where_'
                . 'clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_'
                . 'query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_'
                . 'action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span '
                . 'class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" '
                . 'class="icon ic_b_insrow" /> Copy</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value="%60new%60.%60id'
                . '%60+%3D+1" /></span></td><td class="edit_row_anchor center"  >'
                . '<span class="nowrap">' . "\n"
                . '<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause'
                . '=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query='
                . 'SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action='
                . 'update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class='
                . '"nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class'
                . '="icon ic_b_edit" /> Edit</span></a>' . "\n"
                . '<input type="hidden" class="where_clause" value ="%60new%60.%60'
                . 'id%60+%3D+1" /></span></td><td  class="center"><input type='
                . '"checkbox" id="id_rows_to_delete0_right" name="rows_to_delete'
                . '[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60'
                . '+%3D+1"  /><input type="hidden" class="condition_array" value="'
                . '{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
            ),
            array(
                PMA_DisplayResults::POSITION_NONE,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.'
                . '%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+'
                . 'row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew'
                . '%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_'
                . 'to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure'
                . '.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                array(
                    'edit_lnk' => 'ur',
                    'del_lnk' => 'dr',
                    'sort_lnk' => '0',
                    'nav_bar' => '1',
                    'ins_row' => '1',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1'
                ),
                0,
                '`new`.`id` = 1',
                '%60new%60.%60id%60+%3D+1',
                array(
                    '`new`.`id`' => '= 1',
                ),
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60'
                . 'id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+'
                . '%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60'
                . 'id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+'
                . '%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" '
                . 'alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" '
                . 'alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td  class="center"><input type="checkbox" id="id_rows_to_'
                . 'delete0_left" name="rows_to_delete[0]" class="multi_checkbox '
                . 'checkall" value="%60new%60.%60id%60+%3D+1"  /><input type='
                . '"hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:'
                . '&quot;= 1&quot;}" />    </td>'
            ),
        );
    }

    /**
     * Test for _getCheckboxAndLinks
     *
     * @param string $position          the position of the checkbox and links
     * @param string $del_url           delete url
     * @param array  $displayParts      array with explicit indexes for all the
     *                                  display elements
     * @param string $row_no            row number
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $edit_url          edit url
     * @param string $copy_url          copy url
     * @param string $class             css classes for the td elements
     * @param string $edit_str          text for the edit link
     * @param string $copy_str          text for the copy link
     * @param string $del_str           text for the delete link
     * @param string $js_conf           text for the JS confirmation
     * @param string $output            output of _getCheckboxAndLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCheckboxAndLinks
     */
    public function testGetCheckboxAndLinks(
        $position, $del_url, $displayParts, $row_no, $where_clause,
        $where_clause_html, $condition_array, $edit_url,
        $copy_url, $class, $edit_str, $copy_str, $del_str, $js_conf, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckboxAndLinks',
                array(
                    $position, $del_url, $displayParts, $row_no, $where_clause,
                    $where_clause_html, $condition_array,
                    $edit_url, $copy_url, $class, $edit_str,
                    $copy_str, $del_str, $js_conf
                )
            )
        );
    }

    /**
     * Data provider for testGetPlacedLinks
     *
     * @return array parameters and output
     */
    public function dataProviderForGetPlacedLinks()
    {
        return array(
            array(
                PMA_DisplayResults::POSITION_NONE,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.'
                . '%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+'
                . 'row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew'
                . '%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_'
                . 'to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure'
                . '.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                array(
                    'edit_lnk' => 'ur',
                    'del_lnk' => 'dr',
                    'sort_lnk' => '0',
                    'nav_bar' => '1',
                    'ins_row' => '1',
                    'bkm_form' => '1',
                    'text_btn' => '1',
                    'pview_lnk' => '1'
                ),
                0,
                '`new`.`id` = 1',
                '%60new%60.%60id%60+%3D+1',
                array(
                    '`new`.`id`' => '= 1',
                ),
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60'
                . 'id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+'
                . '%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60'
                . 'id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+'
                . '%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token='
                . 'ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" '
                . 'alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" '
                . 'alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" '
                . 'alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                null,
                '<td  class="center"><input type="checkbox" id="id_rows_to_'
                . 'delete0_left" name="rows_to_delete[0]" class="multi_checkbox '
                . 'checkall" value="%60new%60.%60id%60+%3D+1"  /><input type='
                . '"hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:'
                . '&quot;= 1&quot;}" />    </td>'
            )
        );
    }

    /**
     * Test for _getPlacedLinks
     *
     * @param string  $dir               the direction of links should place
     * @param string  $del_url           the url for delete row
     * @param array   $displayParts      which elements to display
     * @param integer $row_no            the index of current row
     * @param string  $where_clause      the where clause of the sql
     * @param string  $where_clause_html the html encoded where clause
     * @param array   $condition_array   array of keys (primary, unique, condition)
     * @param string  $edit_url          the url for edit row
     * @param string  $copy_url          the url for copy row
     * @param string  $edit_anchor_class the class for html element for edit
     * @param string  $edit_str          the label for edit row
     * @param string  $copy_str          the label for copy row
     * @param string  $del_str           the label for delete row
     * @param string  $js_conf           text for the JS confirmation
     * @param string  $output            output of _getPlacedLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForGetPlacedLinks
     */
    public function testGetPlacedLinks(
        $dir, $del_url, $displayParts, $row_no, $where_clause, $where_clause_html,
        $condition_array, $edit_url, $copy_url,
        $edit_anchor_class, $edit_str, $copy_str, $del_str, $js_conf, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getPlacedLinks',
                array(
                    $dir, $del_url, $displayParts, $row_no, $where_clause,
                    $where_clause_html, $condition_array,
                    $edit_url, $copy_url, $edit_anchor_class,
                    $edit_str, $copy_str, $del_str, $js_conf
                )
            )
        );
    }


    /**
     * Data provider for testGetSpecialLinkUrl
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetSpecialLinkUrl()
    {
        return array(
            array(
                'information_schema',
                'routines',
                'circumference',
                array(
                    'routine_name' => 'circumference',
                    'routine_schema' => 'data',
                    'routine_type' => 'FUNCTION'
                ),
                'routine_name',
                'db_routines.php?item_name=circumference&amp;db=data'
                . '&amp;item_type=FUNCTION&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8'
                . '&amp;token=token'
            ),
            array(
                'information_schema',
                'routines',
                'area',
                array(
                    'routine_name' => 'area',
                    'routine_schema' => 'data',
                    'routine_type' => 'PROCEDURE'
                ),
                'routine_name',
                'db_routines.php?item_name=area&amp;db=data'
                . '&amp;item_type=PROCEDURE&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8'
                . '&amp;token=token'
            ),
            array(
                'information_schema',
                'columns',
                'CHARACTER_SET_NAME',
                array(
                    'table_schema' => 'information_schema',
                    'table_name' => 'CHARACTER_SETS'
                ),
                'column_name',
                'index.php?sql_query=SELECT+%60CHARACTER_SET_NAME%60+FROM+%60info'
                . 'rmation_schema%60.%60CHARACTER_SETS%60&amp;db=information_schema'
                . '&amp;test_name=value&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8'
                . '&amp;token=token'
            )
        );
    }


    /**
     * Test _getSpecialLinkUrl
     *
     * @param string  $db           the database name
     * @param string  $table        the table name
     * @param string  $column_value column value
     * @param array   $row_info     information about row
     * @param string  $field_name   column name
     * @param boolean $output       output of _getSpecialLinkUrl
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetSpecialLinkUrl
     */
    public function testGetSpecialLinkUrl(
        $db, $table, $column_value, $row_info, $field_name,  $output
    ) {
        $GLOBALS['special_schema_links'] = array(
            'information_schema' => array(
                'routines' => array(
                    'routine_name' => array(
                        'link_param' => 'item_name',
                        'link_dependancy_params' => array(
                            0 => array(
                                'param_info' => 'db',
                                'column_name' => 'routine_schema'
                            ),
                            1 => array(
                                'param_info' => 'item_type',
                                'column_name' => 'routine_type'
                            )
                        ),
                        'default_page' => 'db_routines.php'
                    )
                ),
                'columns' => array(
                    'column_name' => array(
                        'link_param' => array(
                            'sql_query',
                            'table_schema',
                            'table_name'
                        ),
                        'link_dependancy_params' => array(
                            0 => array(
                                'param_info' => 'db',
                                'column_name' => 'table_schema'
                            ),
                            1 => array(
                                'param_info' => array('test_name', 'value')
                            )
                        ),
                        'default_page' => 'index.php'
                    )
                )
            )
        );

        $this->object->__set('db', $db);
        $this->object->__set('table', $table);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getSpecialLinkUrl',
                array($column_value, $row_info, $field_name)
            )
        );
    }


    /**
     * Data provider for testGetRowInfoForSpecialLinks
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetRowInfoForSpecialLinks()
    {
        $column_names = array('host', 'db', 'user', 'select_privilages');
        $fields_mata = array();

        foreach ($column_names as $column_name) {
            $field_meta = new stdClass();
            $field_meta->name = $column_name;
            $fields_mata[] = $field_meta;
        }

        return array(
            array(
                $fields_mata,
                count($fields_mata),
                array(
                    0 => 'localhost',
                    1 => 'phpmyadmin',
                    2 => 'pmauser',
                    3 => 'Y'
                ),
                array(
                    0 => '0',
                    1 => '3',
                    2 => '1',
                    3 => '2'
                ),
                array(
                    'host' => 'localhost',
                    'select_privilages' => 'Y',
                    'db' => 'phpmyadmin',
                    'user' => 'pmauser'
                )
            )
        );
    }


    /**
     * Test _getRowInfoForSpecialLinks
     *
     * @param array   $fields_meta  meta information about fields
     * @param integer $fields_count number of fields
     * @param array   $row          current row data
     * @param array   $col_order    the column order
     * @param boolean $output       output of _getRowInfoForSpecialLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetRowInfoForSpecialLinks
     */
    public function testGetRowInfoForSpecialLinks(
        $fields_meta, $fields_count, $row, $col_order,  $output
    ) {
        $this->object->__set('fields_meta', $fields_meta);
        $this->object->__set('fields_cnt', $fields_count);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getRowInfoForSpecialLinks',
                array($row, $col_order)
            )
        );
    }


    /**
     * Data provider for testGetShowAllButtonForTableNavigation
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetShowAllCheckboxForTableNavigation()
    {
        return array(
            array(
                'mysql',
                'user',
                'tbl_structure.php',
                0,
                'SELECT * FROM `user`',
                "\n"
                . '<td><form action="sql.php" method="post">'
                . '<input type="hidden" name="db" value="mysql" />'
                . '<input type="hidden" name="table" value="user" />'
                . '<input type="hidden" name="lang" value="en" />'
                . '<input type="hidden" name="collation_connection" value="utf-8" />'
                . '<input type="hidden" name="token" value="token" />'
                . '<input type="hidden" name="sql_query" value="SELECT * FROM `user`" />'
                . '<input type="hidden" name="pos" value="0" />'
                . '<input type="hidden" name="is_browse_distinct" value="" />'
                . '<input type="hidden" name="session_max_rows" value="all" />'
                . '<input type="hidden" name="goto" value="tbl_structure.php" />'
                . '<input type="checkbox" name="navig" id="showAll_0"'
                . ' class="showAllRows" value="all" />'
                . '<label for="showAll_0">Show all</label></form></td>'
            )
        );
    }


    /**
     * Test _getShowAllButtonForTableNavigation
     *
     * @param string $db             the database name
     * @param string $table          the table name
     * @param string $goto           the URL to go back in case of errors
     * @param int    $unique_id      the unique id for the results set
     * @param string $html_sql_query the sql encoded by html special characters
     * @param string $output         output of _getRowInfoForSpecialLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetShowAllCheckboxForTableNavigation
     */
    public function testGetShowAllCheckboxForTableNavigation(
        $db, $table, $goto, $unique_id , $html_sql_query, $output
    ) {
        $this->object->__set('db', $db);
        $this->object->__set('table', $table);
        $this->object->__set('goto', $goto);
        $this->object->__set('unique_id', $unique_id);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getShowAllCheckboxForTableNavigation',
                array(false, $html_sql_query)
            )
        );
    }


    /**
     * Data provider for testSetHighlightedColumnGlobalField
     *
     * @return array parameters and output
     */
    public function dataProviderForTestSetHighlightedColumnGlobalField()
    {
        return array(
            array(
                array(),
                array()
            ),
            array(
                array(
                    0 => array(
                        'where_clause_identifiers' => array(
                            0 => '`id`',
                            1 => '`id`',
                            2 => '`db_name`'
                        )
                    )
                ),
                array(
                    '`id`' => 'true',
                    '`db_name`' => 'true'
                )
            )
        );
    }


    /**
     * Test _setHighlightedColumnGlobalField
     *
     * @param array $analyzed_sql the analyzed query
     * @param array $output       setting value of _setHighlightedColumnGlobalField
     *
     * @return void
     *
     * @dataProvider dataProviderForTestSetHighlightedColumnGlobalField
     */
    public function testSetHighlightedColumnGlobalField($analyzed_sql, $output)
    {
        $this->_callPrivateFunction(
            '_setHighlightedColumnGlobalField',
            array($analyzed_sql)
        );

        $this->assertEquals(
            $output,
            $this->object->__get('highlight_columns')
        );
    }


    /**
     * Data provider for testGetPartialText
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetPartialText()
    {
        return array(
            array('P', 10, 'foo', false),
            array('P', 1, 'foo', true),
            array('F', 10, 'foo', false),
            array('F', 1, 'foo', false)
        );
    }


    /**
     * Test _getPartialText
     *
     * @param string  $pftext     Partial or Full text
     * @param integer $limitChars Partial or Full text
     * @param string  $str        the string to be tested
     * @param boolean $output     return value of _getPartialText
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetPartialText
     */
    public function testGetPartialText($pftext, $limitChars, $str, $output)
    {
        $_SESSION['tmpval']['pftext'] = $pftext;
        $GLOBALS['cfg']['LimitChars'] = $limitChars;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getPartialText',
                array(&$str)
            )
        );
    }


    /**
     * Data provider for testHandleNonPrintableContents
     *
     * @return array parameters and output
     */
    public function dataProviderForTestHandleNonPrintableContents()
    {
        $transformation_plugin = new Text_Plain_Link();
        $meta = new StdClass();
        $meta->type = 'BLOB';
        $meta->orgtable = 'bar';
        $url_params = array('db' => 'foo', 'table' => 'bar');

        return array(
            array(
                true,
                true,
                'BLOB',
                '1001',
                'PMA_mimeDefaultFunction',
                '',
                'PMA_mimeDefaultFunction',
                $meta,
                $url_params,
                null,
                '<a href="tbl_get_field.php?db=foo&amp;table=bar&amp;server=0'
                . '&amp;lang=en&amp;collation_connection=utf-8'
                . '&amp;token=token" class="disableAjax">31303031</a>'
            ),
            array(
                true,
                false,
                'BLOB',
                '1001',
                'PMA_mimeDefaultFunction',
                '',
                'PMA_mimeDefaultFunction',
                $meta,
                $url_params,
                null,
                '<a href="tbl_get_field.php?db=foo&amp;table=bar&amp;server=0'
                . '&amp;lang=en&amp;collation_connection=utf-8'
                . '&amp;token=token" class="disableAjax">[BLOB - 4 B]</a>'
            ),
            array(
                false,
                false,
                'BINARY',
                '1001',
                $transformation_plugin,
                '',
                'PMA_mimeDefaultFunction',
                $meta,
                $url_params,
                null,
                '1001'
            ),
            array(
                false,
                true,
                'GEOMETRY',
                null,
                '',
                '',
                'PMA_mimeDefaultFunction',
                $meta,
                $url_params,
                null,
                '[GEOMETRY - NULL]'
            )
        );
    }


    /**
     * Test _handleNonPrintableContents
     *
     * @param boolean $display_binary        show binary contents?
     * @param boolean $display_blob          show blob contents?
     * @param string  $category              BLOB|BINARY|GEOMETRY
     * @param string  $content               the binary content
     * @param string  $transformation_plugin transformation plugin.
     *                                       Can also be the default function:
     *                                       PMA_mimeDefaultFunction
     * @param string  $transform_options     transformation parameters
     * @param string  $default_function      default transformation function
     * @param object  $meta                  the meta-information about the field
     * @param array   $url_params            parameters that should go to the
     *                                       download link
     * @param boolean $is_truncated          the result is truncated or not
     * @param string  $output                the output of this function
     *
     * @return void
     *
     * @dataProvider dataProviderForTestHandleNonPrintableContents
     */
    public function testHandleNonPrintableContents(
        $display_binary, $display_blob, $category, $content,
        $transformation_plugin, $transform_options, $default_function,
        $meta, $url_params, $is_truncated, $output
    ) {
        $_SESSION['tmpval']['display_binary'] = $display_binary;
        $_SESSION['tmpval']['display_blob'] = $display_blob;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_handleNonPrintableContents',
                array(
                    $category, $content, $transformation_plugin,
                    $transform_options, $default_function,
                    $meta, $url_params, &$is_truncated
                )
            )
        );
    }


    /**
     * Data provider for testGetDataCellForNonNumericColumns
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetDataCellForNonNumericColumns()
    {
        $transformation_plugin = new Text_Plain_Link();
        $meta = new StdClass();
        $meta->db = 'foo';
        $meta->table = 'tbl';
        $meta->orgtable = 'tbl';
        $meta->type = 'BLOB';
        $meta->flags = 'blob binary';
        $meta->name = 'tblob';
        $meta->orgname = 'tblob';

        $meta2 = new StdClass();
        $meta2->db = 'foo';
        $meta2->table = 'tbl';
        $meta2->orgtable = 'tbl';
        $meta2->type = 'string';
        $meta2->flags = '';
        $meta2->decimals = 0;
        $meta2->name = 'varchar';
        $meta2->orgname = 'varchar';
        $url_params = array('db' => 'foo', 'table' => 'tbl');

        return array(
            array(
                'all',
                '1001',
                'grid_edit',
                $meta,
                array(),
                $url_params,
                false,
                'PMA_mimeDefaultFunction',
                'PMA_mimeDefaultFunction',
                array('http://www.github.com/'),
                false,
                array(),
                0,
                'binary',
                '<td class="left   hex"><a href="tbl_get_field.php?'
                . 'db=foo&amp;table=tbl&amp;server=0&amp;lang=en'
                . '&amp;collation_connection=utf-8'
                . '&amp;token=token" '
                . 'class="disableAjax">[BLOB - 4 B]</a></td>'
            ),
            array(
                'noblob',
                '1001',
                'grid_edit',
                $meta,
                array(),
                $url_params,
                false,
                $transformation_plugin,
                'PMA_mimeDefaultFunction',
                '',
                false,
                array(),
                0,
                'binary',
                '<td class="left grid_edit  transformed hex">'
                . '1001</td>'
            ),
            array(
                'noblob',
                null,
                'grid_edit',
                $meta2,
                array(),
                $url_params,
                false,
                $transformation_plugin,
                'PMA_mimeDefaultFunction',
                '',
                false,
                array(),
                0,
                0,
                '<td  data-decimals="0" data-type="string"  '
                . 'class="grid_edit  null"><i>NULL</i></td>'
            ),
            array(
                'all',
                'foo bar baz',
                'grid_edit',
                $meta2,
                array(),
                $url_params,
                false,
                'PMA_mimeDefaultFunction',
                'PMA_mimeDefaultFunction',
                '',
                false,
                array(),
                0,
                0,
                '<td data-decimals="0" data-type="string" '
                . 'class="grid_edit ">foo bar baz</td>' . "\n"
            )
        );
    }


    /**
     * Test _getDataCellForNonNumericColumns
     *
     * @param boolean $protectBinary         all|blob|noblob|no
     * @param string  $column                the relevant column in data row
     * @param string  $class                 the html class for column
     * @param object  $meta                  the meta-information about the field
     * @param array   $map                   the list of relations
     * @param array   $_url_params           the parameters for generate url
     * @param boolean $condition_field       the column should highlighted
     *                                       or not
     * @param string  $transformation_plugin the name of transformation function
     * @param string  $default_function      the default transformation function
     * @param string  $transform_options     the transformation parameters
     * @param boolean $is_field_truncated    is data truncated due to LimitChars
     * @param array   $analyzed_sql          the analyzed query
     * @param integer $dt_result             the link id associated to the query
     *                                       which results have to be displayed
     * @param integer $col_index             the column index
     * @param string  $output                the output of this function
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetDataCellForNonNumericColumns
     */
    public function testGetDataCellForNonNumericColumns(
        $protectBinary, $column, $class, $meta, $map,
        $_url_params, $condition_field, $transformation_plugin,
        $default_function, $transform_options, $is_field_truncated,
        $analyzed_sql, $dt_result, $col_index, $output
    ) {
        $_SESSION['tmpval']['display_binary'] = true;
        $_SESSION['tmpval']['display_blob'] = false;
        $_SESSION['tmpval']['relational_display'] = false;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ProtectBinary'] = $protectBinary;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getDataCellForNonNumericColumns',
                array(
                    $column, $class, $meta, $map, $_url_params, $condition_field,
                    $transformation_plugin, $default_function, $transform_options,
                    $is_field_truncated, $analyzed_sql, &$dt_result, $col_index
                )
            )
        );
    }
}
