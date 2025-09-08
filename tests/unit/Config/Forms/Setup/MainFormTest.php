<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\MainForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MainForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class MainFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $mainForm = new MainForm(new ConfigFile([]), 1);
        self::assertSame('Main panel', MainForm::getName());

        $forms = $mainForm->getRegisteredForms();
        self::assertCount(7, $forms);

        self::assertArrayHasKey('Startup', $forms);
        $form = $forms['Startup'];
        self::assertSame('Startup', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'ShowCreateDb' => 'ShowCreateDb',
                'ShowStats' => 'ShowStats',
                'ShowServerInfo' => 'ShowServerInfo',
                'ShowPhpInfo' => 'ShowPhpInfo',
                'ShowChgPassword' => 'ShowChgPassword',
            ],
            $form->fields,
        );

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

        self::assertArrayHasKey('Tabs', $forms);
        $form = $forms['Tabs'];
        self::assertSame('Tabs', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'TabsMode' => 'TabsMode',
                'DefaultTabServer' => 'DefaultTabServer',
                'DefaultTabDatabase' => 'DefaultTabDatabase',
                'DefaultTabTable' => 'DefaultTabTable',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('DisplayRelationalSchema', $forms);
        $form = $forms['DisplayRelationalSchema'];
        self::assertSame('DisplayRelationalSchema', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(['PDFDefaultPageSize' => 'PDFDefaultPageSize'], $form->fields);
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'ShowCreateDb',
                'ShowStats',
                'ShowServerInfo',
                'ShowPhpInfo',
                'ShowChgPassword',
                'ShowDbStructureCharset',
                'ShowDbStructureComment',
                'ShowDbStructureCreation',
                'ShowDbStructureLastUpdate',
                'ShowDbStructureLastCheck',
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
                'ProtectBinary',
                'ShowFunctionFields',
                'ShowFieldTypesInDataEditView',
                'InsertRows',
                'ForeignKeyDropdownOrder',
                'ForeignKeyMaxLimit',
                'TabsMode',
                'DefaultTabServer',
                'DefaultTabDatabase',
                'DefaultTabTable',
                'PDFDefaultPageSize',
            ],
            MainForm::getFields(),
        );
    }
}
