<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_checkTimeout()
 * from libraries/import.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';

class PMA_Import_Test extends PHPUnit_Framework_TestCase
{
    function testCheckTimeout()
    {
        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = false;

        $this->assertFalse(PMA_checkTimeout());

        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = true;

        $this->assertTrue(PMA_checkTimeout());
    }
}