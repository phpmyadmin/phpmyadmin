<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Classes;

use PhpMyAdmin\Ulid;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ULID generator implementation used in PhpMyAdmin.
 *
 * These tests validate structural properties of a ULID, correctness of the
 * character set, ordering guarantees (monotonicity), timestamp behavior,
 * and overall uniqueness expectations across sequential generations.
 */
class UlidTest extends TestCase
{
    /**
     * Expected Crockford Base32 alphabet according to the ULID specification.
     *
     * This is intentionally duplicated here instead of referencing the Ulid class
     * to ensure tests remain independent and correctly detect implementation bugs.
     */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * ULID must always be exactly 26 characters long.
     *
     * The ULID spec defines a fixed-length encoded string. Any deviation from 26
     * characters indicates an implementation error (incorrect padding, truncated
     * output, or invalid encoding logic).
     */
    public function testUlidLengthIsAlways26(): void
    {
        $ulid = Ulid::generate();

        self::assertSame(
            26,
            strlen($ulid),
            'ULID must be exactly 26 characters long'
        );
    }

    /**
     * ULID output must consist solely of valid Crockford Base32 characters.
     *
     * This ensures that the encoding logic correctly maps internal binary data
     * to characters listed in the ULID alphabet. Characters such as I, L, O, U
     * must never appear because they are excluded to avoid ambiguity.
     */
    public function testUlidAllowedCharacters(): void
    {
        $ulid = Ulid::generate();
        $chars = str_split($ulid);

        foreach ($chars as $char) {
            self::assertTrue(
                strpos(self::ALPHABET, $char) !== false,
                "Invalid character in ULID: {$char}"
            );
        }
    }

    /**
     * ULIDs generated sequentially should be lexicographically ordered.
     *
     * The monotonic ULID algorithm guarantees that when two ULIDs are generated
     * back-to-back within the same millisecond, the second will always sort
     * after the first. This test confirms that ordering property.
     */
    public function testUlidMonotonicity(): void
    {
        $u1 = Ulid::generate();
        $u2 = Ulid::generate();

        self::assertLessThan(
            $u2,
            $u1,
            'Second ULID must be lexicographically greater than the first'
        );
    }

    /**
     * The first 10 characters of a ULID represent the timestamp portion.
     *
     * This test ensures that when generating a new ULID slightly later, the
     * timestamp-encoded prefix will increase. It loops until the timestamp
     * portion changes (usually within 1–2 iterations), ensuring correctness
     * even if multiple ULIDs fall within the same millisecond window.
     */
    public function testTimestampPortionIncreases(): void
    {
        $u1 = Ulid::generate();
        $t1 = substr($u1, 0, 10);

        // Generate until a timestamp change is observed.
        // This avoids false negatives when multiple ULIDs fall within
        // the same millisecond and therefore share the same time prefix.
        do {
            $u2 = Ulid::generate();
            $t2 = substr($u2, 0, 10);
        } while ($t1 === $t2);

        self::assertLessThan(
            0,
            strcmp($t1, $t2),
            'Timestamp portion must increase when generated later'
        );
    }

    /**
     * Verifies basic uniqueness expectation between sequential ULID generations.
     *
     * While the ULID specification does not mathematically guarantee absolute
     * uniqueness, in practice each generated ULID should be different due to the
     * timestamp and randomness components. This test checks that ULIDs generated
     * in sequence are not identical, including after a short delay.
     */
    public function testUlidsAreAlwaysUnique(): void
    {
        $u1 = Ulid::generate();
        $u2 = Ulid::generate();

        self::assertNotSame(
            $u1,
            $u2,
            'Two sequential ULIDs should not be identical'
        );

        // Sleep briefly to ensure a new timestamp boundary is crossed.
        usleep(2000);

        $u3 = Ulid::generate();

        self::assertNotSame(
            $u2,
            $u3,
            'ULID generated after delay should differ from previous one'
        );
    }
}
