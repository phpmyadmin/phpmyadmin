<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Export;
use PHPUnit\Framework\TestCase;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/** @covers \PhpMyAdmin\Config\Settings\Export */
class ExportTest extends TestCase
{
    /** @dataProvider valuesForFormatProvider */
    public function testFormat(mixed $actual, string $expected): void
    {
        $export = new Export(['format' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->format);
        $this->assertArrayHasKey('format', $exportArray);
        $this->assertSame($expected, $exportArray['format']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFormatProvider(): iterable
    {
        yield 'null value' => [null, 'sql'];
        yield 'valid value' => ['codegen', 'codegen'];
        yield 'valid value 2' => ['csv', 'csv'];
        yield 'valid value 3' => ['excel', 'excel'];
        yield 'valid value 4' => ['htmlexcel', 'htmlexcel'];
        yield 'valid value 5' => ['htmlword', 'htmlword'];
        yield 'valid value 6' => ['latex', 'latex'];
        yield 'valid value 7' => ['ods', 'ods'];
        yield 'valid value 8' => ['odt', 'odt'];
        yield 'valid value 9' => ['pdf', 'pdf'];
        yield 'valid value 10' => ['sql', 'sql'];
        yield 'valid value 11' => ['texytext', 'texytext'];
        yield 'valid value 12' => ['xml', 'xml'];
        yield 'valid value 13' => ['yaml', 'yaml'];
        yield 'invalid value' => ['invalid', 'sql'];
    }

    /** @dataProvider valuesForMethodProvider */
    public function testMethod(mixed $actual, string $expected): void
    {
        $export = new Export(['method' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->method);
        $this->assertArrayHasKey('method', $exportArray);
        $this->assertSame($expected, $exportArray['method']);
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

    /** @dataProvider valuesForCompressionProvider */
    public function testCompression(mixed $actual, string $expected): void
    {
        $export = new Export(['compression' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->compression);
        $this->assertArrayHasKey('compression', $exportArray);
        $this->assertSame($expected, $exportArray['compression']);
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

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testLockTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['lock_tables' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->lock_tables);
        $this->assertArrayHasKey('lock_tables', $exportArray);
        $this->assertSame($expected, $exportArray['lock_tables']);
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
    public function testAsSeparateFiles(mixed $actual, bool $expected): void
    {
        $export = new Export(['as_separate_files' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->as_separate_files);
        $this->assertArrayHasKey('as_separate_files', $exportArray);
        $this->assertSame($expected, $exportArray['as_separate_files']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testAsfile(mixed $actual, bool $expected): void
    {
        $export = new Export(['asfile' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->asfile);
        $this->assertArrayHasKey('asfile', $exportArray);
        $this->assertSame($expected, $exportArray['asfile']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    /** @dataProvider valuesForCharsetProvider */
    public function testCharset(mixed $actual, string $expected): void
    {
        $export = new Export(['charset' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->charset);
        $this->assertArrayHasKey('charset', $exportArray);
        $this->assertSame($expected, $exportArray['charset']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCharsetProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testOnserver(mixed $actual, bool $expected): void
    {
        $export = new Export(['onserver' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->onserver);
        $this->assertArrayHasKey('onserver', $exportArray);
        $this->assertSame($expected, $exportArray['onserver']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testOnserverOverwrite(mixed $actual, bool $expected): void
    {
        $export = new Export(['onserver_overwrite' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->onserver_overwrite);
        $this->assertArrayHasKey('onserver_overwrite', $exportArray);
        $this->assertSame($expected, $exportArray['onserver_overwrite']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testQuickExportOnserver(mixed $actual, bool $expected): void
    {
        $export = new Export(['quick_export_onserver' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->quick_export_onserver);
        $this->assertArrayHasKey('quick_export_onserver', $exportArray);
        $this->assertSame($expected, $exportArray['quick_export_onserver']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testQuickExportOnserverOverwrite(mixed $actual, bool $expected): void
    {
        $export = new Export(['quick_export_onserver_overwrite' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->quick_export_onserver_overwrite);
        $this->assertArrayHasKey('quick_export_onserver_overwrite', $exportArray);
        $this->assertSame($expected, $exportArray['quick_export_onserver_overwrite']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testRememberFileTemplate(mixed $actual, bool $expected): void
    {
        $export = new Export(['remember_file_template' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->remember_file_template);
        $this->assertArrayHasKey('remember_file_template', $exportArray);
        $this->assertSame($expected, $exportArray['remember_file_template']);
    }

    /** @dataProvider valuesForFileTemplateTableProvider */
    public function testFileTemplateTable(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_table' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->file_template_table);
        $this->assertArrayHasKey('file_template_table', $exportArray);
        $this->assertSame($expected, $exportArray['file_template_table']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateTableProvider(): iterable
    {
        yield 'null value' => [null, '@TABLE@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForFileTemplateDatabaseProvider */
    public function testFileTemplateDatabase(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_database' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->file_template_database);
        $this->assertArrayHasKey('file_template_database', $exportArray);
        $this->assertSame($expected, $exportArray['file_template_database']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateDatabaseProvider(): iterable
    {
        yield 'null value' => [null, '@DATABASE@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForFileTemplateServerProvider */
    public function testFileTemplateServer(mixed $actual, string $expected): void
    {
        $export = new Export(['file_template_server' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->file_template_server);
        $this->assertArrayHasKey('file_template_server', $exportArray);
        $this->assertSame($expected, $exportArray['file_template_server']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForFileTemplateServerProvider(): iterable
    {
        yield 'null value' => [null, '@SERVER@'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testCodegenStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['codegen_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->codegen_structure_or_data);
        $this->assertArrayHasKey('codegen_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['codegen_structure_or_data']);
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

    /** @dataProvider valuesForCodegenFormatProvider */
    public function testCodegenFormat(mixed $actual, int $expected): void
    {
        $export = new Export(['codegen_format' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->codegen_format);
        $this->assertArrayHasKey('codegen_format', $exportArray);
        $this->assertSame($expected, $exportArray['codegen_format']);
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

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testOdsColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['ods_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->ods_columns);
        $this->assertArrayHasKey('ods_columns', $exportArray);
        $this->assertSame($expected, $exportArray['ods_columns']);
    }

    /** @dataProvider valuesForOdsNullProvider */
    public function testOdsNull(mixed $actual, string $expected): void
    {
        $export = new Export(['ods_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->ods_null);
        $this->assertArrayHasKey('ods_null', $exportArray);
        $this->assertSame($expected, $exportArray['ods_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForOdsNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultStructureOrDataProvider */
    public function testOdtStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['odt_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_structure_or_data);
        $this->assertArrayHasKey('odt_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['odt_structure_or_data']);
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

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdtColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_columns);
        $this->assertArrayHasKey('odt_columns', $exportArray);
        $this->assertSame($expected, $exportArray['odt_columns']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdtRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_relation' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_relation);
        $this->assertArrayHasKey('odt_relation', $exportArray);
        $this->assertSame($expected, $exportArray['odt_relation']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdtComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_comments' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_comments);
        $this->assertArrayHasKey('odt_comments', $exportArray);
        $this->assertSame($expected, $exportArray['odt_comments']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testOdtMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['odt_mime' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_mime);
        $this->assertArrayHasKey('odt_mime', $exportArray);
        $this->assertSame($expected, $exportArray['odt_mime']);
    }

    /** @dataProvider valuesForOdtNullProvider */
    public function testOdtNull(mixed $actual, string $expected): void
    {
        $export = new Export(['odt_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->odt_null);
        $this->assertArrayHasKey('odt_null', $exportArray);
        $this->assertSame($expected, $exportArray['odt_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForOdtNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultStructureOrDataProvider */
    public function testHtmlwordStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['htmlword_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->htmlword_structure_or_data);
        $this->assertArrayHasKey('htmlword_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['htmlword_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testHtmlwordColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['htmlword_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->htmlword_columns);
        $this->assertArrayHasKey('htmlword_columns', $exportArray);
        $this->assertSame($expected, $exportArray['htmlword_columns']);
    }

    /** @dataProvider valuesForHtmlwordNullProvider */
    public function testHtmlwordNull(mixed $actual, string $expected): void
    {
        $export = new Export(['htmlword_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->htmlword_null);
        $this->assertArrayHasKey('htmlword_null', $exportArray);
        $this->assertSame($expected, $exportArray['htmlword_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForHtmlwordNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultStructureOrDataProvider */
    public function testTexytextStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['texytext_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->texytext_structure_or_data);
        $this->assertArrayHasKey('texytext_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['texytext_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testTexytextColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['texytext_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->texytext_columns);
        $this->assertArrayHasKey('texytext_columns', $exportArray);
        $this->assertSame($expected, $exportArray['texytext_columns']);
    }

    /** @dataProvider valuesForTexytextNullProvider */
    public function testTexytextNull(mixed $actual, string $expected): void
    {
        $export = new Export(['texytext_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->texytext_null);
        $this->assertArrayHasKey('texytext_null', $exportArray);
        $this->assertSame($expected, $exportArray['texytext_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForTexytextNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testCsvColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['csv_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_columns);
        $this->assertArrayHasKey('csv_columns', $exportArray);
        $this->assertSame($expected, $exportArray['csv_columns']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testCsvStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_structure_or_data);
        $this->assertArrayHasKey('csv_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['csv_structure_or_data']);
    }

    /** @dataProvider valuesForCsvNullProvider */
    public function testCsvNull(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_null);
        $this->assertArrayHasKey('csv_null', $exportArray);
        $this->assertSame($expected, $exportArray['csv_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvSeparatorProvider */
    public function testCsvSeparator(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_separator' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_separator);
        $this->assertArrayHasKey('csv_separator', $exportArray);
        $this->assertSame($expected, $exportArray['csv_separator']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvSeparatorProvider(): iterable
    {
        yield 'null value' => [null, ','];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvEnclosedProvider */
    public function testCsvEnclosed(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_enclosed' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_enclosed);
        $this->assertArrayHasKey('csv_enclosed', $exportArray);
        $this->assertSame($expected, $exportArray['csv_enclosed']);
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
        $export = new Export(['csv_escaped' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_escaped);
        $this->assertArrayHasKey('csv_escaped', $exportArray);
        $this->assertSame($expected, $exportArray['csv_escaped']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvEscapedProvider(): iterable
    {
        yield 'null value' => [null, '"'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForCsvTerminatedProvider */
    public function testCsvTerminated(mixed $actual, string $expected): void
    {
        $export = new Export(['csv_terminated' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_terminated);
        $this->assertArrayHasKey('csv_terminated', $exportArray);
        $this->assertSame($expected, $exportArray['csv_terminated']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForCsvTerminatedProvider(): iterable
    {
        yield 'null value' => [null, 'AUTO'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testCsvRemoveCRLF(mixed $actual, bool $expected): void
    {
        $export = new Export(['csv_removeCRLF' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->csv_removeCRLF);
        $this->assertArrayHasKey('csv_removeCRLF', $exportArray);
        $this->assertSame($expected, $exportArray['csv_removeCRLF']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testExcelColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['excel_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->excel_columns);
        $this->assertArrayHasKey('excel_columns', $exportArray);
        $this->assertSame($expected, $exportArray['excel_columns']);
    }

    /** @dataProvider valuesForExcelNullProvider */
    public function testExcelNull(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->excel_null);
        $this->assertArrayHasKey('excel_null', $exportArray);
        $this->assertSame($expected, $exportArray['excel_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForExcelNullProvider(): iterable
    {
        yield 'null value' => [null, 'NULL'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForExcelEditionProvider */
    public function testExcelEdition(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_edition' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->excel_edition);
        $this->assertArrayHasKey('excel_edition', $exportArray);
        $this->assertSame($expected, $exportArray['excel_edition']);
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

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testExcelRemoveCRLF(mixed $actual, bool $expected): void
    {
        $export = new Export(['excel_removeCRLF' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->excel_removeCRLF);
        $this->assertArrayHasKey('excel_removeCRLF', $exportArray);
        $this->assertSame($expected, $exportArray['excel_removeCRLF']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testExcelStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['excel_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->excel_structure_or_data);
        $this->assertArrayHasKey('excel_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['excel_structure_or_data']);
    }

    /** @dataProvider structureOrDataWithDefaultStructureOrDataProvider */
    public function testLatexStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_structure_or_data);
        $this->assertArrayHasKey('latex_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['latex_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testLatexColumns(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_columns' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_columns);
        $this->assertArrayHasKey('latex_columns', $exportArray);
        $this->assertSame($expected, $exportArray['latex_columns']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testLatexRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_relation' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_relation);
        $this->assertArrayHasKey('latex_relation', $exportArray);
        $this->assertSame($expected, $exportArray['latex_relation']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testLatexComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_comments' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_comments);
        $this->assertArrayHasKey('latex_comments', $exportArray);
        $this->assertSame($expected, $exportArray['latex_comments']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testLatexMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_mime' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_mime);
        $this->assertArrayHasKey('latex_mime', $exportArray);
        $this->assertSame($expected, $exportArray['latex_mime']);
    }

    /** @dataProvider valuesForLatexNullProvider */
    public function testLatexNull(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_null' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_null);
        $this->assertArrayHasKey('latex_null', $exportArray);
        $this->assertSame($expected, $exportArray['latex_null']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexNullProvider(): iterable
    {
        yield 'null value' => [null, '\textit{NULL}'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testLatexCaption(mixed $actual, bool $expected): void
    {
        $export = new Export(['latex_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_caption);
        $this->assertArrayHasKey('latex_caption', $exportArray);
        $this->assertSame($expected, $exportArray['latex_caption']);
    }

    /** @dataProvider valuesForLatexStructureCaptionProvider */
    public function testLatexStructureCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_structure_caption);
        $this->assertArrayHasKey('latex_structure_caption', $exportArray);
        $this->assertSame($expected, $exportArray['latex_structure_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexStructure'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLatexStructureContinuedCaptionProvider */
    public function testLatexStructureContinuedCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_continued_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_structure_continued_caption);
        $this->assertArrayHasKey('latex_structure_continued_caption', $exportArray);
        $this->assertSame($expected, $exportArray['latex_structure_continued_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureContinuedCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexStructure strLatexContinued'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLatexDataCaptionProvider */
    public function testLatexDataCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_data_caption);
        $this->assertArrayHasKey('latex_data_caption', $exportArray);
        $this->assertSame($expected, $exportArray['latex_data_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexContent'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLatexDataContinuedCaptionProvider */
    public function testLatexDataContinuedCaption(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_continued_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_data_continued_caption);
        $this->assertArrayHasKey('latex_data_continued_caption', $exportArray);
        $this->assertSame($expected, $exportArray['latex_data_continued_caption']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataContinuedCaptionProvider(): iterable
    {
        yield 'null value' => [null, 'strLatexContent strLatexContinued'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLatexDataLabelProvider */
    public function testLatexDataLabel(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_data_label' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_data_label);
        $this->assertArrayHasKey('latex_data_label', $exportArray);
        $this->assertSame($expected, $exportArray['latex_data_label']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexDataLabelProvider(): iterable
    {
        yield 'null value' => [null, 'tab:@TABLE@-data'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForLatexStructureLabelProvider */
    public function testLatexStructureLabel(mixed $actual, string $expected): void
    {
        $export = new Export(['latex_structure_label' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->latex_structure_label);
        $this->assertArrayHasKey('latex_structure_label', $exportArray);
        $this->assertSame($expected, $exportArray['latex_structure_label']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLatexStructureLabelProvider(): iterable
    {
        yield 'null value' => [null, 'tab:@TABLE@-structure'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testMediawikiStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['mediawiki_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->mediawiki_structure_or_data);
        $this->assertArrayHasKey('mediawiki_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['mediawiki_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testMediawikiCaption(mixed $actual, bool $expected): void
    {
        $export = new Export(['mediawiki_caption' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->mediawiki_caption);
        $this->assertArrayHasKey('mediawiki_caption', $exportArray);
        $this->assertSame($expected, $exportArray['mediawiki_caption']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testMediawikiHeaders(mixed $actual, bool $expected): void
    {
        $export = new Export(['mediawiki_headers' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->mediawiki_headers);
        $this->assertArrayHasKey('mediawiki_headers', $exportArray);
        $this->assertSame($expected, $exportArray['mediawiki_headers']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testOdsStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['ods_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->ods_structure_or_data);
        $this->assertArrayHasKey('ods_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['ods_structure_or_data']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testPdfStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['pdf_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->pdf_structure_or_data);
        $this->assertArrayHasKey('pdf_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['pdf_structure_or_data']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testPhparrayStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['phparray_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->phparray_structure_or_data);
        $this->assertArrayHasKey('phparray_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['phparray_structure_or_data']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testJsonStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['json_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->json_structure_or_data);
        $this->assertArrayHasKey('json_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['json_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testJsonPrettyPrint(mixed $actual, bool $expected): void
    {
        $export = new Export(['json_pretty_print' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->json_pretty_print);
        $this->assertArrayHasKey('json_pretty_print', $exportArray);
        $this->assertSame($expected, $exportArray['json_pretty_print']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testJsonUnicode(mixed $actual, bool $expected): void
    {
        $export = new Export(['json_unicode' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->json_unicode);
        $this->assertArrayHasKey('json_unicode', $exportArray);
        $this->assertSame($expected, $exportArray['json_unicode']);
    }

    /** @dataProvider structureOrDataWithDefaultStructureOrDataProvider */
    public function testSqlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_structure_or_data);
        $this->assertArrayHasKey('sql_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['sql_structure_or_data']);
    }

    /** @dataProvider valuesForSqlCompatibilityProvider */
    public function testSqlCompatibility(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_compatibility' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_compatibility);
        $this->assertArrayHasKey('sql_compatibility', $exportArray);
        $this->assertSame($expected, $exportArray['sql_compatibility']);
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

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlIncludeComments(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_include_comments' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_include_comments);
        $this->assertArrayHasKey('sql_include_comments', $exportArray);
        $this->assertSame($expected, $exportArray['sql_include_comments']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlDisableFk(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_disable_fk' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_disable_fk);
        $this->assertArrayHasKey('sql_disable_fk', $exportArray);
        $this->assertSame($expected, $exportArray['sql_disable_fk']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlViewsAsTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_views_as_tables' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_views_as_tables);
        $this->assertArrayHasKey('sql_views_as_tables', $exportArray);
        $this->assertSame($expected, $exportArray['sql_views_as_tables']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlMetadata(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_metadata' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_metadata);
        $this->assertArrayHasKey('sql_metadata', $exportArray);
        $this->assertSame($expected, $exportArray['sql_metadata']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlUseTransaction(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_use_transaction' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_use_transaction);
        $this->assertArrayHasKey('sql_use_transaction', $exportArray);
        $this->assertSame($expected, $exportArray['sql_use_transaction']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlCreateDatabase(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_database' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_create_database);
        $this->assertArrayHasKey('sql_create_database', $exportArray);
        $this->assertSame($expected, $exportArray['sql_create_database']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlDropDatabase(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_drop_database' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_drop_database);
        $this->assertArrayHasKey('sql_drop_database', $exportArray);
        $this->assertSame($expected, $exportArray['sql_drop_database']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlDropTable(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_drop_table' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_drop_table);
        $this->assertArrayHasKey('sql_drop_table', $exportArray);
        $this->assertSame($expected, $exportArray['sql_drop_table']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlIfNotExists(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_if_not_exists' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_if_not_exists);
        $this->assertArrayHasKey('sql_if_not_exists', $exportArray);
        $this->assertSame($expected, $exportArray['sql_if_not_exists']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlViewCurrentUser(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_view_current_user' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_view_current_user);
        $this->assertArrayHasKey('sql_view_current_user', $exportArray);
        $this->assertSame($expected, $exportArray['sql_view_current_user']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlOrReplaceView(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_or_replace_view' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_or_replace_view);
        $this->assertArrayHasKey('sql_or_replace_view', $exportArray);
        $this->assertSame($expected, $exportArray['sql_or_replace_view']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlProcedureFunction(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_procedure_function' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_procedure_function);
        $this->assertArrayHasKey('sql_procedure_function', $exportArray);
        $this->assertSame($expected, $exportArray['sql_procedure_function']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlCreateTable(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_table' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_create_table);
        $this->assertArrayHasKey('sql_create_table', $exportArray);
        $this->assertSame($expected, $exportArray['sql_create_table']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlCreateView(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_view' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_create_view);
        $this->assertArrayHasKey('sql_create_view', $exportArray);
        $this->assertSame($expected, $exportArray['sql_create_view']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlCreateTrigger(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_create_trigger' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_create_trigger);
        $this->assertArrayHasKey('sql_create_trigger', $exportArray);
        $this->assertSame($expected, $exportArray['sql_create_trigger']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlAutoIncrement(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_auto_increment' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_auto_increment);
        $this->assertArrayHasKey('sql_auto_increment', $exportArray);
        $this->assertSame($expected, $exportArray['sql_auto_increment']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlBackquotes(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_backquotes' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_backquotes);
        $this->assertArrayHasKey('sql_backquotes', $exportArray);
        $this->assertSame($expected, $exportArray['sql_backquotes']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlDates(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_dates' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_dates);
        $this->assertArrayHasKey('sql_dates', $exportArray);
        $this->assertSame($expected, $exportArray['sql_dates']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlRelation(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_relation' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_relation);
        $this->assertArrayHasKey('sql_relation', $exportArray);
        $this->assertSame($expected, $exportArray['sql_relation']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlTruncate(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_truncate' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_truncate);
        $this->assertArrayHasKey('sql_truncate', $exportArray);
        $this->assertSame($expected, $exportArray['sql_truncate']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlDelayed(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_delayed' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_delayed);
        $this->assertArrayHasKey('sql_delayed', $exportArray);
        $this->assertSame($expected, $exportArray['sql_delayed']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlIgnore(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_ignore' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_ignore);
        $this->assertArrayHasKey('sql_ignore', $exportArray);
        $this->assertSame($expected, $exportArray['sql_ignore']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlUtcTime(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_utc_time' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_utc_time);
        $this->assertArrayHasKey('sql_utc_time', $exportArray);
        $this->assertSame($expected, $exportArray['sql_utc_time']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testSqlHexForBinary(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_hex_for_binary' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_hex_for_binary);
        $this->assertArrayHasKey('sql_hex_for_binary', $exportArray);
        $this->assertSame($expected, $exportArray['sql_hex_for_binary']);
    }

    /** @dataProvider valuesForSqlTypeProvider */
    public function testSqlType(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_type' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_type);
        $this->assertArrayHasKey('sql_type', $exportArray);
        $this->assertSame($expected, $exportArray['sql_type']);
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

    /** @dataProvider valuesForSqlMaxQuerySizeProvider */
    public function testSqlMaxQuerySize(mixed $actual, int $expected): void
    {
        $export = new Export(['sql_max_query_size' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_max_query_size);
        $this->assertArrayHasKey('sql_max_query_size', $exportArray);
        $this->assertSame($expected, $exportArray['sql_max_query_size']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForSqlMaxQuerySizeProvider(): iterable
    {
        yield 'null value' => [null, 50000];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 50000];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSqlMime(mixed $actual, bool $expected): void
    {
        $export = new Export(['sql_mime' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_mime);
        $this->assertArrayHasKey('sql_mime', $exportArray);
        $this->assertSame($expected, $exportArray['sql_mime']);
    }

    /** @dataProvider valuesForSqlHeaderCommentProvider */
    public function testSqlHeaderComment(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_header_comment' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_header_comment);
        $this->assertArrayHasKey('sql_header_comment', $exportArray);
        $this->assertSame($expected, $exportArray['sql_header_comment']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSqlHeaderCommentProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForSqlInsertSyntaxProvider */
    public function testSqlInsertSyntax(mixed $actual, string $expected): void
    {
        $export = new Export(['sql_insert_syntax' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->sql_insert_syntax);
        $this->assertArrayHasKey('sql_insert_syntax', $exportArray);
        $this->assertSame($expected, $exportArray['sql_insert_syntax']);
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

    /** @dataProvider valuesForPdfReportTitleProvider */
    public function testPdfReportTitle(mixed $actual, string $expected): void
    {
        $export = new Export(['pdf_report_title' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->pdf_report_title);
        $this->assertArrayHasKey('pdf_report_title', $exportArray);
        $this->assertSame($expected, $exportArray['pdf_report_title']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPdfReportTitleProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testXmlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['xml_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_structure_or_data);
        $this->assertArrayHasKey('xml_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['xml_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportStruc(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_struc' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_struc);
        $this->assertArrayHasKey('xml_export_struc', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_struc']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportEvents(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_events' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_events);
        $this->assertArrayHasKey('xml_export_events', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_events']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportFunctions(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_functions' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_functions);
        $this->assertArrayHasKey('xml_export_functions', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_functions']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportProcedures(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_procedures' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_procedures);
        $this->assertArrayHasKey('xml_export_procedures', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_procedures']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportTables(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_tables' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_tables);
        $this->assertArrayHasKey('xml_export_tables', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_tables']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportTriggers(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_triggers' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_triggers);
        $this->assertArrayHasKey('xml_export_triggers', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_triggers']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportViews(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_views' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_views);
        $this->assertArrayHasKey('xml_export_views', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_views']);
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testXmlExportContents(mixed $actual, bool $expected): void
    {
        $export = new Export(['xml_export_contents' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->xml_export_contents);
        $this->assertArrayHasKey('xml_export_contents', $exportArray);
        $this->assertSame($expected, $exportArray['xml_export_contents']);
    }

    /** @dataProvider structureOrDataWithDefaultDataProvider */
    public function testYamlStructureOrData(mixed $actual, string $expected): void
    {
        $export = new Export(['yaml_structure_or_data' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->yaml_structure_or_data);
        $this->assertArrayHasKey('yaml_structure_or_data', $exportArray);
        $this->assertSame($expected, $exportArray['yaml_structure_or_data']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testRemoveDefinerFromDefinitions(mixed $actual, bool $expected): void
    {
        $export = new Export(['remove_definer_from_definitions' => $actual]);
        $exportArray = $export->asArray();
        $this->assertSame($expected, $export->remove_definer_from_definitions);
        $this->assertArrayHasKey('remove_definer_from_definitions', $exportArray);
        $this->assertSame($expected, $exportArray['remove_definer_from_definitions']);
    }
}
