<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Clock;

use DateTimeImmutable;
use PhpMyAdmin\Clock\Clock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function usleep;

#[CoversClass(Clock::class)]
final class ClockTest extends TestCase
{
    public function testNow(): void
    {
        $clock = new Clock();
        $before = new DateTimeImmutable();
        usleep(2000);
        $now = $clock->now();
        usleep(2000);
        $after = new DateTimeImmutable();
        self::assertGreaterThan($before, $now);
        self::assertLessThan($after, $now);
        self::assertNotSame($clock->now(), $clock->now());
    }
}
