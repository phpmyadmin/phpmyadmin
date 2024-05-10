<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

enum MoveMode
{
    case SingleTable;
    case WholeDatabase;
}
