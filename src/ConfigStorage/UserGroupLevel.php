<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

enum UserGroupLevel: string
{
    case Server = 'server';
    case Database = 'db';
    case Table = 'table';
}
