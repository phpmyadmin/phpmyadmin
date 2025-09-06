<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\User;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\FeaturesForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FeaturesForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class FeaturesFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();
        $config = new Config();
        $config->settings['UserprefsDeveloperTab'] = true;
        Config::$instance = $config;

        $featuresForm = new FeaturesForm(new ConfigFile([]), 1);
        self::assertSame('Features', FeaturesForm::getName());

        $forms = $featuresForm->getRegisteredForms();
        self::assertCount(7, $forms);

        self::assertArrayHasKey('General', $forms);
        $form = $forms['General'];
        self::assertSame('General', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'VersionCheck' => 'VersionCheck',
                'NaturalOrder' => 'NaturalOrder',
                'InitialSlidersState' => 'InitialSlidersState',
                'LoginCookieValidity' => 'LoginCookieValidity',
                'SkipLockedTables' => 'SkipLockedTables',
                'DisableMultiTableMaintenance' => 'DisableMultiTableMaintenance',
                'ShowHint' => 'ShowHint',
                'SendErrorReports' => 'SendErrorReports',
                'ConsoleEnterExecutes' => 'ConsoleEnterExecutes',
                'DisableShortcutKeys' => 'DisableShortcutKeys',
                'FirstDayOfCalendar' => 'FirstDayOfCalendar',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Databases', $forms);
        $form = $forms['Databases'];
        self::assertSame('Databases', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'only_db' => 'Servers/1/only_db',
                'hide_db' => 'Servers/1/hide_db',
                'MaxDbList' => 'MaxDbList',
                'MaxTableList' => 'MaxTableList',
                'DefaultConnectionCollation' => 'DefaultConnectionCollation',
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

        self::assertArrayHasKey('Page_titles', $forms);
        $form = $forms['Page_titles'];
        self::assertSame('Page_titles', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'TitleDefault' => 'TitleDefault',
                'TitleTable' => 'TitleTable',
                'TitleDatabase' => 'TitleDatabase',
                'TitleServer' => 'TitleServer',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Warnings', $forms);
        $form = $forms['Warnings'];
        self::assertSame('Warnings', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'PmaNoRelation_DisableWarning' => 'PmaNoRelation_DisableWarning',
                'SuhosinDisableWarning' => 'SuhosinDisableWarning',
                'LoginCookieValidityDisableWarning' => 'LoginCookieValidityDisableWarning',
                'ReservedWordDisableWarning' => 'ReservedWordDisableWarning',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Console', $forms);
        $form = $forms['Console'];
        self::assertSame('Console', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'Mode' => 'Console/Mode',
                'StartHistory' => 'Console/StartHistory',
                'AlwaysExpand' => 'Console/AlwaysExpand',
                'CurrentQuery' => 'Console/CurrentQuery',
                'EnterExecutes' => 'Console/EnterExecutes',
                'DarkTheme' => 'Console/DarkTheme',
                'Height' => 'Console/Height',
                'GroupQueries' => 'Console/GroupQueries',
                'OrderBy' => 'Console/OrderBy',
                'Order' => 'Console/Order',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Developer', $forms);
        $form = $forms['Developer'];
        self::assertSame('Developer', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(['sql' => 'DBG/sql'], $form->fields);
    }

    public function testGetFields(): void
    {
        self::assertSame(
            [
                'VersionCheck',
                'NaturalOrder',
                'InitialSlidersState',
                'LoginCookieValidity',
                'SkipLockedTables',
                'DisableMultiTableMaintenance',
                'ShowHint',
                'SendErrorReports',
                'ConsoleEnterExecutes',
                'DisableShortcutKeys',
                'FirstDayOfCalendar',
                'Servers/1/only_db',
                'Servers/1/hide_db',
                'MaxDbList',
                'MaxTableList',
                'DefaultConnectionCollation',
                'CharEditing',
                'MinSizeForInputField',
                'MaxSizeForInputField',
                'CharTextareaCols',
                'CharTextareaRows',
                'TextareaCols',
                'TextareaRows',
                'LongtextDoubleTextarea',
                'TitleDefault',
                'TitleTable',
                'TitleDatabase',
                'TitleServer',
                'PmaNoRelation_DisableWarning',
                'SuhosinDisableWarning',
                'LoginCookieValidityDisableWarning',
                'ReservedWordDisableWarning',
                'Console/Mode',
                'Console/StartHistory',
                'Console/AlwaysExpand',
                'Console/CurrentQuery',
                'Console/EnterExecutes',
                'Console/DarkTheme',
                'Console/Height',
                'Console/GroupQueries',
                'Console/OrderBy',
                'Console/Order',
            ],
            FeaturesForm::getFields(),
        );
    }
}
