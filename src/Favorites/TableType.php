<?php

declare(strict_types=1);

namespace PhpMyAdmin\Favorites;

enum TableType: string
{
    case Recent = 'recent';
    case Favorite = 'favorite';
}
