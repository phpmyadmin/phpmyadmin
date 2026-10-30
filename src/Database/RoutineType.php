<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

enum RoutineType: string
{
    case Function = 'FUNCTION';
    case Procedure = 'PROCEDURE';

    public function getOpposite(): RoutineType
    {
        return match ($this) {
            RoutineType::Function => RoutineType::Procedure,
            RoutineType::Procedure => RoutineType::Function,
        };
    }
}
