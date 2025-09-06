<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\User;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\ImportForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ImportForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class ImportFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $importForm = new ImportForm(new ConfigFile([]), 1);
        self::assertSame('Import', ImportForm::getName());

        $forms = $importForm->getRegisteredForms();
        self::assertCount(4, $forms);

        self::assertArrayHasKey('Import_defaults', $forms);
        $form = $forms['Import_defaults'];
        self::assertSame('Import_defaults', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'format' => 'Import/format',
                'charset' => 'Import/charset',
                'allow_interrupt' => 'Import/allow_interrupt',
                'skip_queries' => 'Import/skip_queries',
                'enable_drag_drop_import' => 'enable_drag_drop_import',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Sql', $forms);
        $form = $forms['Sql'];
        self::assertSame('Sql', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'sql_compatibility' => 'Import/sql_compatibility',
                'sql_no_auto_value_on_zero' => 'Import/sql_no_auto_value_on_zero',
                'sql_read_as_multibytes' => 'Import/sql_read_as_multibytes',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Csv', $forms);
        $form = $forms['Csv'];
        self::assertSame('Csv', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                ':group:CSV' => ':group:CSV',
                'csv_replace' => 'Import/csv_replace',
                'csv_ignore' => 'Import/csv_ignore',
                'csv_terminated' => 'Import/csv_terminated',
                'csv_enclosed' => 'Import/csv_enclosed',
                'csv_escaped' => 'Import/csv_escaped',
                'csv_col_names' => 'Import/csv_col_names',
                ':group:end:0' => ':group:end:0',
                ':group:CSV using LOAD DATA' => ':group:CSV using LOAD DATA',
                'ldi_replace' => 'Import/ldi_replace',
                'ldi_ignore' => 'Import/ldi_ignore',
                'ldi_terminated' => 'Import/ldi_terminated',
                'ldi_enclosed' => 'Import/ldi_enclosed',
                'ldi_escaped' => 'Import/ldi_escaped',
                'ldi_local_option' => 'Import/ldi_local_option',
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
                'ods_col_names' => 'Import/ods_col_names',
                'ods_empty_rows' => 'Import/ods_empty_rows',
                'ods_recognize_percentages' => 'Import/ods_recognize_percentages',
                'ods_recognize_currency' => 'Import/ods_recognize_currency',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'Import/format',
                'Import/charset',
                'Import/allow_interrupt',
                'Import/skip_queries',
                'enable_drag_drop_import',
                'Import/sql_compatibility',
                'Import/sql_no_auto_value_on_zero',
                'Import/sql_read_as_multibytes',
                ':group:CSV',
                'Import/csv_replace',
                'Import/csv_ignore',
                'Import/csv_terminated',
                'Import/csv_enclosed',
                'Import/csv_escaped',
                'Import/csv_col_names',
                ':group:end',
                ':group:CSV using LOAD DATA',
                'Import/ldi_replace',
                'Import/ldi_ignore',
                'Import/ldi_terminated',
                'Import/ldi_enclosed',
                'Import/ldi_escaped',
                'Import/ldi_local_option',
                ':group:OpenDocument Spreadsheet',
                'Import/ods_col_names',
                'Import/ods_empty_rows',
                'Import/ods_recognize_percentages',
                'Import/ods_recognize_currency',
            ],
            ImportForm::getFields(),
        );
    }
}
