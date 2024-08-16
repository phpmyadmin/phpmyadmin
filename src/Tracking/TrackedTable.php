<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use PhpMyAdmin\Identifiers\TableName;

final readonly class TrackedTable
{
    public function __construct(public TableName $name, public bool $active)
    {
    }
}
