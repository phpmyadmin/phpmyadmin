<?php
/**
 * Holds the PhpMyAdmin\Database\Designer\DesignerColumn class
 */
namespace PhpMyAdmin\Database\Designer;

use JsonSerializable;

/**
 * Common class for Designer
 */
class DesignerColumn implements JsonSerializable
{
    private $tableName;
    private $databaseName;
    private $columnName;
    private $columnType;
    private $isNullable;

    /**
     * Create a new DesignerColumn
     *
     * @param string $databaseName The database name
     * @param string $tableName    The table name
     * @param string $columnName   The column name
     * @param string $columnType   The column type
     * @param bool   $isNullable   The column is nullable
     */
    public function __construct(
        $databaseName,
        $tableName,
        $columnName,
        $columnType,
        $isNullable
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->columnType = $columnType;
        $this->isNullable = $isNullable;
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
     * Get the column name
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * Get the column name as base64
     */
    public function getColumnNameBase64(): string
    {
        return base64_encode($this->columnName);
    }

    /**
     * Get column type
     */
    public function getColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * Is the column nullable
     *
     * @return bool
     */
    public function getIsNullable()
    {
        return $this->isNullable;
    }

    /**
     * Get the db and table and column separated with a dot
     */
    public function getDbTableColString(): string
    {
        return $this->databaseName . '.' . $this->tableName . '.' . $this->columnName;
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
        return base64_encode($this->getDbTableColString());
    }

    /**
     * Get an unique identifier
     */
    public function getUniqueIdentifierForTable(): string
    {
        return base64_encode($this->getDbTableString());
    }

    public function jsonSerialize()
    {
        return [
            'tableUuid' => $this->getUniqueIdentifierForTable(),
            'uuid' => $this->getUniqueIdentifier(),
            'dbName' => $this->getDatabaseName(),
            'tableName' => $this->getTableName(),
            'colName' => $this->getColumnName(),
            'colType' => $this->columnType(),
            'isNullable' => $this->getIsNullable(),
        ];
    }
}
