<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\BaseFormList;
use PhpMyAdmin\Config\Forms\Page;
use PhpMyAdmin\Config\Forms\Page\PageFormList;
use PhpMyAdmin\Config\Forms\Setup;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Config\Forms\User;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(BaseFormList::class)]
#[CoversClass(PageFormList::class)]
#[CoversClass(SetupFormList::class)]
#[CoversClass(UserFormList::class)]
class FormListTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();
    }

    /**
     * Tests for preferences forms.
     *
     * @param string $class  Class to test
     * @param string $prefix Returned class prefix
     * @psalm-param class-string<BaseFormList> $class
     * @psalm-param class-string<BaseForm> $prefix
     */
    #[DataProvider('formObjects')]
    public function testForms(string $class, string $prefix): void
    {
        $cf = new ConfigFile(Config::getInstance()->baseSettings);

        /* Static API */
        self::assertTrue($class::isValid('Export'));
        self::assertSame($prefix, $class::get('Export'));
        foreach ($class::getAllFormNames() as $form) {
            $formClass = $class::get($form);
            self::assertNotNull($formClass);
            self::assertNotNull($formClass::getName());
        }

        self::assertContains('Export/texytext_columns', $class::getFields());

        /* Instance handling */
        $forms = new $class($cf);
        self::assertInstanceOf(BaseFormList::class, $forms);
        self::assertFalse($forms->process());
        $forms->fixErrors();
        self::assertFalse($forms->hasErrors());
        self::assertSame('', $forms->displayErrors());
    }

    /** @return array<array{class-string<BaseFormList>, class-string<BaseForm>}> */
    public static function formObjects(): array
    {
        return [
            [User\UserFormList::class, User\ExportForm::class],
            [Page\PageFormList::class, Page\ExportForm::class],
            [Setup\SetupFormList::class, Setup\ExportForm::class],
        ];
    }
}
