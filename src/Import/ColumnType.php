<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

enum ColumnType
{
    case None;
    case Varchar;
    case Int;
    case Decimal;
    case BigInt;
    case Geometry;
}
