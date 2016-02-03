<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */

/**
 * Base class for phpMyAdmin tests.
 *
 * @package PhpMyAdmin-test
 */
class PMATestCase extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        require 'libraries/config.default.php';
        $GLOBALS['cfg'] = $cfg;
    }
}