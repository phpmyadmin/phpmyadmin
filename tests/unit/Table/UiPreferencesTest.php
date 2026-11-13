<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Table\UiPreferences;
use PhpMyAdmin\Table\UiProperty;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UiPreferences::class)]
final class UiPreferencesTest extends TestCase
{
    public function testFromArrayWithValidData(): void
    {
        $data = [
            'sorted_col' => '`id` DESC',
            'col_order' => [2, 0, 1],
            'col_visib' => [1, 0, 1],
            'CREATE_TIME' => '2026-11-09 14:00:00',
        ];

        $uiPreferences = UiPreferences::fromArray($data);

        self::assertSame('`id` DESC', $uiPreferences->sortedColumn);
        self::assertSame([2, 0, 1], $uiPreferences->columnOrder);
        self::assertSame([1, 0, 1], $uiPreferences->columnVisibility);
        self::assertSame('2026-11-09 14:00:00', $uiPreferences->createTime);
        self::assertSame($data, $uiPreferences->toArray());
    }

    public function testFromArrayCastsScalarItemsToInt(): void
    {
        $uiPreferences = UiPreferences::fromArray(['col_order' => ['1', '0'], 'col_visib' => [true, false]]);

        self::assertSame([1, 0], $uiPreferences->columnOrder);
        self::assertSame([1, 0], $uiPreferences->columnVisibility);
    }

    #[DataProvider('providerForInvalidData')]
    public function testFromArrayDropsInvalidData(mixed $data): void
    {
        self::assertSame([], UiPreferences::fromArray($data)->toArray());
    }

    /** @return iterable<string, array{mixed}> */
    public static function providerForInvalidData(): iterable
    {
        yield 'null from invalid JSON' => [null];
        yield 'string' => ['col_order'];
        yield 'empty array' => [[]];
        yield 'wrong value types' => [['sorted_col' => 1, 'col_order' => '1,0', 'CREATE_TIME' => 0]];
        yield 'non-list column order' => [['col_order' => [1 => 0, 2 => 1]]];
        yield 'non-scalar item' => [['col_visib' => [1, [0]]]];
    }

    public function testWithAndWithout(): void
    {
        $uiPreferences = (new UiPreferences())
            ->withSortedColumn('`name` ASC')
            ->withColumnOrder([1, 0])
            ->withColumnVisibility([0, 1])
            ->withCreateTime('2026-11-09 14:00:00');

        self::assertTrue($uiPreferences->has(UiProperty::SortedColumn));
        self::assertTrue($uiPreferences->has(UiProperty::ColumnOrder));
        self::assertTrue($uiPreferences->has(UiProperty::ColumnVisibility));

        $withoutOrder = $uiPreferences->without(UiProperty::ColumnOrder);

        self::assertFalse($withoutOrder->has(UiProperty::ColumnOrder));
        self::assertSame(
            ['sorted_col' => '`name` ASC', 'col_visib' => [0, 1], 'CREATE_TIME' => '2026-11-09 14:00:00'],
            $withoutOrder->toArray(),
        );
        self::assertSame([1, 0], $uiPreferences->columnOrder);
    }
}
