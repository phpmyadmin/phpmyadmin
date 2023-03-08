<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Import;
use PHPUnit\Framework\TestCase;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/** @covers \PhpMyAdmin\Config\Settings\Import */
class ImportTest extends TestCase
{
    /** @dataProvider valuesForFormatProvider */
    public function testFormat(mixed $actual, string $expected): void
    {
        $import = new Import(['format' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->format);
        $this->assertArrayHasKey('format', $importArray);
        $this->assertSame($expected, $importArray['format']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFormatProvider(): iterable
    {
        yield 'null value' => [null, 'sql'];
        yield 'valid value' => ['csv', 'csv'];
        yield 'valid value 2' => ['docsql', 'docsql'];
        yield 'valid value 3' => ['ldi', 'ldi'];
        yield 'valid value 4' => ['sql', 'sql'];
        yield 'invalid value' => ['invalid', 'sql'];
    }

    /** @dataProvider valuesForCharsetProvider */
    public function testCharset(mixed $actual, string $expected): void
    {
        $import = new Import(['charset' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->charset);
        $this->assertArrayHasKey('charset', $importArray);
        $this->assertSame($expected, $importArray['charset']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCharsetProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testAllowInterrupt(mixed $actual, bool $expected): void
    {
        $import = new Import(['allow_interrupt' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->allow_interrupt);
        $this->assertArrayHasKey('allow_interrupt', $importArray);
        $this->assertSame($expected, $importArray['allow_interrupt']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    /** @dataProvider valuesForSkipQueriesProvider */
    public function testSkipQueries(mixed $actual, int $expected): void
    {
        $import = new Import(['skip_queries' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->skip_queries);
        $this->assertArrayHasKey('skip_queries', $importArray);
        $this->assertSame($expected, $importArray['skip_queries']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForSkipQueriesProvider(): iterable
    {
        yield 'null value' => [null, 0];
        yield 'valid value' => [0, 0];
        yield 'valid value 2' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 0];
    }

    /** @dataProvider valuesForSqlCompatibilityProvider */
    public function testSqlCompatibility(mixed $actual, string $expected): void
    {
        $import = new Import(['sql_compatibility' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->sql_compatibility);
        $this->assertArrayHasKey('sql_compatibility', $importArray);
        $this->assertSame($expected, $importArray['sql_compatibility']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlCompatibilityProvider(): iterable
    {
        yield 'null value' => [null, 'NONE'];
        yield 'valid value' => ['NONE', 'NONE'];
        yield 'valid value 2' => ['ANSI', 'ANSI'];
        yield 'valid value 3' => ['DB2', 'DB2'];
        yield 'valid value 4' => ['MAXDB', 'MAXDB'];
        yield 'valid value 5' => ['MAXDB', 'MAXDB'];
        yield 'valid value 6' => ['MYSQL323', 'MYSQL323'];
        yield 'valid value 7' => ['MYSQL40', 'MYSQL40'];
        yield 'valid value 8' => ['MSSQL', 'MSSQL'];
        yield 'valid value 9' => ['ORACLE', 'ORACLE'];
        yield 'valid value 10' => ['TRADITIONAL', 'TRADITIONAL'];
        yield 'invalid value' => ['invalid', 'NONE'];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlNoAutoValueOnZero(mixed $actual, bool $expected): void
    {
        $import = new Import(['sql_no_auto_value_on_zero' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->sql_no_auto_value_on_zero);
        $this->assertArrayHasKey('sql_no_auto_value_on_zero', $importArray);
        $this->assertSame($expected, $importArray['sql_no_auto_value_on_zero']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlReadAsMultibytes(mixed $actual, bool $expected): void
    {
        $import = new Import(['sql_read_as_multibytes' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->sql_read_as_multibytes);
        $this->assertArrayHasKey('sql_read_as_multibytes', $importArray);
        $this->assertSame($expected, $importArray['sql_read_as_multibytes']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testCsvReplace(mixed $actual, bool $expected): void
    {
        $import = new Import(['csv_replace' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_replace);
        $this->assertArrayHasKey('csv_replace', $importArray);
        $this->assertSame($expected, $importArray['csv_replace']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testCsvIgnore(mixed $actual, bool $expected): void
    {
        $import = new Import(['csv_ignore' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_ignore);
        $this->assertArrayHasKey('csv_ignore', $importArray);
        $this->assertSame($expected, $importArray['csv_ignore']);
    }

    /** @dataProvider valuesForCsvTerminatedProvider */
    public function testCsvTerminated(mixed $actual, string $expected): void
    {
        $import = new Import(['csv_terminated' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_terminated);
        $this->assertArrayHasKey('csv_terminated', $importArray);
        $this->assertSame($expected, $importArray['csv_terminated']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvTerminatedProvider(): iterable
    {
        yield 'null value' => [null, ','];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvEnclosedProvider */
    public function testCsvEnclosed(mixed $actual, string $expected): void
    {
        $import = new Import(['csv_enclosed' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_enclosed);
        $this->assertArrayHasKey('csv_enclosed', $importArray);
        $this->assertSame($expected, $importArray['csv_enclosed']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvEnclosedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvEscapedProvider */
    public function testCsvEscaped(mixed $actual, string $expected): void
    {
        $import = new Import(['csv_escaped' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_escaped);
        $this->assertArrayHasKey('csv_escaped', $importArray);
        $this->assertSame($expected, $importArray['csv_escaped']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvEscapedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvNewLineProvider */
    public function testCsvNewLine(mixed $actual, string $expected): void
    {
        $import = new Import(['csv_new_line' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_new_line);
        $this->assertArrayHasKey('csv_new_line', $importArray);
        $this->assertSame($expected, $importArray['csv_new_line']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvNewLineProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvColumnsProvider */
    public function testCsvColumns(mixed $actual, string $expected): void
    {
        $import = new Import(['csv_columns' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_columns);
        $this->assertArrayHasKey('csv_columns', $importArray);
        $this->assertSame($expected, $importArray['csv_columns']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvColumnsProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testCsvColNames(mixed $actual, bool $expected): void
    {
        $import = new Import(['csv_col_names' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->csv_col_names);
        $this->assertArrayHasKey('csv_col_names', $importArray);
        $this->assertSame($expected, $importArray['csv_col_names']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testLdiReplace(mixed $actual, bool $expected): void
    {
        $import = new Import(['ldi_replace' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_replace);
        $this->assertArrayHasKey('ldi_replace', $importArray);
        $this->assertSame($expected, $importArray['ldi_replace']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testLdiIgnore(mixed $actual, bool $expected): void
    {
        $import = new Import(['ldi_ignore' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_ignore);
        $this->assertArrayHasKey('ldi_ignore', $importArray);
        $this->assertSame($expected, $importArray['ldi_ignore']);
    }

    /** @dataProvider valuesForLdiTerminatedProvider */
    public function testLdiTerminated(mixed $actual, string $expected): void
    {
        $import = new Import(['ldi_terminated' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_terminated);
        $this->assertArrayHasKey('ldi_terminated', $importArray);
        $this->assertSame($expected, $importArray['ldi_terminated']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLdiTerminatedProvider(): iterable
    {
        yield 'null value' => [null, ';'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLdiEnclosedProvider */
    public function testLdiEnclosed(mixed $actual, string $expected): void
    {
        $import = new Import(['ldi_enclosed' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_enclosed);
        $this->assertArrayHasKey('ldi_enclosed', $importArray);
        $this->assertSame($expected, $importArray['ldi_enclosed']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLdiEnclosedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLdiEscapedProvider */
    public function testLdiEscaped(mixed $actual, string $expected): void
    {
        $import = new Import(['ldi_escaped' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_escaped);
        $this->assertArrayHasKey('ldi_escaped', $importArray);
        $this->assertSame($expected, $importArray['ldi_escaped']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLdiEscapedProvider(): iterable
    {
        yield 'null value' => [null, '\\'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLdiNewLineProvider */
    public function testLdiNewLine(mixed $actual, string $expected): void
    {
        $import = new Import(['ldi_new_line' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_new_line);
        $this->assertArrayHasKey('ldi_new_line', $importArray);
        $this->assertSame($expected, $importArray['ldi_new_line']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLdiNewLineProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLdiColumnsProvider */
    public function testLdiColumns(mixed $actual, string $expected): void
    {
        $import = new Import(['ldi_columns' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_columns);
        $this->assertArrayHasKey('ldi_columns', $importArray);
        $this->assertSame($expected, $importArray['ldi_columns']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLdiColumnsProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLdiLocalOptionProvider */
    public function testLdiLocalOption(mixed $actual, string|bool $expected): void
    {
        $import = new Import(['ldi_local_option' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ldi_local_option);
        $this->assertArrayHasKey('ldi_local_option', $importArray);
        $this->assertSame($expected, $importArray['ldi_local_option']);
    }

    /** @return iterable<string, array{mixed, string|bool}> */
    public static function valuesForLdiLocalOptionProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => ['auto', 'auto'];
        yield 'valid value 2' => [true, true];
        yield 'valid value 3' => [false, false];
        yield 'valid value with type coercion' => ['1', true];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testOdsColNames(mixed $actual, bool $expected): void
    {
        $import = new Import(['ods_col_names' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ods_col_names);
        $this->assertArrayHasKey('ods_col_names', $importArray);
        $this->assertSame($expected, $importArray['ods_col_names']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdsEmptyRows(mixed $actual, bool $expected): void
    {
        $import = new Import(['ods_empty_rows' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ods_empty_rows);
        $this->assertArrayHasKey('ods_empty_rows', $importArray);
        $this->assertSame($expected, $importArray['ods_empty_rows']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdsRecognizePercentages(mixed $actual, bool $expected): void
    {
        $import = new Import(['ods_recognize_percentages' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ods_recognize_percentages);
        $this->assertArrayHasKey('ods_recognize_percentages', $importArray);
        $this->assertSame($expected, $importArray['ods_recognize_percentages']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdsRecognizeCurrency(mixed $actual, bool $expected): void
    {
        $import = new Import(['ods_recognize_currency' => $actual]);
        $importArray = $import->asArray();
        $this->assertSame($expected, $import->ods_recognize_currency);
        $this->assertArrayHasKey('ods_recognize_currency', $importArray);
        $this->assertSame($expected, $importArray['ods_recognize_currency']);
    }
}
