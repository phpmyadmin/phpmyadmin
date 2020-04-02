<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

/**
 * Represents a query
 */
abstract class Query
{
    /**
     * The database name (raw)
     * null if no database has been set
     * @var string|null
     */
    protected $databaseName = null;

    /**
     * The table name
     * null if no table has been set
     * @var string|null
     */
    protected $tableName = null;

    public function database(?string $databaseName): self
    {
        $this->databaseName = $databaseName;
        return $this;
    }

    public function table(?string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getFromExpression(): string
    {
        if ($this->tableName !== null && $this->databaseName !== null) {
            return Util::backquote($this->databaseName) . '.' . Util::backquote($this->tableName);
        }

        if ($this->tableName !== null && $this->databaseName === null) {
            return Util::backquote($this->tableName);
        }

        return 'dual';
    }

    abstract public function toSql(): string;
}
