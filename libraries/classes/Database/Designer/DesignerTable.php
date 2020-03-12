<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

use JsonSerializable;
use PhpMyAdmin\Util;
use function base64_encode;

/**
 * Common class for Designer
 */
class DesignerTable implements JsonSerializable
{
    private $tableName;
    private $databaseName;
    private $tableEngine;
    private $displayField;

    /**
     * The associated columns
     *
     * @var DesignerColumn[]
     */
    private $columns = [];

    /**
     * Create a new DesignerTable
     *
     * @param string      $databaseName The database name
     * @param string      $tableName    The table name
     * @param string      $tableEngine  The table engine
     * @param string|null $displayField The display field if available
     */
    public function __construct(
        string $databaseName,
        string $tableName,
        string $tableEngine,
        ?string $displayField
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->tableEngine = $tableEngine;
        $this->displayField = $displayField;
    }

    /**
     * The table engine supports or not foreign keys
     */
    public function supportsForeignkeys(): bool
    {
        return Util::isForeignKeySupported($this->tableEngine);
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
     * Get the database name encoded in base64 format
     */
    public function getDatabaseNameBase64(): string
    {
        return base64_encode($this->databaseName);
    }

    /**
     * Get the table name encoded in base64 format
     */
    public function getTableNameBase64(): string
    {
        return base64_encode($this->tableName);
    }

    /**
     * Get the table engine
     */
    public function getTableEngine(): string
    {
        return $this->tableEngine;
    }

    /**
     * Get the db and table separated with a dot
     */
    public function getDbTableString(): string
    {
        return $this->databaseName . '.' . $this->tableName;
    }

    /**
     * Get an unique identifier
     */
    public function getUniqueIdentifier(): string
    {
        return base64_encode($this->getDbTableString());
    }

    /**
     * Get the display field if available
     */
    public function getDisplayField(): ?string
    {
        return $this->displayField;
    }

    /**
     * Get the display field encoded in base64
     */
    public function getDisplayFieldBase64(): string
    {
        return base64_encode($this->getDisplayField() ?? '');
    }

    /**
     * Add a column to the table
     *
     * @param DesignerColumn $column The designer column to add
     */
    public function addColumn(DesignerColumn $column): void
    {
        $this->columns[] = $column;
    }

    /**
     * Get columns for a table
     *
     * @return DesignerColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uuid' => $this->getUniqueIdentifier(),
            'tableName' => $this->getTableName(),
            'dbName' => $this->getDatabaseName(),
        ];
    }
}
