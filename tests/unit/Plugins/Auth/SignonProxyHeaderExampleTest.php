<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Auth;

use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use function file_get_contents;
use function get_login_credentials;
use function ini_set;
use function signon_header_is_ambiguous;
use function signon_is_trusted_proxy;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Tests examples/signon-proxy-header.php.
 *
 * The example declares global functions, so it runs in its own process; each test covers many cases.
 */
#[CoversNothing]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class SignonProxyHeaderExampleTest extends AbstractTestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = (string) tempnam(sys_get_temp_dir(), 'pma-signon');

        /** @psalm-suppress UnresolvableInclude */
        require_once __DIR__ . '/../../../../examples/signon-proxy-header.php';
    }

    public function testTrustedProxyCheck(): void
    {
        $cases = [
            'no trusted proxies' => ['10.0.0.5', [], false],
            'invalid remote address' => ['not-an-ip', ['10.0.0.5'], false],
            'exact match' => ['10.0.0.5', ['192.168.1.1', '10.0.0.5'], true],
            'other address' => ['10.0.0.6', ['10.0.0.5'], false],
            'CIDR match' => ['172.18.0.3', ['172.16.0.0/12'], true],
            'CIDR mismatch' => ['172.32.0.3', ['172.16.0.0/12'], false],
            'CIDR boundary inside' => ['10.0.0.127', ['10.0.0.0/25'], true],
            'CIDR boundary outside' => ['10.0.0.128', ['10.0.0.0/25'], false],
            'CIDR /32 neighbour' => ['10.0.0.4', ['10.0.0.5/32'], false],
            'CIDR /0' => ['203.0.113.9', ['0.0.0.0/0'], true],
            'range syntax is not supported' => ['10.0.0.7', ['10.0.0.[5-9]'], false],
            'IPv6 exact match' => ['::1', ['::1'], true],
            'IPv6 exact match in other notation' => ['fd00::5', ['FD00:0:0:0:0:0:0:5'], true],
            'IPv6 CIDR match' => ['fd00::5', ['fd00::/8'], true],
            'IPv6 CIDR mismatch' => ['fe00::5', ['fd00::/8'], false],
            'IPv6 /128 neighbour' => ['2001:470:1:2::4', ['2001:470:1:2::5/128'], false],
            'IPv4 client and IPv6 proxy' => ['10.0.0.5', ['::1'], false],
            'IPv6 client and IPv4 proxy' => ['::1', ['10.0.0.5', '10.0.0.0/8'], false],
            'IPv4-mapped client and IPv4 proxy' => ['::ffff:10.0.0.5', ['10.0.0.5'], true],
            'IPv4-mapped network' => ['10.0.0.5', ['::ffff:10.0.0.0/104'], true],
            'IPv4-mapped network mismatch' => ['10.1.0.5', ['::ffff:10.0.0.0/120'], false],
            'IPv4-mapped network with too short prefix' => ['10.0.0.5', ['::ffff:10.0.0.0/64'], false],
            'malformed proxy entry' => ['10.0.0.5', ['10.0.0', 'proxy.example.com', ''], false],
            // Remote addresses that a lenient parser would let through.
            'bracketed IPv6 entry' => ['::1', ['[::1]'], false],
            'malformed IPv6 CIDR' => ['fd00::5', ['fd00:/8'], false],
            'IPv6 with zone and prefix' => ['fe80::2', ['fe80::1%eth0/64'], false],
            'non numeric prefix' => ['::1', ['::1/abc'], false],
            'empty prefix' => ['10.0.0.5', ['10.0.0.5/'], false],
            'negative prefix' => ['10.0.0.5', ['10.0.0.0/-8'], false],
            'prefix too long' => ['10.0.0.5', ['10.0.0.5/33'], false],
            'huge prefix' => ['10.0.0.5', ['10.0.0.5/99999999999999999999'], false],
        ];

        foreach ($cases as $name => [$remoteAddress, $trustedProxies, $expected]) {
            /** @psalm-suppress UndefinedFunction */
            self::assertSame($expected, signon_is_trusted_proxy($remoteAddress, $trustedProxies), $name);
        }
    }

    public function testAmbiguousHeaders(): void
    {
        $cases = [
            'only the header' => [['Host', 'X-Forwarded-Email'], false],
            'different case' => [['x-forwarded-email'], false],
            'no header' => [['Host'], false],
            'header twice' => [['X-Forwarded-Email', 'x-forwarded-email'], true],
            'underscore look-alike' => [['X_Forwarded_Email'], true],
            'underscore look-alike next to the header' => [['X-Forwarded-Email', 'X_Forwarded_Email'], true],
            'dot look-alike' => [['X-Forwarded-Email', 'X.Forwarded.Email'], true],
            'space look-alike' => [['X Forwarded Email'], true],
            'unrelated similar header' => [['X-Forwarded-Emails'], false],
        ];

        foreach ($cases as $name => [$headers, $expected]) {
            /** @psalm-suppress UndefinedFunction */
            self::assertSame($expected, signon_header_is_ambiguous($headers, 'X-Forwarded-Email'), $name);
        }
    }

    public function testCredentials(): void
    {
        $alice = ['alice', 'password of alice', 'identity' => 'alice@example.com'];
        $kelvin = "alice@example.\u{212A}om";
        $cases = [
            'mapped identity' => ['127.0.0.1', 'alice@example.com', 1, $alice],
            'case is ignored' => ['127.0.0.1', 'ALICE@Example.COM', 1, $alice],
            'surrounding whitespace' => ['127.0.0.1', "  alice@example.com\t", 1, $alice],
            'other server' => ['127.0.0.1', 'alice@example.com', 2, ['', '', 'identity' => 'alice@example.com']],
            'unknown identity' => ['127.0.0.1', 'Carol@example.com', 1, ['', '', 'identity' => 'carol@example.com']],
            'untrusted address' => ['203.0.113.9', 'alice@example.com', 1, ['', '']],
            'no address' => [null, 'alice@example.com', 1, ['', '']],
            'no header' => ['127.0.0.1', null, 1, ['', '']],
            'empty header' => ['127.0.0.1', '   ', 1, ['', '']],
            'control characters' => ['127.0.0.1', "alice@example.com\nbob@example.com", 1, ['', '']],
            'invalid UTF-8' => ['127.0.0.1', "alice@example.com\xFF", 1, ['', '']],
            // U+212A KELVIN SIGN is lower-cased to "k" by mb_strtolower(), but is a different identity.
            'look-alike unicode letter' => ['127.0.0.1', $kelvin, 1, ['', '', 'identity' => $kelvin]],
        ];

        // The example reports refused requests to the PHP error log. Newer PHPUnit versions take over the
        // error log while a test runs, so it is redirected here instead of in setUp().
        $previousErrorLog = ini_set('error_log', $this->logFile);

        foreach ($cases as $name => [$remoteAddress, $header, $server, $expected]) {
            $_SERVER = [];
            if ($remoteAddress !== null) {
                $_SERVER['REMOTE_ADDR'] = $remoteAddress;
            }

            if ($header !== null) {
                $_SERVER['HTTP_X_FORWARDED_EMAIL'] = $header;
            }

            /** @psalm-suppress UndefinedFunction */
            self::assertSame($expected, get_login_credentials('', $server), $name);
        }

        ini_set('error_log', (string) $previousErrorLog);

        $log = (string) file_get_contents($this->logFile);
        self::assertStringContainsString(
            "phpMyAdmin signon: ignoring the X-Forwarded-Email header from untrusted address '203.0.113.9'",
            $log,
        );
        self::assertStringContainsString('from untrusted address NULL', $log);
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);

        parent::tearDown();
    }
}
