<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_showMessage from common.lib
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_showMessage_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
include 'libraries/core.lib.php';
include 'libraries/vendor_config.php';
include 'libraries/config.default.php';
include 'libraries/database_interface.lib.php';
include 'libraries/Table.class.php';
require 'libraries/php-gettext/gettext.inc';

class PMA_showMessage_test extends PHPUnit_Framework_TestCase
{
    protected $backupGlobals = FALSE;

    function testShowMessageNotAjax(){
//        global $cfg, $GLOBALS;

//        $GLOBALS['sql_query'] = "SELECT * FROM tTable";
//        $GLOBALS['table'] = 'tbl1';
//        $cfg['SQP']['fmtType'] = 'none';
//        $cfg['ShowTooltip'] = false;
//        $cfg['ShowSQL'] = true;
//        $cfg['MaxCharactersInDisplayedSQL'] = 1000;
//
//        print_r($cfg);

        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $this->assertEquals("",PMA_showMessage("msg"));
        $this->assertTrue(true);
    }
}