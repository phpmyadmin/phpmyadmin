<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

enum TableType: string
{
    case Table = 'table';
    case View = 'view';
}
