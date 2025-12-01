<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Import;

use function time;

use const PHP_INT_MAX;

/**
 * @covers \PhpMyAdmin\Import
 */
class ImportTest extends AbstractTestCase
{
    /** @var Import $import */
    private $import;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['ServerDefault'] = '';
        $this->import = new Import();
    }

    /**
     * Test for checkTimeout
     */
    public function testCheckTimeout(): void
    {
        global $timestamp, $maximum_time, $timeout_passed;

        //Reinit values.
        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = false;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = true;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        $timestamp = time();
        $maximum_time = 30;
        $timeout_passed = true;

        self::assertTrue($this->import->checkTimeout());

        //Reinit values.
        $timestamp = time() - 15;
        $maximum_time = 30;
        $timeout_passed = false;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        $timestamp = time() - 60;
        $maximum_time = 30;
        $timeout_passed = false;

        self::assertTrue($this->import->checkTimeout());
    }

    /**
     * Test for lookForUse
     */
    public function testLookForUse(): void
    {
        self::assertSame([
            null,
            null,
        ], $this->import->lookForUse(null, null, null));

        self::assertSame([
            'myDb',
            null,
        ], $this->import->lookForUse(null, 'myDb', null));

        self::assertSame([
            'myDb',
            true,
        ], $this->import->lookForUse(null, 'myDb', true));

        self::assertSame([
            'myDb',
            true,
        ], $this->import->lookForUse('select 1 from myTable', 'myDb', true));

        self::assertSame([
            'anotherDb',
            true,
        ], $this->import->lookForUse('use anotherDb', 'myDb', false));

        self::assertSame([
            'anotherDb',
            true,
        ], $this->import->lookForUse('use anotherDb', 'myDb', true));

        self::assertSame([
            'anotherDb',
            true,
        ], $this->import->lookForUse('use `anotherDb`;', 'myDb', true));
    }

    /**
     * Test for getColumnAlphaName
     *
     * @param string $expected Expected result of the function
     * @param int    $num      The column number
     *
     * @dataProvider provGetColumnAlphaName
     */
    public function testGetColumnAlphaName(string $expected, int $num): void
    {
        self::assertSame($expected, $this->import->getColumnAlphaName($num));
    }

    /**
     * Data provider for testGetColumnAlphaName
     *
     * @return array
     */
    public static function provGetColumnAlphaName(): array
    {
        return [
            [
                'A',
                1,
            ],
            [
                'Z',
                0,
            ],
            [
                'AA',
                27,
            ],
            [
                'AZ',
                52,
            ],
            [
                'BA',
                53,
            ],
            [
                'BB',
                54,
            ],
        ];
    }

    /**
     * Test for getColumnNumberFromName
     *
     * @param int    $expected Expected result of the function
     * @param string $name     column name(i.e. "A", or "BC", etc.)
     *
     * @dataProvider provGetColumnNumberFromName
     */
    public function testGetColumnNumberFromName(int $expected, string $name): void
    {
        self::assertSame($expected, $this->import->getColumnNumberFromName($name));
    }

    /**
     * Data provider for testGetColumnNumberFromName
     *
     * @return array
     */
    public static function provGetColumnNumberFromName(): array
    {
        return [
            [
                1,
                'A',
            ],
            [
                26,
                'Z',
            ],
            [
                27,
                'AA',
            ],
            [
                52,
                'AZ',
            ],
            [
                53,
                'BA',
            ],
            [
                54,
                'BB',
            ],
        ];
    }

    /**
     * Test for getDecimalPrecision
     *
     * @param int    $expected Expected result of the function
     * @param string $size     Size of field
     *
     * @dataProvider provGetDecimalPrecision
     */
    public function testGetDecimalPrecision(int $expected, string $size): void
    {
        self::assertSame($expected, $this->import->getDecimalPrecision($size));
    }

    /**
     * Data provider for testGetDecimalPrecision
     *
     * @return array
     */
    public static function provGetDecimalPrecision(): array
    {
        return [
            [
                2,
                '2,1',
            ],
            [
                6,
                '6,2',
            ],
            [
                6,
                '6,0',
            ],
            [
                16,
                '16,2',
            ],
        ];
    }

    /**
     * Test for getDecimalScale
     *
     * @param int    $expected Expected result of the function
     * @param string $size     Size of field
     *
     * @dataProvider provGetDecimalScale
     */
    public function testGetDecimalScale(int $expected, string $size): void
    {
        self::assertSame($expected, $this->import->getDecimalScale($size));
    }

    /**
     * Data provider for testGetDecimalScale
     *
     * @return array
     */
    public static function provGetDecimalScale(): array
    {
        return [
            [
                1,
                '2,1',
            ],
            [
                2,
                '6,2',
            ],
            [
                0,
                '6,0',
            ],
            [
                20,
                '30,20',
            ],
        ];
    }

    /**
     * Test for getDecimalSize
     *
     * @param array  $expected Expected result of the function
     * @param string $cell     Cell content
     *
     * @dataProvider provGetDecimalSize
     */
    public function testGetDecimalSize(array $expected, string $cell): void
    {
        self::assertSame($expected, $this->import->getDecimalSize($cell));
    }

    /**
     * Data provider for testGetDecimalSize
     *
     * @return array
     */
    public static function provGetDecimalSize(): array
    {
        return [
            [
                [
                    2,
                    1,
                    '2,1',
                ],
                '2.1',
            ],
            [
                [
                    2,
                    1,
                    '2,1',
                ],
                '6.2',
            ],
            [
                [
                    3,
                    1,
                    '3,1',
                ],
                '10.0',
            ],
            [
                [
                    4,
                    2,
                    '4,2',
                ],
                '30.20',
            ],
        ];
    }

    /**
     * Test for detectType
     *
     * @param int         $expected Expected result of the function
     * @param int|null    $type     Last cumulative column type (VARCHAR or INT or
     *                              BIGINT or DECIMAL or NONE)
     * @param string|null $cell     String representation of the cell for which a
     *                              best-fit type is to be determined
     *
     * @dataProvider provDetectType
     */
    public function testDetectType(int $expected, ?int $type, ?string $cell): void
    {
        self::assertSame($expected, $this->import->detectType($type, $cell));
    }

    /**
     * Data provider for testDetectType
     *
     * @return array
     */
    public static function provDetectType(): array
    {
        $data = [
            [
                Import::NONE,
                null,
                'NULL',
            ],
            [
                Import::NONE,
                Import::NONE,
                'NULL',
            ],
            [
                Import::INT,
                Import::INT,
                'NULL',
            ],
            [
                Import::VARCHAR,
                Import::VARCHAR,
                'NULL',
            ],
            [
                Import::VARCHAR,
                null,
                null,
            ],
            [
                Import::VARCHAR,
                Import::INT,
                null,
            ],
            [
                Import::INT,
                Import::INT,
                '10',
            ],
            [
                Import::DECIMAL,
                Import::DECIMAL,
                '10.2',
            ],
            [
                Import::DECIMAL,
                Import::INT,
                '10.2',
            ],
            [
                Import::VARCHAR,
                Import::VARCHAR,
                'test',
            ],
            [
                Import::VARCHAR,
                Import::INT,
                'test',
            ],
        ];

        if (PHP_INT_MAX > 2147483647) {
            $data[] = [
                Import::BIGINT,
                Import::BIGINT,
                '2147483648',
            ];
            $data[] = [
                Import::BIGINT,
                Import::INT,
                '2147483648',
            ];
        } else {
            // To be fixed ?
            // Can not detect a BIGINT since the value is over PHP_INT_MAX
            $data[] = [
                Import::VARCHAR,
                Import::BIGINT,
                '2147483648',
            ];
            $data[] = [
                Import::VARCHAR,
                Import::INT,
                '2147483648',
            ];
        }

        return $data;
    }

    /**
     * Test for detectSize
     *
     * @param string|int $lastCumulativeSize Last cumulative column size
     * @param int|null   $lastCumulativeType Last cumulative column type (NONE or VARCHAR or DECIMAL or INT or BIGINT)
     * @param int        $currentCellType    Type of the current cell (NONE or VARCHAR or DECIMAL or INT or BIGINT)
     * @param string     $cell               The current cell
     *
     * @dataProvider provTestDetectSize
     */
    public function testDetectSize(
        string $expected,
        $lastCumulativeSize,
        ?int $lastCumulativeType,
        int $currentCellType,
        string $cell
    ): void {
        $actual = $this->import->detectSize($lastCumulativeSize, $lastCumulativeType, $currentCellType, $cell);
        self::assertSame($expected, $actual);
    }

    /**
     * Data provider for testDetectSize
     *
     * @return array<array{string,string|int,int,int,string}>
     */
    public function provTestDetectSize(): array
    {
        return [
            [
                '8,3',
                '7,2',
                Import::DECIMAL,
                Import::DECIMAL,
                '11.555',
            ],
        ];
    }

    /**
     * Test for checkIfRollbackPossible
     */
    public function testPMACheckIfRollbackPossible(): void
    {
        $GLOBALS['db'] = 'PMA';

        $sqlQuery = 'UPDATE `table_1` AS t1, `table_2` t2 SET `table_1`.`id` = `table_2`.`id` WHERE 1';

        self::assertTrue($this->import->checkIfRollbackPossible($sqlQuery));
    }

    /**
     * Data provider for testSkipByteOrderMarksFromContents
     *
     * @return array[]
     */
    public static function providerContentWithByteOrderMarks(): array
    {
        return [
            [
                "\xEF\xBB\xBF blabla上海",
                ' blabla上海',
            ],
            [
                "\xEF\xBB\xBF blabla",
                ' blabla',
            ],
            [
                "\xEF\xBB\xBF blabla\xEF\xBB\xBF",
                " blabla\xEF\xBB\xBF",
            ],
            [
                "\xFE\xFF blabla",
                ' blabla',
            ],
            [
                "\xFE\xFF blabla\xFE\xFF",
                " blabla\xFE\xFF",
            ],
            [
                "\xFF\xFE blabla",
                ' blabla',
            ],
            [
                "\xFF\xFE blabla\xFF\xFE",
                " blabla\xFF\xFE",
            ],
            [
                "\xEF\xBB\xBF\x44\x52\x4F\x50\x20\x54\x41\x42\x4C\x45\x20\x49\x46\x20\x45\x58\x49\x53\x54\x53",
                'DROP TABLE IF EXISTS',
            ],
        ];
    }

    /**
     * Test for skipByteOrderMarksFromContents
     *
     * @param string $input         The contents to strip BOM
     * @param string $cleanContents The contents cleaned
     *
     * @dataProvider providerContentWithByteOrderMarks
     */
    public function testSkipByteOrderMarksFromContents(string $input, string $cleanContents): void
    {
        self::assertSame($cleanContents, $this->import->skipByteOrderMarksFromContents($input));
    }

    /**
     * Test for runQuery
     */
    public function testRunQuery(): void
    {
        $GLOBALS['run_query'] = true;
        $sqlData = [];

        $query = 'SELECT 1';
        $full = 'SELECT 1';

        $this->import->runQuery($query, $full, $sqlData);

        self::assertSame([], $sqlData);
        self::assertSame([
            'sql' => 'SELECT 1;',
            'full' => 'SELECT 1;',
        ], $GLOBALS['import_run_buffer']);
        self::assertNull($GLOBALS['sql_query']);
        self::assertNull($GLOBALS['complete_query']);
        self::assertNull($GLOBALS['display_query']);

        $query = 'SELECT 2';
        $full = 'SELECT 2';

        $this->import->runQuery($query, $full, $sqlData);

        self::assertSame([
            'valid_sql' => ['SELECT 1;'],
            'valid_full' => ['SELECT 1;'],
            'valid_queries' => 1,
        ], $sqlData);
        self::assertSame([
            'sql' => 'SELECT 2;',
            'full' => 'SELECT 2;',
        ], $GLOBALS['import_run_buffer']);
        self::assertSame('SELECT 1;', $GLOBALS['sql_query']);
        self::assertSame('SELECT 1;', $GLOBALS['complete_query']);
        self::assertSame('SELECT 1;', $GLOBALS['display_query']);

        $query = '';
        $full = '';

        $this->import->runQuery($query, $full, $sqlData);

        self::assertSame([
            'valid_sql' => [
                'SELECT 1;',
                'SELECT 2;',
            ],
            'valid_full' => [
                'SELECT 1;',
                'SELECT 2;',
            ],
            'valid_queries' => 2,
        ], $sqlData);

        self::assertArrayNotHasKey('import_run_buffer', $GLOBALS);
        self::assertSame('SELECT 2;', $GLOBALS['sql_query']);
        self::assertSame('SELECT 1;SELECT 2;', $GLOBALS['complete_query']);
        self::assertSame('SELECT 1;SELECT 2;', $GLOBALS['display_query']);
    }
}
