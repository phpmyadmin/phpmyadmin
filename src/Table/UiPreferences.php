<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use function array_is_list;
use function is_array;
use function is_scalar;
use function is_string;

final readonly class UiPreferences
{
    private const CREATE_TIME = 'CREATE_TIME';

    /**
     * @param list<int>|null $columnOrder
     * @param list<int>|null $columnVisibility
     */
    public function __construct(
        public string|null $sortedColumn = null,
        public array|null $columnOrder = null,
        public array|null $columnVisibility = null,
        public string|null $createTime = null,
    ) {
    }

    public static function fromArray(mixed $data): self
    {
        if (! is_array($data)) {
            return new self();
        }

        return new self(
            self::toStringOrNull($data[UiProperty::SortedColumn->value] ?? null),
            self::toIntListOrNull($data[UiProperty::ColumnOrder->value] ?? null),
            self::toIntListOrNull($data[UiProperty::ColumnVisibility->value] ?? null),
            self::toStringOrNull($data[self::CREATE_TIME] ?? null),
        );
    }

    /** @return array<string, string|list<int>> */
    public function toArray(): array
    {
        $data = [];
        if ($this->sortedColumn !== null) {
            $data[UiProperty::SortedColumn->value] = $this->sortedColumn;
        }

        if ($this->columnOrder !== null) {
            $data[UiProperty::ColumnOrder->value] = $this->columnOrder;
        }

        if ($this->columnVisibility !== null) {
            $data[UiProperty::ColumnVisibility->value] = $this->columnVisibility;
        }

        if ($this->createTime !== null) {
            $data[self::CREATE_TIME] = $this->createTime;
        }

        return $data;
    }

    public function has(UiProperty $property): bool
    {
        return match ($property) {
            UiProperty::SortedColumn => $this->sortedColumn !== null,
            UiProperty::ColumnOrder => $this->columnOrder !== null,
            UiProperty::ColumnVisibility => $this->columnVisibility !== null,
        };
    }

    public function without(UiProperty $property): self
    {
        return new self(
            $property === UiProperty::SortedColumn ? null : $this->sortedColumn,
            $property === UiProperty::ColumnOrder ? null : $this->columnOrder,
            $property === UiProperty::ColumnVisibility ? null : $this->columnVisibility,
            $this->createTime,
        );
    }

    public function withSortedColumn(string $sortedColumn): self
    {
        return new self($sortedColumn, $this->columnOrder, $this->columnVisibility, $this->createTime);
    }

    /** @param list<int> $columnOrder */
    public function withColumnOrder(array $columnOrder): self
    {
        return new self($this->sortedColumn, $columnOrder, $this->columnVisibility, $this->createTime);
    }

    /** @param list<int> $columnVisibility */
    public function withColumnVisibility(array $columnVisibility): self
    {
        return new self($this->sortedColumn, $this->columnOrder, $columnVisibility, $this->createTime);
    }

    public function withCreateTime(string $createTime): self
    {
        return new self($this->sortedColumn, $this->columnOrder, $this->columnVisibility, $createTime);
    }

    private static function toStringOrNull(mixed $value): string|null
    {
        return is_string($value) ? $value : null;
    }

    /** @return list<int>|null */
    private static function toIntListOrNull(mixed $value): array|null
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        $list = [];
        foreach ($value as $item) {
            if (! is_scalar($item)) {
                return null;
            }

            $list[] = (int) $item;
        }

        return $list;
    }
}
