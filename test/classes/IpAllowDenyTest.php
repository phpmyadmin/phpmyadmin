<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\IpAllowDeny
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\IpAllowDenyTest class
 *
 * this class is for testing PhpMyAdmin\IpAllowDeny
 *
 * @package PhpMyAdmin-test
 */
class IpAllowDenyTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['user'] = "pma_username";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "allow % 255.255.255.0/4";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "allow % from 255.255.2.0/4";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "allow % from 2001:4998:c:a0d:0000:0000:4998:1020";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "allow % from 2001:4998:c:a0d:0000:0000:4998:[1001-2010]";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "allow % from 2001:4998:c:a0d:0000:0000:4998:3020/24";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = "deny % 255.255.0.0/8";
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = "deny % from 255.255.0.0/8";
    }

    /**
     * Test for Core::getIp
     *
     * @return void
     *
     * @dataProvider proxyIPs
     */
    public function testGetIp($remote, $header, $expected, $proxyip = null)
    {
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $GLOBALS['cfg']['TrustedProxies'] = array();

        if (!is_null($remote)) {
            $_SERVER['REMOTE_ADDR'] = $remote;
        }

        if (!is_null($header)) {
            if (is_null($proxyip)) {
                $proxyip = $remote;
            }
            $GLOBALS['cfg']['TrustedProxies'][$proxyip] = 'TEST_FORWARDED_HEADER';
            $_SERVER['TEST_FORWARDED_HEADER'] = $header;
        }

        $this->assertEquals(
            $expected,
            Core::getIp()
        );

        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $GLOBALS['cfg']['TrustedProxies'] = array();
    }

    /**
     * Data provider for Core::getIp tests
     *
     * @return array
     */
    public function proxyIPs()
    {
        return array(
            // Nothing set
            array(null, null, false),
            // Remote IP set
            array('101.0.0.25', null, '101.0.0.25'),
            // Proxy
            array('101.0.0.25', '192.168.10.10', '192.168.10.10'),
            // Several proxies
            array('101.0.0.25', '192.168.10.1, 192.168.100.100', '192.168.10.1'),
            // Invalid proxy
            array('101.0.0.25', 'invalid', false),
            // Direct IP with proxy enabled
            array('101.0.0.25', '192.168.10.10', '101.0.0.25', '10.10.10.10'),
        );
    }

    /**
     * Test for IpAllowDeny::ipMaskTest
     *
     * @return void
     */
    public function testIpMaskTest()
    {
        //IPV4 testing
        $testRange = "255.255.0.0/8";
        $ipToTest = "10.0.0.0";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );

        $testRange = "255.255.0.0/4";
        $ipToTest = "255.3.0.0";
        $this->assertEquals(
            true,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );

        $testRange = "255.255.0.[0-10]";
        $ipToTest = "255.3.0.3";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = "255.3.0.12";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );

        //IPV6 testing
        //not range
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:1020";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:1020";
        $this->assertEquals(
            true,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:1020";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:2020";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );

        //range
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:1020";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:[1001-2010]";
        $this->assertEquals(
            true,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:3020";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:[1001-2010]";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );

        //CDIR
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:1020";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:[1001-2010]";
        $this->assertEquals(
            true,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = "2001:4998:c:a0d:0000:0000:4998:1000";
        $testRange = "2001:4998:c:a0d:0000:0000:4998:3020/24";
        $this->assertEquals(
            false,
            IpAllowDeny::ipMaskTest($testRange, $ipToTest)
        );
    }

    /**
     * Test for IpAllowDeny::allowDeny
     *
     * @return void
     */
    public function testAllowDeny()
    {
        $_SERVER['REMOTE_ADDR'] = "";
        $this->assertEquals(
            false,
            IpAllowDeny::allowDeny("allow")
        );

        $_SERVER['REMOTE_ADDR'] = "255.0.1.0";
        $this->assertEquals(
            true,
            IpAllowDeny::allowDeny("allow")
        );
        $_SERVER['REMOTE_ADDR'] = "10.0.0.0";
        $this->assertEquals(
            false,
            IpAllowDeny::allowDeny("allow")
        );

        $_SERVER['REMOTE_ADDR'] = "255.255.0.1";
        $this->assertEquals(
            true,
            IpAllowDeny::allowDeny("deny")
        );
        $_SERVER['REMOTE_ADDR'] = "255.124.0.5";
        $this->assertEquals(
            true,
            IpAllowDeny::allowDeny("deny")
        );
        $_SERVER['REMOTE_ADDR'] = "122.124.0.5";
        $this->assertEquals(
            false,
            IpAllowDeny::allowDeny("deny")
        );

        //IPV6
        $_SERVER['REMOTE_ADDR'] = "2001:4998:c:a0d:0000:0000:4998:1020";
        $this->assertEquals(
            true,
            IpAllowDeny::allowDeny("allow")
        );
        $_SERVER['REMOTE_ADDR'] = "2001:4998:c:a0d:0000:0000:4998:1000";
        $this->assertEquals(
            false,
            IpAllowDeny::allowDeny("allow")
        );
        $_SERVER['REMOTE_ADDR'] = "2001:4998:c:a0d:0000:0000:4998:1020";
        $this->assertEquals(
            true,
            IpAllowDeny::allowDeny("allow")
        );
    }
}
