<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for fetching routine data from HTTP request
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\TypesMySQL;



/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';

/**
 * Test for fetching routine data from HTTP request
 *
 * @package PhpMyAdmin-test
 */
class PMA_RTN_GetDataFromRequest_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        global $cfg;

        $cfg['ShowFunctionFields'] = false;

        $GLOBALS['PMA_Types'] = new TypesMySQL();
    }

    /**
     * Test for PMA_RTN_getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testgetDataFromRequest($in, $out)
    {
        global $_REQUEST;

        unset($_REQUEST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_REQUEST[$key] = $value;
            }
        }
        PMA_RTN_setGlobals();
        $this->assertEquals($out, PMA_RTN_getDataFromRequest());
    }

    /**
     * Data provider for testgetDataFromRequest
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array(
                array(
                    'item_name'                 => '',
                    'item_original_name'        => '',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => '',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => '',
                    'item_type_toggle'          => '',
                    'item_original_type'        => '',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => '',
                    'item_sqldataaccess'        => ''
                ),
                array(
                    'item_name'                 => '',
                    'item_original_name'        => '',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => '',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_num_params'           => 0,
                    'item_param_dir'            => array(),
                    'item_param_name'           => array(),
                    'item_param_type'           => array(),
                    'item_param_length'         => array(),
                    'item_param_opts_num'       => array(),
                    'item_param_opts_text'      => array(),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => ''
                )
            ),
            array(
                array(
                    'item_name'                 => 'proc2',
                    'item_original_name'        => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_param_dir'            => array(0 => 'IN', 1 => 'FAIL'),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => 'INT', 1 => 'FAIL'),
                    'item_param_length'         => array(0 => '20', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'latin1'),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                array(
                    'item_name'                 => 'proc2',
                    'item_original_name'        => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_num_params'           => 2,
                    'item_param_dir'            => array(0 => 'IN', 1 => ''),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => 'INT', 1 => ''),
                    'item_param_length'         => array(0 => '20', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'latin1'),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => ' checked=\'checked\'',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => ' selected=\'selected\'',
                    'item_sqldataaccess'        => 'NO SQL'
                )
            ),
            array(
                array(
                    'item_name'                 => 'func2',
                    'item_original_name'        => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'CHARSET utf8',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'FUNCTION',
                    'item_type_toggle'          => 'PROCEDURE',
                    'item_original_type'        => 'FUNCTION',
                    'item_param_dir'            => array(0 => '', 1 => ''),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(
                        0 => '<s>XSS</s>',
                        1 => 'TEXT'
                    ),
                    'item_param_length'         => array(0 => '10,10', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'utf8'),
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                array(
                    'item_name'                 => 'func2',
                    'item_original_name'        => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'CHARSET utf8',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'FUNCTION',
                    'item_type_toggle'          => 'PROCEDURE',
                    'item_original_type'        => 'FUNCTION',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array(),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => '', 1 => 'TEXT'),
                    'item_param_length'         => array(0 => '10,10', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'utf8'),
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => ' selected=\'selected\'',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => ''
                )
            ),
        );
    }
}
