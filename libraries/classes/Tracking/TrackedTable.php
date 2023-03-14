<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use PhpMyAdmin\Dbal\TableName;

final class TrackedTable
{
    public function __construct(public readonly TableName $name, public readonly bool $active)
    {
    }
}
