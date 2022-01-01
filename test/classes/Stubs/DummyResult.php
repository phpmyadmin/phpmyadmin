<?php
/**
 * Extension independent database result
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Stubs;

use Generator;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\FieldMetadata;

use function array_column;
use function is_string;

/**
 * Extension independent database result
 */
class DummyResult implements ResultInterface
{
    /**
     * The result identifier produced by the DBiExtension
     *
     * @var int|false $result
     */
    private $result;

    /**
     * Link to DbiDummy instance
     *
     * @var DbiDummy
     */
    private $link;

    /**
     * @param int|false $result
     */
    public function __construct(DbiDummy $link, $result)
    {
        $this->link = $link;
        $this->result = $result;
    }

    /**
     * Returns a generator that traverses through the whole result set
     * and returns each row as an associative array
     *
     * @return Generator<int, array<string, string|null>, mixed, void>
     */
    public function getIterator(): Generator
    {
        if ($this->result === false) {
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
     * @return array<string,string|null>
     */
    public function fetchAssoc(): array
    {
        if ($this->result === false) {
            return [];
        }

        return $this->link->fetchAssoc($this->result) ?? [];
    }

    /**
     * Returns the next row of the result with numeric keys
     *
     * @return array<int,string|null>
     */
    public function fetchRow(): array
    {
        if ($this->result === false) {
            return [];
        }

        return $this->link->fetchRow($this->result) ?? [];
    }

    /**
     * Returns a single value from the given result; false on error
     *
     * @param int|string $field
     *
     * @return string|false|null
     */
    public function fetchValue($field = 0)
    {
        if (is_string($field)) {
            $row = $this->fetchAssoc();
        } else {
            $row = $this->fetchRow();
        }

        return $row[$field] ?? false;
    }

    /**
     * Returns all rows of the result
     *
     * @return array<int, array<string,string|null>>
     */
    public function fetchAllAssoc(): array
    {
        if ($this->result === false) {
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
     */
    public function fetchAllColumn(): array
    {
        if ($this->result === false) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->seek(0);

        $rows = [];
        while ($row = $this->fetchRow()) {
            $rows[] = $row[0];
        }

        return $rows;
    }

    /**
     * Returns values as single dimensional array where the key is the first column
     * and the value is the second column, e.g.
     * SELECT id, name FROM users
     * produces: ['123' => 'John', '124' => 'Jane']
     *
     * @return array<string, string|null>
     */
    public function fetchAllKeyPair(): array
    {
        if ($this->result === false) {
            return [];
        }

        // This function should return all rows, not only the remaining rows
        $this->seek(0);

        $rows = [];
        while ($row = $this->fetchRow()) {
            $rows[$row[0] ?? ''] = $row[1];
        }

        return $rows;
    }

    /**
     * Returns the number of fields in the result
     */
    public function numFields(): int
    {
        if ($this->result === false) {
            return 0;
        }

        return $this->link->numFields($this->result);
    }

    /**
     * Returns the number of rows in the result
     *
     * @return string|int
     * @psalm-return int|numeric-string
     */
    public function numRows()
    {
        return $this->link->numRows($this->result);
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
        if ($this->result === false) {
            return false;
        }

        return $this->link->dataSeek($this->result, $offset);
    }

    /**
     * returns meta info for fields in $result
     *
     * @return array<int, FieldMetadata> meta info for fields in $result
     */
    public function getFieldsMeta(): array
    {
        if ($this->result === false) {
            return [];
        }

        return $this->link->getFieldsMeta($this->result);
    }

    /**
     * Returns the names of the fields in the result
     *
     * @return array<int, string> Fields names
     */
    public function getFieldNames(): array
    {
        if ($this->result === false) {
            return [];
        }

        return array_column($this->link->getFieldsMeta($this->result), 'name');
    }
}
