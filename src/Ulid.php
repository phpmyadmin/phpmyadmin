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
    /**
     * Stores the timestamp (in milliseconds) of the last generated ULID.
     * null means no ULID has been generated yet in this runtime.
     */
    private static ?int $lastTimestamp = null;

    /**
     * Stores the last generated 10-byte randomness segment used for monotonic ULIDs.
     *
     * The value is an array of 10 integers (0–255). A null value means that no ULID
     * has been generated yet in this runtime and the randomness has not been initialized.
     *
     * @var int[]|null
     */
    private static ?array $lastRandomness = null;

    /**
     * Crockford's Base32 alphabet used for ULID encoding.
     *
     * This alphabet intentionally excludes letters that can be visually ambiguous:
     * I, L, O, and U. This improves readability and reduces transcription errors.
     *
     * Order of characters is fixed and must not be modified, as each character
     * maps to a 5-bit value (0–31) per ULID specification.
     *
     * @see https://github.com/ulid/spec
     * @see https://www.crockford.com/base32.html
     */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Generates a monotonic ULID string.
     *
     * A monotonic ULID guarantees lexicographical ordering when multiple ULIDs
     * are generated within the same millisecond. If the timestamp does not
     * change, the previous 10-byte randomness segment is incremented instead of
     * generating new random bytes. This requires maintaining internal state for
     * both timestamp and randomness.
     *
     * @return string
     * @throws \Exception If random_bytes() fails.
     */
    public static function generate(): string
    {
        $timestamp = (int) floor(microtime(true) * 1000);

        // If we are still within the same millisecond, increment the previous
        // randomness segment to maintain lexicographical monotonicity.
        if (self::$lastTimestamp === $timestamp) {
            self::incrementRandomness();
        } else {
            // New millisecond → generate fresh randomness and store state
            self::$lastTimestamp = $timestamp;

            //Randomness is stored so it can be incremented on subsequent calls
            //within the same millisecond, which is required for monotonic ULID.
            self::$lastRandomness = unpack('C*', random_bytes(10));
        }

        $timePart = self::encodeTime($timestamp);
        $randomPart = self::encodeRandom(self::$lastRandomness);

        return $timePart . $randomPart;
    }

    /**
     * Encodes a 48-bit millisecond timestamp into a 10-character
     * Crockford Base32 string as defined by the ULID specification.
     *
     * The timestamp is divided into Base32 segments and padded on the left
     * to ensure the output is always exactly 10 characters long.
     *
     * @param int $timestamp Milliseconds since Unix epoch.
     * @return string 10-character timestamp component of the ULID.
     */
    private static function encodeTime(int $timestamp): string
    {
        return self::encodeInteger($timestamp, 10);
    }

    /**
     * Converts a list of 10 random bytes into a 16-character Crockford Base32
     * randomness component for the ULID.
     *
     * The input array must contain integers between 0 and 255. The bytes are
     * converted to a binary string and then encoded into Base32.
     *
     * @param int[] $bytes Array of 10 integers representing randomness.
     * @return string 16-character randomness component.
     */
    private static function encodeRandom(array $bytes): string
    {
        $binary = '';
        foreach ($bytes as $byte) {
            $binary .= chr($byte);
        }
        return self::encodeBinary($binary, 16);
    }

    /**
     * Encodes a non-negative integer into a Crockford Base32 string with fixed padding.
     *
     * The number is repeatedly divided by 32 and converted into Base32 digits.
     * The output is left-padded with '0' until it reaches the required length.
     *
     * @param int $value    The integer to encode.
     * @param int $padding  Output length to enforce.
     * @return string Base32-encoded, left-padded representation.
     */
    private static function encodeInteger(int $value, int $padding): string
    {
        $result = '';

        while ($value > 0) {
            $result = self::ALPHABET[$value % 32] . $result;
            $value = intdiv($value, 32);
        }

        return str_pad($result, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Encodes a binary string into a Crockford Base32 string with fixed padding.
     *
     * Each byte is converted into an 8-bit binary representation. The combined
     * bitstream is then split into 5-bit chunks, each mapped to a Base32 character.
     * Any incomplete final chunk is right-padded with zeros.
     *
     * The returned string is padded or trimmed to the exact length specified.
     *
     * @param string $binary  Raw binary data.
     * @param int $padding    Required output length.
     * @return string Base32-encoded string of length $padding.
     */
    private static function encodeBinary(string $binary, int $padding): string
    {
        $bits = '';

        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        $bitsLen = strlen($bits);
        for ($i = 0; $i < $bitsLen; $i += 5) {
            $chunk = substr($bits, $i, 5);
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $output .= self::ALPHABET[bindec($chunk)];
        }

        return substr(str_pad($output, $padding, '0'), 0, $padding);
    }

    /**
     * Increments the 10-byte randomness segment used for generating monotonic ULIDs.
     *
     * When multiple ULIDs are generated within the same millisecond, the previous
     * randomness block is incremented as a big-endian 80-bit unsigned integer. This
     * guarantees strictly increasing lexicographical order.
     *
     * The incrementation cascades like an odometer: if a byte overflows beyond 255,
     * it resets to 0 and the next more significant byte is incremented.
     *
     * @return void
     */
    private static function incrementRandomness(): void
    {
        // 10 bytes of randomness as per ULID spec (80 bits)
        $randomBytes = 10;
        for ($i = $randomBytes; $i >= 1; $i--) {
            self::$lastRandomness[$i]++;
            if (self::$lastRandomness[$i] <= 255) {
                return;
            }
            self::$lastRandomness[$i] = 0;
        }
    }
}
