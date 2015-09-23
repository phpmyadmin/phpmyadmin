<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating routine execution dialog
 *
 * @package PhpMyAdmin-test
 */

$GLOBALS['server'] = 0;
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once './libraries/Types.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';

/**
 * Test for generating routine execution dialog
 *
 * @package PhpMyAdmin-test
 */
class PMA_RTN_GetExecuteForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        global $cfg;
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['server'] = 0;
        $cfg['ServerDefault'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = '';
        $cfg['ShowFunctionFields'] = true;
        $cfg['DefaultFunctions']['FUNC_NUMBER'] = '';
        $cfg['DefaultFunctions']['FUNC_DATE'] = '';
        $cfg['DefaultFunctions']['FUNC_SPATIAL'] = 'GeomFromText';
    }

    /**
     * Test for PMA_RTN_getExecuteForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider provider1
     */
    public function testgetExecuteForm1($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_RTN_getExecuteForm($data)
        );
    }

    /**
     * Data provider for testgetExecuteForm1
     *
     * @return array
     */
    public function provider1()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => array(
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN'
            ),
            'item_param_name'           => array(
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe'
            ),
            'item_param_type'           => array(
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET'
            ),
            'item_param_length'         => array(
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'"
            ),
            'item_param_length_arr'     => array(
                0 => array(),
                1 => array('22'),
                2 => array(),
                3 => array(),
                4 => array("'a'", "'b'"),
                5 => array("'a'", "'b'")
            ),
            'item_param_opts_num'       => array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_param_opts_text'      => array(
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                "name='item_name'"
            ),
            array(
                $data,
                "name='funcs[foo]'"
            ),
            array(
                $data,
                "<input class='datefield' type='text' name='params[foo]' />"
            ),
            array(
                $data,
                "name='funcs[fob]'"
            ),
            array(
                $data,
                "<input class='datetimefield' type='text' name='params[fob]'"
            ),
            array(
                $data,
                "name='params[fod][]'"
            ),
            array(
                $data,
                "name='params[foe][]'"
            ),
            array(
                $data,
                "name='execute_routine'"
            ),
        );
    }

    /**
     * Test for PMA_RTN_getExecuteForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider provider2
     */
    public function testgetExecuteForm2($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        PMA_RTN_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_RTN_getExecuteForm($data)
        );
    }

    /**
     * Data provider for testgetExecuteForm2
     *
     * @return array
     */
    public function provider2()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => array(
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN'
            ),
            'item_param_name'           => array(
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe'
            ),
            'item_param_type'           => array(
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET'
            ),
            'item_param_length'         => array(
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'"
            ),
            'item_param_length_arr'     => array(
                0 => array(),
                1 => array('22'),
                2 => array(),
                3 => array(),
                4 => array("'a'", "'b'"),
                5 => array("'a'", "'b'")
            ),
            'item_param_opts_num'       => array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_param_opts_text'      => array(
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                "name='execute_routine'"
            ),
            array(
                $data,
                "name='ajax_request'"
            ),
        );
    }
}
