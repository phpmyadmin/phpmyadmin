<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

/**
 * @psalm-immutable
 */
final class Connection
{
    /** @var object */
    public $connection;

    public function __construct(object $connection)
    {
        $this->connection = $connection;
    }
}
