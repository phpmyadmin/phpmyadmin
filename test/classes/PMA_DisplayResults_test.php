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
require_once 'libraries/common.lib.php';



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

}
