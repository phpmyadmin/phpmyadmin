<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use PDO;
use PDOStatement;

/**
 * Holds a PDO handle together with the per-connection state that mysqli keeps
 * internally (the statement of the last executed query).
 */
final class PdoConnection
{
    public PDOStatement|null $lastStatement = null;

    public function __construct(public readonly PDO $pdo)
    {
    }
}
