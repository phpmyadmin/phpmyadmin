<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli_stmt;

use function count;

final class MysqliStatement implements Statement
{
    /** @var mysqli_stmt */
    private $statement;

    public function __construct(mysqli_stmt $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Executes a prepared statement.
     *
     * @param list<string> $params
     */
    public function execute(array $params): bool
    {
        $paramCount = $this->statement->param_count;
        if (count($params) !== $paramCount) {
            return false;
        }

        return $this->statement->execute($params);
    }

    /**
     * Gets a result set from a prepared statement.
     */
    public function getResult(): ResultInterface
    {
        return new MysqliResult($this->statement->get_result());
    }
}
