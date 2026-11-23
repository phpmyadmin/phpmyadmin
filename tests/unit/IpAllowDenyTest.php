<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\IpAllowDeny;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(IpAllowDeny::class)]
class IpAllowDenyTest extends AbstractTestCase
{
    private IpAllowDeny $ipAllowDeny;

    private Config $config;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->config = Config::getInstance();
        $config->selectedServer['user'] = 'pma_username';
        $config->selectedServer['AllowDeny']['rules'][] = 'allow % 255.255.255.0/4';
        $config->selectedServer['AllowDeny']['rules'][] = 'allow % from 255.255.2.0/4';
        $config->selectedServer['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:1020';
        $config->selectedServer['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        $config->selectedServer['AllowDeny']['rules'][] = 'allow % from 2001:4998:c:a0d:0000:0000:4998:3020/24';
        $config->selectedServer['AllowDeny']['rules'][] = 'deny % 255.255.0.0/8';
        $config->selectedServer['AllowDeny']['rules'][] = 'deny % from 255.255.0.0/8';

        $this->ipAllowDeny = new IpAllowDeny();
    }

    /**
     * Test for Core::getIp
     *
     * @param string|null $remote   remote
     * @param string|null $header   header
     * @param string|bool $expected expected result
     * @param string      $proxyip  proxyip
     */
    #[DataProvider('proxyIPs')]
    public function testGetIp(
        string|null $remote,
        string|null $header,
        string|bool $expected,
        string|null $proxyip = null,
    ): void {
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $config = Config::getInstance();
        $config->settings['TrustedProxies'] = [];

        if ($remote !== null) {
            $_SERVER['REMOTE_ADDR'] = $remote;
        }

        if ($header !== null) {
            if ($proxyip === null) {
                $proxyip = $remote;
            }

            $config->settings['TrustedProxies'][$proxyip] = 'TEST_FORWARDED_HEADER';
            $_SERVER['TEST_FORWARDED_HEADER'] = $header;
        }

        self::assertSame(
            $expected,
            Core::getIp(),
        );

        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['TEST_FORWARDED_HEADER']);
        $config->settings['TrustedProxies'] = [];
    }

    /**
     * Data provider for Core::getIp tests
     *
     * @return array<int, array<string|bool|null>>
     */
    public static function proxyIPs(): array
    {
        return [
            // Nothing set
            [null, null, false],
            // Remote IP set
            ['101.0.0.25', null, '101.0.0.25'],
            // Proxy
            ['101.0.0.25', '192.168.10.10', '192.168.10.10'],
            // Several proxies
            ['101.0.0.25', '192.168.10.1, 192.168.100.100', '192.168.10.1'],
            // Invalid proxy
            ['101.0.0.25', 'invalid', false],
            // Direct IP with proxy enabled
            ['101.0.0.25', '192.168.10.10', '101.0.0.25', '10.10.10.10'],
        ];
    }

    #[DataProvider('providerIpMaskTest')]
    public function testIpMaskTest(string $testRange, string $ipToTest, bool $expected): void
    {
        self::assertSame($expected, $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest));
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function providerIpMaskTest(): iterable
    {
        yield 'IPv4 CIDR, host bits set, outside /8' => ['255.255.0.0/8', '10.0.0.0', false];
        yield 'IPv4 CIDR, host bits set, inside /4' => ['255.255.0.0/4', '255.3.0.0', true];
        yield 'IPv4 octet differs' => ['255.255.0.[0-10]', '255.3.0.3', false];
        yield 'IPv4 octet differs, last octet outside range' => ['255.255.0.[0-10]', '255.3.0.12', false];
        yield 'IPv6 exact' => ['2001:4998:c:a0d:0000:0000:4998:1020', '2001:4998:c:a0d:0000:0000:4998:1020', true];
        yield 'IPv6 exact, other address' => [
            '2001:4998:c:a0d:0000:0000:4998:2020',
            '2001:4998:c:a0d:0000:0000:4998:1020',
            false,
        ];

        yield 'IPv6 inside range' => [
            '2001:4998:c:a0d:0000:0000:4998:[1001-2010]',
            '2001:4998:c:a0d:0000:0000:4998:1020',
            true,
        ];

        yield 'IPv6 outside range' => [
            '2001:4998:c:a0d:0000:0000:4998:[1001-2010]',
            '2001:4998:c:a0d:0000:0000:4998:3020',
            false,
        ];

        yield 'IPv6 outside CIDR' => [
            '2001:4998:c:a0d:0000:0000:4998:3020/115',
            '2001:4998:c:a0d:0000:0000:4998:1000',
            false,
        ];

        yield 'IPv4 CIDR, first bit differs /8' => ['10.0.0.0/8', '138.0.0.1', false];
        yield 'IPv4 CIDR, first bit differs /24' => ['192.168.1.0/24', '64.168.1.7', false];
        yield 'IPv4 CIDR, first bit differs /12' => ['172.16.0.0/12', '44.16.0.1', false];
        yield 'IPv4 CIDR, localhost shortcut' => ['127.0.0.1/8', '255.0.0.1', false];
        yield 'IPv4 CIDR, inside /8' => ['10.0.0.0/8', '10.255.255.255', true];
        yield 'IPv4 CIDR, outside /12' => ['172.16.0.0/12', '172.32.0.0', false];
        yield 'IPv4 CIDR, inside /24' => ['192.168.1.0/24', '192.168.1.200', true];
        yield 'IPv4 CIDR, any address /0' => ['0.0.0.0/0', '200.1.2.3', true];
        yield 'IPv4 CIDR, outside /1' => ['128.0.0.0/1', '100.1.2.3', false];
        yield 'IPv4 CIDR, exact /32' => ['1.2.3.4/32', '1.2.3.4', true];
        yield 'IPv4 CIDR, prefix too long, same address' => ['1.2.3.4/33', '1.2.3.4', true];
        yield 'IPv4 CIDR, invalid subnet' => ['1.2.3.256/8', '1.2.3.4', false];
        yield 'IPv4 CIDR, invalid address' => ['1.2.3.0/24', 'invalid', false];
        yield 'IPv4 inside range' => ['10.0.0.[1-5]', '10.0.0.3', true];
        yield 'IPv4 outside range' => ['10.0.0.[1-5]', '10.0.0.6', false];
        yield 'IPv4 partial octet range' => ['10.0.0.1[0-5]', '10.0.0.3', false];
        yield 'IPv4 too few octets' => ['10.0.0', '10.0.0.3', false];
        yield 'IPv4 too many octets' => ['10.0.0.3.9', '10.0.0.3', false];
        yield 'IPv4 range, invalid address' => ['10.0.0.[1-5]', 'invalid', false];
        yield 'IPv6 malformed range' => ['2001:db8::[1-', '2001:db8::5', false];
        yield 'IPv6 CIDR, host bits set, lower address' => ['2001:db8::5/64', '2001:db8::1', true];
        yield 'IPv6 CIDR, invalid prefix' => ['2001:db8::/zz', 'fe80::1', false];
        yield 'IPv4 CIDR, any address /0, IPv6 client' => ['0.0.0.0/0', '2001:db8::1', true];
        yield 'IPv4 CIDR, IPv6 client' => ['127.0.0.1/8', '::1', false];
    }

    /**
     * Test for allowDeny
     */
    public function testAllowDeny(): void
    {
        $_SERVER['REMOTE_ADDR'] = '';
        self::assertFalse(
            $this->ipAllowDeny->allow(),
        );

        $_SERVER['REMOTE_ADDR'] = '255.0.1.0';
        self::assertTrue(
            $this->ipAllowDeny->allow(),
        );
        $_SERVER['REMOTE_ADDR'] = '10.0.0.0';
        self::assertFalse(
            $this->ipAllowDeny->allow(),
        );

        $_SERVER['REMOTE_ADDR'] = '255.255.0.1';
        self::assertTrue(
            $this->ipAllowDeny->deny(),
        );
        $_SERVER['REMOTE_ADDR'] = '255.124.0.5';
        self::assertTrue(
            $this->ipAllowDeny->deny(),
        );
        $_SERVER['REMOTE_ADDR'] = '122.124.0.5';
        self::assertFalse(
            $this->ipAllowDeny->deny(),
        );

        //IPV6
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue(
            $this->ipAllowDeny->allow(),
        );
        $_SERVER['REMOTE_ADDR'] = '2001:4a98:c:a0d:0000:0000:4998:1000';
        self::assertFalse(
            $this->ipAllowDeny->allow(),
        );
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue(
            $this->ipAllowDeny->allow(),
        );
    }

    #[DataProvider('providerValidRule')]
    public function testValidRule(string $rule, bool $expected): void
    {
        $this->config->selectedServer['AllowDeny']['rules'] = [$rule];
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        self::assertSame($expected, $this->ipAllowDeny->deny());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function providerValidRule(): iterable
    {
        yield 'IPv4 exact' => ['deny % from 1.2.3.4', true];
        yield 'IPv4 range' => ['deny % from 10.0.0.[1-5]', false];
        yield 'CIDR' => ['deny % from 1.2.3.0/24', true];
        yield 'IPv6 exact' => ['deny % from 2001:db8::1', false];
        yield 'IPv6 range' => ['deny % from 2001:db8::[1-ff]', false];
        yield 'shortcut' => ['deny % from all', true];
        yield 'without from' => ['deny % 1.2.3.4', true];
        yield 'extra whitespace' => [" deny\t%   from  1.2.3.4 ", true];
    }

    #[DataProvider('providerInvalidRule')]
    public function testInvalidRuleDeniesEveryone(string $rule): void
    {
        $this->config->selectedServer['AllowDeny']['rules'] = ['allow % from 1.2.3.4', $rule];
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        self::assertFalse($this->ipAllowDeny->allow());
        self::assertTrue($this->ipAllowDeny->deny());
    }

    /** @return iterable<string, array{string}> */
    public static function providerInvalidRule(): iterable
    {
        yield 'empty' => [''];
        yield 'type only' => ['deny'];
        yield 'missing mask' => ['deny % from'];
        yield 'unknown type' => ['Deny % from 1.2.3.4'];
        yield 'two masks' => ['deny % from 1.2.3.4 5.6.7.8'];
        yield 'unknown shortcut' => ['deny % from everyone'];
        yield 'CIDR, invalid address' => ['deny % from 1.2.3.256/8'];
        yield 'CIDR, invalid prefix' => ['deny % from 1.2.3.0/x'];
        yield 'IPv6 range, invalid address' => ['deny % from 2001::db8::[1-2]'];
        yield 'IPv6 range, unclosed bracket' => ['allow root from 2001:db8::[1-'];
        yield 'IPv6 range, partial group' => ['deny % from 2001:db8::1[0-5]'];
        yield 'IPv6 range, not in last group' => ['deny % from 2001:db8:[1-2]::1'];
        yield 'IPv6, invalid address' => ['deny % from 2001:db8::g'];
        yield 'IPv4, invalid address' => ['deny % from 1.2.3.256'];
        yield 'IPv4 range, too few octets' => ['deny % from 10.0.[1-5]'];
        yield 'IPv4 range, partial octet' => ['deny % from 10.0.0.1[0-5]'];
    }
}
