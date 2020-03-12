<?php

declare(strict_types=1);

/**
 * Holds the PhpMyAdmin\Database\Designer\DesignerColumn class
 */

namespace PhpMyAdmin\Database\Designer;

use JsonSerializable;
use function base64_encode;
use function strpos;

/**
 * Common class for Designer
 */
class DesignerColumn implements JsonSerializable
{
    /** @var string */
    private $tableName;
    /** @var string */
    private $databaseName;
    /** @var string */
    private $columnName;
    /** @var string */
    private $columnType;
    /** @var bool */
    private $isNullable;
    /** @var string */
    private $columnTypeForImage;
    /** @var bool */
    private $isPkOrUnique;

    /**
     * Create a new DesignerColumn
     *
     * @param string $databaseName The database name
     * @param string $tableName    The table name
     * @param string $columnName   The column name
     * @param string $columnType   The column type
     * @param bool   $isNullable   The column is nullable
     * @param bool   $isPkOrUnique The column is a primary key or is unique
     */
    public function __construct(
        string $databaseName,
        string $tableName,
        string $columnName,
        string $columnType,
        bool $isNullable,
        bool $isPkOrUnique
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->columnType = $columnType;
        $this->isNullable = $isNullable;
        $this->isPkOrUnique = $isPkOrUnique;
        $this->fillColumnTypeForImage();
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
     * Get column type for image
     */
    public function getColumnTypeForImage(): string
    {
        return $this->columnTypeForImage;
    }

    private function fillColumnTypeForImage(): void
    {
        if ($this->isPkOrUnique) {
            $this->columnTypeForImage = 'designer/FieldKey_small';
        } else {
            if (strpos($this->columnType, 'char') !== false
                || strpos($this->columnType, 'text') !== false) {
                $this->columnTypeForImage = 'designer/Field_small_char';
            } elseif (strpos($this->columnType, 'int') !== false
                || strpos($this->columnType, 'float') !== false
                || strpos($this->columnType, 'double') !== false
                || strpos($this->columnType, 'decimal') !== false) {
                $this->columnTypeForImage = 'designer/Field_small_int';
            } elseif (strpos($this->columnType, 'date') !== false
                || strpos($this->columnType, 'time') !== false
                || strpos($this->columnType, 'year') !== false) {
                $this->columnTypeForImage = 'designer/Field_small_date';
            } else {
                // Nothing found
                $this->columnTypeForImage = 'designer/Field_small';
            }
        }
    }

    /**
     * Is the column nullable
     */
    public function getIsNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * Is the column a primary key or is unique
     */
    public function getIsPkOrUnique(): bool
    {
        return $this->isPkOrUnique;
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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'tableUuid' => $this->getUniqueIdentifierForTable(),
            'uuid' => $this->getUniqueIdentifier(),
            'dbName' => $this->getDatabaseName(),
            'tableName' => $this->getTableName(),
            'colName' => $this->getColumnName(),
            'colType' => $this->getColumnType(),
            'isNullable' => $this->getIsNullable(),
        ];
    }
}
