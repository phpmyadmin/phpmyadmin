<?php

declare(strict_types=1);

/**
 * Holds the PhpMyAdmin\Database\Designer\DesignerForeignKey class
 */

namespace PhpMyAdmin\Database\Designer;

use JsonSerializable;
use function base64_encode;

/**
 * Common class for Designer
 */
class DesignerForeignKey implements JsonSerializable
{
    private $foreignKeyDatabaseName;
    private $foreignKeyTableName;
    private $foreignKeyColumnName;
    private $foreignKeyName;
    private $sourceDatabaseName;
    private $sourceTableName;
    private $sourceColumnName;

    /**
     * Create a new DesignerForeignKey
     *
     * @param string $foreignKeyDatabaseName The foreign key database name
     * @param string $foreignKeyTableName    The foreign key table name
     * @param string $foreignKeyColumnName   The foreign key column name
     * @param string $foreignKeyName         The foreign key name
     * @param string $sourceDatabaseName     The source database name
     * @param string $sourceTableName        The source table name
     * @param string $sourceColumnName       The source column name
     */
    public function __construct(
        string $foreignKeyDatabaseName,
        string $foreignKeyTableName,
        string $foreignKeyColumnName,
        string $foreignKeyName,
        string $sourceDatabaseName,
        string $sourceTableName,
        string $sourceColumnName
    ) {
        $this->foreignKeyDatabaseName = $foreignKeyDatabaseName;
        $this->foreignKeyTableName = $foreignKeyTableName;
        $this->foreignKeyColumnName = $foreignKeyColumnName;
        $this->foreignKeyName = $foreignKeyName;
        $this->sourceDatabaseName = $sourceDatabaseName;
        $this->sourceTableName = $sourceTableName;
        $this->sourceColumnName = $sourceColumnName;
    }

    /**
     * Get the database name
     */
    public function getDatabaseName(): string
    {
        return $this->foreignKeyDatabaseName;
    }

    /**
     * Get the table name
     */
    public function getTableName(): string
    {
        return $this->foreignKeyTableName;
    }

    /**
     * Get the column name
     */
    public function getColumnName(): string
    {
        return $this->foreignKeyColumnName;
    }

    /**
     * Get the foreign key name
     */
    public function getForeignKeyName(): string
    {
        return $this->foreignKeyName;
    }

    /**
     * Get the source column
     */
    public function getSourceColumnName(): string
    {
        return $this->sourceColumnName;
    }

    /**
     * Get the db and table and column separated with a dot
     */
    public function getDbTableColString(): string
    {
        return $this->foreignKeyDatabaseName . '.' . $this->foreignKeyTableName . '.' . $this->foreignKeyColumnName;
    }

    /**
     * Get the db and table and column separated with a dot
     */
    public function getSourceDbTableColString(): string
    {
        return $this->sourceDatabaseName . '.' . $this->sourceTableName . '.' . $this->sourceColumnName;
    }

    /**
     * Get the db and table separated with a dot
     */
    public function getDbTableString(): string
    {
        return $this->foreignKeyDatabaseName . '.' . $this->foreignKeyTableName;
    }

    /**
     * Get the db and table separated with a dot
     */
    public function getSourceDbTableString(): string
    {
        return $this->sourceDatabaseName . '.' . $this->sourceTableName;
    }

    /**
     * Get an unique identifier
     */
    public function getUniqueIdentifier(): string
    {
        return base64_encode($this->getDbTableColString());
    }

    /**
     * Get an unique identifier
     */
    public function getUniqueIdentifierForTable(): string
    {
        return base64_encode($this->getDbTableString());
    }

    /**
     * Get an unique identifier for source
     */
    public function getSourceUniqueIdentifier(): string
    {
        return base64_encode($this->getSourceDbTableColString());
    }

    /**
     * Get an unique identifier for source table
     */
    public function getSourceUniqueIdentifierForTable(): string
    {
        return base64_encode($this->getSourceDbTableString());
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'tableUuid' => $this->getUniqueIdentifierForTable(),
            'sourceTableUuid' => $this->getSourceUniqueIdentifierForTable(),
            'uuid' => $this->getUniqueIdentifier(),
            'uuidSource' => $this->getSourceUniqueIdentifier(),
            'fkName' => $this->getForeignKeyName(),
            'dbName' => $this->getDatabaseName(),
            'tableName' => $this->getTableName(),
            'colName' => $this->getColumnName(),
        ];
    }
}
