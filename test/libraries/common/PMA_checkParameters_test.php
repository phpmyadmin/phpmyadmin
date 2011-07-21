<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_checkParameters from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_checkParameters_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
require_once 'libraries/Theme.class.php';

class PMA_checkParameters_test extends PHPUnit_Extensions_OutputTestCase
{
    function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    function testCheckParameterMissing()
    {
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();

        $this->expectOutputRegex("/Missing parameter: field/" );

        PMA_checkParameters(array('db', 'table', 'field'), false);
    }

    function testCheckParameter()
    {
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['table'] = "tblTable";
        $GLOBALS['field'] = "test_field";
        $GLOBALS['sql_query'] = "SELECT * FROM tblTable;";

        $this->expectOutputString("");
        PMA_checkParameters(array('db', 'table', 'field', 'sql_query'), false);
    }
}