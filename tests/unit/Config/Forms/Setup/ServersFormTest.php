<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Forms\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ServersForm::class)]
#[CoversClass(BaseForm::class)]
#[CoversClass(FormDisplay::class)]
#[CoversClass(Form::class)]
final class ServersFormTest extends AbstractTestCase
{
    public function testRegisteredForms(): void
    {
        Form::resetGroupCounter();

        $serversForm = new ServersForm(new ConfigFile([]), 1);
        self::assertSame('', ServersForm::getName());

        $forms = $serversForm->getRegisteredForms();
        self::assertCount(5, $forms);

        self::assertArrayHasKey('Server', $forms);
        $form = $forms['Server'];
        self::assertSame('Server', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'verbose' => 'Servers/1/verbose',
                'host' => 'Servers/1/host',
                'port' => 'Servers/1/port',
                'socket' => 'Servers/1/socket',
                'ssl' => 'Servers/1/ssl',
                'compress' => 'Servers/1/compress',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Server_auth', $forms);
        $form = $forms['Server_auth'];
        self::assertSame('Server_auth', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'auth_type' => 'Servers/1/auth_type',
                ':group:Config authentication' => 'Servers/1/:group:Config authentication',
                'user' => 'Servers/1/user',
                'password' => 'Servers/1/password',
                ':group:end:0' => 'Servers/1/:group:end:0',
                ':group:HTTP authentication' => 'Servers/1/:group:HTTP authentication',
                'auth_http_realm' => 'Servers/1/auth_http_realm',
                ':group:end:1' => 'Servers/1/:group:end:1',
                ':group:Signon authentication' => 'Servers/1/:group:Signon authentication',
                'SignonSession' => 'Servers/1/SignonSession',
                'SignonURL' => 'Servers/1/SignonURL',
                'LogoutURL' => 'Servers/1/LogoutURL',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Server_config', $forms);
        $form = $forms['Server_config'];
        self::assertSame('Server_config', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'only_db' => 'Servers/1/only_db',
                'hide_db' => 'Servers/1/hide_db',
                'AllowRoot' => 'Servers/1/AllowRoot',
                'AllowNoPassword' => 'Servers/1/AllowNoPassword',
                'DisableIS' => 'Servers/1/DisableIS',
                'order' => 'Servers/1/AllowDeny/order',
                'rules' => 'Servers/1/AllowDeny/rules',
                'SessionTimeZone' => 'Servers/1/SessionTimeZone',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Server_pmadb', $forms);
        $form = $forms['Server_pmadb'];
        self::assertSame('Server_pmadb', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame(
            [
                'Servers/1/pmadb' => 'phpmyadmin',
                'Servers/1/bookmarktable' => 'pma__bookmark',
                'Servers/1/relation' => 'pma__relation',
                'Servers/1/userconfig' => 'pma__userconfig',
                'Servers/1/users' => 'pma__users',
                'Servers/1/usergroups' => 'pma__usergroups',
                'Servers/1/navigationhiding' => 'pma__navigationhiding',
                'Servers/1/table_info' => 'pma__table_info',
                'Servers/1/column_info' => 'pma__column_info',
                'Servers/1/history' => 'pma__history',
                'Servers/1/recent' => 'pma__recent',
                'Servers/1/favorite' => 'pma__favorite',
                'Servers/1/table_uiprefs' => 'pma__table_uiprefs',
                'Servers/1/tracking' => 'pma__tracking',
                'Servers/1/table_coords' => 'pma__table_coords',
                'Servers/1/pdf_pages' => 'pma__pdf_pages',
                'Servers/1/savedsearches' => 'pma__savedsearches',
                'Servers/1/central_columns' => 'pma__central_columns',
                'Servers/1/designer_settings' => 'pma__designer_settings',
                'Servers/1/export_templates' => 'pma__export_templates',
                'Servers/1/MaxTableUiprefs' => 100,
            ],
            $form->default,
        );
        self::assertSame(
            [
                'pmadb' => 'Servers/1/pmadb',
                'controlhost' => 'Servers/1/controlhost',
                'controlport' => 'Servers/1/controlport',
                'controluser' => 'Servers/1/controluser',
                'controlpass' => 'Servers/1/controlpass',
                'bookmarktable' => 'Servers/1/bookmarktable',
                'relation' => 'Servers/1/relation',
                'userconfig' => 'Servers/1/userconfig',
                'users' => 'Servers/1/users',
                'usergroups' => 'Servers/1/usergroups',
                'navigationhiding' => 'Servers/1/navigationhiding',
                'table_info' => 'Servers/1/table_info',
                'column_info' => 'Servers/1/column_info',
                'history' => 'Servers/1/history',
                'recent' => 'Servers/1/recent',
                'favorite' => 'Servers/1/favorite',
                'table_uiprefs' => 'Servers/1/table_uiprefs',
                'tracking' => 'Servers/1/tracking',
                'table_coords' => 'Servers/1/table_coords',
                'pdf_pages' => 'Servers/1/pdf_pages',
                'savedsearches' => 'Servers/1/savedsearches',
                'central_columns' => 'Servers/1/central_columns',
                'designer_settings' => 'Servers/1/designer_settings',
                'export_templates' => 'Servers/1/export_templates',
                'MaxTableUiprefs' => 'Servers/1/MaxTableUiprefs',
            ],
            $form->fields,
        );

        self::assertArrayHasKey('Server_tracking', $forms);
        $form = $forms['Server_tracking'];
        self::assertSame('Server_tracking', $form->name);
        self::assertSame(1, $form->index);
        self::assertSame([], $form->default);
        self::assertSame(
            [
                'tracking_version_auto_create' => 'Servers/1/tracking_version_auto_create',
                'tracking_default_statements' => 'Servers/1/tracking_default_statements',
                'tracking_add_drop_view' => 'Servers/1/tracking_add_drop_view',
                'tracking_add_drop_table' => 'Servers/1/tracking_add_drop_table',
                'tracking_add_drop_database' => 'Servers/1/tracking_add_drop_database',
            ],
            $form->fields,
        );
    }

    public function testGetFields(): void
    {
        self::assertSame(['Servers', 'Servers', 'Servers', 'Servers', 'Servers'], ServersForm::getFields());
    }
}
