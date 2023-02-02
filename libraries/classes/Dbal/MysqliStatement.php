<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli_stmt;

use function count;
use function str_repeat;

use const PHP_VERSION_ID;

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

        if (PHP_VERSION_ID >= 80100) {
            /**
             * @psalm-suppress TooManyArguments
             * @phpstan-ignore-next-line
             */
            return $this->statement->execute($params);
        }

        $types = str_repeat('s', $paramCount);
        if (! $this->statement->bind_param($types, ...$params)) {
            return false;
        }

        return $this->statement->execute();
    }

    /**
     * Gets a result set from a prepared statement.
     */
    public function getResult(): ResultInterface
    {
        return new MysqliResult($this->statement->get_result());
    }
}
