<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

enum DeleteLinkEnum
{
    case NO_DELETE;
    case DELETE_ROW;
    case KILL_PROCESS;
}
