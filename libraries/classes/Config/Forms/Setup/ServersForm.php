<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

class ServersForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Server' => array('Servers' => array(1 => array(
                'verbose',
                'host',
                'port',
                'socket',
                'ssl',
                'compress'))),
            'Server_auth' => array('Servers' => array(1 => array(
                'auth_type',
                ':group:' . __('Config authentication'),
                    'user',
                    'password',
                    ':group:end',
                ':group:' . __('HTTP authentication'),
                    'auth_http_realm',
                    ':group:end',
                ':group:' . __('Signon authentication'),
                    'SignonSession',
                    'SignonURL',
                    'LogoutURL'))),
            'Server_config' => array('Servers' => array(1 => array(
                'only_db',
                'hide_db',
                'AllowRoot',
                'AllowNoPassword',
                'DisableIS',
                'AllowDeny/order',
                'AllowDeny/rules',
                'SessionTimeZone'))),
            'Server_pmadb' => array('Servers' => array(1 => array(
                'pmadb' => 'phpmyadmin',
                'controlhost',
                'controlport',
                'controluser',
                'controlpass',
                'bookmarktable' => 'pma__bookmark',
                'relation' => 'pma__relation',
                'userconfig' => 'pma__userconfig',
                'users' => 'pma__users',
                'usergroups' => 'pma__usergroups',
                'navigationhiding' => 'pma__navigationhiding',
                'table_info' => 'pma__table_info',
                'column_info' => 'pma__column_info',
                'history' => 'pma__history',
                'recent' => 'pma__recent',
                'favorite' => 'pma__favorite',
                'table_uiprefs' => 'pma__table_uiprefs',
                'tracking' => 'pma__tracking',
                'table_coords' => 'pma__table_coords',
                'pdf_pages' => 'pma__pdf_pages',
                'savedsearches' => 'pma__savedsearches',
                'central_columns' => 'pma__central_columns',
                'designer_settings' => 'pma__designer_settings',
                'export_templates' => 'pma__export_templates',
                'MaxTableUiprefs' => 100))),
            'Server_tracking' => array('Servers' => array(1 => array(
                'tracking_version_auto_create',
                'tracking_default_statements',
                'tracking_add_drop_view',
                'tracking_add_drop_table',
                'tracking_add_drop_database',
            ))),
        );
    }
}
