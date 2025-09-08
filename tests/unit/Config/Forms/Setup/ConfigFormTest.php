<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ConfigForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class ConfigFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $configForm = new ConfigForm(new ConfigFile([]), 1);
        self::assertSame('', ConfigForm::getName());

        $forms = $configForm->getRegisteredForms();
        self::assertCount(1, $forms);

        self::assertArrayHasKey('Config', $forms);
        $form = $forms['Config'];
        self::assertSame('Config', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(['DefaultLang' => 'DefaultLang', 'ServerDefault' => 'ServerDefault'], $form->fields);
    }

    public function testGetFields(): void
    {
        self::assertSame(['DefaultLang', 'ServerDefault'], ConfigForm::getFields());
    }
}
