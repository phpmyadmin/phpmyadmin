<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;

/**
 * Index column wrapper
 */
class IndexColumn
{
    /** @var string The column name */
    private string $name = '';

    /** @var int The column sequence number in the index, starting with 1. */
    private int $seqInIndex = 1;

    /** @var string|null How the column is sorted in the index. "A" (Ascending) or NULL (Not sorted) */
    private string|null $collation = null;

    /**
     * The number of indexed characters if the column is only partly indexed,
     * NULL if the entire column is indexed.
     */
    private int|null $subPart = null;

    /**
     * Contains YES if the column may contain NULL.
     * If not, the column contains NO.
     */
    private string $null = '';

    /**
     * An estimate of the number of unique values in the index. This is updated
     * by running ANALYZE TABLE or myisamchk -a. Cardinality is counted based on
     * statistics stored as integers, so the value is not necessarily exact even
     * for small tables. The higher the cardinality, the greater the chance that
     * MySQL uses the index when doing joins.
     */
    private int|null $cardinality = null;

    /**
     * If the Index uses an expression and not a name
     */
    private string|null $expression = null;

    /** @param mixed[] $params an array containing the parameters of the index column */
    public function __construct(array $params = [])
    {
        $this->set($params);
    }

    /**
     * If the Index has an expression
     */
    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }

    /**
     * The Index expression if it has one
     */
    public function getExpression(): string|null
    {
        return $this->expression;
    }

    /**
     * Sets parameters of the index column
     *
     * @param mixed[] $params an array containing the parameters of the index column
     */
    public function set(array $params): void
    {
        if (isset($params['Column_name'])) {
            $this->name = $params['Column_name'];
        }

        if (isset($params['Seq_in_index'])) {
            $this->seqInIndex = (int) $params['Seq_in_index'];
        }

        if (isset($params['Collation'])) {
            $this->collation = $params['Collation'];
        }

        if (isset($params['Cardinality'])) {
            $this->cardinality = (int) $params['Cardinality'];
        }

        if (isset($params['Sub_part'])) {
            $this->subPart = (int) $params['Sub_part'];
        }

        if (isset($params['Expression'])) {
            $this->expression = $params['Expression'];
        }

        if (! isset($params['Null'])) {
            return;
        }

        $this->null = $params['Null'];
    }

    /**
     * Returns the column name
     *
     * @return string column name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the column collation
     *
     * @return string|null column collation
     */
    public function getCollation(): string|null
    {
        return $this->collation;
    }

    /**
     * Returns the cardinality of the column
     *
     * @return int|null cardinality of the column
     */
    public function getCardinality(): int|null
    {
        return $this->cardinality;
    }

    /**
     * Returns whether the column is nullable
     *
     * @param bool $asText whether to returned the string representation
     *
     * @return string nullability of the column. True/false or Yes/No depending
     *                on the value of the $as_text parameter
     */
    public function getNull(bool $asText = false): string
    {
        if ($asText) {
            if (! $this->null || $this->null === 'NO') {
                return __('No');
            }

            return __('Yes');
        }

        return $this->null;
    }

    /**
     * Returns the sequence number of the column in the index
     *
     * @return int sequence number of the column in the index
     */
    public function getSeqInIndex(): int
    {
        return $this->seqInIndex;
    }

    /**
     * Returns the number of indexed characters if the column is only
     * partly indexed
     *
     * @return int|null the number of indexed characters
     */
    public function getSubPart(): int|null
    {
        return $this->subPart;
    }

    /**
     * Gets the properties in an array for comparison purposes
     *
     * @return array<string, int|string|null>
     * @psalm-return array{
     *   Column_name: string,
     *   Seq_in_index: int,
     *   Collation: string|null,
     *   Sub_part: int|null,
     *   Null: string
     * }
     */
    public function getCompareData(): array
    {
        return [
            'Column_name' => $this->name,
            'Seq_in_index' => $this->seqInIndex,
            'Collation' => $this->collation,
            'Sub_part' => $this->subPart,
            'Null' => $this->null,
        ];
    }
}
