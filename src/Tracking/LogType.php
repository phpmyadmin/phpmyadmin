<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

enum LogType: string
{
    case Schema = 'schema';
    case Data = 'data';
    case SchemaAndData = 'schema_and_data';
}
