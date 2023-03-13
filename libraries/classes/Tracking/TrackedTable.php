<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

final class TrackedTable
{
    public function __construct(public readonly string $name, public readonly bool $active)
    {
    }
}
