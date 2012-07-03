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
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/js_escape.lib.php';


class PMA_DisplayResults_test extends PHPUnit_Framework_TestCase
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
        $this->object = $this->getMockForAbstractClass('PMA_DisplayResults', array('as', '','',''));

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
     * Call private functions by making the visibitlity to public.
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
     * @param $the_disp_mode string  the synthetic value for display_mode (see a few lines above for explanations)
     * @param $the_total the total number of rows returned by the SQL
     *                                 query without any programmatically appended
     *                                 LIMIT clause
     *                                 (just a copy of $unlim_num_rows if it exists,
     *                                 elsecomputed inside this function)
     *
     * @param $output output from the _setDisplayMode method
     *
     * @dataProvider providerForTestSetDisplayModeCase1
     */
    public function testSetDisplayModeCase1($the_disp_mode, $the_total, $output){

        $GLOBALS['is_count'] = true;
        $GLOBALS['is_maint'] = true;

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            ),
            $output
         );
    }

    /**
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
     * @param $the_disp_mode string  the synthetic value for display_mode (see a few lines above for explanations)
     * @param $the_total the total number of rows returned by the SQL
     *                                 query without any programmatically appended
     *                                 LIMIT clause
     *                                 (just a copy of $unlim_num_rows if it exists,
     *                                 elsecomputed inside this function)
     *
     * @param $output output from the _setDisplayMode method
     *
     * @dataProvider providerForTestSetDisplayModeCase2
     */
    public function testSetDisplayModeCase2($the_disp_mode, $the_total, $output){


        $GLOBALS['is_count'] = false;
        $GLOBALS['is_maint'] = false;
        $GLOBALS['is_analyse'] = false;
        $GLOBALS['is_explain'] = false;
        $GLOBALS['is_show'] = true;
        $GLOBALS['sql_query'] = 'SELECT * FROM `pma_bookmark` WHERE 1';
        $GLOBALS['unlim_num_rows'] = 1;

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            ),
            $output
        );
    }

    /**
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
     * @param $the_disp_mode string  the synthetic value for display_mode (see a few lines above for explanations)
     * @param $the_total the total number of rows returned by the SQL
     *                                 query without any programmatically appended
     *                                 LIMIT clause
     *                                 (just a copy of $unlim_num_rows if it exists,
     *                                 elsecomputed inside this function)
     *
     * @param $output output from the _setDisplayMode method
     *
     * @dataProvider providerForTestSetDisplayModeCase3
     */
    public function testSetDisplayModeCase3($the_disp_mode, $the_total, $output){

        $GLOBALS['is_count'] = false;
        $GLOBALS['is_maint'] = false;
        $GLOBALS['is_analyse'] = false;
        $GLOBALS['is_explain'] = false;
        $GLOBALS['printview'] = '1';

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_setDisplayMode',
                array(&$the_disp_mode, &$the_total)
            ),
            $output
        );
    }

    /**
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
     */
    public function testisSelect(){

        $GLOBALS['is_count'] = false;
        $GLOBALS['is_export'] = false;
        $GLOBALS['is_func'] = false;
        $GLOBALS['is_analyse'] = false;
        $GLOBALS['analyzed_sql'][0]['select_expr'] = array();
        $GLOBALS['analyzed_sql'][0]['queryflags']['select_from'] = 'pma';
        $GLOBALS['analyzed_sql'][0]['table_ref'] = array('table_ref');

        $this->assertTrue(
            $this->_callPrivateFunction(
                '_isSelect',
                array()
            )
        );
    }

    /**
     * @param string  $caption            iconic caption for button
     * @param string  $title              text for button
     * @param integer $pos                position for next query
     * @param string  $html_sql_query     query ready for display
     * @param $output output from the _getTableNavigationButton method
     *
     * @dataProvider providerForTestGetTableNavigationButton
     */
    public function testGetTableNavigationButton($caption, $title, $pos, $html_sql_query, $output){

        $GLOBALS['cfg']['NavigationBarIconic'] = true;
        $GLOBALS['cfg']['AjaxEnable'] = true;
        $_SESSION[' PMA_token '] = 'token';

        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getTableNavigationButton',
                array(&$caption, $title, $pos, $html_sql_query)
            ),
            $output
        );
    }

    /**
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
                '<td><form action="sql.php" method="post" ><input type="hidden" name="db" value="as" /><input type="hidden" name="token" value="token" /><input type="hidden" name="sql_query" value="SELECT * FROM `pma_bookmark` WHERE 1" /><input type="hidden" name="pos" value="1" /><input type="hidden" name="goto" value="" /><input type="submit" name="navig" class="ajax" value="btn"  title="Submit" /></form></td>'
            )
        );
    }

    /**
     * @param integer $pos_next                  the offset for the "next" page
     * @param integer $pos_prev                  the offset for the "previous" page
     * @param string  $id_for_direction_dropdown the id for the direction dropdown
     * @param $output output from the _getTableNavigation method
     *
     * @dataProvider providerForTestGetTableNavigation
     */
    public function testGetTableNavigation($pos_next, $pos_prev, $id_for_direction_dropdown, $output){

        $_SESSION['tmp_user_values']['max_rows'] = '20';
        $GLOBALS['cfg']['AjaxEnable'] = true;
        $_SESSION['tmp_user_values']['pos'] = true;
        $GLOBALS['num_rows'] = '20';
        $GLOBALS['unlim_num_rows'] = '50';
        $GLOBALS['cfg']['ShowAll'] = true;
        $GLOBALS['cfg']['ShowDisplayDirection'] = true;
        $_SESSION['tmp_user_values']['repeat_cells'] = '1';
        $_SESSION['tmp_user_values']['disp_direction'] = '1';

        $this->assertEquals(
            str_word_count($this->_callPrivateFunction(
                '_getTableNavigation',
                array($pos_next, $pos_prev, $id_for_direction_dropdown)
            )),
            $output
        );
    }

    /**
     * @return array data for testGetTableNavigation
     */
    public function providerForTestGetTableNavigation()
    {
        return array(
            array(
                21,
                41,
                '123',
                '526'
            )
        );
    }    
    
    /**
     * Data provider for testGetResettedClassForInlineEdit
     * 
     * @return array parameters and output
     */
    public function dataProviderForTestGetResettedClassForInlineEdit()
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
     * Test for _getResettedClassForInlineEdit
     * 
     * @param string  $grid_edit_class  the class for all editable columns
     * @param string  $not_null_class   the class for not null columns
     * @param string  $relation_class   the class for relations in a column
     * @param string  $hide_class       the class for visibility of a column
     * @param string  $field_type_class the class related to type of the field
     * @param integer $row_no           the row index
     * @param string  $output           output of__getResettedClassForInlineEdit
     * 
     * @dataProvider dataProviderForTestGetResettedClassForInlineEdit
     */
    public function testGetResettedClassForInlineEdit(
        $grid_edit_class, $not_null_class, $relation_class,
        $hide_class, $field_type_class, $row_no, $output
    ) {
        $GLOBALS['cfg']['BrowsePointerEnable'] = true;
        $GLOBALS['cfg']['BrowseMarkerEnable'] = true;
        $GLOBALS['printview'] = 2;
        $_SESSION['tmp_user_values']['disp_direction']
            = PMA_DisplayResults::DISP_DIR_VERTICAL;        
        
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getResettedClassForInlineEdit',
                array(
                    $grid_edit_class, $not_null_class, $relation_class,
                    $hide_class, $field_type_class, $row_no
                )
            ),
            $output
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 1
     */
    public function testGetClassForDateTimeRelatedFieldsCase1()
    {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::DATETIME_FIELD)
            ),
            'datetimefield'
        );
    }
    
    /**
     * Test for _getClassForDateTimeRelatedFields - case 2
     */
    public function testGetClassForDateTimeRelatedFieldsCase2()
    {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::DATE_FIELD)
            ),
            'datefield'
        );
    }
    
    /**
     * Test for _getClassForDateTimeRelatedFields - case 3
     */
    public function testGetClassForDateTimeRelatedFieldsCase3()
    {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                array(PMA_DisplayResults::STRING_FIELD)
            ),
            ''
        );
    }
    
    /**
     * Provide data for testGetOperationLinksForVerticleTableCase1
     * 
     * @return array parameters and output
     */
    public function dataProviderForTestGetOperationLinksForVerticleTableCase1()
    {
        return array(
            array(
                array(
                    'emptypre' => 4,
                    'emptyafter' => 0,
                    'textbtn' => " \n \n \n",
                    'desc' => array(" \n\nid\n \n", " \n\nname\n \n"),
                    'edit' => array('<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D"...'),
                    'copy' => array('<td class="odd row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60new%60.%60id%60+%3D+1" /></span></"...'),
                    'delete' => array('<td class="odd row_0 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Da41d74bba668a938d5822179770ab75e&amp;token=a41d74bba668a938d5822179770ab75e" onclick="return confirmLink(this, \'DEL"...'),
                    'data' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('<td class="data grid_edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    ),
                    'row_delete' => array('<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[0]" class="multi_checkbox" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'),
                    'rowdata' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    )
                ),
                'edit',
                '<tr>
<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D"...</tr>
'
            )
        );
    }

    /**
     * Test for _getOperationLinksForVerticleTable - case 1
     * 
     * @param array  $vertical_display the information to display
     * @param string $operation        edit/copy/delete
     * @param string $output           output of _getOperationLinksForVerticleTable
     * 
     * @dataProvider dataProviderForTestGetOperationLinksForVerticleTableCase1
     */
    public function testGetOperationLinksForVerticleTableCase1(
        $vertical_display, $operation, $output
    ) {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getOperationLinksForVerticleTable',
                array($vertical_display, $operation)
            ),
            $output
        );
    }
    
    /**
     * Provide data for testGetOperationLinksForVerticleTableCase2
     * 
     * @return array parameters and output
     */
    public function dataProviderForTestGetOperationLinksForVerticleTableCase2()
    {
        return array(
            array(
                array(
                    'emptypre' => 4,
                    'emptyafter' => 0,
                    'textbtn' => " \n \n \n",
                    'desc' => array(" \n\nid\n \n", " \n\nname\n \n"),
                    'edit' => array('<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D"...'),
                    'copy' => array('<td class="odd row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60new%60.%60id%60+%3D+1" /></span></"...'),
                    'delete' => array('<td class="odd row_0 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Da41d74bba668a938d5822179770ab75e&amp;token=a41d74bba668a938d5822179770ab75e" onclick="return confirmLink(this, \'DEL"...'),
                    'data' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('<td class="data grid_edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    ),
                    'row_delete' => '',
                    'rowdata' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    )
                ),
                'edit',
                '<tr>
 
 
 
<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=update&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60new%60.%60id%60+%3D"...</tr>
'
            )
        );
    }

    /**
     * Test for _getOperationLinksForVerticleTable - case 2
     * 
     * @param array  $vertical_display the information to display
     * @param string $operation        edit/copy/delete
     * @param string $output           output of _getOperationLinksForVerticleTable
     * 
     * @dataProvider dataProviderForTestGetOperationLinksForVerticleTableCase2
     */
    public function testGetOperationLinksForVerticleTableCase2(
        $vertical_display, $operation, $output
    ) {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getOperationLinksForVerticleTable',
                array($vertical_display, $operation)
            ),
            $output
        );
    }
    
    
    /**
     * Provide data for testGetOperationLinksForVerticleTableCase3
     * 
     * @return array parameters and output
     */
    public function dataProviderForTestGetOperationLinksForVerticleTableCase3()
    {
        return array(
            array(
                array(
                    'emptypre' => 4,
                    'emptyafter' => 0,
                    'textbtn' => " \n \n \n",
                    'desc' => array(" \n\nid\n \n", " \n\nname\n \n"),
                    'edit' => '',
                    'copy' => array('<td class="odd row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=new&amp;where_clause=%60new%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60new%60&amp;goto=sql.php&amp;default_action=insert&amp;token=a41d74bba668a938d5822179770ab75e" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60new%60.%60id%60+%3D+1" /></span></"...'),
                    'delete' => array('<td class="odd row_0 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Da41d74bba668a938d5822179770ab75e&amp;token=a41d74bba668a938d5822179770ab75e" onclick="return confirmLink(this, \'DEL"...'),
                    'data' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('<td class="data grid_edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    ),
                    'row_delete' => '',
                    'rowdata' => array(
                        array('<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'),
                        array('edit not_null    row_0 vpointer vmarker ">cv x c c</td>\n')
                    )
                ),
                'delete',
                '<tr>
 
 
 
<td class="odd row_0 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=new&amp;sql_query=DELETE+FROM+%60data%60.%60new%60+WHERE+%60new%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Dnew%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560new%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Da41d74bba668a938d5822179770ab75e&amp;token=a41d74bba668a938d5822179770ab75e" onclick="return confirmLink(this, \'DEL"...</tr>
'
            )
        );
    }

    /**
     * Test for _getOperationLinksForVerticleTable - case 3
     * 
     * @param array  $vertical_display the information to display
     * @param string $operation        edit/copy/delete
     * @param string $output           output of _getOperationLinksForVerticleTable
     * 
     * @dataProvider dataProviderForTestGetOperationLinksForVerticleTableCase3
     */
    public function testGetOperationLinksForVerticleTableCase3(
        $vertical_display, $operation, $output
    ) {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getOperationLinksForVerticleTable',
                array($vertical_display, $operation)
            ),
            $output
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
                array(
                    'emptypre' => 4,
                    'emptyafter' => 0,
                    'textbtn' => '<th  rowspan="4" class="vmiddle">\n        \n    </th>\n',
                    'desc' => array(
                        '<th class="draggable" data-column="id">\n\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60%0AORDER+BY+%60a_sales%60.%60id%60+ASC&amp;session_max_rows=30&amp;token=d1aecb47ef7c081e068e7008b38a5d76" title="Sort">id</a>\n    </th>\n',
                        '<th class="draggable" data-column="cars_id">\n\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60%0AORDER+BY+%60a_sales%60.%60cars_id%60+ASC&amp;session_max_rows=30&amp;token=d1aecb47ef7c081e068e7008b38a5d76" title="Sort">cars_id</a>\n    </th>\n',
                        '<th class="draggable" data-column="customer_id">\n\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60%0AORDER+BY+%60a_sales%60.%60customer_id%60++DESC&amp;session_max_rows=30&amp;token=d1aecb47ef7c081e068e7008b38a5d76" onmouseover="$(\'.soimg2\').toggle()" onmouseout="$(\'.soimg2\').toggle()" title="Sort">customer_id <img src="themes/dot.gif" title="" alt="Ascending" class="icon ic_s_asc soimg2" /> <img src="themes/dot.gif" title="" alt="Descending" class="icon ic_s_desc"...'
                    ),
                    'edit' => array(
                        '<td class="odd edit_row_anchor row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+2&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=update&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60a_sales"...',
                        '<td class="even edit_row_anchor row_1 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+3&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=update&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60a_sale"...',
                        '<td class="odd edit_row_anchor row_2 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=update&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60a_sales"...',
                        '<td class="even edit_row_anchor row_3 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+4&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=update&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Edit" alt="Edit" class="icon ic_b_edit" /> Edit</span></a>\n<input type="hidden" class="where_clause" value ="%60a_sale"...'
                    ),
                    'copy' => array(
                        '<td class="odd row_0 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+2&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=insert&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60a_sales%60.%60id%60+%3"...',
                        '<td class="even row_1 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+3&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=insert&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60a_sales%60.%60id%60+%"...',
                        '<td class="odd row_2 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+1&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=insert&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60a_sales%60.%60id%60+%3"...',
                        '<td class="even row_3 vpointer vmarker center"  ><span class="nowrap">\n<a href="tbl_change.php?db=data&amp;table=a_sales&amp;where_clause=%60a_sales%60.%60id%60+%3D+4&amp;clause_is_unique=1&amp;sql_query=SELECT+%2A+FROM+%60a_sales%60&amp;goto=sql.php&amp;default_action=insert&amp;token=d1aecb47ef7c081e068e7008b38a5d76" ><span class="nowrap"><img src="themes/dot.gif" title="Copy" alt="Copy" class="icon ic_b_insrow" /> Copy</span></a>\n<input type="hidden" class="where_clause" value="%60a_sales%60.%60id%60+%"...'
                    ),
                    'delete' => array(
                        '<td class="odd row_0 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=DELETE+FROM+%60data%60.%60a_sales%60+WHERE+%60a_sales%60.%60id%60+%3D+2&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Da_sales%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560a_sales%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;token=d1aecb47ef7c081e068e7008b38a5d76" onclick="return co"...',
                        '<td class="even row_1 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=DELETE+FROM+%60data%60.%60a_sales%60+WHERE+%60a_sales%60.%60id%60+%3D+3&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Da_sales%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560a_sales%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;token=d1aecb47ef7c081e068e7008b38a5d76" onclick="return c"...',
                        '<td class="odd row_2 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=DELETE+FROM+%60data%60.%60a_sales%60+WHERE+%60a_sales%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Da_sales%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560a_sales%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;token=d1aecb47ef7c081e068e7008b38a5d76" onclick="return co"...',
                        '<td class="even row_3 vpointer vmarker center"  >\n<a href="sql.php?db=data&amp;table=a_sales&amp;sql_query=DELETE+FROM+%60data%60.%60a_sales%60+WHERE+%60a_sales%60.%60id%60+%3D+4&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3Ddata%26table%3Da_sales%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560a_sales%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Dd1aecb47ef7c081e068e7008b38a5d76&amp;token=d1aecb47ef7c081e068e7008b38a5d76" onclick="return c"...'
                    ),
                    'data' => array(
                        array(
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">2</td>\n',
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">6</td>\n',
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n'
                        ),
                        array(
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">3</td>\n',
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">7</td>\n',
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">2</td>\n'
                        ),
                        array(
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">1</td>\n',
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">9</td>\n',
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">3</td>\n'
                        ),
                        array(
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">4</td>\n',
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">8</td>\n',
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">5</td>\n'
                        )
                    ),
                    'row_delete' => array(
                        '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[0]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+2"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 2&quot;}" />    </td>',
                        '<td class="even row_1 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete1[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[1]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+3"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 3&quot;}" />    </td>',
                        '<td class="odd row_2 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete2[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[2]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 1&quot;}" />    </td>',
                        '<td class="even row_3 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete3[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[3]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+4"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 4&quot;}" />    </td>'
                    ),
                    'rowdata' => array(
                        array(
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">2</td>\n',
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">3</td>\n',
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">1</td>\n',
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">4</td>\n'
                        ),
                        array(
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">6</td>\n',
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">7</td>\n',
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">9</td>\n',
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">8</td>\n'
                        ),
                        array(
                            '<td class="right data grid_edit not_null    row_0 vpointer vmarker nowrap ">1</td>\n',
                            '<td class="right data grid_edit not_null    row_1 vpointer vmarker nowrap ">2</td>\n',
                            '<td class="right data grid_edit not_null    row_2 vpointer vmarker nowrap ">3</td>\n',
                            '<td class="right data grid_edit not_null    row_3 vpointer vmarker nowrap ">5</td>\n'
                        )
                    )
                ),
                '_left',
                '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0_left" name="rows_to_delete[0]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+2"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 2&quot;}" />    </td><td class="even row_1 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete1_left" name="rows_to_delete[1]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+3"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 3&quot;}" />    </td><td class="odd row_2 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete2_left" name="rows_to_delete[2]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 1&quot;}" />    </td><td class="even row_3 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete3_left" name="rows_to_delete[3]" class="multi_checkbox" value="%60a_sales%60.%60id%60+%3D+4"  /><input type="hidden" class="condition_array" value="{&quot;`a_sales`.`id`&quot;:&quot;= 4&quot;}" />    </td>'
            )
        );
    }
    
    /**
     * Test for _getCheckBoxesForMultipleRowOperations
     * 
     * @param array  $vertical_display the information to display
     * @param string $dir              _left / _right
     * @param string $output           output of _getCheckBoxesForMultipleRowOperations
     * 
     * @dataProvider dataProviderForGetCheckBoxesForMultipleRowOperations
     */
    public function testGetCheckBoxesForMultipleRowOperations(
        $vertical_display, $dir, $output
    ) {
        $_SESSION['tmp_user_values']['repeat_cells'] = 0;
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getCheckBoxesForMultipleRowOperations',
                array($vertical_display, $dir)
            ),
            $output
        );
    }
    
    /**
     * Test for _getOffsets - case 1
     */
    public function testGetOffsetsCase1()
    {
        $_SESSION['tmp_user_values']['max_rows'] = PMA_DisplayResults::ALL_ROWS;
        $this->assertEquals(
            $this->_callPrivateFunction('_getOffsets', array()),
            array(0, 0)
        );
    }
    
    /**
     * Test for _getOffsets - case 2
     */
    public function testGetOffsetsCase2()
    {
        $_SESSION['tmp_user_values']['max_rows'] = 5;
        $_SESSION['tmp_user_values']['pos'] = 4;
        $this->assertEquals(
            $this->_callPrivateFunction('_getOffsets', array()),
            array(9, 0)
        );
    }
    
    /**
     * Data provider for testGetSortParamsCase1
     * 
     * @return array parameters and output
     */
    public function dataProviderForGetSortParamsCase1()
    {
        return array(
            array('', array('', '', ''))
        );
    }
    
    /**
     * Test for _getSortParams - case 1
     * 
     * @param string $order_by_clause the order by clause of the sql query
     * @param string $output          output of _getSortParams
     * 
     * @dataProvider dataProviderForGetSortParamsCase1
     */
    public function testGetSortParamsCase1($order_by_clause, $output)
    {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getSortParams', array($order_by_clause)
            ),
            $output
        );
    }
    
    /**
     * Data provider for testGetSortParamsCase2
     * 
     * @return array parameters and output
     */
    public function dataProviderForGetSortParamsCase2()
    {
        return array(
            array(
                '`a_sales`.`customer_id` ASC',
                array(
                    '`a_sales`.`customer_id` ASC',
                    '`a_sales`.`customer_id`',
                    'ASC'
                )
            )
        );
    }
    
    /**
     * Test for _getSortParams - case 2
     * 
     * @param string $order_by_clause the order by clause of the sql query
     * @param string $output          output of _getSortParams
     * 
     * @dataProvider dataProviderForGetSortParamsCase2
     */
    public function testGetSortParamsCase2($order_by_clause, $output)
    {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getSortParams', array($order_by_clause)
            ),
            $output
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
                '<td class="odd row_0 vpointer vmarker" class="center"><input type="checkbox" id="id_rows_to_delete0[%_PMA_CHECKBOX_DIR_%]" name="rows_to_delete[0]" class="multi_checkbox" value="%60new%60.%60id%60+%3D+1"  /><input type="hidden" class="condition_array" value="{&quot;`new`.`id`&quot;:&quot;= 1&quot;}" />    </td>'
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
     * @param string $output            output of _getSortParams
     * @param string $output            output of _getCheckboxForMultiRowSubmissions
     * 
     * @dataProvider dataProviderForGetCheckboxForMultiRowSubmissions
     */
    public function testGetCheckboxForMultiRowSubmissions(
        $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
        $del_query, $id_suffix, $class, $output
    ) {
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getCheckboxForMultiRowSubmissions',
                array(
                    $del_url, $is_display, $row_no, $where_clause_html,
                    $condition_array, $del_query, $id_suffix, $class
                )                    
            ),
            $output
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
     * @dataProvider dataProviderForGetEditLink
     */
    public function testGetEditLink(
        $edit_url, $class, $edit_str, $where_clause, $where_clause_html, $output
    ) {
        
        $GLOBALS['cfg']['PropertiesIconic'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;
        
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getEditLink',
                array(
                    $edit_url, $class, $edit_str, $where_clause, $where_clause_html
                )
            ),
            $output
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
     * @dataProvider dataProviderForGetCopyLink
     */
    public function testGetCopyLink(
        $copy_url, $copy_str, $where_clause, $where_clause_html, $class, $output
    ) {
        
        $GLOBALS['cfg']['PropertiesIconic'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;
        
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getCopyLink',
                array(
                    $copy_url, $copy_str, $where_clause, $where_clause_html, $class
                )
            ),
            $output
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
<a href="sql.php?db=Data&amp;table=customer&amp;sql_query=DELETE+FROM+%60Data%60.%60customer%60+WHERE+%60customer%60.%60id%60+%3D+1&amp;message_to_show=The+row+has+been+deleted&amp;goto=sql.php%3Fdb%3DData%26table%3Dcustomer%26sql_query%3DSELECT%2B%252A%2BFROM%2B%2560customer%2560%26message_to_show%3DThe%2Brow%2Bhas%2Bbeen%2Bdeleted%26goto%3Dtbl_structure.php%26token%3Df597309d3a066c3c81a6cb015a79636d&amp;token=f597309d3a066c3c81a6cb015a79636d" onclick="return confirmLink(this, \'DELETE FROM `Data`.`customer` WHERE `customer`.`id` = 1\')"><span class="nowrap"><img src="themes/dot.gif" title="Delete" alt="Delete" class="icon ic_b_drop" /> Delete</span></a>
</td>'
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
     * @dataProvider dataProviderForGetDeleteLink
     */
    public function testGetDeleteLink(
        $del_url, $del_str, $js_conf, $class, $output
    ) {
        
        $GLOBALS['cfg']['PropertiesIconic'] = 'both';
        $GLOBALS['cfg']['LinkLengthLimit'] = 1000;
        
        $this->assertEquals(
            $this->_callPrivateFunction(
                '_getDeleteLink',
                array(
                    $del_url, $del_str, $js_conf, $class
                )
            ),
            $output
        );
    }
    

}
