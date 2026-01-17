<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Import\ColumnType;
use PhpMyAdmin\Import\DecimalSize;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function time;

use const PHP_INT_MAX;

#[CoversClass(Import::class)]
class ImportTest extends AbstractTestCase
{
    private Import $import;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$completeQuery = null;
        Current::$displayQuery = null;
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$executedQueries = 0;
        $this->import = new Import();
    }

    /**
     * Test for checkTimeout
     */
    public function testCheckTimeout(): void
    {
        //Reinit values.
        ImportSettings::$timestamp = time();
        ImportSettings::$maximumTime = 0;
        ImportSettings::$timeoutPassed = false;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        ImportSettings::$timestamp = time();
        ImportSettings::$maximumTime = 0;
        ImportSettings::$timeoutPassed = true;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        ImportSettings::$timestamp = time();
        ImportSettings::$maximumTime = 30;
        ImportSettings::$timeoutPassed = true;

        self::assertTrue($this->import->checkTimeout());

        //Reinit values.
        ImportSettings::$timestamp = time() - 15;
        ImportSettings::$maximumTime = 30;
        ImportSettings::$timeoutPassed = false;

        self::assertFalse($this->import->checkTimeout());

        //Reinit values.
        ImportSettings::$timestamp = time() - 60;
        ImportSettings::$maximumTime = 30;
        ImportSettings::$timeoutPassed = false;

        self::assertTrue($this->import->checkTimeout());
    }

    /**
     * Test for lookForUse
     */
    public function testLookForUse(): void
    {
        self::assertSame(
            '',
            $this->import->lookForUse('select 1 from myTable'),
        );

        self::assertSame(
            'anotherDb',
            $this->import->lookForUse('use anotherDb'),
        );

        self::assertSame(
            'anotherDb',
            $this->import->lookForUse('use `anotherDb`;'),
        );
    }

    /**
     * Test for getColumnAlphaName
     *
     * @param string $expected Expected result of the function
     * @param int    $num      The column number
     */
    #[DataProvider('provGetColumnAlphaName')]
    public function testGetColumnAlphaName(string $expected, int $num): void
    {
        self::assertSame($expected, $this->import->getColumnAlphaName($num));
    }

    /**
     * Data provider for testGetColumnAlphaName
     *
     * @return array<int, array{string, int}>
     */
    public static function provGetColumnAlphaName(): array
    {
        return [['A', 1], ['Z', 0], ['AA', 27], ['AZ', 52], ['BA', 53], ['BB', 54]];
    }

    /**
     * Test for getColumnNumberFromName
     *
     * @param int    $expected Expected result of the function
     * @param string $name     column name(i.e. "A", or "BC", etc.)
     */
    #[DataProvider('provGetColumnNumberFromName')]
    public function testGetColumnNumberFromName(int $expected, string $name): void
    {
        self::assertSame($expected, $this->import->getColumnNumberFromName($name));
    }

    /**
     * Data provider for testGetColumnNumberFromName
     *
     * @return array<int, array{int, string}>
     */
    public static function provGetColumnNumberFromName(): array
    {
        return [[1, 'A'], [26, 'Z'], [27, 'AA'], [52, 'AZ'], [53, 'BA'], [54, 'BB']];
    }

    /**
     * Test for getDecimalSize
     */
    #[DataProvider('provGetDecimalSize')]
    public function testGetDecimalSize(int $precision, int $scale, string $cell): void
    {
        $actual = DecimalSize::fromCell($cell);
        self::assertSame($precision, $actual->precision);
        self::assertSame($scale, $actual->scale);
    }

    /**
     * Data provider for testGetDecimalSize
     *
     * @return array{int, int, string}[]
     */
    public static function provGetDecimalSize(): array
    {
        return [[2, 1, '2.1'], [2, 1, '6.2'], [3, 1, '10.0'], [4, 2, '30.20']];
    }

    /**
     * Test for detectType
     *
     * @param ColumnType      $expected Expected result of the function
     * @param ColumnType|null $type     Last cumulative column type (VARCHAR or INT or
     *                           BIGINT or DECIMAL or NONE)
     * @param string          $cell     String representation of the cell for which a
     *                                       best-fit type is to be determined
     */
    #[DataProvider('provDetectType')]
    public function testDetectType(ColumnType $expected, ColumnType|null $type, string $cell): void
    {
        self::assertSame($expected, $this->import->detectType($type, $cell));
    }

    /**
     * Data provider for testDetectType
     *
     * @return array{ColumnType, ColumnType|null, string}[]
     */
    public static function provDetectType(): array
    {
        $data = [
            [ColumnType::None, null, 'NULL'],
            [ColumnType::None, ColumnType::None, 'NULL'],
            [ColumnType::Int, ColumnType::Int, 'NULL'],
            [ColumnType::Varchar, ColumnType::Varchar, 'NULL'],
            [ColumnType::Varchar, null, ''],
            [ColumnType::Varchar, ColumnType::Int, ''],
            [ColumnType::Int, ColumnType::Int, '10'],
            [ColumnType::Decimal, ColumnType::Decimal, '10.2'],
            [ColumnType::Decimal, ColumnType::Int, '10.2'],
            [ColumnType::Varchar, ColumnType::Varchar, 'test'],
            [ColumnType::Varchar, ColumnType::Int, 'test'],
        ];

        if (PHP_INT_MAX > 2147483647) {
            $data[] = [ColumnType::BigInt, ColumnType::BigInt, '2147483648'];
            $data[] = [ColumnType::BigInt, ColumnType::Int, '2147483648'];
        } else {
            // To be fixed ?
            // Can not detect a BIGINT since the value is over PHP_INT_MAX
            $data[] = [ColumnType::Varchar, ColumnType::BigInt, '2147483648'];
            $data[] = [ColumnType::Varchar, ColumnType::Int, '2147483648'];
        }

        return $data;
    }

    /**
     * Test for checkIfRollbackPossible
     *
     * @param string $sqlQuery SQL Query for which rollback is possible
     */
    #[DataProvider('provPMACheckIfRollbackPossiblePositive')]
    public function testCheckIfRollbackPossiblePositive(string $sqlQuery): void
    {
        Current::$database = 'PMA';

        self::assertTrue(
            $this->import->checkIfRollbackPossible($sqlQuery),
            'Test case for ' . $sqlQuery . ' is failed',
        );
    }

    /**
     * Data provider for testPMACheckIfRollbackPossiblePositive
     *
     * @return array<int, array<int, string>>
     */
    public static function provPMACheckIfRollbackPossiblePositive(): array
    {
        return [
            ['UPDATE `table_1` AS t1, `table_2` t2 SET `table_1`.`id` = `table_2`.`id` WHERE 1'],
            ['INSERT INTO `table_1` (id) VALUES (123)'],
            ['REPLACE INTO `table_1` (id) VALUES (123)'],
            ['DELETE FROM `table_1` WHERE TRUE'],
            ['SET @foo = 1'],
            ['SET @@max_connections = 1'],
            ['SET max_connections = 1'],
            ['SET @foo = 1, max_connections = 1'],
        ];
    }

    /**
     * Negative test for checkIfRollbackPossible
     *
     * @param string $sqlQuery SQL Query for which rollback is possible
     */
    #[DataProvider('provPMACheckIfRollbackPossibleNegative')]
    public function testCheckIfRollbackPossibleNegative(string $sqlQuery): void
    {
        Current::$database = 'PMA';

        self::assertFalse(
            $this->import->checkIfRollbackPossible($sqlQuery),
            'Test case for ' . $sqlQuery . ' is failed',
        );
    }

    /**
     * Data provider for testPMACheckIfRollbackPossibleNegative
     *
     * @return array<int, array<int, string>>
     */
    public static function provPMACheckIfRollbackPossibleNegative(): array
    {
        return [
            ['ALTER TABLE `table_1` DROP COLUMN id'],
            ['SELECT * FROM `test_db`.`test_table_complex`;'],
            ['SET GLOBAL max_connections = 1'],
            ['SET @@GLOBAL.max_connections = 1'],
            ['SET CHARACTER SET utf8mb4'],
            ['SET SESSION max_connections = 1'],
            ['SET @@SESSION.max_connections = 1'],
        ];
    }

    /**
     * Data provider for testSkipByteOrderMarksFromContents
     *
     * @return mixed[][]
     */
    public static function providerContentWithByteOrderMarks(): array
    {
        return [
            ["\xEF\xBB\xBF blabla上海", ' blabla上海'],
            ["\xEF\xBB\xBF blabla", ' blabla'],
            ["\xEF\xBB\xBF blabla\xEF\xBB\xBF", " blabla\xEF\xBB\xBF"],
            ["\xFE\xFF blabla", ' blabla'],
            ["\xFE\xFF blabla\xFE\xFF", " blabla\xFE\xFF"],
            ["\xFF\xFE blabla", ' blabla'],
            ["\xFF\xFE blabla\xFF\xFE", " blabla\xFF\xFE"],
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
     */
    #[DataProvider('providerContentWithByteOrderMarks')]
    public function testSkipByteOrderMarksFromContents(string $input, string $cleanContents): void
    {
        self::assertSame($cleanContents, $this->import->skipByteOrderMarksFromContents($input));
    }

    /**
     * Test for runQuery
     */
    public function testRunQuery(): void
    {
        ImportSettings::$runQuery = true;
        $sqlData = [];

        $this->import->runQuery('SELECT 1', $sqlData);

        self::assertSame([], $sqlData);
        self::assertSame('', Current::$sqlQuery);
        self::assertNull(Current::$completeQuery);
        self::assertNull(Current::$displayQuery);

        $this->import->runQuery('SELECT 2', $sqlData);

        self::assertSame(['SELECT 1;'], $sqlData);
        self::assertSame('SELECT 1;', Current::$sqlQuery);
        self::assertSame('SELECT 1;', Current::$completeQuery);
        self::assertSame('SELECT 1;', Current::$displayQuery);

        $this->import->runQuery('', $sqlData);

        self::assertSame(['SELECT 1;', 'SELECT 2;'], $sqlData);

        self::assertSame('SELECT 2;', Current::$sqlQuery);
        self::assertSame('SELECT 1;SELECT 2;', Current::$completeQuery);
        self::assertSame('SELECT 1;SELECT 2;', Current::$displayQuery);
    }
}
