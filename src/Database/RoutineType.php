<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

enum RoutineType: string
{
    case Function = 'FUNCTION';
    case Procedure = 'PROCEDURE';
}
