<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

enum MoveScope: string
{
    case StructureOnly = 'structure';
    case StructureAndData = 'data';
    case DataOnly = 'dataonly';
    case Move = 'move';
}
