<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Export;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
#[CoversClass(Export::class)]
class ExportTest extends TestCase
{
    #[DataProvider('valuesForFormatProvider')]
    public function testFormat(mixed $actual, string $expected): void
    {
        $export = new Export(['format' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->format);
        self::assertSame($expected, $exportArray['format']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFormatProvider(): iterable
    {
        yield 'null value' => [null, 'sql'];
        yield 'valid value' => ['codegen', 'codegen'];
        yield 'valid value 2' => ['csv', 'csv'];
        yield 'valid value 3' => ['excel', 'excel'];
        yield 'valid value 4' => ['htmlword', 'htmlword'];
        yield 'valid value 5' => ['latex', 'latex'];
        yield 'valid value 6' => ['ods', 'ods'];
        yield 'valid value 7' => ['odt', 'odt'];
        yield 'valid value 8' => ['pdf', 'pdf'];
        yield 'valid value 9' => ['sql', 'sql'];
        yield 'valid value 10' => ['texytext', 'texytext'];
        yield 'valid value 11' => ['xml', 'xml'];
        yield 'valid value 12' => ['yaml', 'yaml'];
        yield 'invalid value' => ['invalid', 'sql'];
    }

    #[DataProvider('valuesForMethodProvider')]
    public function testMethod(mixed $actual, string $expected): void
    {
        $export = new Export(['method' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->method);
        self::assertSame($expected, $exportArray['method']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForMethodProvider(): iterable
    {
        yield 'null value' => [null, 'quick'];
        yield 'valid value' => ['quick', 'quick'];
        yield 'valid value 2' => ['custom', 'custom'];
        yield 'valid value 3' => ['custom-no-form', 'custom-no-form'];
        yield 'invalid value' => ['invalid', 'quick'];
    }

    #[DataProvider('valuesForCompressionProvider')]
    public function testCompression(mixed $actual, string $expected): void
    {
        $export = new Export(['compression' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->compression);
        self::assertSame($expected, $exportArray['compression']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCompressionProvider(): iterable
    {
        yield 'null value' => [null, 'none'];
        yield 'valid value' => ['none', 'none'];
        yield 'valid value 2' => ['zip', 'zip'];
        yield 'valid value 3' => ['gzip', 'gzip'];
        yield 'invalid value' => ['invalid', 'none'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testLockTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['lock_tables' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->lock_tables);
        self::assertSame($expected, $exportArray['lock_tables']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testAsSeparateFiles(mixed $actual, bool $expected): void
    {
        $export = new Export(['as_separate_files' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->as_separate_files);
        self::assertSame($expected, $exportArray['as_separate_files']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testAsfile(mixed $actual, bool $expected): void
    {
        $export = new Export(['asfile' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->asfile);
        self::assertSame($expected, $exportArray['asfile']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    #[DataProvider('valuesForCharsetProvider')]
    public function testCharset(mixed $actual, string $expected): void
    {
        $export = new Export(['charset' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->charset);
        self::assertSame($expected, $exportArray['charset']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCharsetProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testOnserver(mixed $actual, bool $expected): void
    {
        $export = new Export(['onserver' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->onserver);
        self::assertSame($expected, $exportArray['onserver']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testOnserverOverwrite(mixed $actual, bool $expected): void
    {
        $export = new Export(['onserver_overwrite' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->onserver_overwrite);
        self::assertSame($expected, $exportArray['onserver_overwrite']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testQuickExportOnserver(mixed $actual, bool $expected): void
    {
        $export = new Export(['quick_export_onserver' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->quick_export_onserver);
        self::assertSame($expected, $exportArray['quick_export_onserver']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testQuickExportOnserverOverwrite(mixed $actual, bool $expected): void
    {
        $export = new Export(['quick_export_onserver_overwrite' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->quick_export_onserver_overwrite);
        self::assertSame($expected, $exportArray['quick_export_onserver_overwrite']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testRememberFileTemplate(mixed $actual, bool $expected): void
    {
        $export = new Export(['remember_file_template' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->remember_file_template);
        self::assertSame($expected, $exportArray['remember_file_template']);
    }

    #[DataProvider('valuesForFileTemplateTableProvider')]
    public function testFileTemplateTable(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_table' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->file_template_table);
        self::assertSame($expected, $exportArray['file_template_table']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateTableProvider(): iterable
    {
        yield 'null value' => [null, '@TABLE@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForFileTemplateDatabaseProvider')]
    public function testFileTemplateDatabase(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_database' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->file_template_database);
        self::assertSame($expected, $exportArray['file_template_database']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateDatabaseProvider(): iterable
    {
        yield 'null value' => [null, '@DATABASE@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForFileTemplateServerProvider')]
    public function testFileTemplateServer(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_server' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->file_template_server);
        self::assertSame($expected, $exportArray['file_template_server']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateServerProvider(): iterable
    {
        yield 'null value' => [null, '@SERVER@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testCodegenStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['codegen_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->codegen_structure_or_data);
        self::assertSame($expected, $exportArray['codegen_structure_or_data']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function structureOrDataWithDefaultDataProvider(): iterable
    {
        yield 'null value' => [null, 'data'];
        yield 'valid value' => ['structure', 'structure'];
        yield 'valid value 2' => ['data', 'data'];
        yield 'valid value 3' => ['structure_and_data', 'structure_and_data'];
        yield 'invalid value' => ['invalid', 'data'];
    }

    #[DataProvider('valuesForCodegenFormatProvider')]
    public function testCodegenFormat(mixed $actual, int $expected): void
    {
        $export = new Export(['codegen_format' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->codegen_format);
        self::assertSame($expected, $exportArray['codegen_format']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForCodegenFormatProvider(): iterable
    {
        yield 'null value' => [null, 0];
        yield 'valid value' => [0, 0];
        yield 'valid value 2' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 0];
        yield 'invalid value 2' => [2, 0];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testOdsColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['ods_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->ods_columns);
        self::assertSame($expected, $exportArray['ods_columns']);
    }

    #[DataProvider('valuesForOdsNullProvider')]
    public function testOdsNull(mixed $actual, string $expected): void
    {
        $export = new Export(['ods_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->ods_null);
        self::assertSame($expected, $exportArray['ods_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForOdsNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultStructureOrDataProvider')]
    public function testOdtStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['odt_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_structure_or_data);
        self::assertSame($expected, $exportArray['odt_structure_or_data']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function structureOrDataWithDefaultStructureOrDataProvider(): iterable
    {
        yield 'null value' => [null, 'structure_and_data'];
        yield 'valid value' => ['structure', 'structure'];
        yield 'valid value 2' => ['data', 'data'];
        yield 'valid value 3' => ['structure_and_data', 'structure_and_data'];
        yield 'invalid value' => ['invalid', 'structure_and_data'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testOdtColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_columns);
        self::assertSame($expected, $exportArray['odt_columns']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testOdtRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_relation' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_relation);
        self::assertSame($expected, $exportArray['odt_relation']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testOdtComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_comments' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_comments);
        self::assertSame($expected, $exportArray['odt_comments']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testOdtMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_mime' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_mime);
        self::assertSame($expected, $exportArray['odt_mime']);
    }

    #[DataProvider('valuesForOdtNullProvider')]
    public function testOdtNull(mixed $actual, string $expected): void
    {
        $export = new Export(['odt_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->odt_null);
        self::assertSame($expected, $exportArray['odt_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForOdtNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultStructureOrDataProvider')]
    public function testHtmlwordStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['htmlword_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->htmlword_structure_or_data);
        self::assertSame($expected, $exportArray['htmlword_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testHtmlwordColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['htmlword_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->htmlword_columns);
        self::assertSame($expected, $exportArray['htmlword_columns']);
    }

    #[DataProvider('valuesForHtmlwordNullProvider')]
    public function testHtmlwordNull(mixed $actual, string $expected): void
    {
        $export = new Export(['htmlword_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->htmlword_null);
        self::assertSame($expected, $exportArray['htmlword_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForHtmlwordNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultStructureOrDataProvider')]
    public function testTexytextStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['texytext_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->texytext_structure_or_data);
        self::assertSame($expected, $exportArray['texytext_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testTexytextColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['texytext_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->texytext_columns);
        self::assertSame($expected, $exportArray['texytext_columns']);
    }

    #[DataProvider('valuesForTexytextNullProvider')]
    public function testTexytextNull(mixed $actual, string $expected): void
    {
        $export = new Export(['texytext_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->texytext_null);
        self::assertSame($expected, $exportArray['texytext_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForTexytextNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testCsvColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['csv_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_columns);
        self::assertSame($expected, $exportArray['csv_columns']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testCsvStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_structure_or_data);
        self::assertSame($expected, $exportArray['csv_structure_or_data']);
    }

    #[DataProvider('valuesForCsvNullProvider')]
    public function testCsvNull(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_null);
        self::assertSame($expected, $exportArray['csv_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForCsvSeparatorProvider')]
    public function testCsvSeparator(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_separator' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_separator);
        self::assertSame($expected, $exportArray['csv_separator']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvSeparatorProvider(): iterable
    {
        yield 'null value' => [null, ','];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForCsvEnclosedProvider')]
    public function testCsvEnclosed(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_enclosed' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_enclosed);
        self::assertSame($expected, $exportArray['csv_enclosed']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvEnclosedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForCsvEscapedProvider')]
    public function testCsvEscaped(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_escaped' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_escaped);
        self::assertSame($expected, $exportArray['csv_escaped']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvEscapedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForCsvTerminatedProvider')]
    public function testCsvTerminated(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_terminated' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_terminated);
        self::assertSame($expected, $exportArray['csv_terminated']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvTerminatedProvider(): iterable
    {
        yield 'null value' => [null, 'AUTO'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testCsvRemoveCRLF(mixed $actual, bool $expected): void
    {
        $export = new Export(['csv_removeCRLF' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->csv_removeCRLF);
        self::assertSame($expected, $exportArray['csv_removeCRLF']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testExcelColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['excel_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->excel_columns);
        self::assertSame($expected, $exportArray['excel_columns']);
    }

    #[DataProvider('valuesForExcelNullProvider')]
    public function testExcelNull(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->excel_null);
        self::assertSame($expected, $exportArray['excel_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForExcelNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForExcelEditionProvider')]
    public function testExcelEdition(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_edition' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->excel_edition);
        self::assertSame($expected, $exportArray['excel_edition']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForExcelEditionProvider(): iterable
    {
        yield 'null value' => [null, 'win'];
        yield 'valid value' => ['win', 'win'];
        yield 'valid value 2' => ['mac_excel2003', 'mac_excel2003'];
        yield 'valid value 3' => ['mac_excel2008', 'mac_excel2008'];
        yield 'invalid value' => ['invalid', 'win'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testExcelRemoveCRLF(mixed $actual, bool $expected): void
    {
        $export = new Export(['excel_removeCRLF' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->excel_removeCRLF);
        self::assertSame($expected, $exportArray['excel_removeCRLF']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testExcelStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->excel_structure_or_data);
        self::assertSame($expected, $exportArray['excel_structure_or_data']);
    }

    #[DataProvider('structureOrDataWithDefaultStructureOrDataProvider')]
    public function testLatexStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_structure_or_data);
        self::assertSame($expected, $exportArray['latex_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLatexColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_columns' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_columns);
        self::assertSame($expected, $exportArray['latex_columns']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLatexRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_relation' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_relation);
        self::assertSame($expected, $exportArray['latex_relation']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLatexComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_comments' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_comments);
        self::assertSame($expected, $exportArray['latex_comments']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLatexMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_mime' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_mime);
        self::assertSame($expected, $exportArray['latex_mime']);
    }

    #[DataProvider('valuesForLatexNullProvider')]
    public function testLatexNull(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_null' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_null);
        self::assertSame($expected, $exportArray['latex_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexNullProvider(): iterable
    {
        yield 'null value' => [null, '\textit{NULL}'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLatexCaption(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_caption);
        self::assertSame($expected, $exportArray['latex_caption']);
    }

    #[DataProvider('valuesForLatexStructureCaptionProvider')]
    public function testLatexStructureCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_structure_caption);
        self::assertSame($expected, $exportArray['latex_structure_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexStructure'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLatexStructureContinuedCaptionProvider')]
    public function testLatexStructureContinuedCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_continued_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_structure_continued_caption);
        self::assertSame($expected, $exportArray['latex_structure_continued_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureContinuedCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexStructure strLatexContinued'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLatexDataCaptionProvider')]
    public function testLatexDataCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_data_caption);
        self::assertSame($expected, $exportArray['latex_data_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexContent'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLatexDataContinuedCaptionProvider')]
    public function testLatexDataContinuedCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_continued_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_data_continued_caption);
        self::assertSame($expected, $exportArray['latex_data_continued_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataContinuedCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexContent strLatexContinued'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLatexDataLabelProvider')]
    public function testLatexDataLabel(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_label' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_data_label);
        self::assertSame($expected, $exportArray['latex_data_label']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataLabelProvider(): iterable
    {
        yield 'null value' => [null, 'tab:@TABLE@-data'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLatexStructureLabelProvider')]
    public function testLatexStructureLabel(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_label' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->latex_structure_label);
        self::assertSame($expected, $exportArray['latex_structure_label']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureLabelProvider(): iterable
    {
        yield 'null value' => [null, 'tab:@TABLE@-structure'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testMediawikiStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['mediawiki_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->mediawiki_structure_or_data);
        self::assertSame($expected, $exportArray['mediawiki_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testMediawikiCaption(mixed $actual, bool $expected): void
    {
        $export = new Export(['mediawiki_caption' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->mediawiki_caption);
        self::assertSame($expected, $exportArray['mediawiki_caption']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testMediawikiHeaders(mixed $actual, bool $expected): void
    {
        $export = new Export(['mediawiki_headers' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->mediawiki_headers);
        self::assertSame($expected, $exportArray['mediawiki_headers']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testOdsStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['ods_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->ods_structure_or_data);
        self::assertSame($expected, $exportArray['ods_structure_or_data']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testPdfStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['pdf_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->pdf_structure_or_data);
        self::assertSame($expected, $exportArray['pdf_structure_or_data']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testPhparrayStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['phparray_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->phparray_structure_or_data);
        self::assertSame($expected, $exportArray['phparray_structure_or_data']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testJsonStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['json_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->json_structure_or_data);
        self::assertSame($expected, $exportArray['json_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testJsonPrettyPrint(mixed $actual, bool $expected): void
    {
        $export = new Export(['json_pretty_print' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->json_pretty_print);
        self::assertSame($expected, $exportArray['json_pretty_print']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testJsonUnicode(mixed $actual, bool $expected): void
    {
        $export = new Export(['json_unicode' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->json_unicode);
        self::assertSame($expected, $exportArray['json_unicode']);
    }

    #[DataProvider('structureOrDataWithDefaultStructureOrDataProvider')]
    public function testSqlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_structure_or_data);
        self::assertSame($expected, $exportArray['sql_structure_or_data']);
    }

    #[DataProvider('valuesForSqlCompatibilityProvider')]
    public function testSqlCompatibility(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_compatibility' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_compatibility);
        self::assertSame($expected, $exportArray['sql_compatibility']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlCompatibilityProvider(): iterable
    {
        yield 'null value' => [null, 'NONE'];
        yield 'valid value' => ['NONE', 'NONE'];
        yield 'valid value 2' => ['ANSI', 'ANSI'];
        yield 'valid value 3' => ['DB2', 'DB2'];
        yield 'valid value 4' => ['MAXDB', 'MAXDB'];
        yield 'valid value 5' => ['MYSQL323', 'MYSQL323'];
        yield 'valid value 6' => ['MYSQL40', 'MYSQL40'];
        yield 'valid value 7' => ['MSSQL', 'MSSQL'];
        yield 'valid value 8' => ['ORACLE', 'ORACLE'];
        yield 'valid value 9' => ['TRADITIONAL', 'TRADITIONAL'];
        yield 'valid value 10' => ['', 'NONE'];
        yield 'invalid value' => ['invalid', 'NONE'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlIncludeComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_include_comments' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_include_comments);
        self::assertSame($expected, $exportArray['sql_include_comments']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlDisableFk(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_disable_fk' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_disable_fk);
        self::assertSame($expected, $exportArray['sql_disable_fk']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlViewsAsTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_views_as_tables' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_views_as_tables);
        self::assertSame($expected, $exportArray['sql_views_as_tables']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlMetadata(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_metadata' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_metadata);
        self::assertSame($expected, $exportArray['sql_metadata']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlUseTransaction(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_use_transaction' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_use_transaction);
        self::assertSame($expected, $exportArray['sql_use_transaction']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlCreateDatabase(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_database' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_create_database);
        self::assertSame($expected, $exportArray['sql_create_database']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlDropDatabase(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_drop_database' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_drop_database);
        self::assertSame($expected, $exportArray['sql_drop_database']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlDropTable(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_drop_table' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_drop_table);
        self::assertSame($expected, $exportArray['sql_drop_table']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlIfNotExists(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_if_not_exists' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_if_not_exists);
        self::assertSame($expected, $exportArray['sql_if_not_exists']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlViewCurrentUser(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_view_current_user' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_view_current_user);
        self::assertSame($expected, $exportArray['sql_view_current_user']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlOrReplaceView(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_or_replace_view' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_or_replace_view);
        self::assertSame($expected, $exportArray['sql_or_replace_view']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlProcedureFunction(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_procedure_function' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_procedure_function);
        self::assertSame($expected, $exportArray['sql_procedure_function']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlCreateTable(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_table' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_create_table);
        self::assertSame($expected, $exportArray['sql_create_table']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlCreateView(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_view' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_create_view);
        self::assertSame($expected, $exportArray['sql_create_view']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlCreateTrigger(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_trigger' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_create_trigger);
        self::assertSame($expected, $exportArray['sql_create_trigger']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlAutoIncrement(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_auto_increment' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_auto_increment);
        self::assertSame($expected, $exportArray['sql_auto_increment']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlBackquotes(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_backquotes' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_backquotes);
        self::assertSame($expected, $exportArray['sql_backquotes']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlDates(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_dates' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_dates);
        self::assertSame($expected, $exportArray['sql_dates']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_relation' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_relation);
        self::assertSame($expected, $exportArray['sql_relation']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlTruncate(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_truncate' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_truncate);
        self::assertSame($expected, $exportArray['sql_truncate']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlDelayed(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_delayed' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_delayed);
        self::assertSame($expected, $exportArray['sql_delayed']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlIgnore(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_ignore' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_ignore);
        self::assertSame($expected, $exportArray['sql_ignore']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlUtcTime(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_utc_time' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_utc_time);
        self::assertSame($expected, $exportArray['sql_utc_time']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSqlHexForBinary(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_hex_for_binary' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_hex_for_binary);
        self::assertSame($expected, $exportArray['sql_hex_for_binary']);
    }

    #[DataProvider('valuesForSqlTypeProvider')]
    public function testSqlType(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_type' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_type);
        self::assertSame($expected, $exportArray['sql_type']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlTypeProvider(): iterable
    {
        yield 'null value' => [null, 'INSERT'];
        yield 'valid value' => ['INSERT', 'INSERT'];
        yield 'valid value 2' => ['UPDATE', 'UPDATE'];
        yield 'valid value 3' => ['REPLACE', 'REPLACE'];
        yield 'invalid value' => ['invalid', 'INSERT'];
    }

    #[DataProvider('valuesForSqlMaxQuerySizeProvider')]
    public function testSqlMaxQuerySize(mixed $actual, int $expected): void
    {
        $export = new Export(['sql_max_query_size' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_max_query_size);
        self::assertSame($expected, $exportArray['sql_max_query_size']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForSqlMaxQuerySizeProvider(): iterable
    {
        yield 'null value' => [null, 50000];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 50000];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSqlMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_mime' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_mime);
        self::assertSame($expected, $exportArray['sql_mime']);
    }

    #[DataProvider('valuesForSqlHeaderCommentProvider')]
    public function testSqlHeaderComment(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_header_comment' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_header_comment);
        self::assertSame($expected, $exportArray['sql_header_comment']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlHeaderCommentProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForSqlInsertSyntaxProvider')]
    public function testSqlInsertSyntax(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_insert_syntax' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->sql_insert_syntax);
        self::assertSame($expected, $exportArray['sql_insert_syntax']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlInsertSyntaxProvider(): iterable
    {
        yield 'null value' => [null, 'both'];
        yield 'valid value' => ['complete', 'complete'];
        yield 'valid value 2' => ['extended', 'extended'];
        yield 'valid value 3' => ['both', 'both'];
        yield 'valid value 4' => ['none', 'none'];
        yield 'invalid value' => ['invalid', 'both'];
        yield 'invalid value 2' => ['', 'both'];
    }

    #[DataProvider('valuesForPdfReportTitleProvider')]
    public function testPdfReportTitle(mixed $actual, string $expected): void
    {
        $export = new Export(['pdf_report_title' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->pdf_report_title);
        self::assertSame($expected, $exportArray['pdf_report_title']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPdfReportTitleProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testXmlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['xml_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_structure_or_data);
        self::assertSame($expected, $exportArray['xml_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportStruc(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_struc' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_struc);
        self::assertSame($expected, $exportArray['xml_export_struc']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportEvents(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_events' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_events);
        self::assertSame($expected, $exportArray['xml_export_events']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportFunctions(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_functions' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_functions);
        self::assertSame($expected, $exportArray['xml_export_functions']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportProcedures(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_procedures' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_procedures);
        self::assertSame($expected, $exportArray['xml_export_procedures']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_tables' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_tables);
        self::assertSame($expected, $exportArray['xml_export_tables']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportTriggers(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_triggers' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_triggers);
        self::assertSame($expected, $exportArray['xml_export_triggers']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportViews(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_views' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_views);
        self::assertSame($expected, $exportArray['xml_export_views']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testXmlExportContents(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_contents' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->xml_export_contents);
        self::assertSame($expected, $exportArray['xml_export_contents']);
    }

    #[DataProvider('structureOrDataWithDefaultDataProvider')]
    public function testYamlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['yaml_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->yaml_structure_or_data);
        self::assertSame($expected, $exportArray['yaml_structure_or_data']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testRemoveDefinerFromDefinitions(mixed $actual, bool $expected): void
    {
        $export = new Export(['remove_definer_from_definitions' => $actual]);
        $exportArray = $export->asArray();
        self::assertSame($expected, $export->remove_definer_from_definitions);
        self::assertSame($expected, $exportArray['remove_definer_from_definitions']);
    }
}
