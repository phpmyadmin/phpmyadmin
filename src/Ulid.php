<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Exception;

/**
 * ULID generator class (monotonic).
 *
 * Based on ULID specification: https://github.com/ulid/spec
 * Inspired by Symfony/Uid and robinvdvleuten/ulid implementations.
 */
class Ulid
{
    private static $lastTimestamp = null;
    private static $lastRandomness = null;

    /** Crockford Base32 Alphabet */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generates a ULID string (monotonic).
     *
     * @return string
     * @throws Exception
     */
    public static function generate(): string
    {
        $timestamp = (int) floor(microtime(true) * 1000);

        if (self::$lastTimestamp === $timestamp) {
            self::incrementRandomness();
        } else {
            self::$lastTimestamp = $timestamp;
            self::$lastRandomness = unpack('C*', random_bytes(10));
        }

        $timePart = self::encodeTime($timestamp);
        $randomPart = self::encodeRandom(self::$lastRandomness);

        return $timePart . $randomPart;
    }

    /**
     * Encodes a 48-bit timestamp as a 10-character Crockford Base32 string.
     */
    private static function encodeTime(int $timestamp): string
    {
        return self::encodeInteger($timestamp, 10);
    }

    /**
     * Turns random bytes into a 16-character randomness segment.
     */
    private static function encodeRandom(array $bytes): string
    {
        $binary = '';
        foreach ($bytes as $b) {
            $binary .= chr($b);
        }
        return self::encodeBinary($binary, 16);
    }

    /**
     * Encodes an integer into Crockford Base32 with padding.
     */
    private static function encodeInteger(int $value, int $padding): string
    {
        $alphabet = self::ALPHABET;
        $result = '';

        if ($value === 0) {
            $result = '0';
        } else {
            while ($value > 0) {
                $result = $alphabet[$value % 32] . $result;
                $value = (int) ($value / 32);
            }
        }

        return str_pad($result, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Encodes binary data into Crockford Base32.
     */
    private static function encodeBinary(string $binary, int $padding): string
    {
        $bits = '';
        $alphabet = self::ALPHABET;

        for ($i = 0, $len = strlen($binary); $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($binary[$i])), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        $bitsLen = strlen($bits);
        for ($i = 0; $i < $bitsLen; $i += 5) {
            $chunk = substr($bits, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $output .= $alphabet[bindec($chunk)];
        }

        return substr(str_pad($output, $padding, '0'), 0, $padding);
    }

    /**
     * Increments the randomness for monotonic ULID.
     */
    private static function incrementRandomness(): void
    {
        $bytes = &self::$lastRandomness;
        for ($i = 10; $i >= 1; $i--) {
            $bytes[$i]++;
            if ($bytes[$i] <= 255) {
                return;
            }
            $bytes[$i] = 0;
        }
    }
}
