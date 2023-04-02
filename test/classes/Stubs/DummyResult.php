<?php
/**
 * Extension independent database result
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Stubs;

use Generator;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Tests\FieldHelper;

use function array_column;
use function array_key_exists;
use function count;
use function is_string;

use const MYSQLI_TYPE_STRING;

/**
 * Extension independent database result
 */
class DummyResult implements ResultInterface
{
    /**
     * The result identifier produced by the DBiExtension
     *
     * @var mixed[][]|null
     * @psalm-var list<non-empty-list<string|null>>
     */
    private array|null $result;

    /**
     * @var string[]
     * @psalm-var list<non-empty-string>
     */
    private array $columns;

    /**
     * @var FieldMetadata[]
     * @psalm-var list<FieldMetadata>
     */
    private array $metadata;

    private int $pos = 0;

    /**
     * @psalm-param array{
     *     query: string,
     *     result: list<non-empty-list<string|null>>|true,
     *     columns?: list<non-empty-string>,
     *     metadata?: list<FieldMetadata>,
     * } $query
     */
    public function __construct(array $query)
    {
        $this->columns = $query['columns'] ?? [];
        $this->metadata = $query['metadata'] ?? [];
        $this->result = $query['result'] === true ? null : $query['result'];
    }

    /**
     * Returns a generator that traverses through the whole result set
     * and returns each row as an associative array
     *
     * @psalm-return Generator<int, array<array-key, string|null>, mixed, void>
     */
    public function getIterator(): Generator
    {
        if (! $this->result) {
            return;
        }

        $this->seek(0);
        while ($row = $this->fetchAssoc()) {
            yield $row;
        }
    }

    /**
     * Returns the next row of the result with associative keys
     *
     * @return array<string|null>
     */
    public function fetchAssoc(): array
    {
        if (! $this->result) {
            return [];
        }

        $row = $this->result[$this->pos++] ?? [];
        if (! $this->columns) {
            return $row;
        }

        $ret = [];
        foreach ($row as $key => $val) {
            $ret[$this->columns[$key]] = $val;
        }

        return $ret;
    }

    /**
     * Returns the next row of the result with numeric keys
     *
     * @return array<int,string|null>
     * @psalm-return list<string|null>
     */
    public function fetchRow(): array
    {
        if (! $this->result || $this->pos >= count($this->result)) {
            return [];
        }

        return $this->result[$this->pos++];
    }

    /**
     * Returns a single value from the given result; false on error
     */
    public function fetchValue(int|string $field = 0): string|false|null
    {
        if (is_string($field)) {
            $row = $this->fetchAssoc();
        } else {
            $row = $this->fetchRow();
        }

        if (! array_key_exists($field, $row)) {
            return false;
        }

        return $row[$field] ?? null;
    }

    /**
     * Returns all rows of the result
     *
     * @return array<array<string|null>>
     * @psalm-return list<array<string|null>>
     */
    public function fetchAllAssoc(): array
    {
        if (! $this->result) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->seek(0);

        $rows = [];
        while ($row = $this->fetchAssoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Returns values from the first column of each row
     *
     * @return array<int, string|null>
     * @psalm-return list<string|null>
     */
    public function fetchAllColumn(): array
    {
        if (! $this->result) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->seek(0);

        return array_column($this->result, 0);
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
        if (! $this->result) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->seek(0);

        return array_column($this->result, 1, 0);
    }

    /**
     * Returns the number of fields in the result
     */
    public function numFields(): int
    {
        if (! $this->result) {
            return 0;
        }

        return count($this->columns);
    }

    /**
     * Returns the number of rows in the result
     *
     * @psalm-return int
     */
    public function numRows(): int
    {
        if (! $this->result) {
            return 0;
        }

        return count($this->result);
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
        if (! $this->result) {
            return false;
        }

        $this->pos = $offset;

        return $offset < count($this->result);
    }

    /**
     * returns meta info for fields in $result
     *
     * @return array<int, FieldMetadata> meta info for fields in $result
     * @psalm-return list<FieldMetadata>
     */
    public function getFieldsMeta(): array
    {
        $metadata = $this->metadata;
        foreach ($this->columns as $i => $column) {
            if (isset($metadata[$i])) {
                $metadata[$i]->name = $column;
            } else {
                $metadata[] = FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING, 'name' => $column]);
            }
        }

        return $metadata;
    }

    /**
     * Returns the names of the fields in the result
     *
     * @return array<int, string> Fields names
     * @psalm-return list<non-empty-string>
     */
    public function getFieldNames(): array
    {
        if (! $this->result) {
            return [];
        }

        return array_column($this->getFieldsMeta(), 'name');
    }
}
