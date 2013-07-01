<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for server_bin_log.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/server_bin_log.lib.php';
require_once 'libraries/Theme.class.php';

class PMA_ServerBinlog_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        $_REQUEST['log'] = "index1";
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "server";
    }

    /**
     * Test for PMA_getLogSelector
     *
     * @return void
     */
    public function testPMA_getLogSelector()
    {
        $binary_log_file_names = array();
        $binary_log_file_names[] = array("Log_name"=>"index1", "File_size"=>100);
        $binary_log_file_names[] = array("Log_name"=>"index2", "File_size"=>200);
        
        $url_params = array();
        $url_params['log'] = "log";
        $url_params['dontlimitchars'] = 1;

        $html = PMA_getLogSelector($binary_log_file_names, $url_params);
        $this->assertContains(
            'Select binary log to view',
            $html
        );
        $this->assertContains(
            '<option value="index1" selected="selected">index1 (100 B)</option>',
            $html
        );
        $this->assertContains(
            '<option value="index2">index2 (200 B)</option>',
            $html
        );
    }
}
