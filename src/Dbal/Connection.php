<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

/** @psalm-immutable */
final class Connection
{
    public function __construct(public object $connection)
    {
    }
}
