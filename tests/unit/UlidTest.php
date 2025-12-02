<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Classes;

use PhpMyAdmin\Ulid;
use PHPUnit\Framework\TestCase;

class UlidTest extends TestCase
{
    // ULID uses Crockford Base32 alphabet.
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    // ULID must always be 26 characters.
    public function testUlidLength(): void
    {
        $ulid = Ulid::generate();
        $this->assertSame(
            26,
            strlen($ulid),
            'ULID must be exactly 26 characters long'
        );
    }

    // ULID must contain only allowed Crockford Base32 characters.
    public function testUlidAllowedCharacters(): void
    {
        $ulid = Ulid::generate();
        $chars = str_split($ulid);

        foreach ($chars as $char) {
            $this->assertTrue(
                strpos(self::ALPHABET, $char) !== false,
                "Invalid character in ULID: {$char}"
            );
        }
    }

    // ULIDs created one after another should be in order thanks to the monotonic logic.
    public function testUlidMonotonicity(): void
    {
        $u1 = Ulid::generate();
        $u2 = Ulid::generate();

        $this->assertLessThan(
            0,
            strcmp($u1, $u2),
            'Second ULID must be lexicographically greater than the first'
        );
    }

    // The first 10 characters are the timestamp. If you generate a ULID a little later, that part should go up.
    public function testTimestampPortionIncreases(): void
    {
        $u1 = Ulid::generate();

        // Wait for about 2ms to make sure the next ULID lands in a new millisecond.
        usleep(2000);

        $u2 = Ulid::generate();

        $this->assertLessThan(
            0,
            strcmp(substr($u1, 0, 10), substr($u2, 0, 10)),
            'Timestamp portion must increase when generated later'
        );
    }

    // Generate a bunch of ULIDs and check for duplicates. Each one should be unique, period.
    public function testMultipleUlidsAreUnique(): void
    {
        $generated = [];

        for ($i = 0; $i < 1000; $i++) {
            $u = Ulid::generate();
            $this->assertArrayNotHasKey(
                $u,
                $generated,
                "Duplicate ULID found: {$u}"
            );
            $generated[$u] = true;
        }

        $this->assertCount(
            1000,
            $generated,
            'All ULIDs generated in batch should be unique'
        );
    }
}