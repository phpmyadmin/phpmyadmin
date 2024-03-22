<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

enum ConnectionType: int
{
    /** User connection. */
    case User = 0;

    /** Control user connection. */
    case ControlUser = 1;

    /** Auxiliary connection. Used for example for replication setup. */
    case Auxiliary = 2;
}
