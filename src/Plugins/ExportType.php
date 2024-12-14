<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

enum ExportType: string
{
    case Server = 'server';
    case Database = 'database';
    case Table = 'table';
    case Raw = 'raw';
}
