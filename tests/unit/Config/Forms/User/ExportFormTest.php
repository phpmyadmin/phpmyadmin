<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\User;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\ExportForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class ExportFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $exportForm = new ExportForm(new ConfigFile([]), 1);
        self::assertSame('Export', ExportForm::getName());

        $forms = $exportForm->getRegisteredForms();
        self::assertCount(8, $forms);

        self::assertArrayHasKey('Export_defaults', $forms);
        $form = $forms['Export_defaults'];
        self::assertSame('Export_defaults', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame(['Export/asfile' => ':group'], $form->default);
        self::assertSame(
            [
                'method' => 'Export/method',
                ':group:Quick' => ':group:Quick',
                'quick_export_onserver' => 'Export/quick_export_onserver',
                'quick_export_onserver_overwrite' => 'Export/quick_export_onserver_overwrite',
                ':group:end:0' => ':group:end:0',
                ':group:Custom' => ':group:Custom',
                'format' => 'Export/format',
                'compression' => 'Export/compression',
                'charset' => 'Export/charset',
                'lock_tables' => 'Export/lock_tables',
                'as_separate_files' => 'Export/as_separate_files',
                'asfile' => 'Export/asfile',
                'onserver' => 'Export/onserver',
                'onserver_overwrite' => 'Export/onserver_overwrite',
                ':group:end:1' => ':group:end:1',
                'file_template_table' => 'Export/file_template_table',
                'file_template_database' => 'Export/file_template_database',
                'file_template_server' => 'Export/file_template_server',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Sql', $forms);
        $form = $forms['Sql'];
        self::assertSame('Sql', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame(
            [
                'Export/sql_include_comments' => ':group',
                'Export/sql_create_table' => ':group',
                'Export/sql_create_view' => ':group',
            ],
            $form->default,
        );
        self::assertSame(
            [
                'sql_include_comments' => 'Export/sql_include_comments',
                'sql_dates' => 'Export/sql_dates',
                'sql_relation' => 'Export/sql_relation',
                'sql_mime' => 'Export/sql_mime',
                ':group:end:2' => ':group:end:2',
                'sql_use_transaction' => 'Export/sql_use_transaction',
                'sql_disable_fk' => 'Export/sql_disable_fk',
                'sql_views_as_tables' => 'Export/sql_views_as_tables',
                'sql_metadata' => 'Export/sql_metadata',
                'sql_compatibility' => 'Export/sql_compatibility',
                'sql_structure_or_data' => 'Export/sql_structure_or_data',
                ':group:Structure' => ':group:Structure',
                'sql_drop_database' => 'Export/sql_drop_database',
                'sql_create_database' => 'Export/sql_create_database',
                'sql_drop_table' => 'Export/sql_drop_table',
                'sql_create_table' => 'Export/sql_create_table',
                'sql_if_not_exists' => 'Export/sql_if_not_exists',
                'sql_auto_increment' => 'Export/sql_auto_increment',
                ':group:end:3' => ':group:end:3',
                'sql_create_view' => 'Export/sql_create_view',
                'sql_view_current_user' => 'Export/sql_view_current_user',
                'sql_or_replace_view' => 'Export/sql_or_replace_view',
                ':group:end:4' => ':group:end:4',
                'sql_procedure_function' => 'Export/sql_procedure_function',
                'sql_create_trigger' => 'Export/sql_create_trigger',
                'sql_backquotes' => 'Export/sql_backquotes',
                ':group:end:5' => ':group:end:5',
                ':group:Data' => ':group:Data',
                'sql_delayed' => 'Export/sql_delayed',
                'sql_ignore' => 'Export/sql_ignore',
                'sql_type' => 'Export/sql_type',
                'sql_insert_syntax' => 'Export/sql_insert_syntax',
                'sql_max_query_size' => 'Export/sql_max_query_size',
                'sql_hex_for_binary' => 'Export/sql_hex_for_binary',
                'sql_utc_time' => 'Export/sql_utc_time',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('CodeGen', $forms);
        $form = $forms['CodeGen'];
        self::assertSame('CodeGen', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(['codegen_format' => 'Export/codegen_format'], $form->fields);

        self::assertArrayHasKey('Csv', $forms);
        $form = $forms['Csv'];
        self::assertSame('Csv', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                ':group:CSV' => ':group:CSV',
                'csv_separator' => 'Export/csv_separator',
                'csv_enclosed' => 'Export/csv_enclosed',
                'csv_escaped' => 'Export/csv_escaped',
                'csv_terminated' => 'Export/csv_terminated',
                'csv_null' => 'Export/csv_null',
                'csv_removeCRLF' => 'Export/csv_removeCRLF',
                'csv_columns' => 'Export/csv_columns',
                ':group:end:6' => ':group:end:6',
                ':group:CSV for MS Excel' => ':group:CSV for MS Excel',
                'excel_null' => 'Export/excel_null',
                'excel_removeCRLF' => 'Export/excel_removeCRLF',
                'excel_columns' => 'Export/excel_columns',
                'excel_edition' => 'Export/excel_edition',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Latex', $forms);
        $form = $forms['Latex'];
        self::assertSame('Latex', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'latex_caption' => 'Export/latex_caption',
                'latex_structure_or_data' => 'Export/latex_structure_or_data',
                ':group:Structure' => ':group:Structure',
                'latex_structure_caption' => 'Export/latex_structure_caption',
                'latex_structure_continued_caption' => 'Export/latex_structure_continued_caption',
                'latex_structure_label' => 'Export/latex_structure_label',
                'latex_relation' => 'Export/latex_relation',
                'latex_comments' => 'Export/latex_comments',
                'latex_mime' => 'Export/latex_mime',
                ':group:end:7' => ':group:end:7',
                ':group:Data' => ':group:Data',
                'latex_columns' => 'Export/latex_columns',
                'latex_data_caption' => 'Export/latex_data_caption',
                'latex_data_continued_caption' => 'Export/latex_data_continued_caption',
                'latex_data_label' => 'Export/latex_data_label',
                'latex_null' => 'Export/latex_null',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Microsoft_Office', $forms);
        $form = $forms['Microsoft_Office'];
        self::assertSame('Microsoft_Office', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                ':group:Microsoft Word 2000' => ':group:Microsoft Word 2000',
                'htmlword_structure_or_data' => 'Export/htmlword_structure_or_data',
                'htmlword_null' => 'Export/htmlword_null',
                'htmlword_columns' => 'Export/htmlword_columns',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Open_Document', $forms);
        $form = $forms['Open_Document'];
        self::assertSame('Open_Document', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                ':group:OpenDocument Spreadsheet' => ':group:OpenDocument Spreadsheet',
                'ods_columns' => 'Export/ods_columns',
                'ods_null' => 'Export/ods_null',
                ':group:end:8' => ':group:end:8',
                ':group:OpenDocument Text' => ':group:OpenDocument Text',
                'odt_structure_or_data' => 'Export/odt_structure_or_data',
                ':group:Structure' => ':group:Structure',
                'odt_relation' => 'Export/odt_relation',
                'odt_comments' => 'Export/odt_comments',
                'odt_mime' => 'Export/odt_mime',
                ':group:end:9' => ':group:end:9',
                ':group:Data' => ':group:Data',
                'odt_columns' => 'Export/odt_columns',
                'odt_null' => 'Export/odt_null',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Texy', $forms);
        $form = $forms['Texy'];
        self::assertSame('Texy', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'texytext_structure_or_data' => 'Export/texytext_structure_or_data',
                ':group:Data' => ':group:Data',
                'texytext_null' => 'Export/texytext_null',
                'texytext_columns' => 'Export/texytext_columns',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'Export/method',
                ':group:Quick',
                'Export/quick_export_onserver',
                'Export/quick_export_onserver_overwrite',
                ':group:end',
                ':group:Custom',
                'Export/format',
                'Export/compression',
                'Export/charset',
                'Export/lock_tables',
                'Export/as_separate_files',
                'Export/asfile',
                'Export/onserver',
                'Export/onserver_overwrite',
                ':group:end',
                'Export/file_template_table',
                'Export/file_template_database',
                'Export/file_template_server',
                'Export/sql_include_comments',
                'Export/sql_dates',
                'Export/sql_relation',
                'Export/sql_mime',
                ':group:end',
                'Export/sql_use_transaction',
                'Export/sql_disable_fk',
                'Export/sql_views_as_tables',
                'Export/sql_metadata',
                'Export/sql_compatibility',
                'Export/sql_structure_or_data',
                ':group:Structure',
                'Export/sql_drop_database',
                'Export/sql_create_database',
                'Export/sql_drop_table',
                'Export/sql_create_table',
                'Export/sql_if_not_exists',
                'Export/sql_auto_increment',
                ':group:end',
                'Export/sql_create_view',
                'Export/sql_view_current_user',
                'Export/sql_or_replace_view',
                ':group:end',
                'Export/sql_procedure_function',
                'Export/sql_create_trigger',
                'Export/sql_backquotes',
                ':group:end',
                ':group:Data',
                'Export/sql_delayed',
                'Export/sql_ignore',
                'Export/sql_type',
                'Export/sql_insert_syntax',
                'Export/sql_max_query_size',
                'Export/sql_hex_for_binary',
                'Export/sql_utc_time',
                'Export/codegen_format',
                ':group:CSV',
                'Export/csv_separator',
                'Export/csv_enclosed',
                'Export/csv_escaped',
                'Export/csv_terminated',
                'Export/csv_null',
                'Export/csv_removeCRLF',
                'Export/csv_columns',
                ':group:end',
                ':group:CSV for MS Excel',
                'Export/excel_null',
                'Export/excel_removeCRLF',
                'Export/excel_columns',
                'Export/excel_edition',
                'Export/latex_caption',
                'Export/latex_structure_or_data',
                ':group:Structure',
                'Export/latex_structure_caption',
                'Export/latex_structure_continued_caption',
                'Export/latex_structure_label',
                'Export/latex_relation',
                'Export/latex_comments',
                'Export/latex_mime',
                ':group:end',
                ':group:Data',
                'Export/latex_columns',
                'Export/latex_data_caption',
                'Export/latex_data_continued_caption',
                'Export/latex_data_label',
                'Export/latex_null',
                ':group:Microsoft Word 2000',
                'Export/htmlword_structure_or_data',
                'Export/htmlword_null',
                'Export/htmlword_columns',
                ':group:OpenDocument Spreadsheet',
                'Export/ods_columns',
                'Export/ods_null',
                ':group:end',
                ':group:OpenDocument Text',
                'Export/odt_structure_or_data',
                ':group:Structure',
                'Export/odt_relation',
                'Export/odt_comments',
                'Export/odt_mime',
                ':group:end',
                ':group:Data',
                'Export/odt_columns',
                'Export/odt_null',
                'Export/texytext_structure_or_data',
                ':group:Data',
                'Export/texytext_null',
                'Export/texytext_columns',
            ],
            ExportForm::getFields(),
        );
    }
}
