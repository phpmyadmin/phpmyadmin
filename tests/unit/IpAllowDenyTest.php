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

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = Config::getInstance();
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

    /**
     * Test for ipMaskTest
     */
    public function testIpMaskTest(): void
    {
        //IPV4 testing
        $testRange = '255.255.0.0/8';
        $ipToTest = '10.0.0.0';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );

        $testRange = '255.255.0.0/4';
        $ipToTest = '255.3.0.0';
        self::assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );

        $testRange = '255.255.0.[0-10]';
        $ipToTest = '255.3.0.3';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );
        $ipToTest = '255.3.0.12';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );

        //IPV6 testing
        //not range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:2020';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );

        //range
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:3020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );

        //CDIR
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1020';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:[1001-2010]';
        self::assertTrue(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );
        $ipToTest = '2001:4998:c:a0d:0000:0000:4998:1000';
        $testRange = '2001:4998:c:a0d:0000:0000:4998:3020/24';
        self::assertFalse(
            $this->ipAllowDeny->ipMaskTest($testRange, $ipToTest),
        );
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
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1000';
        self::assertFalse(
            $this->ipAllowDeny->allow(),
        );
        $_SERVER['REMOTE_ADDR'] = '2001:4998:c:a0d:0000:0000:4998:1020';
        self::assertTrue(
            $this->ipAllowDeny->allow(),
        );
    }
}
