<?php

declare(strict_types=1);

namespace PhpMyAdmin\I18n;

enum TextDirection: string
{
    case LeftToRight = 'ltr';
    case RightToLeft = 'rtl';
}
