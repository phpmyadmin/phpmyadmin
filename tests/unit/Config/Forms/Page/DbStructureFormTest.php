<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Page;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Page\DbStructureForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DbStructureForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class DbStructureFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $dbStructureForm = new DbStructureForm(new ConfigFile([]), 1);
        self::assertSame('', DbStructureForm::getName());

        $forms = $dbStructureForm->getRegisteredForms();
        self::assertCount(1, $forms);

        self::assertArrayHasKey('DbStructure', $forms);
        $form = $forms['DbStructure'];
        self::assertSame('DbStructure', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'ShowDbStructureCharset' => 'ShowDbStructureCharset',
                'ShowDbStructureComment' => 'ShowDbStructureComment',
                'ShowDbStructureCreation' => 'ShowDbStructureCreation',
                'ShowDbStructureLastUpdate' => 'ShowDbStructureLastUpdate',
                'ShowDbStructureLastCheck' => 'ShowDbStructureLastCheck',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'ShowDbStructureCharset',
                'ShowDbStructureComment',
                'ShowDbStructureCreation',
                'ShowDbStructureLastUpdate',
                'ShowDbStructureLastCheck',
            ],
            DbStructureForm::getFields(),
        );
    }
}
