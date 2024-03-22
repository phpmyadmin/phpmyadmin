<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

enum MoveScope: string
{
    case Structure = 'structure';
    case Data = 'data';
    case DataOnly = 'dataonly';
}
