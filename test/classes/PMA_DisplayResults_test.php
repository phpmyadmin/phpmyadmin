<?php
/**
 * Tests for displaing results
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
        $this->object = new PMA_DisplayResults('as', '', '', '');
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 0;
        $GLOBALS['text_dir'] = 'ltr';
        include_once 'libraries/Response.class.php';
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
        if (! method_exists($method, 'setAccessible')) {
            $this->markTestSkipped('ReflectionClass::setAccessible not available');
        }
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for setting display mode
     *
     * @param string  $the_disp_mode the synthetic value for display_mode (see
     *                               a few lines above for explanations)
     * @param integer $the_total     the total number of rows returned by the SQL
     *                               query without any programmatically appended
     *                               LIMIT clause
     *                               (just a copy of $unlim_num_rows if it exists,
     *                               elsecomputed inside this function)
     * @param string  $output        output from the _setDisplayMode method
     *
     * @return void
     *
     * @dataProvider providerForTestSetDisplayModeCase1
     */
    public function testSetDisplayModeCase1($the_disp_mode, $the_total, $output)
    {
        if (!isset($GLOBALS['fields_meta'])) {
            $fields_meta = array();
            $fields_meta[0] = new stdClass();
            $fields_meta[0]->table = 'company';
        } else {
            $fields_meta = $GLOBALS['fields_meta'];
        }

        $this->object->setProperties(
            null, $fields_meta, true, null, null,
            null, null, null, null, null, null,
            true, null, null, null, null, null, false
        );

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            )
        );
    }

    /**
     * Provider for testSetDisplayModeCase1
     *
     * @return array data for testSetDisplayModeCase1
     */
    public function providerForTestSetDisplayModeCase1()
    {
        return array(
            array(
                'urkp111111',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 1,
                    'text_btn' => 1,
                    'pview_lnk' => 1
                )
            ),
            array(
                'nnnn000000',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 0,
                    'text_btn' => 0,
                    'pview_lnk' => 0
                )
            )
        );
    }

    /**
     * Test for setting display mode
     *
     * @param string  $the_disp_mode the synthetic value for display_mode (see a
     *                               few lines above for explanations)
     * @param integer $the_total     the total number of rows returned by the SQL
     *                               query without any programmatically appended
     *                               LIMIT clause
     *                               (just a copy of $unlim_num_rows if it exists,
     *                               elsecomputed inside this function)
     * @param string  $output        output from the _setDisplayMode method
     *
     * @return void
     *
     * @dataProvider providerForTestSetDisplayModeCase2
     */
    public function testSetDisplayModeCase2($the_disp_mode, $the_total, $output)
    {
        if (!isset($GLOBALS['fields_meta'])) {
            $fields_meta = array();
            $fields_meta[0] = new stdClass();
            $fields_meta[0]->table = 'company';
        } else {
            $fields_meta = $GLOBALS['fields_meta'];
        }

        $this->object->setProperties(
            1, $fields_meta, false, null, null,
            false, null, null, null, null, null,
            false, false, true, null, null, null, false
        );

        $this->object->__set('sql_query', 'SELECT * FROM `pma_bookmark` WHERE 1');

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            )
        );
    }

    /**
     * Provider for testSetDisplayModeCase2
     *
     * @return array data for testSetDisplayModeCase2
     */
    public function providerForTestSetDisplayModeCase2()
    {
        return array(
            array(
                'urkp111111',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 1,
                    'text_btn' => 1,
                    'pview_lnk' => 1
                )
            ),
            array(
                'nnnn000000',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 0,
                    'text_btn' => 0,
                    'pview_lnk' => 0
                )
            )
        );
    }

    /**
     * Test for setting display mode
     *
     * @param string  $the_disp_mode the synthetic value for display_mode (see a
     *                               few lines above for explanations)
     * @param integer $the_total     the total number of rows returned by the SQL
     *                               query without any programmatically appended
     *                               LIMIT clause
     *                               (just a copy of $unlim_num_rows if it exists,
     *                               elsecomputed inside this function)
     * @param string  $output        output from the _setDisplayMode method
     *
     * @return void
     *
     * @dataProvider providerForTestSetDisplayModeCase3
     */
    public function testSetDisplayModeCase3($the_disp_mode, $the_total, $output)
    {
        if (!isset($GLOBALS['fields_meta'])) {
            $fields_meta = array();
            $fields_meta[0] = new stdClass();
            $fields_meta[0]->table = 'company';
        } else {
            $fields_meta = $GLOBALS['fields_meta'];
        }

        $this->object->setProperties(
            1, $fields_meta, false, null, null,
            false, null, null, null, null, null,
            false, false, null, null, '1', null, false
        );

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            )
        );
    }

    /**
     * Provider for testSetDisplayModeCase3
     *
     * @return array data for testSetDisplayModeCase3
     */
    public function providerForTestSetDisplayModeCase3()
    {
        return array(
            array(
                'urkp111111',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 0,
                    'text_btn' => 0,
                    'pview_lnk' => 0
                )
            ),
            array(
                'nnnn000000',
                5,
                array(
                    'edit_lnk' => 'nn',
                    'del_lnk' => 'nn',
                    'sort_lnk' => 0,
                    'nav_bar' => 0,
                    'ins_row' => 0,
                    'bkm_form' => 0,
                    'text_btn' => 0,
                    'pview_lnk' => 0
                )
            )
        );
    }

    /**
     * Test for _isSelect function
     *
     * @return void
     */
    public function testisSelect()
    {
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
                array(&$caption, $title, $pos, $html_sql_query)
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
                . '<input type="hidden" name="token" value="token" />'
                . '<input type="hidden" name="sql_query" value="SELECT * '
                . 'FROM `pma_bookmark` WHERE 1" />'
                . '<input type="hidden" name="pos" value="1" />'
                . '<input type="hidden" name="goto" value="" />'
                . '<input type="submit" name="navig" class="ajax" '
                . 'value="btn"  title="Submit" /></form></td>'
            )
        );
    }

    /**
     * Test for table navigation
     *
     * @param integer $pos_next                  the offset for the "next" page
     * @param integer $pos_prev                  the offset for the "previous" page
     * @param string  $id_for_direction_dropdown the id for the direction dropdown
     * @param boolean $is_innodb                 the table type is innoDb or not
     * @param string  $output                    output from the _getTableNavigation
     *                                           method
     *
     * @return void
     *
     * @dataProvider providerForTestGetTableNavigation
     */
    public function testGetTableNavigation(
        $pos_next, $pos_prev, $id_for_direction_dropdown, $is_innodb, $output
    ) {
        $_SESSION['tmp_user_values']['max_rows'] = '20';
        $_SESSION['tmp_user_values']['pos'] = true;
        $GLOBALS['num_rows'] = '20';
        $GLOBALS['unlim_num_rows'] = '50';
        $GLOBALS['cfg']['ShowAll'] = true;
        $GLOBALS['cfg']['ShowDisplayDirection'] = true;
        $_SESSION['tmp_user_values']['repeat_cells'] = '1';
        $_SESSION['tmp_user_values']['disp_direction'] = '1';

        $this->assertEquals(
            $output,
            str_word_count(
                $this->_callPrivateFunction(
                    '_getTableNavigation',
                    array(
                        $pos_next, $pos_prev, $id_for_direction_dropdown, $is_innodb
                    )
                )
            )
        );
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
                '123',
                false,
                '330'
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
                0,
                'data grid_edit not_null    row_0 vpointer vmarker'
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
     * @param integer $row_no           the row index
     * @param string  $output           output of__getResettedClassForInlineEdit
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetClassesForColumn
     */
    public function testGetClassesForColumn(
        $grid_edit_class, $not_null_class, $relation_class,
        $hide_class, $field_type_class, $row_no, $output
    ) {
        $GLOBALS['cfg']['BrowsePointerEnable'] = true;
        $GLOBALS['cfg']['BrowseMarkerEnable'] = true;
        $_SESSION['tmp_user_values']['disp_direction']
            = PMA_DisplayResults::DISP_DIR_VERTICAL;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getClassesForColumn',
                array(
                    $grid_edit_class, $not_null_class, $relation_class,
                    $hide_class, $field_type_class, $row_no
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
            '',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::STRING_FIELD)
            )
        );
    }

    /**
     * Provide data for testGetOperationLinksForVerticleTable
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetOperationLinksForVerticleTable()
    {
        return array(
            array(
                'edit',
                '<tr>
</tr>
'
            ),
            array(
                'copy',
                "<tr>\nCOPY1COPY2</tr>\n"
            ),
            array(
                'delete',
                "<tr>\nDELETE1DELETE2</tr>\n"
            ),
        );
    }

    /**
     * Test for _getOperationLinksForVerticleTable
     *
     * @param string $operation edit/copy/delete
     * @param string $output    output of _getOperationLinksForVerticleTable
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetOperationLinksForVerticleTable
     */
    public function testGetOperationLinksForVerticleTable(
        $operation, $output
    ) {
        $vertical_display = array(
            'row_delete' => array(),
            'textbtn' => '<th  rowspan="4" class="vmiddle">\n        \n    </th>\n',
            'edit' => array(),
            'copy' => array('COPY1', 'COPY2'),
            'delete' => array('DELETE1', 'DELETE2'),
        );

        $this->object->__set('vertical_display', $vertical_display);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getOperationLinksForVerticleTable',
                array($operation)
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
                array('edit_lnk' => null, 'del_lnk' => null),//array('edit_lnk' => 'nn', 'del_lnk' => 'nn'),
                '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" class="multi_checkbox" value="%60cars%60.%60id%60+%3D+3"  /><input type="hidden" class="condition_array" value="{&quot;`cars`.`id`&quot;:&quot;= 3&quot;}" />    </td><td class="even row_1 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete1_left" name="rows_to_delete[1]" class="multi_checkbox" value="%60cars%60.%60id%60+%3D+9"  /><input type="hidden" class="condition_array" value="{&quot;`cars`.`id`&quot;:&quot;= 9&quot;}" />    </td>'
            )
        );
    }

    /**
     * Test for _getCheckBoxesForMultipleRowOperations
     *
     * @param string $dir    _left / _right
     * @param array $is_display display mode
     * @param string $output output of _getCheckBoxesForMultipleRowOperations
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCheckBoxesForMultipleRowOperations
     */
    public function testGetCheckBoxesForMultipleRowOperations(
        $dir, $is_display, $output
    ) {
        $vertical_display = array(
            'row_delete' => array(
                '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[0]" class="multi_checkbox" value="%60cars%60.%60id%60+%3D+3"  /><input type="hidden" class="condition_array" value="{&quot;`cars`.`id`&quot;:&quot;= 3&quot;}" />    </td>',
                '<td class="even row_1 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete1[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[1]" class="multi_checkbox" value="%60cars%60.%60id%60+%3D+9"  /><input type="hidden" class="condition_array" value="{&quot;`cars`.`id`&quot;:&quot;= 9&quot;}" />    </td>'
            )
        );

        $this->object->__set('vertical_display', $vertical_display);

        $_SESSION['tmp_user_values']['repeat_cells'] = 0;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckBoxesForMultipleRowOperations',
                array($dir, $is_display)
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
        $_SESSION['tmp_user_values']['max_rows'] = PMA_DisplayResults::ALL_ROWS;
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
        $_SESSION['tmp_user_values']['max_rows'] = 5;
        $_SESSION['tmp_user_values']['pos'] = 4;
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
            array('', array('', '', '')),
            array(
                '`a_sales`.`customer_id` ASC',
                array(
                    '`a_sales`.`customer_id` ASC',
                    '`a_sales`.`customer_id`',
                    'ASC'
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
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;token=d1aecb47ef7c081e068e7008b38a5d76',
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
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '[%_PMA_CHECKBOX_DIR_%]',
                'odd row_0 vpointer vmarker',
                '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
            )
        );
    }

    /**
     * Test for _getCheckboxForMultiRowSubmissions
     *
     * @param string $del_url           delete url
     * @param array  $is_display        array with explicit indexes for all
     *                                  the display elements
     * @param string $row_no            the row number
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $del_query         delete query
     * @param string $id_suffix         suffix for the id
     * @param string $class             css classes for the td element
     * @param string $output            output of _getCheckboxForMultiRowSubmissions
     *
     * @return void
     *
     * @dataProvider dataProviderForGetCheckboxForMultiRowSubmissions
     */
    public function testGetCheckboxForMultiRowSubmissions(
        $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
        $del_query, $id_suffix, $class, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckboxForMultiRowSubmissions',
                array(
                    $del_url, $is_display, $row_no, $where_clause_html,
                    $condition_array, $del_query, $id_suffix, $class
                )
            )
        );
    }

    /**
     * Data provider for testGetEditLink
     *
     * @return array parametres and output
     */
    public function dataProviderForGetEditLink()
    {
        return array(
            array(
                'tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_action=update&amp;token=bbd5003198a3bd856b21d9607d6c6a1e',
                'odd edit_row_anchor row_0 vpointer vmarker',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '`customer`.`id` = 1',
                '%60customer%60.%60id%60+%3D+1',
                '<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">
<a href="tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_action=update&amp;token=bbd5003198a3bd856b21d9607d6c6a1e" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>
<input type="hidden" class="where_clause" value ="%60customer%60.%60id%60+%3D+1" /></span></td>'
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
                'tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_action=insert&amp;token=f597309d3a066c3c81a6cb015a79636d',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '`customer`.`id` = 1',
                '%60customer%60.%60id%60+%3D+1',
                'odd row_0 vpointer vmarker',
                '<td class="odd row_0 vpointer vmarker center"  ><span class="nowrap">
<a href="tbl_change.php?db=Data&amp;table=customer&amp;where_clause=%60customer%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60customer%60&amp;goto=sql.php&amp;default_action=insert&amp;token=f597309d3a066c3c81a6cb015a79636d" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>
<input type="hidden" class="where_clause" value="%60customer%60.%60id%60+%3D+1" /></span></td>'
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
                'sql.php?db=Data&amp;table=customer&amp;sql_query=DELETE+FROM+%60Data%60.%60customer%60+WHERE+%60customer%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3DData%26table%3Dcustomer%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560customer%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Df597309d3a066c3c81a6cb015a79636d&amp;token=f597309d3a066c3c81a6cb015a79636d',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `Data`.`customer` WHERE `customer`.`id` = 1',
                'odd row_0 vpointer vmarker',
                '<td class="odd row_0 vpointer vmarker center"  >
<a href="sql.php?db=Data&amp;table=customer&amp;sql_query=DELETE+FROM+%60Data%60.%60customer%60+WHERE+%60customer%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3DData%26table%3Dcustomer%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560customer%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Df597309d3a066c3c81a6cb015a79636d&amp;token=f597309d3a066c3c81a6cb015a79636d" class="delete_row"><span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span></a>
<div class="hide">DELETE FROM `Data`.`customer` WHERE `customer`.`id` = 1</div></td>'
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
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
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
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                'l',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td  class="center"><input type="checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td><td class="edit_row_anchor center"  ><span class="nowrap">
<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>
<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D+1" /></span></td><td class="center"  ><span class="nowrap">
<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>
<input type="hidden" class="where_clause" value="%60new%60.%60id%60+%3D+1" /></span></td><td class="center"  >
<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" class="delete_row"><span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span></a>
<div class="hide">DELETE FROM `data`.`new` WHERE `new`.`id` = 1</div></td>'
            ),
            array(
                PMA_DisplayResults::POSITION_RIGHT,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
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
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                'l',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td class="center"  >
<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" class="delete_row"><span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span></a>
<div class="hide">DELETE FROM `data`.`new` WHERE `new`.`id` = 1</div></td><td class="center"  ><span class="nowrap">
<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>
<input type="hidden" class="where_clause" value="%60new%60.%60id%60+%3D+1" /></span></td><td class="edit_row_anchor center"  ><span class="nowrap">
<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>
<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D+1" /></span></td><td  class="center"><input type="checkbox" id="id_rows_to_delete0_right" name="rows_to_delete[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
            ),
            array(
                PMA_DisplayResults::POSITION_NONE,
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
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
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                'l',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                '<td  class="center"><input type="checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
            ),
        );
    }

    /**
     * Test for _getCheckboxAndLinks
     *
     * @param string $position          the position of the checkbox and links
     * @param string $del_url           delete url
     * @param array  $is_display        array with explicit indexes for all the
     *                                  display elements
     * @param string $row_no            row number
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $del_query         delete query
     * @param string $id_suffix         suffix for the id
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
        $position, $del_url, $is_display, $row_no, $where_clause,
        $where_clause_html, $condition_array, $del_query, $id_suffix, $edit_url,
        $copy_url, $class, $edit_str, $copy_str, $del_str, $js_conf, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getCheckboxAndLinks',
                array(
                    $position, $del_url, $is_display, $row_no, $where_clause,
                    $where_clause_html, $condition_array, $del_query,
                    $id_suffix, $edit_url, $copy_url, $class, $edit_str,
                    $copy_str, $del_str, $js_conf
                )
            )
        );
    }

    /**
     * Test for _mimeDefaultFunction
     *
     * @return void
     */
    public function testMimeDefaultFunction()
    {
        $this->assertEquals(
            "A 'quote' is &lt;b&gt;bold&lt;/b&gt;",
            $this->_callPrivateFunction(
                '_mimeDefaultFunction',
                array("A 'quote' is <b>bold</b>")
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
                'sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dae4c6d18375f446dfa068420c1f6a4e8&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
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
                'DELETE FROM `data`.`new` WHERE `new`.`id` = 1',
                'l',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=ae4c6d18375f446dfa068420c1f6a4e8',
                'edit_row_anchor',
                '<span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span>',
                '<span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span>',
                null,
                '<td  class="center"><input type="checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" class="multi_checkbox checkall" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
            )
        );
    }

    /**
     * Test for _getPlacedLinks
     *
     * @param string  $dir               the direction of links should place
     * @param string  $del_url           the url for delete row
     * @param array   $is_display        which elements to display
     * @param integer $row_no            the index of current row
     * @param string  $where_clause      the where clause of the sql
     * @param string  $where_clause_html the html encoded where clause
     * @param array   $condition_array   array of keys (primary, unique, condition)
     * @param string  $del_query         the query for delete row
     * @param string  $dir_letter        the letter denoted the direction
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
        $dir, $del_url, $is_display, $row_no, $where_clause, $where_clause_html,
        $condition_array, $del_query, $dir_letter, $edit_url, $copy_url,
        $edit_anchor_class, $edit_str, $copy_str, $del_str, $js_conf, $output
    ) {
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getPlacedLinks',
                array(
                    $dir, $del_url, $is_display, $row_no, $where_clause,
                    $where_clause_html, $condition_array, $del_query,
                    $dir_letter, $edit_url, $copy_url, $edit_anchor_class,
                    $edit_str, $copy_str, $del_str, $js_conf
                )
            )
        );
    }


    /**
     * Data provider for testIsNeedToSyntaxHighlight
     *
     * @return array parameters and output
     */
    public function dataProviderForTestIsNeedToSyntaxHighlight()
    {
        return array(
            array(
                'information_schema',
                'processlist',
                array(
                    'information_schema' => array(
                        'processlist' => array(
                            'info' => array(
                                'libraries/plugins/transformations/Text_Plain_Formatted.class.php',
                                'Text_Plain_Formatted',
                                'Text_Plain'
                            )
                        )
                    )
                ),
                'info',
                true
            ),
            array(
                'incorrect_database',
                'processlist',
                array(
                    'information_schema' => array(
                        'processlist' => array(
                            'info' => array(
                                'libraries/plugins/transformations/Text_Plain_Formatted.class.php',
                                'Text_Plain_Formatted',
                                'Text_Plain'
                            )
                        )
                    )
                ),
                'info',
                false
            )
        );
    }


    /**
     * Test _isNeedToSyntaxHighlight
     *
     * @param string  $db     the database name
     * @param string  $table  the table name
     * @param array   $data   predefined data of columns need to syntax highlighted
     * @param string  $field  the field name
     * @param boolean $output output of _isNeedToSyntaxHighlight
     *
     * @return void
     *
     * @dataProvider dataProviderForTestIsNeedToSyntaxHighlight
     */
    public function testIsNeedToSyntaxHighlight($db, $table, $data, $field,  $output)
    {
        $this->object->__set('db', $db);
        $this->object->__set('table', $table);
        $this->object->__set('syntax_highlighting_column_info', $data);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_isNeedToSyntaxHighlight',
                array($field)
            )
        );
    }


    /**
     * Data provider for testIsFieldNeedToLink
     *
     * @return array parameters and output
     */
    public function dataProviderForTestIsFieldNeedToLink()
    {
        return array(
            array(
                'mysql',
                'proc',
                'db',
                true
            ),
            array(
                'incorrect_database',
                'processlist',
                'info',
                false
            )
        );
    }


    /**
     * Test _isFieldNeedToLink
     *
     * @param string  $db     the database name
     * @param string  $table  the table name
     * @param string  $field  the field name
     * @param boolean $output output of _isFieldNeedToLink
     *
     * @return void
     *
     * @dataProvider dataProviderForTestIsFieldNeedToLink
     */
    public function testIsFieldNeedToLink($db, $table, $field,  $output)
    {
        $GLOBALS['special_schema_links'] = array(
            'mysql' => array(
                'proc' => array(
                    'db' => array(
                        'link_param' => 'db',
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
                '_isFieldNeedToLink',
                array($field)
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
                'db_routines.php?item_name=circumference&amp;db=data&amp;edit_item=1&amp;item_type=FUNCTION&amp;server=0&amp;lang=en&amp;token=token'
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
                'db_routines.php?item_name=area&amp;db=data&amp;edit_item=1&amp;item_type=PROCEDURE&amp;server=0&amp;lang=en&amp;token=token'
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
                'index.php?sql_query=SELECT+%60CHARACTER_SET_NAME%60+FROM+%60information_schema%60.%60CHARACTER_SETS%60&amp;db=information_schema&amp;test_name=value&amp;server=0&amp;lang=en&amp;token=token'
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
     * @param array   $fields_meta meta information about fields
     * @param integer $fiels_count number of fields
     * @param array   $row         current row data
     * @param array   $col_order   the column order
     * @param boolean $output      output of _getRowInfoForSpecialLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetRowInfoForSpecialLinks
     */
    public function testGetRowInfoForSpecialLinks(
        $fields_meta, $fiels_count, $row, $col_order,  $output
    ) {
        $this->object->__set('fields_meta', $fields_meta);
        $this->object->__set('fields_cnt', $fiels_count);

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
    public function dataProviderForTestGetShowAllButtonForTableNavigation()
    {
        return array(
            array(
                'mysql',
                'user',
                'tbl_structure.php',
                'SELECT * FROM `user`',
                '
<td><form action="sql.php" method="post"><input type="hidden" name="db" value="mysql" /><input type="hidden" name="table" value="user" /><input type="hidden" name="lang" value="en" /><input type="hidden" name="token" value="token" /><input type="hidden" name="sql_query" value="SELECT * FROM `user`" /><input type="hidden" name="pos" value="0" /><input type="hidden" name="session_max_rows" value="all" /><input type="hidden" name="goto" value="tbl_structure.php" /><input type="submit" name="navig" value="Show all" /></form></td>'
            )
        );
    }


    /**
     * Test _getShowAllButtonForTableNavigation
     *
     * @param string $db             the database name
     * @param string $table          the table name
     * @param string $goto           the URL to go back in case of errors
     * @param string $html_sql_query the sql encoded by html special characters
     * @param string $output         output of _getRowInfoForSpecialLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetShowAllButtonForTableNavigation
     */
    public function testGetShowAllButtonForTableNavigation(
        $db, $table, $goto, $html_sql_query, $output
    ) {
        $this->object->__set('db', $db);
        $this->object->__set('table', $table);
        $this->object->__set('goto', $goto);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getShowAllButtonForTableNavigation',
                array($html_sql_query)
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
}
