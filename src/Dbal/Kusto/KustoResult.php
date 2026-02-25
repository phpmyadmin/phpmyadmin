<?php
/**
 * Result set implementation for Kusto (Azure Data Explorer) query responses
 */

declare(strict_types=1);

namespace PhpMyAdmin\Dbal\Kusto;

use Generator;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\FieldMetadata;

use function array_column;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function is_string;

/**
 * Wraps Kusto V2 query response data into the ResultInterface contract.
 *
 * Kusto returns results as JSON with columns and rows arrays.
 * This class materialises the entire result in memory (Kusto responses are
 * already fully buffered anyway).
 */
final class KustoResult implements ResultInterface
{
    /** Column names in order */
    private readonly array $columns;

    /** Column types in order (Kusto type strings like "string", "long", "datetime") */
    private readonly array $columnTypes;

    /** All rows as list of associative arrays */
    private readonly array $rows;

    /** Current cursor position for fetch*() calls */
    private int $cursor = 0;

    /**
     * @param list<array{ColumnName: string, ColumnType: string}> $columns
     * @param list<list<mixed>> $rows Raw row data from Kusto (positional)
     */
    public function __construct(array $columns, array $rows)
    {
        $colNames = [];
        $colTypes = [];
        foreach ($columns as $col) {
            $colNames[] = $col['ColumnName'];
            $colTypes[] = $col['ColumnType'] ?? 'string';
        }

        $this->columns = $colNames;
        $this->columnTypes = $colTypes;

        // Convert positional rows to associative
        $assocRows = [];
        foreach ($rows as $row) {
            $assoc = [];
            foreach ($colNames as $i => $name) {
                $assoc[$name] = isset($row[$i]) ? (string) $row[$i] : null;
            }

            $assocRows[] = $assoc;
        }

        $this->rows = $assocRows;
    }

    /**
     * Create an empty result set
     */
    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * Create from a Kusto V2 response primary result table.
     *
     * @param array{Columns: list<array{ColumnName: string, ColumnType: string}>, Rows: list<list<mixed>>} $table
     */
    public static function fromKustoTable(array $table): self
    {
        return new self($table['Columns'] ?? [], $table['Rows'] ?? []);
    }

    /** @psalm-return Generator<int, array<array-key, string|null>, mixed, void> */
    public function getIterator(): Generator
    {
        foreach ($this->rows as $row) {
            yield $row;
        }
    }

    /** @return array<string|null> */
    public function fetchAssoc(): array
    {
        if (! isset($this->rows[$this->cursor])) {
            return [];
        }

        return $this->rows[$this->cursor++];
    }

    /** @return list<string|null> */
    public function fetchRow(): array
    {
        if (! isset($this->rows[$this->cursor])) {
            return [];
        }

        return array_values($this->rows[$this->cursor++]);
    }

    public function fetchValue(int|string $field = 0): string|false|null
    {
        $row = is_string($field) ? $this->fetchAssoc() : $this->fetchRow();

        if (! array_key_exists($field, $row)) {
            return false;
        }

        return $row[$field];
    }

    /** @return list<array<array-key, string|null>> */
    public function fetchAllAssoc(): array
    {
        $this->cursor = 0;

        return $this->rows;
    }

    /** @return list<string|null> */
    public function fetchAllColumn(int|string $column = 0): array
    {
        $this->cursor = 0;

        if (is_string($column)) {
            return array_column($this->rows, $column);
        }

        // For numeric column index, extract values positionally
        $result = [];
        foreach ($this->rows as $row) {
            $values = array_values($row);
            $result[] = $values[$column] ?? null;
        }

        return $result;
    }

    /** @return array<array-key, string|null> */
    public function fetchAllKeyPair(): array
    {
        $this->cursor = 0;

        if (count($this->columns) < 2) {
            return [];
        }

        return array_column($this->rows, $this->columns[1], $this->columns[0]);
    }

    public function numFields(): int
    {
        return count($this->columns);
    }

    /** @return int */
    public function numRows(): string|int
    {
        return count($this->rows);
    }

    public function seek(int $offset): bool
    {
        if ($offset < 0 || $offset >= count($this->rows)) {
            return false;
        }

        $this->cursor = $offset;

        return true;
    }

    /** @return list<FieldMetadata> */
    public function getFieldsMeta(): array
    {
        $fields = [];
        foreach ($this->columns as $i => $name) {
            $fields[] = KustoFieldMetadata::create($name, $this->columnTypes[$i] ?? 'string');
        }

        return $fields;
    }

    /** @return list<string> */
    public function getFieldNames(): array
    {
        return $this->columns;
    }

    /** Get Kusto column types */
    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }
}
