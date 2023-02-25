<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

use PhpMyAdmin\Utils\ForeignKey;

/**
 * Common functions for Designer
 */
class DesignerTable
{
    /**
     * Create a new DesignerTable
     *
     * @param string      $databaseName The database name
     * @param string      $tableName    The table name
     * @param string      $tableEngine  The table engine
     * @param string|null $displayField The display field if available
     */
    public function __construct(
        private string $databaseName,
        private string $tableName,
        private string $tableEngine,
        private string|null $displayField,
    ) {
    }

    /**
     * The table engine supports or not foreign keys
     */
    public function supportsForeignkeys(): bool
    {
        return ForeignKey::isSupported($this->tableEngine);
    }

    /**
     * Get the database name
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Get the table name
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get the table engine
     */
    public function getTableEngine(): string
    {
        return $this->tableEngine;
    }

    /**
     * Get the displayed field
     */
    public function getDisplayField(): string|null
    {
        return $this->displayField;
    }

    /**
     * Get the db and table separated with a dot
     */
    public function getDbTableString(): string
    {
        return $this->databaseName . '.' . $this->tableName;
    }
}
