<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Utils\NestedArray;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NestedArray::class)]
final class NestedArrayTest extends TestCase
{
    /**
     * Test for NestedArray::get
     */
    public function testGet(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => ['val1', 'val2', 'val3'],
            'sarr' => ['arr1' => [1, 2, 3], [3, ['a', 'b', 'c'], 4]],
        ];

        self::assertSame(
            NestedArray::get('int', $arr),
            $arr['int'],
        );

        self::assertSame(
            NestedArray::get('str', $arr),
            $arr['str'],
        );

        self::assertSame(
            NestedArray::get('arr/0', $arr),
            $arr['arr'][0],
        );

        self::assertSame(
            NestedArray::get('arr/1', $arr),
            $arr['arr'][1],
        );

        self::assertSame(
            NestedArray::get('arr/2', $arr),
            $arr['arr'][2],
        );

        self::assertSame(
            NestedArray::get('sarr/arr1/0', $arr),
            $arr['sarr']['arr1'][0],
        );

        self::assertSame(
            NestedArray::get('sarr/arr1/1', $arr),
            $arr['sarr']['arr1'][1],
        );

        self::assertSame(
            NestedArray::get('sarr/arr1/2', $arr),
            $arr['sarr']['arr1'][2],
        );

        self::assertSame(
            NestedArray::get('sarr/0/0', $arr),
            $arr['sarr'][0][0],
        );

        self::assertSame(
            NestedArray::get('sarr/0/1', $arr),
            $arr['sarr'][0][1],
        );

        self::assertSame(
            NestedArray::get('sarr/0/1/2', $arr),
            $arr['sarr'][0][1][2],
        );

        self::assertNull(
            NestedArray::get('sarr/not_exiting/1', $arr),
        );

        self::assertSame(
            0,
            NestedArray::get('sarr/not_exiting/1', $arr, 0),
        );

        self::assertSame(
            'default_val',
            NestedArray::get('sarr/not_exiting/1', $arr, 'default_val'),
        );

        self::assertSame('default_val', NestedArray::get('int/x', $arr, 'default_val'));
        self::assertSame('default_val', NestedArray::get('str/0', $arr, 'default_val'));
        self::assertSame('default_val', NestedArray::get('str/x/y', $arr, 'default_val'));
    }

    /**
     * Test for NestedArray::set
     */
    public function testSet(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => ['val1', 'val2', 'val3'],
            'sarr' => ['arr1' => [1, 2, 3], [3, ['a', 'b', 'c'], 4]],
        ];

        $arr = NestedArray::set('int', $arr, 5);
        self::assertSame(5, $arr['int']);

        $arr = NestedArray::set('str', $arr, '_str');
        self::assertSame('_str', $arr['str']);

        $arr = NestedArray::set('arr/0', $arr, 'val_arr_0');
        self::assertSame('val_arr_0', $arr['arr'][0]);

        $arr = NestedArray::set('arr/1', $arr, 'val_arr_1');
        self::assertSame('val_arr_1', $arr['arr'][1]);

        $arr = NestedArray::set('arr/2', $arr, 'val_arr_2');
        self::assertSame('val_arr_2', $arr['arr'][2]);

        $arr = NestedArray::set('sarr/arr1/0', $arr, 'val_sarr_arr_0');
        self::assertSame('val_sarr_arr_0', $arr['sarr']['arr1'][0]);

        $arr = NestedArray::set('sarr/arr1/1', $arr, 'val_sarr_arr_1');
        self::assertSame('val_sarr_arr_1', $arr['sarr']['arr1'][1]);

        $arr = NestedArray::set('sarr/arr1/2', $arr, 'val_sarr_arr_2');
        self::assertSame('val_sarr_arr_2', $arr['sarr']['arr1'][2]);

        $arr = NestedArray::set('sarr/0/0', $arr, 5);
        self::assertSame(5, $arr['sarr'][0][0]);

        $arr = NestedArray::set('sarr/0/1/0', $arr, 'e');
        self::assertSame('e', $arr['sarr'][0][1][0]);

        $arr = NestedArray::set('sarr/not_existing/1', $arr, 'some_val');
        self::assertSame('some_val', $arr['sarr']['not_existing'][1]);

        $arr = NestedArray::set('sarr/0/2', $arr, null);
        self::assertNull($arr['sarr'][0][2]);

        $arr = NestedArray::set('int/x', $arr, 'val_int_x');
        self::assertSame(['x' => 'val_int_x'], $arr['int']);

        $arr = NestedArray::set('str/0', $arr, 'val_str_0');
        self::assertSame(['val_str_0'], $arr['str']);

        $arr = NestedArray::set('arr/0/x/y', $arr, 'val_arr_0_x_y');
        self::assertSame([['x' => ['y' => 'val_arr_0_x_y']], 'val_arr_1', 'val_arr_2'], $arr['arr']);
    }

    /**
     * Test for NestedArray::remove
     */
    public function testRemove(): void
    {
        $arr = [
            'int' => 1,
            'str' => 'str_val',
            'arr' => ['val1', 'val2', 'val3'],
            'sarr' => ['arr1' => [1, 2, 3], [3, ['a', 'b', 'c'], 4]],
        ];

        $tmpArr = $arr;
        $arr = NestedArray::remove('int/x', $arr);
        self::assertSame($tmpArr, $arr);

        $arr = NestedArray::remove('int', $arr);
        self::assertArrayNotHasKey('int', $arr);

        $arr = NestedArray::remove('str', $arr);
        self::assertArrayNotHasKey('str', $arr);

        $arr = NestedArray::remove('arr/0', $arr);
        self::assertArrayNotHasKey(0, $arr['arr']);

        $arr = NestedArray::remove('arr/1', $arr);
        self::assertArrayNotHasKey(1, $arr['arr']);

        $arr = NestedArray::remove('arr/2', $arr);
        self::assertArrayNotHasKey('arr', $arr);

        $tmpArr = $arr;
        $arr = NestedArray::remove('sarr/not_existing/1', $arr);
        self::assertSame($tmpArr, $arr);

        $arr = NestedArray::remove('sarr/arr1/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr']['arr1']);

        $arr = NestedArray::remove('sarr/arr1/1', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr']['arr1']);

        $arr = NestedArray::remove('sarr/arr1/2', $arr);
        self::assertArrayNotHasKey('arr1', $arr['sarr']);

        $arr = NestedArray::remove('sarr/0/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr'][0]);

        $arr = NestedArray::remove('sarr/0/1/0', $arr);
        self::assertArrayNotHasKey(0, $arr['sarr'][0][1]);

        $arr = NestedArray::remove('sarr/0/1/1', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr'][0][1]);

        $arr = NestedArray::remove('sarr/0/1/2', $arr);
        self::assertArrayNotHasKey(1, $arr['sarr'][0]);

        $arr = NestedArray::remove('sarr/0/2', $arr);
        self::assertEmpty($arr);
    }
}
