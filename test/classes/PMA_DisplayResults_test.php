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
     * @return array array data for testGetTableNavigation
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
    

}
