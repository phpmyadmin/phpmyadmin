<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating routine execution dialog
 *
 * @package PhpMyAdmin-test
 */
$GLOBALS['server'] = 0;
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once './libraries/Types.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';

class PMA_RTN_getExecuteForm_test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $cfg;
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['server'] = 0;
        $cfg['ServerDefault'] = 1;
        $GLOBALS['lang'] = 'en';
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['cfg']['ServerDefault'] = '';
        $cfg['ShowFunctionFields'] = true;
        $cfg['DefaultFunctions']['FUNC_NUMBER'] = '';
        $cfg['DefaultFunctions']['FUNC_DATE'] = '';
        $cfg['DefaultFunctions']['FUNC_SPATIAL'] = 'GeomFromText';
        $cfg['MySQLManualType'] = 'viewable';
        $cfg['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';
    }

    /**
     * @dataProvider provider_1
     */
    public function testgetExecuteForm_1($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getExecuteForm($data), false);
    }

    public function provider_1()
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
            'item_param_dir'            => array(0 => 'IN',   1 => 'OUT',     2 => 'IN',       3 => 'IN',       4 => 'IN',      5 => 'IN'),
            'item_param_name'           => array(0 => 'foo',  1 => 'foa',     2 => 'fob',      3 => 'foc',      4 => 'fod',     5 => 'foe'),
            'item_param_type'           => array(0 => 'DATE', 1 => 'VARCHAR', 2 => 'DATETIME', 3 => 'GEOMETRY', 4 => 'ENUM',    5 => 'SET'),
            'item_param_length'         => array(0 => '',     1 => '22',      2 => '',         3 => '',         4 => "'a','b'", 5 => "'a','b'"),
            'item_param_opts_num'       => array(0 => '',     1 => '',        2 => '',         3 => '',         4 => '',        5 => ''),
            'item_param_opts_text'      => array(0 => '',     1 => 'utf8',    2 => '',         3 => '',         4 => '',        5 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'funcs[foo]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'params[foo]',
                        'class' => 'datefield'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'funcs[fob]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'params[fob]',
                        'class' => 'datetimefield'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'params[fod][]'
                    ),
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'params[foe][]'
                    ),
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'execute_routine'
                    )
                )
            ),
        );
    }

    /**
     * @dataProvider provider_2
     */
    public function testgetExecuteForm_2($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getExecuteForm($data), false);
    }

    public function provider_2()
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
            'item_param_dir'            => array(0 => 'IN',   1 => 'OUT',     2 => 'IN',       3 => 'IN',       4 => 'IN',      5 => 'IN'),
            'item_param_name'           => array(0 => 'foo',  1 => 'foa',     2 => 'fob',      3 => 'foc',      4 => 'fod',     5 => 'foe'),
            'item_param_type'           => array(0 => 'DATE', 1 => 'VARCHAR', 2 => 'DATETIME', 3 => 'GEOMETRY', 4 => 'ENUM',    5 => 'SET'),
            'item_param_length'         => array(0 => '',     1 => '22',      2 => '',         3 => '',         4 => "'a','b'", 5 => "'a','b'"),
            'item_param_opts_num'       => array(0 => '',     1 => '',        2 => '',         3 => '',         4 => '',        5 => ''),
            'item_param_opts_text'      => array(0 => '',     1 => 'utf8',    2 => '',         3 => '',         4 => '',        5 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'execute_routine'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'ajax_request'
                    )
                )
            ),
        );
    }
}
?>
