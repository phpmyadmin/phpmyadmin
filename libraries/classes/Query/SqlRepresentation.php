<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

/**
 * Ensures the object can be represented as a SQL query
 */
interface SqlRepresentation
{
    function toSql(): string;
}
