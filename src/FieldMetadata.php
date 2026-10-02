<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function in_array;

/**
 * Handles fields Metadata
 *
 * NOTE: Getters are not used in all implementations due to the important cost of getters calls
 */
final class FieldMetadata
{
    /**
     * The MySQL protocol field flags, matching the MYSQLI_*_FLAG constants.
     * Declared here so that the metadata can be handled without the mysqli extension.
     *
     * @see https://dev.mysql.com/doc/dev/mysql-server/latest/group__group__cs__column__definition__flags.html
     */
    public const NOT_NULL_FLAG = 1;
    public const PRI_KEY_FLAG = 2;
    public const UNIQUE_KEY_FLAG = 4;
    public const MULTIPLE_KEY_FLAG = 8;
    public const BLOB_FLAG = 16;
    public const UNSIGNED_FLAG = 32;
    public const ZEROFILL_FLAG = 64;
    public const ENUM_FLAG = 256;
    public const SET_FLAG = 2048;

    /**
     * The MySQL protocol field types, matching the MYSQLI_TYPE_* constants.
     *
     * @see https://dev.mysql.com/doc/dev/mysql-server/latest/field__types_8h.html
     */
    public const MYSQL_TYPE_DECIMAL = 0;
    public const MYSQL_TYPE_TINY = 1;
    public const MYSQL_TYPE_SHORT = 2;
    public const MYSQL_TYPE_LONG = 3;
    public const MYSQL_TYPE_FLOAT = 4;
    public const MYSQL_TYPE_DOUBLE = 5;
    public const MYSQL_TYPE_NULL = 6;
    public const MYSQL_TYPE_TIMESTAMP = 7;
    public const MYSQL_TYPE_LONGLONG = 8;
    public const MYSQL_TYPE_INT24 = 9;
    public const MYSQL_TYPE_DATE = 10;
    public const MYSQL_TYPE_TIME = 11;
    public const MYSQL_TYPE_DATETIME = 12;
    public const MYSQL_TYPE_YEAR = 13;
    public const MYSQL_TYPE_NEWDATE = 14;
    public const MYSQL_TYPE_BIT = 16;
    public const MYSQL_TYPE_JSON = 245;
    public const MYSQL_TYPE_NEWDECIMAL = 246;
    public const MYSQL_TYPE_ENUM = 247;
    public const MYSQL_TYPE_SET = 248;
    public const MYSQL_TYPE_TINY_BLOB = 249;
    public const MYSQL_TYPE_MEDIUM_BLOB = 250;
    public const MYSQL_TYPE_LONG_BLOB = 251;
    public const MYSQL_TYPE_BLOB = 252;
    public const MYSQL_TYPE_VAR_STRING = 253;
    public const MYSQL_TYPE_STRING = 254;
    public const MYSQL_TYPE_GEOMETRY = 255;

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

    private int|null $mappedType;

    /** @readonly */
    public bool $isMappedTypeBit;

    /** @readonly */
    public bool $isMappedTypeGeometry;

    /** @readonly */
    public bool $isMappedTypeTimestamp;

    /**
     * The column name
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
     *     name: string,
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
        $this->isMultipleKey = (bool) ($flags & self::MULTIPLE_KEY_FLAG);
        $this->isPrimaryKey = (bool) ($flags & self::PRI_KEY_FLAG);
        $this->isUniqueKey = (bool) ($flags & self::UNIQUE_KEY_FLAG);
        $this->isNotNull = (bool) ($flags & self::NOT_NULL_FLAG);
        $this->isUnsigned = (bool) ($flags & self::UNSIGNED_FLAG);
        $this->isZerofill = (bool) ($flags & self::ZEROFILL_FLAG);
        $this->isBlob = (bool) ($flags & self::BLOB_FLAG);
        $this->isEnum = (bool) ($flags & self::ENUM_FLAG);
        $this->isSet = (bool) ($flags & self::SET_FLAG);

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
        $this->isBinary = in_array($type, [
            self::MYSQL_TYPE_TINY_BLOB,
            self::MYSQL_TYPE_BLOB,
            self::MYSQL_TYPE_MEDIUM_BLOB,
            self::MYSQL_TYPE_LONG_BLOB,
            self::MYSQL_TYPE_VAR_STRING,
            self::MYSQL_TYPE_STRING,
        ], true)
            && $this->charsetnr === 63;
    }

    /**
     * @see https://dev.mysql.com/doc/connectors/en/apis-php-mysqli.constants.html
     *
     * @psalm-return self::TYPE_*|null
     */
    private function getMappedInternalType(int $type): int|null
    {
        return match ($type) {
            self::MYSQL_TYPE_DECIMAL => self::TYPE_REAL,
            self::MYSQL_TYPE_NEWDECIMAL => self::TYPE_REAL,
            self::MYSQL_TYPE_TINY => self::TYPE_INT,
            self::MYSQL_TYPE_SHORT => self::TYPE_INT,
            self::MYSQL_TYPE_LONG => self::TYPE_INT,
            self::MYSQL_TYPE_FLOAT => self::TYPE_REAL,
            self::MYSQL_TYPE_DOUBLE => self::TYPE_REAL,
            self::MYSQL_TYPE_NULL => self::TYPE_NULL,
            self::MYSQL_TYPE_TIMESTAMP => self::TYPE_TIMESTAMP,
            self::MYSQL_TYPE_LONGLONG => self::TYPE_INT,
            self::MYSQL_TYPE_INT24 => self::TYPE_INT,
            self::MYSQL_TYPE_DATE => self::TYPE_DATE,
            self::MYSQL_TYPE_TIME => self::TYPE_TIME,
            self::MYSQL_TYPE_DATETIME => self::TYPE_DATETIME,
            self::MYSQL_TYPE_YEAR => self::TYPE_YEAR,
            self::MYSQL_TYPE_NEWDATE => self::TYPE_DATE,
            self::MYSQL_TYPE_ENUM => self::TYPE_UNKNOWN,
            self::MYSQL_TYPE_SET => self::TYPE_UNKNOWN,
            self::MYSQL_TYPE_TINY_BLOB => self::TYPE_BLOB,
            self::MYSQL_TYPE_MEDIUM_BLOB => self::TYPE_BLOB,
            self::MYSQL_TYPE_LONG_BLOB => self::TYPE_BLOB,
            self::MYSQL_TYPE_BLOB => self::TYPE_BLOB,
            self::MYSQL_TYPE_VAR_STRING => self::TYPE_STRING,
            self::MYSQL_TYPE_STRING => self::TYPE_STRING,
            // MySQL returns MYSQL_TYPE_STRING for CHAR
            // and MYSQL_TYPE_CHAR === MYSQL_TYPE_TINY
            // so this would override TINYINT and mark all TINYINT as string
            // see https://github.com/phpmyadmin/phpmyadmin/issues/8569
            //$typeAr[self::MYSQL_TYPE_CHAR]   = self::TYPE_STRING;
            self::MYSQL_TYPE_GEOMETRY => self::TYPE_GEOMETRY,
            self::MYSQL_TYPE_BIT => self::TYPE_BIT,
            self::MYSQL_TYPE_JSON => self::TYPE_JSON,
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
            || $this->isType(self::TYPE_DATETIME)
            || $this->isType(self::TYPE_TIMESTAMP);
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
