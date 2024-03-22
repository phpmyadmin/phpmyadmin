<?php
/**
 * Library for extracting information about the sub-partitions
 */

declare(strict_types=1);

namespace PhpMyAdmin\Partitioning;

/**
 * Represents a sub partition of a table
 */
class SubPartition
{
    protected string|null $name = null;
    protected int|null $ordinal = null;
    protected string|null $method = null;
    protected string|null $expression = null;
    protected int $rows = 0;
    protected int $dataLength = 0;
    protected int $indexLength = 0;
    protected string $comment = '';

    /**
     * Constructs a partition
     *
     * @param mixed[] $row fetched row from information_schema.PARTITIONS
     */
    public function __construct(array $row)
    {
        $this->name = $row['SUBPARTITION_NAME'];
        $this->ordinal = $row['SUBPARTITION_ORDINAL_POSITION'] !== null
            ? (int) $row['SUBPARTITION_ORDINAL_POSITION'] : null;
        $this->method = $row['SUBPARTITION_METHOD'];
        $this->expression = $row['SUBPARTITION_EXPRESSION'];
        $this->loadCommonData($row);
    }

    /**
     * Loads some data that is common to both partitions and sub partitions
     *
     * @param mixed[] $row fetched row
     */
    protected function loadCommonData(array $row): void
    {
        $this->rows = (int) $row['TABLE_ROWS'];
        $this->dataLength = (int) $row['DATA_LENGTH'];
        $this->indexLength = (int) $row['INDEX_LENGTH'];
        $this->comment = $row['PARTITION_COMMENT'];
    }

    /**
     * Return the partition name
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Return the ordinal of the partition
     */
    public function getOrdinal(): int|null
    {
        return $this->ordinal;
    }

    /**
     * Returns the partition method
     */
    public function getMethod(): string|null
    {
        return $this->method;
    }

    /**
     * Returns the partition expression
     */
    public function getExpression(): string|null
    {
        return $this->expression;
    }

    /**
     * Returns the number of data rows
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    /**
     * Returns the data length
     */
    public function getDataLength(): int
    {
        return $this->dataLength;
    }

    /**
     * Returns the index length
     */
    public function getIndexLength(): int
    {
        return $this->indexLength;
    }

    /**
     * Returns the partition comment
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
