<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for ip_allow_deny.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/ip_allow_deny.lib.php';

class PMA_Ip_allow_deny_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SERVER['REMOTE_ADDR'] = "101.0.0.25";
        $GLOBALS['cfg']['Server']['user'] = "pma_username";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = "allow % 255.255.255.0/4";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = "allow % from 255.255.2.0/4";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = "deny % 255.255.0.0/8";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = "deny % from 255.255.0.0/8";
        include_once 'libraries/ip_allow_deny.lib.php';
    }

    /**
     * Test for PMA_getIp
     *
     * @return void
     */
    public function testPMA_getIp()
    {
        $this->assertEquals(
            "101.0.0.25",
            PMA_getIp()
        );
    }

    /**
     * Test for PMA_ipMaskTest
     *
     * @return void
     */
    public function testPMA_ipMaskTest()
    {
        $testRange = "255.255.0.0/8";
        $ipToTest = "10.0.0.0";
        $this->assertEquals(
            false,
            PMA_ipMaskTest($testRange, $ipToTest)
        );
        
        $testRange = "255.255.0.0/4";
        $ipToTest = "255.3.0.0";
        $this->assertEquals(
            true,
            PMA_ipMaskTest($testRange, $ipToTest)
        );
    }

    /**
     * Test for PMA_allowDeny
     *
     * @return void
     */
    public function testPMA_allowDeny()
    {
        $_SERVER['REMOTE_ADDR'] = "255.0.1.0";
        $this->assertEquals(
            true,
            PMA_allowDeny("allow")
        );
        $_SERVER['REMOTE_ADDR'] = "10.0.0.0";
        $this->assertEquals(
            false,
            PMA_allowDeny("allow")
        );
        
        $_SERVER['REMOTE_ADDR'] = "255.255.0.1";
        $this->assertEquals(
            true,
            PMA_allowDeny("deny")
        );
        $_SERVER['REMOTE_ADDR'] = "255.124.0.5";
        $this->assertEquals(
            true,
            PMA_allowDeny("deny")
        );
        $_SERVER['REMOTE_ADDR'] = "122.124.0.5";
        $this->assertEquals(
            false,
            PMA_allowDeny("deny")
        );
    }
}
