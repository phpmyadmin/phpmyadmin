<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Page;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Page\EditForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EditForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class EditFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $editForm = new EditForm(new ConfigFile([]), 1);
        self::assertSame('', EditForm::getName());

        $forms = $editForm->getRegisteredForms();
        self::assertCount(2, $forms);

        self::assertArrayHasKey('Edit', $forms);
        $form = $forms['Edit'];
        self::assertSame('Edit', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'ProtectBinary' => 'ProtectBinary',
                'ShowFunctionFields' => 'ShowFunctionFields',
                'ShowFieldTypesInDataEditView' => 'ShowFieldTypesInDataEditView',
                'InsertRows' => 'InsertRows',
                'ForeignKeyDropdownOrder' => 'ForeignKeyDropdownOrder',
                'ForeignKeyMaxLimit' => 'ForeignKeyMaxLimit',

            ],
            $form->fields,
        );

        self::assertArrayHasKey('Text_fields', $forms);
        $form = $forms['Text_fields'];
        self::assertSame('Text_fields', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'CharEditing' => 'CharEditing',
                'MinSizeForInputField' => 'MinSizeForInputField',
                'MaxSizeForInputField' => 'MaxSizeForInputField',
                'CharTextareaCols' => 'CharTextareaCols',
                'CharTextareaRows' => 'CharTextareaRows',
                'TextareaCols' => 'TextareaCols',
                'TextareaRows' => 'TextareaRows',
                'LongtextDoubleTextarea' => 'LongtextDoubleTextarea',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'ProtectBinary',
                'ShowFunctionFields',
                'ShowFieldTypesInDataEditView',
                'InsertRows',
                'ForeignKeyDropdownOrder',
                'ForeignKeyMaxLimit',
                'CharEditing',
                'MinSizeForInputField',
                'MaxSizeForInputField',
                'CharTextareaCols',
                'CharTextareaRows',
                'TextareaCols',
                'TextareaRows',
                'LongtextDoubleTextarea',
            ],
            EditForm::getFields(),
        );
    }
}
