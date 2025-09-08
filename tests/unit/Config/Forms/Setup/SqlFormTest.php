<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SqlForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class SqlFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $sqlForm = new SqlForm(new ConfigFile([]), 1);
        self::assertSame('SQL queries', SqlForm::getName());

        $forms = $sqlForm->getRegisteredForms();
        self::assertCount(2, $forms);

        self::assertArrayHasKey('Sql_queries', $forms);
        $form = $forms['Sql_queries'];
        self::assertSame('Sql_queries', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'ShowSQL' => 'ShowSQL',
                'Confirm' => 'Confirm',
                'QueryHistoryMax' => 'QueryHistoryMax',
                'IgnoreMultiSubmitErrors' => 'IgnoreMultiSubmitErrors',
                'MaxCharactersInDisplayedSQL' => 'MaxCharactersInDisplayedSQL',
                'RetainQueryBox' => 'RetainQueryBox',
                'CodemirrorEnable' => 'CodemirrorEnable',
                'LintEnable' => 'LintEnable',
                'EnableAutocompleteForTablesAndColumns' => 'EnableAutocompleteForTablesAndColumns',
                'DefaultForeignKeyChecks' => 'DefaultForeignKeyChecks',
                'QueryHistoryDB' => 'QueryHistoryDB',
                'AllowSharedBookmarks' => 'AllowSharedBookmarks',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Sql_box', $forms);
        $form = $forms['Sql_box'];
        self::assertSame('Sql_box', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'Edit' => 'SQLQuery/Edit',
                'Explain' => 'SQLQuery/Explain',
                'ShowAsPHP' => 'SQLQuery/ShowAsPHP',
                'Refresh' => 'SQLQuery/Refresh',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'ShowSQL',
                'Confirm',
                'QueryHistoryMax',
                'IgnoreMultiSubmitErrors',
                'MaxCharactersInDisplayedSQL',
                'RetainQueryBox',
                'CodemirrorEnable',
                'LintEnable',
                'EnableAutocompleteForTablesAndColumns',
                'DefaultForeignKeyChecks',
                'QueryHistoryDB',
                'AllowSharedBookmarks',
                'SQLQuery/Edit',
                'SQLQuery/Explain',
                'SQLQuery/ShowAsPHP',
                'SQLQuery/Refresh',
            ],
            SqlForm::getFields(),
        );
    }
}
