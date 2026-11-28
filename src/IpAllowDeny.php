<?php
/**
 * This library is used with the server IP allow/deny host authentication
 * feature
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function bin2hex;
use function count;
use function explode;
use function filter_var;
use function hash_equals;
use function hexdec;
use function inet_ntop;
use function inet_pton;
use function intdiv;
use function mb_strtolower;
use function min;
use function ord;
use function preg_match;
use function preg_split;
use function str_contains;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const PREG_SPLIT_NO_EMPTY;

/**
 * PhpMyAdmin\IpAllowDeny class
 */
class IpAllowDeny
{
    private readonly Config $config;

    public function __construct()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Matches for IPv4 or IPv6 addresses
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    public function ipMaskTest(string $testRange, string $ipToTest): bool
    {
        $mappedIpv4 = $this->getMappedIpv4($ipToTest);
        if ($mappedIpv4 !== null && $this->ipMaskTest($testRange, $mappedIpv4)) {
            return true;
        }

        if (str_contains($testRange, ':') || str_contains($ipToTest, ':')) {
            // assume IPv6
            return $this->ipv6MaskTest($testRange, $ipToTest);
        }

        return $this->ipv4MaskTest($testRange, $ipToTest);
    }

    /**
     * Returns the IPv4 address from an IPv4-mapped IPv6 address, for example 10.0.0.5 from ::ffff:10.0.0.5
     */
    private function getMappedIpv4(string $ip): string|null
    {
        $ipBin = inet_pton($ip);
        if ($ipBin === false || strlen($ipBin) !== 16 || ! str_starts_with($ipBin, str_repeat("\0", 10) . "\xff\xff")) {
            return null;
        }

        $ipv4 = inet_ntop(substr($ipBin, 12));

        return $ipv4 === false ? null : $ipv4;
    }

    /**
     * Based on IP Pattern Matcher
     * Originally by J.Adams <jna@retina.net>
     * Found on <https://www.php.net/manual/en/function.ip2long.php>
     * Modified for phpMyAdmin
     *
     * Matches:
     * xxx.xxx.xxx.xxx        (exact)
     * xxx.xxx.xxx.[yyy-zzz]  (range)
     * xxx.xxx.xxx.xxx/nn     (CIDR)
     *
     * Does not match:
     * xxx.xxx.xxx.xx[yyy-zzz]  (range, partial octets not supported)
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    private function ipv4MaskTest(string $testRange, string $ipToTest): bool
    {
        if (preg_match('|^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/([0-9]+)$|', $testRange, $regs) === 1) {
            return $this->cidrMatch($regs[1], (int) $regs[2], $ipToTest);
        }

        // range based
        $maskocts = explode('.', $testRange);
        $ipocts = explode('.', $ipToTest);
        if (count($maskocts) !== 4 || count($ipocts) !== 4) {
            return false;
        }

        // perform a range match
        for ($i = 0; $i < 4; $i++) {
            if (preg_match('|^\[([0-9]+)\-([0-9]+)\]$|', $maskocts[$i], $regs) === 1) {
                if ($ipocts[$i] > $regs[2] || $ipocts[$i] < $regs[1]) {
                    return false;
                }
            } elseif ($maskocts[$i] !== $ipocts[$i]) {
                return false;
            }
        }

        return true;
    }

    private function cidrMatch(string $subnet, int $prefix, string $ipToTest): bool
    {
        $subnetBin = inet_pton($subnet);
        $ipBin = inet_pton($ipToTest);
        if ($subnetBin === false || $ipBin === false) {
            return false;
        }

        // A /0 prefix matches every address of both families, so the 'all' shortcut (0.0.0.0/0) covers IPv6 clients
        if ($prefix === 0) {
            return true;
        }

        if (strlen($subnetBin) !== strlen($ipBin)) {
            return false;
        }

        $prefix = min($prefix, strlen($subnetBin) * 8);
        $fullBytes = intdiv($prefix, 8);
        if (substr($subnetBin, 0, $fullBytes) !== substr($ipBin, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << 8 - $remainingBits) & 0xFF;

        return (ord($subnetBin[$fullBytes]) & $mask) === (ord($ipBin[$fullBytes]) & $mask);
    }

    /**
     * IPv6 matcher
     *
     * Matches:
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx
     * (exact)
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:[yyyy-zzzz]
     * (range, only at end of IP - no subnets)
     * xxxx:xxxx:xxxx:xxxx/nn
     * (CIDR)
     *
     * Does not match:
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xx[yyy-zzz]
     * (range, partial octets not supported)
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    private function ipv6MaskTest(string $testRange, string $ipToTest): bool
    {
        $testRange = mb_strtolower($testRange);
        $ipToTest = mb_strtolower($ipToTest);

        $ipHex = bin2hex((string) inet_pton($ipToTest));

        if (str_contains($testRange, '[')) {
            $rangeMatch = [];
            if (preg_match('/\[([0-9a-f]+)\-([0-9a-f]+)\]/', $testRange, $rangeMatch) === 1) {
                $rangeStart = $rangeMatch[1];
                $rangeEnd = $rangeMatch[2];

                // get the first and last allowed IPs
                $firstIp = str_replace($rangeMatch[0], $rangeStart, $testRange);
                $firstHex = bin2hex((string) inet_pton($firstIp));
                $lastIp = str_replace($rangeMatch[0], $rangeEnd, $testRange);
                $lastHex = bin2hex((string) inet_pton($lastIp));

                if ($ipHex === '' || strlen($ipHex) !== strlen($firstHex)) {
                    return false;
                }

                // check if the IP to test is within the range
                return $ipHex >= $firstHex && $ipHex <= $lastHex;
            }

            return false;
        }

        if (str_contains($testRange, '/')) {
            if (preg_match('|^([^/]+)/([0-9]+)$|', $testRange, $regs) !== 1) {
                return false;
            }

            return $this->cidrMatch($regs[1], (int) $regs[2], $ipToTest);
        }

        $rangeHex = bin2hex((string) inet_pton($testRange));

        return hash_equals($ipHex, $rangeHex);
    }

    /**
     * Runs through IP Allow rules the use of it below for more information
     *
     * @see     Core::getIp()
     */
    public function allow(): bool
    {
        return $this->allowDeny('allow');
    }

    /**
     * Runs through IP Deny rules the use of it below for more information
     *
     * @see     Core::getIp()
     */
    public function deny(): bool
    {
        return $this->allowDeny('deny');
    }

    /**
     * Runs through IP Allow/Deny rules the use of it below for more information
     *
     * @see     Core::getIp()
     *
     * @param string $type 'allow' | 'deny' type of rule to match
     */
    private function allowDeny(string $type): bool
    {
        $rules = $this->parseRules();
        if ($rules === null) {
            // No allow rule matches and every deny rule matches, so every rule order denies access
            return $type === 'deny';
        }

        $remoteIp = Core::getIp();
        if ($remoteIp === '' || $remoteIp === false) {
            return $type === 'deny';
        }

        $username = $this->config->selectedServer['user'];

        foreach ($rules as $rule) {
            if ($rule['type'] !== $type) {
                continue;
            }

            if ($rule['user'] !== '%' && ! hash_equals($rule['user'], $username)) {
                continue;
            }

            if ($this->ipMaskTest($rule['mask'], $remoteIp)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array{type: 'allow'|'deny', user: string, mask: string}>|null */
    private function parseRules(): array|null
    {
        // lookup table for some name shortcuts
        $shortcuts = ['all' => ['0.0.0.0/0'], 'localhost' => ['127.0.0.1/8', '::1/128']];

        // The prefix lengths are IPv4 network sizes, on IPv6 they would cover a large part of the internet
        $serverAddress = Core::getEnv('SERVER_ADDR');
        if (filter_var($serverAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $shortcuts['localnetA'] = [$serverAddress . '/8'];
            $shortcuts['localnetB'] = [$serverAddress . '/16'];
            $shortcuts['localnetC'] = [$serverAddress . '/24'];
        }

        $rules = [];
        foreach ($this->config->selectedServer['AllowDeny']['rules'] as $rule) {
            $parts = preg_split('/\s+/', trim($rule), -1, PREG_SPLIT_NO_EMPTY);
            if ($parts === false) {
                return null;
            }

            if (count($parts) === 4 && $parts[2] === 'from') {
                $mask = $parts[3];
            } elseif (count($parts) === 3 && $parts[2] !== 'from') {
                $mask = $parts[2];
            } else {
                return null;
            }

            $type = $parts[0];
            if ($type !== 'allow' && $type !== 'deny') {
                return null;
            }

            foreach ($shortcuts[$mask] ?? [$mask] as $resolvedMask) {
                if (! $this->isValidMask($resolvedMask)) {
                    return null;
                }

                $rules[] = ['type' => $type, 'user' => $parts[1], 'mask' => $resolvedMask];
            }
        }

        return $rules;
    }

    private function isValidMask(string $mask): bool
    {
        if (preg_match('|^([^/]+)/([0-9]+)$|', $mask, $regs) === 1) {
            return filter_var($regs[1], FILTER_VALIDATE_IP) !== false
                && (int) $regs[2] <= (str_contains($regs[1], ':') ? 128 : 32);
        }

        if (str_contains($mask, ':')) {
            // The range must be the whole last group, because ipv6MaskTest() compares whole addresses
            if (preg_match('/^([0-9a-f:]*:)\[([0-9a-f]+)-([0-9a-f]+)\]$/i', $mask, $regs) === 1) {
                return filter_var($regs[1] . $regs[2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
                    && filter_var($regs[1] . $regs[3], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
                    && hexdec($regs[2]) <= hexdec($regs[3]);
            }

            return filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        if (! str_contains($mask, '[')) {
            return filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        }

        $octets = explode('.', $mask);
        if (count($octets) !== 4) {
            return false;
        }

        foreach ($octets as $octet) {
            if (preg_match('/^\[([0-9]+)-([0-9]+)\]$/', $octet, $regs) === 1) {
                $first = (int) $regs[1];
                $last = (int) $regs[2];
            } elseif (preg_match('/^(?:0|[1-9][0-9]*)$/', $octet) === 1) {
                $first = $last = (int) $octet;
            } else {
                return false;
            }

            if ($first > $last || $last > 255) {
                return false;
            }
        }

        return true;
    }
}
