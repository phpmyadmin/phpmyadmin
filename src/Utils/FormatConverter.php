<?php
/**
 * Format converter
 */

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use PhpMyAdmin\Util;

use function bin2hex;
use function hex2bin;
use function inet_ntop;
use function inet_pton;
use function ip2long;
use function long2ip;
use function str_starts_with;
use function substr;

/**
 * Format converter
 */
class FormatConverter
{
    /**
     * Transforms a binary to an IP
     */
    public static function binaryToIp(string $buffer, bool $isBinary): string
    {
        if (! str_starts_with($buffer, '0x')) {
            return $isBinary ? bin2hex($buffer) : $buffer;
        }

        $ipHex = substr($buffer, 2);
        $ipBin = hex2bin($ipHex);

        if ($ipBin === false) {
            return $buffer;
        }

        $ip = inet_ntop($ipBin);

        return $ip === false ? $buffer : $ip;
    }

    /**
     * Transforms an IP to a binary
     */
    public static function ipToBinary(string $buffer): string
    {
        $val = inet_pton($buffer);
        if ($val !== false) {
            return '0x' . bin2hex($val);
        }

        return $buffer;
    }

    /**
     * Transforms an IP to a long
     *
     * @param string $buffer Data to transform
     */
    public static function ipToLong(string $buffer): int|string
    {
        $ipLong = ip2long($buffer);
        if ($ipLong === false) {
            return $buffer;
        }

        return $ipLong;
    }

    /**
     * Transforms a long to an IP
     *
     * @param string $buffer Data to transform
     */
    public static function longToIp(string $buffer): string
    {
        if (! Util::isInteger($buffer) || $buffer < 0 || $buffer > 4294967295) {
            return $buffer;
        }

        $bufferAsFloat = (float) $buffer;

        return (string) long2ip((int) $bufferAsFloat);
    }
}
