<?php
/**
 * Factory for creating FieldMetadata objects from Kusto column types
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use PhpMyAdmin\FieldMetadata;
use stdClass;

use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DOUBLE;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_LONGLONG;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_VAR_STRING;

/**
 * Creates FieldMetadata instances from Kusto data types.
 *
 * Kusto types are mapped to the closest MYSQLI_TYPE_* constants so that
 * the rest of phpMyAdmin can render columns, format values, etc.
 */
final class KustoFieldMetadata
{
    /**
     * Map a Kusto type string to a MYSQLI_TYPE_* constant.
     */
    private static function mapKustoType(string $kustoType): int
    {
        return match ($kustoType) {
            'bool' => MYSQLI_TYPE_LONG,          // treated as int
            'int' => MYSQLI_TYPE_LONG,
            'long' => MYSQLI_TYPE_LONGLONG,
            'real', 'decimal' => MYSQLI_TYPE_DOUBLE,
            'datetime' => MYSQLI_TYPE_DATETIME,
            'timespan' => MYSQLI_TYPE_TIMESTAMP,
            'guid' => MYSQLI_TYPE_VAR_STRING,
            'dynamic' => MYSQLI_TYPE_BLOB,       // JSON-like
            default => MYSQLI_TYPE_STRING,        // string, and everything else
        };
    }

    /**
     * Estimate a display length for Kusto types.
     */
    private static function estimateLength(string $kustoType): int
    {
        return match ($kustoType) {
            'bool' => 5,
            'int' => 11,
            'long' => 20,
            'real', 'decimal' => 22,
            'datetime' => 26,    // ISO 8601 format
            'timespan' => 26,
            'guid' => 36,
            'dynamic' => 65535,
            default => 255,
        };
    }

    /**
     * Create a FieldMetadata from a Kusto column definition.
     */
    public static function create(string $columnName, string $kustoType, string $table = ''): FieldMetadata
    {
        $mysqliType = self::mapKustoType($kustoType);

        $field = new stdClass();
        $field->name = $columnName;
        $field->orgname = $columnName;
        $field->table = $table;
        $field->orgtable = $table;
        $field->max_length = 0;
        $field->length = self::estimateLength($kustoType);
        $field->charsetnr = 33;  // utf8_general_ci
        $field->flags = 0;
        $field->type = $mysqliType;
        $field->decimals = ($kustoType === 'real' || $kustoType === 'decimal') ? 6 : 0;
        $field->db = '';
        $field->def = '';
        $field->catalog = 'def';

        return new FieldMetadata($field);
    }
}
