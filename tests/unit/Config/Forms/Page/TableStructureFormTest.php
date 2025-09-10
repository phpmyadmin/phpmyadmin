<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Page;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Page\TableStructureForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(TableStructureForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class TableStructureFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $tableStructureForm = new TableStructureForm(new ConfigFile([]), 1);
        self::assertSame('', TableStructureForm::getName());

        $forms = $tableStructureForm->getRegisteredForms();
        self::assertCount(1, $forms);

        self::assertArrayHasKey('TableStructure', $forms);
        $form = $forms['TableStructure'];
        self::assertSame('TableStructure', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'HideStructureActions' => 'HideStructureActions',
                'ShowColumnComments' => 'ShowColumnComments',
                ':group:Default transformations' => ':group:Default transformations',
                'Hex' => 'DefaultTransformations/Hex',
                'Substring' => 'DefaultTransformations/Substring',
                'Bool2Text' => 'DefaultTransformations/Bool2Text',
                'External' => 'DefaultTransformations/External',
                'PreApPend' => 'DefaultTransformations/PreApPend',
                'DateFormat' => 'DefaultTransformations/DateFormat',
                'Inline' => 'DefaultTransformations/Inline',
                'TextImageLink' => 'DefaultTransformations/TextImageLink',
                'TextLink' => 'DefaultTransformations/TextLink',
                ':group:end:0' => ':group:end:0',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'HideStructureActions',
                'ShowColumnComments',
                ':group:Default transformations',
                'DefaultTransformations/Hex',
                'DefaultTransformations/Substring',
                'DefaultTransformations/Bool2Text',
                'DefaultTransformations/External',
                'DefaultTransformations/PreApPend',
                'DefaultTransformations/DateFormat',
                'DefaultTransformations/Inline',
                'DefaultTransformations/TextImageLink',
                'DefaultTransformations/TextLink',
                ':group:end',
            ],
            TableStructureForm::getFields(),
        );
    }
}
