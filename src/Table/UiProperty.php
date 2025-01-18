<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

enum UiProperty: string
{
    case SortedColumn = 'sorted_col';
    case ColumnOrder = 'col_order';
    case ColumnVisibility = 'col_visib';
}
