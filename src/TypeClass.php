<?php

declare(strict_types=1);

namespace PhpMyAdmin;

enum TypeClass: string
{
    case Number = 'NUMBER';
    case Date = 'DATE';
    case Char = 'CHAR';
    case Spatial = 'SPATIAL';
    case Json = 'JSON';
    case Uuid = 'UUID';
    case Unknown = '';
}
