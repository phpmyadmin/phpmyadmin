<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;

/**
 * PhpMyAdmin\Tests\IpAllowDenyTest class
 *
 * this class is for testing PhpMyAdmin\IpAllowDeny
 */
class IpAllowDenyTest extends AbstractTestCase
{
    /** @var IpAllowDeny */
    private $ipAllowDeny;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['cfg']['Server']['user'] = 'pma_username';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'allow % 255.255.255.0/4';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'allow % from 255.255.2.0/4';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'allow % from 2001:4998:c:a0d:0000:0000:4998:1020';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'allow % from 2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'allow % from 2001:4998:c:a0d:0000:0000:4998:3020/24';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'deny % 255.255.0.0/8';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][]
            = 'deny % from 255.255.0.0/8';

        $this->ipAllowDeny = new IpAllowDeny();
    }

    /**
     * Test for Core::getIp
     *
     * @param string|null $remote   remote
     * @param string|null $header   header
     * @param string|bool $expected expected result
     * @param string      $proxyip  proxyip
     *
     * @dataProvider proxyIPs
     */
    public function testGetIp(?string $remote, ?string $header, $expected, ?string $proxyip = null): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $GLOBALS['cfg']['TrustedProxies'] = [];

        if ($remote !== null) {
            $_SERVER['REMOTE_ADDR'] = $remote;
        }

        if ($header !== null) {
            if ($proxyip === null) {
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
        $GLOBALS['cfg']['TrustedProxies'] = [];
    }

    /**
     * Data provider for Core::getIp tests
     *
     * @return array
     */
    public function proxyIPs(): array
    {
        return [
            // Nothing set
            [
                null,
                null,
                false,
            ],
            // Remote IP set
            [
                '101.0.0.25',
                null,
                '101.0.0.25',
            ],
            // Proxy
            [
                '101.0.0.25',
                '192.168.10.10',
                '192.168.10.10',
            ],
            // Several proxies
            [
                '101.0.0.25',
                '192.168.10.1, 192.168.100.100',
                '192.168.10.1',
            ],
            // Invalid proxy
            [
                '101.0.0.25',
                'invalid',
                false,
            ],
            // Direct IP with proxy enabled
            [
                '101.0.0.25',
                '192.168.10.10',
                '101.0.0.25',
                '10.10.10.10',
            ],
        ];
    }

    /**
     * Test for ipMaskTest
     */
    public function testIpMaskTest(): void
    {
        //IPV4 testing
        $testRange = '255.255.0.0/8';
        $ipToTest = '10.0.0.0';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );

        $testRange = '255.255.0.0/4';
        $ipToTest = '255.3.0.0';
        $this->assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );

        $testRange = '255.255.0.[0-10]';
        $ipToTest = '255.3.0.3';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = '255.3.0.12';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );

        //IPV6 testing
        //not range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:1020';
        $this->assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:2020';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );

        //range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $this->assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:3020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );

        //CDIR
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $this->assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1000';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:3020/24';
        $this->assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest)
        );
    }

    /**
     * Test for allowDeny
     */
    public function testAllowDeny(): void
    {
        $_SERVER['REMOTE_ADDR'] = '';
        $this->assertFalse(
            $this->ipAllowDeny->allow()
        );

        $_SERVER['REMOTE_ADDR'] = '255.0.1.0';
        $this->assertTrue(
            $this->ipAllowDeny->allow()
        );
        $_SERVER['REMOTE_ADDR'] = '10.0.0.0';
        $this->assertFalse(
            $this->ipAllowDeny->allow()
        );

        $_SERVER['REMOTE_ADDR'] = '255.255.0.1';
        $this->assertTrue(
            $this->ipAllowDeny->deny()
        );
        $_SERVER['REMOTE_ADDR'] = '255.124.0.5';
        $this->assertTrue(
            $this->ipAllowDeny->deny()
        );
        $_SERVER['REMOTE_ADDR'] = '122.124.0.5';
        $this->assertFalse(
            $this->ipAllowDeny->deny()
        );

        //IPV6
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        $this->assertTrue(
            $this->ipAllowDeny->allow()
        );
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1000';
        $this->assertFalse(
            $this->ipAllowDeny->allow()
        );
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        $this->assertTrue(
            $this->ipAllowDeny->allow()
        );
    }
}
