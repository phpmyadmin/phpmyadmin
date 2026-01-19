<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final class MockClock implements ClockInterface
{
    public function __construct(public DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return clone $this->now;
    }

    /** @param non-empty-string $dateTime */
    public static function from(string $dateTime = 'now'): ClockInterface
    {
        return new self(new DateTimeImmutable($dateTime));
    }
}
