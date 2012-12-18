<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_checkPageValidity() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

class PMA_checkPageValidity_test extends PHPUnit_Framework_TestCase
{
    protected $goto_whitelist = array(
        'db_create.php',
        'db_datadict.php',
        'db_sql.php',
        'db_export.php',
        'db_search.php',
        'export.php',
        'import.php',
        'index.php',
        'pdf_pages.php',
        'pdf_schema.php',
        'querywindow.php',
        'server_binlog.php',
        'server_variables.php',
        'sql.php',
        'tbl_select.php',
        'transformation_overview.php',
        'transformation_wrapper.php',
        'user_password.php',
    );

    function testGotoNowhere()
    {
        $page = null;
        $this->assertFalse(PMA_checkPageValidity($page, null));
    }

    function testGotoWhitelist()
    {
        $page = 'export.php';

        $this->assertTrue(PMA_checkPageValidity($page, $this->goto_whitelist));
    }

    function testGotoNotInWhitelist()
    {
        $page = 'shell.php';

        $this->assertFalse(PMA_checkPageValidity($page, $this->goto_whitelist));
    }

    function testGotoWhitelistPage()
    {
        $page = 'index.php?sql.php&test=true';

        $this->assertTrue(PMA_checkPageValidity($page, $this->goto_whitelist));
    }

    function testGotoWhitelistEncodedPage()
    {
        $page = 'index.php%3Fsql.php%26test%3Dtrue';

        $this->assertTrue(PMA_checkPageValidity($page, $this->goto_whitelist));
    }

}
