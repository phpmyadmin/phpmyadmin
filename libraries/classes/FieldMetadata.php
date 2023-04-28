<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function define;
use function defined;

use const MYSQLI_BLOB_FLAG;
use const MYSQLI_ENUM_FLAG;
use const MYSQLI_MULTIPLE_KEY_FLAG;
use const MYSQLI_NOT_NULL_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_SET_FLAG;
use const MYSQLI_TYPE_BIT;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_DOUBLE;
use const MYSQLI_TYPE_ENUM;
use const MYSQLI_TYPE_FLOAT;
use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_INT24;
use const MYSQLI_TYPE_JSON;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_LONG_BLOB;
use const MYSQLI_TYPE_LONGLONG;
use const MYSQLI_TYPE_MEDIUM_BLOB;
use const MYSQLI_TYPE_NEWDATE;
use const MYSQLI_TYPE_NEWDECIMAL;
use const MYSQLI_TYPE_NULL;
use const MYSQLI_TYPE_SET;
use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIME;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_TINY;
use const MYSQLI_TYPE_TINY_BLOB;
use const MYSQLI_TYPE_VAR_STRING;
use const MYSQLI_TYPE_YEAR;
use const MYSQLI_UNIQUE_KEY_FLAG;
use const MYSQLI_UNSIGNED_FLAG;
use const MYSQLI_ZEROFILL_FLAG;

// Issue #16043 - client API mysqlnd seem not to have MYSQLI_TYPE_JSON defined
if (! defined('MYSQLI_TYPE_JSON')) {
    define('MYSQLI_TYPE_JSON', 245);
}

/**
 * Handles fields Metadata
 *
 * NOTE: Getters are not used in all implementations due to the important cost of getters calls
 */
final class FieldMetadata
{
    public const TYPE_GEOMETRY = 1;
    public const TYPE_BIT = 2;
    public const TYPE_JSON = 3;
    public const TYPE_REAL = 4;
    public const TYPE_INT = 5;
    public const TYPE_BLOB = 6;
    public const TYPE_UNKNOWN = -1;
    public const TYPE_NULL = 7;
    public const TYPE_STRING = 8;
    public const TYPE_DATE = 9;
    public const TYPE_TIME = 10;
    public const TYPE_TIMESTAMP = 11;
    public const TYPE_DATETIME = 12;
    public const TYPE_YEAR = 13;

    /** @readonly */
    public bool $isMultipleKey;

    /** @readonly */
    public bool $isPrimaryKey;

    /** @readonly */
    public bool $isUniqueKey;

    /** @readonly */
    public bool $isNotNull;

    /** @readonly */
    public bool $isUnsigned;

    /** @readonly */
    public bool $isZerofill;

    /** @readonly */
    public bool $isNumeric;

    /** @readonly */
    public bool $isBlob;

    /** @readonly */
    public bool $isBinary;

    /** @readonly */
    public bool $isEnum;

    /** @readonly */
    public bool $isSet;

    private int|null $mappedType = null;

    /** @readonly */
    public bool $isMappedTypeBit;

    /** @readonly */
    public bool $isMappedTypeGeometry;

    /** @readonly */
    public bool $isMappedTypeTimestamp;

    /**
     * The column name
     *
     * @psalm-var non-empty-string
     */
    public string $name;

    /**
     * The original column name if an alias did exist
     */
    public string $orgname;

    /**
     * The table name
     */
    public string $table;

    /**
     * The original table name
     */
    public string $orgtable;

    /**
     * The charset number
     *
     * @readonly
     */
    public int $charsetnr;

    /**
     * The number of decimals used (for integer fields)
     *
     * @readonly
     */
    public int $decimals;

    /**
     * The width of the field, as specified in the table definition.
     *
     * @readonly
     */
    public int $length;

    /**
     * A field only used by the Results class
     */
    public string|null $internalMediaType = null;

    /**
     * @psalm-param object{
     *     name: non-empty-string,
     *     orgname: string,
     *     table: string,
     *     orgtable: string,
     *     max_length: int,
     *     length: int,
     *     charsetnr: int,
     *     flags: int,
     *     type: int,
     *     decimals: int,
     *     db: string,
     *     def: string,
     *     catalog: string,
     * } $field
     */
    public function __construct(object $field)
    {
        $type = $field->type;
        $this->mappedType = $this->getMappedInternalType($type);

        $flags = $field->flags;
        $this->isMultipleKey = (bool) ($flags & MYSQLI_MULTIPLE_KEY_FLAG);
        $this->isPrimaryKey = (bool) ($flags & MYSQLI_PRI_KEY_FLAG);
        $this->isUniqueKey = (bool) ($flags & MYSQLI_UNIQUE_KEY_FLAG);
        $this->isNotNull = (bool) ($flags & MYSQLI_NOT_NULL_FLAG);
        $this->isUnsigned = (bool) ($flags & MYSQLI_UNSIGNED_FLAG);
        $this->isZerofill = (bool) ($flags & MYSQLI_ZEROFILL_FLAG);
        $this->isBlob = (bool) ($flags & MYSQLI_BLOB_FLAG);
        $this->isEnum = (bool) ($flags & MYSQLI_ENUM_FLAG);
        $this->isSet = (bool) ($flags & MYSQLI_SET_FLAG);

        // as flags 32768 can be NUM_FLAG or GROUP_FLAG
        // reference: https://www.php.net/manual/en/mysqli-result.fetch-fields.php
        // so check field type instead of flags
        $this->isNumeric = $this->isType(self::TYPE_INT) || $this->isType(self::TYPE_REAL);

        // MYSQLI_PART_KEY_FLAG => 'part_key',
        // MYSQLI_TIMESTAMP_FLAG => 'timestamp',
        // MYSQLI_AUTO_INCREMENT_FLAG => 'auto_increment',

        $this->isMappedTypeBit = $this->isType(self::TYPE_BIT);
        $this->isMappedTypeGeometry = $this->isType(self::TYPE_GEOMETRY);
        $this->isMappedTypeTimestamp = $this->isType(self::TYPE_TIMESTAMP);

        $this->name = $field->name;
        $this->orgname = $field->orgname;
        $this->table = $field->table;
        $this->orgtable = $field->orgtable;
        $this->charsetnr = $field->charsetnr;
        $this->decimals = $field->decimals;
        $this->length = $field->length;

        // 63 is the number for the MySQL charset "binary"
        $this->isBinary = (
            $type === MYSQLI_TYPE_TINY_BLOB ||
            $type === MYSQLI_TYPE_BLOB ||
            $type === MYSQLI_TYPE_MEDIUM_BLOB ||
            $type === MYSQLI_TYPE_LONG_BLOB ||
            $type === MYSQLI_TYPE_VAR_STRING ||
            $type === MYSQLI_TYPE_STRING
        ) && $this->charsetnr == 63;
    }

    /**
     * @see https://dev.mysql.com/doc/connectors/en/apis-php-mysqli.constants.html
     *
     * @psalm-return self::TYPE_*|null
     */
    private function getMappedInternalType(int $type): int|null
    {
        return match ($type) {
            MYSQLI_TYPE_DECIMAL => self::TYPE_REAL,
            MYSQLI_TYPE_NEWDECIMAL => self::TYPE_REAL,
            MYSQLI_TYPE_TINY => self::TYPE_INT,
            MYSQLI_TYPE_SHORT => self::TYPE_INT,
            MYSQLI_TYPE_LONG => self::TYPE_INT,
            MYSQLI_TYPE_FLOAT => self::TYPE_REAL,
            MYSQLI_TYPE_DOUBLE => self::TYPE_REAL,
            MYSQLI_TYPE_NULL => self::TYPE_NULL,
            MYSQLI_TYPE_TIMESTAMP => self::TYPE_TIMESTAMP,
            MYSQLI_TYPE_LONGLONG => self::TYPE_INT,
            MYSQLI_TYPE_INT24 => self::TYPE_INT,
            MYSQLI_TYPE_DATE => self::TYPE_DATE,
            MYSQLI_TYPE_TIME => self::TYPE_TIME,
            MYSQLI_TYPE_DATETIME => self::TYPE_DATETIME,
            MYSQLI_TYPE_YEAR => self::TYPE_YEAR,
            MYSQLI_TYPE_NEWDATE => self::TYPE_DATE,
            MYSQLI_TYPE_ENUM => self::TYPE_UNKNOWN,
            MYSQLI_TYPE_SET => self::TYPE_UNKNOWN,
            MYSQLI_TYPE_TINY_BLOB => self::TYPE_BLOB,
            MYSQLI_TYPE_MEDIUM_BLOB => self::TYPE_BLOB,
            MYSQLI_TYPE_LONG_BLOB => self::TYPE_BLOB,
            MYSQLI_TYPE_BLOB => self::TYPE_BLOB,
            MYSQLI_TYPE_VAR_STRING => self::TYPE_STRING,
            MYSQLI_TYPE_STRING => self::TYPE_STRING,
            // MySQL returns MYSQLI_TYPE_STRING for CHAR
            // and MYSQLI_TYPE_CHAR === MYSQLI_TYPE_TINY
            // so this would override TINYINT and mark all TINYINT as string
            // see https://github.com/phpmyadmin/phpmyadmin/issues/8569
            //$typeAr[MYSQLI_TYPE_CHAR]        = self::TYPE_STRING;
            MYSQLI_TYPE_GEOMETRY => self::TYPE_GEOMETRY,
            MYSQLI_TYPE_BIT => self::TYPE_BIT,
            MYSQLI_TYPE_JSON => self::TYPE_JSON,
            default => null,
        };
    }

    public function isNotNull(): bool
    {
        return $this->isNotNull;
    }

    public function isNumeric(): bool
    {
        return $this->isNumeric;
    }

    public function isBinary(): bool
    {
        return $this->isBinary;
    }

    public function isBlob(): bool
    {
        return $this->isBlob;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isUniqueKey(): bool
    {
        return $this->isUniqueKey;
    }

    public function isMultipleKey(): bool
    {
        return $this->isMultipleKey;
    }

    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    public function isZerofill(): bool
    {
        return $this->isZerofill;
    }

    public function isEnum(): bool
    {
        return $this->isEnum;
    }

    public function isSet(): bool
    {
        return $this->isSet;
    }

    /**
     * Checks that it is type DATE/TIME/DATETIME
     */
    public function isDateTimeType(): bool
    {
        return $this->isType(self::TYPE_DATE)
            || $this->isType(self::TYPE_TIME)
            || $this->isType(self::TYPE_DATETIME);
    }

    /**
     * Checks that it contains time
     * A "DATE" field returns false for example
     */
    public function isTimeType(): bool
    {
        return $this->isType(self::TYPE_TIME)
            || $this->isType(self::TYPE_TIMESTAMP)
            || $this->isType(self::TYPE_DATETIME);
    }

    /**
     * Get the mapped type as a string
     *
     * @return string Empty when nothing could be matched
     */
    public function getMappedType(): string
    {
        return match ($this->mappedType) {
            self::TYPE_GEOMETRY => 'geometry',
            self::TYPE_BIT => 'bit',
            self::TYPE_JSON => 'json',
            self::TYPE_REAL => 'real',
            self::TYPE_INT => 'int',
            self::TYPE_BLOB => 'blob',
            self::TYPE_UNKNOWN => 'unknown',
            self::TYPE_NULL => 'null',
            self::TYPE_STRING => 'string',
            self::TYPE_DATE => 'date',
            self::TYPE_TIME => 'time',
            self::TYPE_TIMESTAMP => 'timestamp',
            self::TYPE_DATETIME => 'datetime',
            self::TYPE_YEAR => 'year',
            default => '',
        };
    }

    /**
     * Check if it is the mapped type
     *
     * @phpstan-param self::TYPE_* $type
     */
    public function isType(int $type): bool
    {
        return $this->mappedType === $type;
    }

    /**
     * Check if it is NOT the mapped type
     *
     * @phpstan-param self::TYPE_* $type
     */
    public function isNotType(int $type): bool
    {
        return $this->mappedType !== $type;
    }
}
