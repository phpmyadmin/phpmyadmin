<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getTableCount_test from core.lib.php
 * PMA_getTableCount_test returns count of tables in given db
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/common.lib.php';
require_once 'libraries/config.default.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/database_interface.lib.php';

require_once 'config.sample.inc.php';

class PMA_getTableCount_test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
    }

    function testTableCount()
    {
        $GLOBALS['cfg']['Server']['extension'] = 'mysql';
        $GLOBALS['cfg']['Server']['host'] = 'localhost';
        $GLOBALS['cfg']['Server']['user'] = 'root';

        $this->assertEquals(5, PMA_getTableCount('meddb'));
        $this->assertTrue(true);
    }
}
