<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\User;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\NaviForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NaviForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class NaviFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $naviForm = new NaviForm(new ConfigFile([]), 1);
        self::assertSame('Navigation panel', NaviForm::getName());

        $forms = $naviForm->getRegisteredForms();
        self::assertCount(5, $forms);

        self::assertArrayHasKey('Navi_panel', $forms);
        $form = $forms['Navi_panel'];
        self::assertSame('Navi_panel', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'ShowDatabasesNavigationAsTree' => 'ShowDatabasesNavigationAsTree',
                'NavigationLinkWithMainPanel' => 'NavigationLinkWithMainPanel',
                'NavigationDisplayLogo' => 'NavigationDisplayLogo',
                'NavigationLogoLink' => 'NavigationLogoLink',
                'NavigationLogoLinkWindow' => 'NavigationLogoLinkWindow',
                'NavigationTreePointerEnable' => 'NavigationTreePointerEnable',
                'FirstLevelNavigationItems' => 'FirstLevelNavigationItems',
                'NavigationTreeDisplayItemFilterMinimum' => 'NavigationTreeDisplayItemFilterMinimum',
                'NumRecentTables' => 'NumRecentTables',
                'NumFavoriteTables' => 'NumFavoriteTables',
                'NavigationWidth' => 'NavigationWidth',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Navi_tree', $forms);
        $form = $forms['Navi_tree'];
        self::assertSame('Navi_tree', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'MaxNavigationItems' => 'MaxNavigationItems',
                'NavigationTreeEnableGrouping' => 'NavigationTreeEnableGrouping',
                'NavigationTreeEnableExpansion' => 'NavigationTreeEnableExpansion',
                'NavigationTreeShowTables' => 'NavigationTreeShowTables',
                'NavigationTreeShowViews' => 'NavigationTreeShowViews',
                'NavigationTreeShowFunctions' => 'NavigationTreeShowFunctions',
                'NavigationTreeShowProcedures' => 'NavigationTreeShowProcedures',
                'NavigationTreeShowEvents' => 'NavigationTreeShowEvents',
                'NavigationTreeAutoexpandSingleDb' => 'NavigationTreeAutoexpandSingleDb',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Navi_servers', $forms);
        $form = $forms['Navi_servers'];
        self::assertSame('Navi_servers', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            ['NavigationDisplayServers' => 'NavigationDisplayServers', 'DisplayServersList' => 'DisplayServersList'],
            $form->fields,
        );

        self::assertArrayHasKey('Navi_databases', $forms);
        $form = $forms['Navi_databases'];
        self::assertSame('Navi_databases', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'NavigationTreeDisplayDbFilterMinimum' => 'NavigationTreeDisplayDbFilterMinimum',
                'NavigationTreeDbSeparator' => 'NavigationTreeDbSeparator',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Navi_tables', $forms);
        $form = $forms['Navi_tables'];
        self::assertSame('Navi_tables', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'NavigationTreeDefaultTabTable' => 'NavigationTreeDefaultTabTable',
                'NavigationTreeDefaultTabTable2' => 'NavigationTreeDefaultTabTable2',
                'NavigationTreeTableSeparator' => 'NavigationTreeTableSeparator',
                'NavigationTreeTableLevel' => 'NavigationTreeTableLevel',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'ShowDatabasesNavigationAsTree',
                'NavigationLinkWithMainPanel',
                'NavigationDisplayLogo',
                'NavigationLogoLink',
                'NavigationLogoLinkWindow',
                'NavigationTreePointerEnable',
                'FirstLevelNavigationItems',
                'NavigationTreeDisplayItemFilterMinimum',
                'NumRecentTables',
                'NumFavoriteTables',
                'NavigationWidth',
                'MaxNavigationItems',
                'NavigationTreeEnableGrouping',
                'NavigationTreeEnableExpansion',
                'NavigationTreeShowTables',
                'NavigationTreeShowViews',
                'NavigationTreeShowFunctions',
                'NavigationTreeShowProcedures',
                'NavigationTreeShowEvents',
                'NavigationTreeAutoexpandSingleDb',
                'NavigationDisplayServers',
                'DisplayServersList',
                'NavigationTreeDisplayDbFilterMinimum',
                'NavigationTreeDbSeparator',
                'NavigationTreeDefaultTabTable',
                'NavigationTreeDefaultTabTable2',
                'NavigationTreeTableSeparator',
                'NavigationTreeTableLevel',
            ],
            NaviForm::getFields(),
        );
    }
}
