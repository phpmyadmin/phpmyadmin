<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

enum StructureOrData: string
{
    case Structure = 'structure';
    case Data = 'data';
    case StructureAndData = 'structure_and_data';
}
