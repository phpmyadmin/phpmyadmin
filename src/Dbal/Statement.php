<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

interface Statement
{
    /**
     * Executes a prepared statement.
     *
     * @param list<string> $params
     */
    public function execute(array $params): bool;

    /**
     * Gets a result set from a prepared statement.
     */
    public function getResult(): ResultInterface;
}
