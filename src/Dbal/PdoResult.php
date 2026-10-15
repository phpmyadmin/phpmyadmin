<?php
/**
 * Extension independent database result for the PDO extension
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use Generator;
use PDO;
use PDOStatement;
use PhpMyAdmin\FieldMetadata;
use Webmozart\Assert\Assert;

use function array_column;
use function array_combine;
use function array_key_exists;
use function count;
use function is_array;
use function is_int;
use function is_string;

/**
 * Extension independent database result for the PDO extension
 *
 * Buffered results are materialized into memory so that the result can be
 * iterated more than once and {@see self::seek()} can be implemented, since
 * a PDOStatement only offers a forward-only cursor.
 */
final class PdoResult implements ResultInterface
{
    /**
     * The result rows with numeric keys, null when the result is unbuffered
     *
     * @var list<list<string|null>>|null
     */
    private array|null $rows = null;

    private int $position = 0;

    /** @var int Number of rows fetched so far from an unbuffered result */
    private int $fetchedRows = 0;

    private PDOStatement|null $statement;

    /**
     * The raw column meta as returned by PDOStatement::getColumnMeta()
     *
     * @var list<array<string, mixed>>
     */
    private array $meta = [];

    /** @var list<string> */
    private array $fieldNames = [];

    public function __construct(PDOStatement|bool|null $statement, bool $buffered = true)
    {
        $this->statement = $statement instanceof PDOStatement ? $statement : null;

        if ($this->statement === null || $this->statement->columnCount() === 0) {
            $this->statement = null;

            return;
        }

        // The column meta has to be read while the cursor is still open.
        $fieldCount = $this->statement->columnCount();
        for ($i = 0; $i < $fieldCount; $i++) {
            /** @var array{name?: string}|false $meta */
            $meta = $this->statement->getColumnMeta($i);
            if (! is_array($meta)) {
                $meta = [];
            }

            $this->meta[] = $meta;
            $this->fieldNames[] = $meta['name'] ?? '';
        }

        if (! $buffered) {
            return;
        }

        /** @var list<list<string|null>> $rows */
        $rows = $this->statement->fetchAll(PDO::FETCH_NUM);
        $this->rows = $rows;
        $this->statement = null;
    }

    /**
     * Returns a generator that traverses through the whole result set
     * and returns each row as an associative array
     *
     * @psalm-return Generator<int, array<array-key, string|null>, mixed, void>
     */
    public function getIterator(): Generator
    {
        if ($this->rows !== null) {
            foreach ($this->rows as $row) {
                yield array_combine($this->fieldNames, $row);
            }

            return;
        }

        while (($row = $this->fetchAssoc()) !== []) {
            yield $row;
        }
    }

    /**
     * Returns the next row of the result with associative keys
     *
     * @return array<string|null>
     * @psalm-return array<array-key, string|null>
     */
    public function fetchAssoc(): array
    {
        $row = $this->fetchRow();

        return $row === [] ? [] : array_combine($this->fieldNames, $row);
    }

    /**
     * Returns the next row of the result with numeric keys
     *
     * @return array<int,string|null>
     * @psalm-return list<string|null>
     */
    public function fetchRow(): array
    {
        if ($this->rows !== null) {
            return $this->rows[$this->position++] ?? [];
        }

        if ($this->statement === null) {
            return [];
        }

        /** @var list<string|null>|false $row */
        $row = $this->statement->fetch(PDO::FETCH_NUM);
        if (! is_array($row)) {
            return [];
        }

        $this->fetchedRows++;

        return $row;
    }

    /**
     * Returns a single value from the given result; false on error
     */
    public function fetchValue(int|string $field = 0): string|false|null
    {
        $row = is_string($field) ? $this->fetchAssoc() : $this->fetchRow();

        if (! array_key_exists($field, $row)) {
            return false;
        }

        return $row[$field];
    }

    /**
     * Returns all rows of the result
     *
     * @return array<int, array<string|null>>
     * @psalm-return list<array<array-key, string|null>>
     */
    public function fetchAllAssoc(): array
    {
        $rows = [];
        foreach ($this->allRows() as $row) {
            $rows[] = array_combine($this->fieldNames, $row);
        }

        return $rows;
    }

    /**
     * Returns values from the selected column of each row
     *
     * @return array<int, string|null>
     * @psalm-return list<string|null>
     */
    public function fetchAllColumn(int|string $column = 0): array
    {
        $rows = $this->allRows();
        if (is_string($column)) {
            /** @var list<string|null> $columnValues */
            $columnValues = array_column($this->fieldNamesToKeys($rows), $column);

            return $columnValues;
        }

        /** @var list<string|null> $columnValues */
        $columnValues = array_column($rows, $column);

        return $columnValues;
    }

    /**
     * Returns values as single dimensional array where the key is the first column
     * and the value is the second column, e.g.
     * SELECT id, name FROM users
     * produces: ['123' => 'John', '124' => 'Jane']
     *
     * @return array<string|null>
     * @psalm-return array<array-key, string|null>
     */
    public function fetchAllKeyPair(): array
    {
        Assert::greaterThanEq($this->numFields(), 2);

        /** @var array<array-key, string|null> $keyPairs */
        $keyPairs = array_column($this->allRows(), 1, 0);

        return $keyPairs;
    }

    /**
     * Returns the number of fields in the result
     */
    public function numFields(): int
    {
        return count($this->fieldNames);
    }

    /**
     * Returns the number of rows in the result
     */
    public function numRows(): int
    {
        if ($this->rows !== null) {
            return count($this->rows);
        }

        return $this->fetchedRows;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param int $offset offset to seek
     *
     * @return bool True if the offset exists, false otherwise
     */
    public function seek(int $offset): bool
    {
        if ($this->rows === null || $offset >= count($this->rows)) {
            return false;
        }

        $this->position = $offset;

        return true;
    }

    /**
     * Returns meta info for fields in $result
     *
     * @return list<FieldMetadata> meta info for fields in $result
     */
    public function getFieldsMeta(): array
    {
        $fields = [];
        foreach ($this->meta as $meta) {
            $fields[] = new FieldMetadata($this->toMysqliShapedField($meta));
        }

        return $fields;
    }

    /**
     * Returns the names of the fields in the result
     *
     * @return list<string> Fields names
     */
    public function getFieldNames(): array
    {
        return $this->fieldNames;
    }

    /**
     * Returns all rows of the result with numeric keys
     *
     * @psalm-return list<list<string|null>>
     */
    private function allRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        if ($this->statement === null) {
            return [];
        }

        /** @var list<list<string|null>> $rows */
        $rows = $this->statement->fetchAll(PDO::FETCH_NUM);
        $this->fetchedRows += count($rows);

        return $rows;
    }

    /**
     * @psalm-param list<list<string|null>> $rows
     *
     * @psalm-return list<array<array-key, string|null>>
     */
    private function fieldNamesToKeys(array $rows): array
    {
        $assocRows = [];
        foreach ($rows as $row) {
            $assocRows[] = array_combine($this->fieldNames, $row);
        }

        return $assocRows;
    }

    /**
     * Builds an object with the shape of a mysqli field so that the meta info
     * of both extensions can be handled by {@see FieldMetadata}.
     *
     * PDO does not expose the original column/table names nor the charset of
     * a column, so those degrade to the aliased name and to a non-binary
     * charset respectively.
     *
     * @param array<string, mixed> $meta
     *
     * @psalm-return object{
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
     * }
     */
    private function toMysqliShapedField(array $meta): object
    {
        /** @var list<string> $flagNames */
        $flagNames = is_array($meta['flags'] ?? null) ? $meta['flags'] : [];
        $flags = 0;
        foreach ($flagNames as $flagName) {
            $flags |= match ($flagName) {
                'not_null' => FieldMetadata::NOT_NULL_FLAG,
                'primary_key' => FieldMetadata::PRI_KEY_FLAG,
                'unique_key' => FieldMetadata::UNIQUE_KEY_FLAG,
                'multiple_key' => FieldMetadata::MULTIPLE_KEY_FLAG,
                'blob' => FieldMetadata::BLOB_FLAG,
                'unsigned' => FieldMetadata::UNSIGNED_FLAG,
                'zerofill' => FieldMetadata::ZEROFILL_FLAG,
                default => 0,
            };
        }

        /** @var string|null $name */
        $name = $meta['name'] ?? null;
        $name = is_string($name) ? $name : '';
        /** @var string|null $table */
        $table = $meta['table'] ?? null;
        $table = is_string($table) ? $table : '';
        /** @var int|string|null $length */
        $length = $meta['len'] ?? null;
        /** @var int|string|null $decimals */
        $decimals = $meta['precision'] ?? null;

        return (object) [
            'name' => $name,
            'orgname' => $name,
            'table' => $table,
            'orgtable' => $table,
            'max_length' => 0,
            'length' => is_int($length) ? $length : 0,
            'charsetnr' => 0,
            'flags' => $flags,
            'type' => $this->mapNativeType($meta['native_type'] ?? null),
            'decimals' => is_int($decimals) ? $decimals : 0,
            'db' => '',
            'def' => '',
            'catalog' => 'def',
        ];
    }

    /**
     * Maps the native type name reported by pdo_mysql to the matching
     * MySQL protocol type constant.
     */
    private function mapNativeType(mixed $nativeType): int
    {
        return match ($nativeType) {
            'DECIMAL' => FieldMetadata::MYSQL_TYPE_DECIMAL,
            'NEWDECIMAL' => FieldMetadata::MYSQL_TYPE_NEWDECIMAL,
            'TINY' => FieldMetadata::MYSQL_TYPE_TINY,
            'SHORT' => FieldMetadata::MYSQL_TYPE_SHORT,
            'LONG' => FieldMetadata::MYSQL_TYPE_LONG,
            'FLOAT' => FieldMetadata::MYSQL_TYPE_FLOAT,
            'DOUBLE' => FieldMetadata::MYSQL_TYPE_DOUBLE,
            'NULL' => FieldMetadata::MYSQL_TYPE_NULL,
            'TIMESTAMP' => FieldMetadata::MYSQL_TYPE_TIMESTAMP,
            'LONGLONG' => FieldMetadata::MYSQL_TYPE_LONGLONG,
            'INT24' => FieldMetadata::MYSQL_TYPE_INT24,
            'DATE' => FieldMetadata::MYSQL_TYPE_DATE,
            'TIME' => FieldMetadata::MYSQL_TYPE_TIME,
            'DATETIME' => FieldMetadata::MYSQL_TYPE_DATETIME,
            'YEAR' => FieldMetadata::MYSQL_TYPE_YEAR,
            'NEWDATE' => FieldMetadata::MYSQL_TYPE_NEWDATE,
            'ENUM' => FieldMetadata::MYSQL_TYPE_ENUM,
            'SET' => FieldMetadata::MYSQL_TYPE_SET,
            'TINY_BLOB' => FieldMetadata::MYSQL_TYPE_TINY_BLOB,
            'MEDIUM_BLOB' => FieldMetadata::MYSQL_TYPE_MEDIUM_BLOB,
            'LONG_BLOB' => FieldMetadata::MYSQL_TYPE_LONG_BLOB,
            'BLOB' => FieldMetadata::MYSQL_TYPE_BLOB,
            'VAR_STRING' => FieldMetadata::MYSQL_TYPE_VAR_STRING,
            'STRING' => FieldMetadata::MYSQL_TYPE_STRING,
            'GEOMETRY' => FieldMetadata::MYSQL_TYPE_GEOMETRY,
            'BIT' => FieldMetadata::MYSQL_TYPE_BIT,
            'JSON' => FieldMetadata::MYSQL_TYPE_JSON,
            default => FieldMetadata::MYSQL_TYPE_NULL,
        };
    }
}
