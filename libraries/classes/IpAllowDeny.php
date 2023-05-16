<?php
/**
 * This library is used with the server IP allow/deny host authentication
 * feature
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function bin2hex;
use function dechex;
use function explode;
use function hash_equals;
use function hexdec;
use function inet_pton;
use function ip2long;
use function is_array;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function min;
use function preg_match;
use function str_replace;
use function substr_replace;

/**
 * PhpMyAdmin\IpAllowDeny class
 */
class IpAllowDeny
{
    /**
     * Matches for IPv4 or IPv6 addresses
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    public function ipMaskTest(string $testRange, string $ipToTest): bool
    {
        if (mb_strpos($testRange, ':') > -1 || mb_strpos($ipToTest, ':') > -1) {
            // assume IPv6
            return $this->ipv6MaskTest($testRange, $ipToTest);
        }

        return $this->ipv4MaskTest($testRange, $ipToTest);
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
    public function ipv4MaskTest(string $testRange, string $ipToTest): bool
    {
        $result = true;
        $match = preg_match('|([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/([0-9]+)|', $testRange, $regs);
        if ($match) {
            // performs a mask match
            $ipl = ip2long($ipToTest);
            $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

            $maskl = 0;

            for ($i = 0; $i < 31; $i++) {
                if ($i >= $regs[5] - 1) {
                    continue;
                }

                $maskl += 2 ** (30 - $i);
            }

            return ($maskl & $rangel) === ($maskl & $ipl);
        }

        // range based
        $maskocts = explode('.', $testRange);
        $ipocts = explode('.', $ipToTest);

        // perform a range match
        for ($i = 0; $i < 4; $i++) {
            if (preg_match('|\[([0-9]+)\-([0-9]+)\]|', $maskocts[$i], $regs)) {
                if (($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1])) {
                    $result = false;
                }
            } elseif ($maskocts[$i] !== $ipocts[$i]) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * IPv6 matcher
     * CIDR section taken from https://stackoverflow.com/a/10086404
     * Modified for phpMyAdmin
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
    public function ipv6MaskTest(string $testRange, string $ipToTest): bool
    {
        $result = true;

        // convert to lowercase for easier comparison
        $testRange = mb_strtolower($testRange);
        $ipToTest = mb_strtolower($ipToTest);

        $isCidr = mb_strpos($testRange, '/') > -1;
        $isRange = mb_strpos($testRange, '[') > -1;
        $isSingle = ! $isCidr && ! $isRange;

        $ipHex = bin2hex((string) inet_pton($ipToTest));

        if ($isSingle) {
            $rangeHex = bin2hex((string) inet_pton($testRange));

            return hash_equals($ipHex, $rangeHex);
        }

        if ($isRange) {
            // what range do we operate on?
            $rangeMatch = [];
            $match = preg_match('/\[([0-9a-f]+)\-([0-9a-f]+)\]/', $testRange, $rangeMatch);
            if ($match) {
                $rangeStart = $rangeMatch[1];
                $rangeEnd = $rangeMatch[2];

                // get the first and last allowed IPs
                $firstIp = str_replace($rangeMatch[0], $rangeStart, $testRange);
                $firstHex = bin2hex((string) inet_pton($firstIp));
                $lastIp = str_replace($rangeMatch[0], $rangeEnd, $testRange);
                $lastHex = bin2hex((string) inet_pton($lastIp));

                // check if the IP to test is within the range
                $result = ($ipHex >= $firstHex && $ipHex <= $lastHex);
            }

            return $result;
        }

        if ($isCidr) {
            // Split in address and prefix length
            [$firstIp, $subnet] = explode('/', $testRange);

            // Parse the address into a binary string
            $firstBin = inet_pton($firstIp);
            $firstHex = bin2hex((string) $firstBin);

            $flexbits = 128 - (int) $subnet;

            // Build the hexadecimal string of the last address
            $lastHex = $firstHex;

            $pos = 31;
            while ($flexbits > 0) {
                // Get the character at this position
                $orig = mb_substr($lastHex, $pos, 1);

                // Convert it to an integer
                $origval = hexdec($orig);

                // OR it with (2^flexbits)-1, with flexbits limited to 4 at a time
                $newval = $origval | 2 ** min(4, $flexbits) - 1;

                // Convert it back to a hexadecimal character
                $new = dechex($newval);

                // And put that character back in the string
                $lastHex = substr_replace($lastHex, $new, $pos, 1);

                // We processed one nibble, move to previous position
                $flexbits -= 4;
                --$pos;
            }

            // check if the IP to test is within the range
            $result = ($ipHex >= $firstHex && $ipHex <= $lastHex);
        }

        return $result;
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
        // Grabs true IP of the user and returns if it can't be found
        $remoteIp = Core::getIp();
        if (empty($remoteIp)) {
            return false;
        }

        // copy username
        $username = $GLOBALS['cfg']['Server']['user'];

        // copy rule database
        if (isset($GLOBALS['cfg']['Server']['AllowDeny']['rules'])) {
            $rules = $GLOBALS['cfg']['Server']['AllowDeny']['rules'];
            if (! is_array($rules)) {
                $rules = [];
            }
        } else {
            $rules = [];
        }

        // lookup table for some name shortcuts
        $shortcuts = ['all' => '0.0.0.0/0', 'localhost' => '127.0.0.1/8'];

        // Provide some useful shortcuts if server gives us address:
        if (Core::getenv('SERVER_ADDR')) {
            $shortcuts['localnetA'] = Core::getenv('SERVER_ADDR') . '/8';
            $shortcuts['localnetB'] = Core::getenv('SERVER_ADDR') . '/16';
            $shortcuts['localnetC'] = Core::getenv('SERVER_ADDR') . '/24';
        }

        foreach ($rules as $rule) {
            // extract rule data
            $ruleData = explode(' ', $rule);

            // check for rule type
            if ($ruleData[0] !== $type) {
                continue;
            }

            // check for username
            if (
                ($ruleData[1] !== '%') //wildcarded first
                && (! hash_equals($ruleData[1], $username))
            ) {
                continue;
            }

            // check if the config file has the full string with an extra
            // 'from' in it and if it does, just discard it
            if ($ruleData[2] === 'from') {
                $ruleData[2] = $ruleData[3];
            }

            // Handle shortcuts with above array
            if (isset($shortcuts[$ruleData[2]])) {
                $ruleData[2] = $shortcuts[$ruleData[2]];
            }

            // Add code for host lookups here
            // Excluded for the moment

            // Do the actual matching now
            if ($this->ipMaskTest($ruleData[2], $remoteIp)) {
                return true;
            }
        }

        return false;
    }
}
