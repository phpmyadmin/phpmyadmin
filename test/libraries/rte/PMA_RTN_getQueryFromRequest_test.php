<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating CREATE [PROCEDURE|FUNCTION] query from HTTP request
 *
 * @package PhpMyAdmin-test
 */

/*
 * Needed for backquote() and PMA_RTN_getQueryFromRequest()
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once './libraries/Types.class.php';

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';


class PMA_RTN_getQueryFromRequest_test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */
    public function testgetQueryFromRequest($request, $query, $num_err)
    {
        global $_REQUEST, $errors, $cfg;

        $cfg['ShowFunctionFields'] = false;

        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();

        $errors = array();
        PMA_RTN_setGlobals();

        unset($_REQUEST);
        $_REQUEST = $request;
        $this->assertEquals($query, PMA_RTN_getQueryFromRequest());
        $this->assertEquals($num_err, count($errors));
    }

    public function provider()
    {
        return array(
            // Testing success
            array(
                array(
                    'item_name'                 => 'p r o c',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => 'foo',
                    'item_definer'              => 'me@home',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '0',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                'CREATE DEFINER=`me`@`home` PROCEDURE `p r o c`() COMMENT \'foo\' DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;',
                0
            ),
            array(
                array(
                    'item_name'                 => 'pr``oc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => '',
                    'item_definer'              => 'someuser@somehost',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('IN', 'INOUT'),
                    'item_param_name'           => array('pa`ram', 'par 2'),
                    'item_param_type'           => array('INT', 'ENUM'),
                    'item_param_length'         => array('10', '\'a\', \'b\''),
                    'item_param_opts_num'       => array('ZEROFILL', ''),
                    'item_param_opts_text'      => array('utf8', 'latin1'),
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar'
                ),
                'CREATE DEFINER=`someuser`@`somehost` PROCEDURE `pr````oc`(IN `pa``ram` INT(10) ZEROFILL, INOUT `par 2` ENUM(\'a\', \'b\') CHARSET latin1) NOT DETERMINISTIC SQL SECURITY DEFINER SELECT \'foobar\';',
                0
            ),
            array(
                array(
                    'item_name'                 => 'func\\',
                    'item_returnlength'         => '5,5',
                    'item_returnopts_num'       => 'UNSIGNED ZEROFILL',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => 'foo\'s bar',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '1',
                    'item_param_dir'            => '',
                    'item_param_name'           => array('pa`ram'),
                    'item_param_type'           => array('VARCHAR'),
                    'item_param_length'         => array('45'),
                    'item_param_opts_num'       => array(''),
                    'item_param_opts_text'      => array('latin1'),
                    'item_returntype'           => 'DECIMAL',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'READ SQL DATA'
                ),
                'CREATE FUNCTION `func\\`(`pa``ram` VARCHAR(45) CHARSET latin1) RETURNS DECIMAL(5,5) UNSIGNED ZEROFILL COMMENT \'foo\'\'s bar\' DETERMINISTIC SQL SECURITY DEFINER SELECT \'foobar\';',
                0
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'utf8',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '1',
                    'item_returntype'           => 'VARCHAR',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'READ SQL DATA'
                ),
                'CREATE FUNCTION `func`() RETURNS VARCHAR(20) CHARSET utf8 NOT DETERMINISTIC SQL SECURITY DEFINER SELECT 0;',
                0
            ),
            // Testing failures
            array(
                array(
                ),
                'CREATE () NOT DETERMINISTIC ', // invalid query
                3
            ),
            array(
                array(
                    'item_name'                 => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => 'foo',
                    'item_definer'              => 'mehome', // invalid definer format
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '0',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                'CREATE PROCEDURE `proc`() COMMENT \'foo\' DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;', // valid query
                1
            ),
            array(
                array(
                    'item_name'                 => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('FAIL', 'INOUT'), // invalid direction
                    'item_param_name'           => array('pa`ram', 'goo'),
                    'item_param_type'           => array('INT', 'ENUM'),
                    'item_param_length'         => array('10', ''), // missing ENUM values
                    'item_param_opts_num'       => array('ZEROFILL', ''),
                    'item_param_opts_text'      => array('utf8', 'latin1'),
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar' // invalid, will just be ignored withour throwing errors
                ),
                'CREATE PROCEDURE `proc`((10) ZEROFILL, INOUT `goo` ENUM CHARSET latin1) NOT DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                2
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '', // missing length for VARCHAR
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'utf8',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('IN'),
                    'item_param_name'           => array(''), // missing name
                    'item_param_type'           => array('INT'),
                    'item_param_length'         => array('10'),
                    'item_param_opts_num'       => array('ZEROFILL'),
                    'item_param_opts_text'      => array('latin1'),
                    'item_returntype'           => 'VARCHAR',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                'CREATE FUNCTION `func`() RETURNS VARCHAR CHARSET utf8 NOT DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                2
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '0',
                    'item_returntype'           => 'FAIL', // invalid return type
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                'CREATE FUNCTION `func`()  NOT DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                1
            ),
        );
    }
}
?>
