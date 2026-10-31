<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @covers \PhpMyAdmin\IpAllowDeny
 */
#[CoversClass(IpAllowDeny::class)]
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
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'allow % 255.255.255.0/4';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'allow % from 255.255.2.0/4';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:1020';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:3020/24';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'deny % 255.255.0.0/8';
        $GLOBALS['cfg']['Server']['AllowDeny']['rules'][] = 'deny % from 255.255.0.0/8';

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
    #[DataProvider('proxyIPs')]
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

        self::assertSame($expected, Core::getIp());

        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $GLOBALS['cfg']['TrustedProxies'] = [];
    }

    /**
     * Data provider for Core::getIp tests
     *
     * @return array
     */
    public static function proxyIPs(): array
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
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));

        $testRange = '255.255.0.0/4';
        $ipToTest = '255.3.0.0';
        self::assertTrue($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));

        $testRange = '255.255.0.[0-10]';
        $ipToTest = '255.3.0.3';
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
        $ipToTest = '255.3.0.12';
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));

        //IPV6 testing
        //not range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:2020';
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));

        //range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertTrue($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:3020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));

        //CDIR
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertTrue($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1000';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:3020/24';
        self::assertFalse($this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
    }

    /** @dataProvider providerIpv4Cidr */
    #[DataProvider('providerIpv4Cidr')]
    public function testIpv4MaskTestWithCidr(string $testRange, string $ipToTest, bool $expected): void
    {
        self::assertSame($expected, $this->ipAllowDeny->ipv4MaskTest($testRange, $ipToTest));
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function providerIpv4Cidr(): iterable
    {
        yield 'first bit differs /8' => ['10.0.0.0/8', '138.0.0.1', false];
        yield 'first bit differs /24' => ['192.168.1.0/24', '64.168.1.7', false];
        yield 'first bit differs /12' => ['172.16.0.0/12', '44.16.0.1', false];
        yield 'localhost shortcut' => ['127.0.0.1/8', '255.0.0.1', false];
        yield 'inside /8' => ['10.0.0.0/8', '10.255.255.255', true];
        yield 'inside /12' => ['172.16.0.0/12', '172.31.255.255', true];
        yield 'outside /12' => ['172.16.0.0/12', '172.32.0.0', false];
        yield 'inside /24' => ['192.168.1.0/24', '192.168.1.200', true];
        yield 'outside /24' => ['192.168.1.0/24', '192.168.2.1', false];
        yield 'any address /0' => ['0.0.0.0/0', '200.1.2.3', true];
        yield 'inside /1' => ['128.0.0.0/1', '200.1.2.3', true];
        yield 'outside /1' => ['128.0.0.0/1', '100.1.2.3', false];
        yield 'exact /32' => ['1.2.3.4/32', '1.2.3.4', true];
        yield 'other /32' => ['1.2.3.4/32', '1.2.3.5', false];
        yield 'prefix too long, same address' => ['1.2.3.4/33', '1.2.3.4', true];
        yield 'prefix too long, other address' => ['1.2.3.4/33', '1.2.3.5', false];
        yield 'invalid subnet' => ['1.2.3.256/8', '1.2.3.4', false];
        yield 'invalid address' => ['1.2.3.0/24', 'invalid', false];
    }

    /**
     * Test for allowDeny
     */
    public function testAllowDeny(): void
    {
        $_SERVER['REMOTE_ADDR'] = '';
        self::assertFalse($this->ipAllowDeny->allow());

        $_SERVER['REMOTE_ADDR'] = '255.0.1.0';
        self::assertTrue($this->ipAllowDeny->allow());
        $_SERVER['REMOTE_ADDR'] = '10.0.0.0';
        self::assertFalse($this->ipAllowDeny->allow());

        $_SERVER['REMOTE_ADDR'] = '255.255.0.1';
        self::assertTrue($this->ipAllowDeny->deny());
        $_SERVER['REMOTE_ADDR'] = '255.124.0.5';
        self::assertTrue($this->ipAllowDeny->deny());
        $_SERVER['REMOTE_ADDR'] = '122.124.0.5';
        self::assertFalse($this->ipAllowDeny->deny());

        //IPV6
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue($this->ipAllowDeny->allow());
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1000';
        self::assertFalse($this->ipAllowDeny->allow());
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue($this->ipAllowDeny->allow());
    }
}
