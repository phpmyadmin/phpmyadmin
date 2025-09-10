<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Page;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Page\BrowseForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BrowseForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class BrowseFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $browseForm = new BrowseForm(new ConfigFile([]), 1);
        self::assertSame('', BrowseForm::getName());

        $forms = $browseForm->getRegisteredForms();
        self::assertCount(1, $forms);

        self::assertArrayHasKey('Browse', $forms);
        $form = $forms['Browse'];
        self::assertSame('Browse', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'TableNavigationLinksMode' => 'TableNavigationLinksMode',
                'ActionLinksMode' => 'ActionLinksMode',
                'ShowAll' => 'ShowAll',
                'MaxRows' => 'MaxRows',
                'Order' => 'Order',
                'BrowsePointerEnable' => 'BrowsePointerEnable',
                'BrowseMarkerEnable' => 'BrowseMarkerEnable',
                'GridEditing' => 'GridEditing',
                'SaveCellsAtOnce' => 'SaveCellsAtOnce',
                'RepeatCells' => 'RepeatCells',
                'LimitChars' => 'LimitChars',
                'RowActionLinks' => 'RowActionLinks',
                'RowActionLinksWithoutUnique' => 'RowActionLinksWithoutUnique',
                'TablePrimaryKeyOrder' => 'TablePrimaryKeyOrder',
                'RememberSorting' => 'RememberSorting',
                'RelationalDisplay' => 'RelationalDisplay',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'TableNavigationLinksMode',
                'ActionLinksMode',
                'ShowAll',
                'MaxRows',
                'Order',
                'BrowsePointerEnable',
                'BrowseMarkerEnable',
                'GridEditing',
                'SaveCellsAtOnce',
                'RepeatCells',
                'LimitChars',
                'RowActionLinks',
                'RowActionLinksWithoutUnique',
                'TablePrimaryKeyOrder',
                'RememberSorting',
                'RelationalDisplay',
            ],
            BrowseForm::getFields(),
        );
    }
}
