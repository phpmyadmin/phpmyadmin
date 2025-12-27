<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

enum SeparateFiles: string
{
    case None = '';
    case Database = 'database';
    case Server = 'server';
}
