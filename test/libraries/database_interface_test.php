<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for faked database access
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests basic functionality of dummy dbi driver
 *
 * @package PhpMyAdmin-test
 */
class PMA_dbi_test extends PHPUnit_Framework_TestCase
{
    function testQuery()
    {
        $this->assertEquals(0, PMA_DBI_real_query('SELECT 1'));
    }

    function testFetch()
    {
        $this->assertEquals(array('1'), PMA_DBI_fetch_array(0));
    }
}

