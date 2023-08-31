<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

/**
 * @psalm-immutable
 * @psalm-type ConnectionType = Connection::TYPE_USER|Connection::TYPE_CONTROL|Connection::TYPE_AUXILIARY
 */
final class Connection
{
    /** User connection. */
    public const TYPE_USER = 0;

    /** Control user connection. */
    public const TYPE_CONTROL = 1;

    /** Auxiliary connection. Used for example for replication setup. */
    public const TYPE_AUXILIARY = 2;

    public function __construct(public object $connection)
    {
    }
}
