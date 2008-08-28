<?php
/**
 * List of avaible forms, each form is described as an array of fields to display.
 * Fields MUST have their counterparts in the $cfg array.
 *
 * There are two possible notations:
 * $forms['Form name'] = array('Servers' => array(1 => array('host')));
 * can be written as
 * $forms['Form name'] = array('Servers/1/host');
 *
 * You can assign default values set by special button ("set value: ..."), eg.:
 * $forms['Server_pmadb'] = array('Servers' => array(1 => array(
 *  'pmadb' => 'phpmyadmin')));
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

$forms = array();
$forms['_config.php'] = array(
    'DefaultLang',
    'ServerDefault');
$forms['Server'] = array('Servers' => array(1 => array(
    'verbose',
    'host',
    'port',
    'socket',
    'ssl',
    'connect_type',
    'extension',
    'compress',
    'auth_type',
    'user',
    'password',
    'nopassword')));
$forms['Server_login_options'] = array('Servers' => array(1 => array(
    'SignonSession',
    'SignonURL',
    'LogoutURL',
    'auth_swekey_config' => './swekey.conf')));
$forms['Server_config'] = array('Servers' => array(1 => array(
    'only_db',
    'hide_db',
    'AllowRoot',
    'DisableIS',
    'AllowDeny/order',
    'AllowDeny/rules',
    'ShowDatabasesCommand',
    'CountTables')));
$forms['Server_pmadb'] = array('Servers' => array(1 => array(
    'pmadb' => 'phpmyadmin',
    'controluser',
    'controlpass',
    'verbose_check',
    'bookmarktable' => 'pma_bookmark',
    'relation' => 'pma_relation',
    'table_info' => 'pma_table_info',
    'table_coords' => 'pma_table_coords',
    'pdf_pages' => 'pma_pdf_pages',
    'column_info' => 'pma_column_info',
    'history' => 'pma_history',
    'designer_coords' => 'designer_coords')));
$forms['Import_export'] = array(
    'UploadDir',
    'SaveDir',
    'AllowAnywhereRecoding',
    'DefaultCharset',
    'RecodingEngine',
    'IconvExtraParams',
    'ZipDump',
    'GZipDump',
    'BZipDump',
    'CompressOnFly');
$forms['Security'] = array(
    'blowfish_secret',
    'ForceSSL',
    'CheckConfigurationPermissions',
    'TrustedProxies',
    'AllowUserDropDatabase',
    'AllowArbitraryServer',
    'LoginCookieRecall',
    'LoginCookieValidity',
    'LoginCookieStore',
    'LoginCookieDeleteAll');
$forms['Sql_queries'] = array(
    'ShowSQL',
    'Confirm',
    'QueryHistoryDB',
    'QueryHistoryMax',
    'IgnoreMultiSubmitErrors',
    'VerboseMultiSubmit');
$forms['Other_core_settings'] = array(
    'MaxDbList',
    'MaxTableList',
    'MaxCharactersInDisplayedSQL',
    'OBGzip',
    'PersistentConnections',
    'ExecTimeLimit',
    'MemoryLimit',
    'SkipLockedTables',
    'UseDbSearch');
$forms['Left_frame'] = array(
    'LeftFrameLight',
    'LeftDisplayLogo',
    'LeftLogoLink',
    'LeftLogoLinkWindow',
    'LeftDefaultTabTable',
    'LeftPointerEnable');
$forms['Left_servers'] = array(
    'LeftDisplayServers',
    'DisplayServersList');
$forms['Left_databases'] = array(
    'DisplayDatabasesList',
    'LeftFrameDBTree',
    'LeftFrameDBSeparator',
    'ShowTooltipAliasDB');
$forms['Left_tables'] = array(
    'LeftFrameTableSeparator',
    'LeftFrameTableLevel',
    'ShowTooltip',
    'ShowTooltipAliasTB');
$forms['Startup'] = array(
    'ShowStats',
    'ShowPhpInfo',
    'ShowServerInfo',
    'ShowChgPassword',
    'ShowCreateDb',
    'SuggestDBName');
$forms['Browse'] = array(
    'NavigationBarIconic',
    'ShowAll',
    'MaxRows',
    'Order',
    'BrowsePointerEnable',
    'BrowseMarkerEnable');
$forms['Edit'] = array(
    'ProtectBinary',
    'ShowFunctionFields',
    'CharEditing',
    'CharTextareaCols',
    'CharTextareaRows',
    'InsertRows',
    'ForeignKeyDropdownOrder',
    'ForeignKeyMaxLimit');
$forms['Tabs'] = array(
    'LightTabs',
    'PropertiesIconic',
    'DefaultTabServer',
    'DefaultTabDatabase',
    'DefaultTabTable');
$forms['Sql_box'] = array('SQLQuery' => array(
    'Edit',
    'Explain',
    'ShowAsPHP',
    'Validate',
    'Refresh'));
$forms['Import'] = array('Import' => array(
    'format',
    'allow_interrupt',
    'skip_queries'));
$forms['Import_sql'] = array('Import' => array(
    'sql_compatibility'));
$forms['Import_csv'] = array('Import' => array(
    'csv_replace',
    'csv_terminated',
    'csv_enclosed',
    'csv_escaped',
    'csv_new_line',
    'csv_columns'));
$forms['Import_ldi'] = array('Import' => array(
    'ldi_replace',
    'ldi_terminated',
    'ldi_enclosed',
    'ldi_escaped',
    'ldi_new_line',
    'ldi_columns',
    'ldi_local_option'));
$forms['Export_defaults'] = array('Export' => array(
    'format',
    'compression',
    'asfile',
    'charset',
    'onserver',
    'onserver_overwrite',
    'remember_file_template',
    'file_template_table',
    'file_template_database',
    'file_template_server'));
?>