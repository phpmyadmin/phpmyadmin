<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * List of avaible forms, each form is described as an array of fields to display.
 * Fields MUST have their counterparts in the $cfg array.
 *
 * To define form field, use the notatnion below:
 * $forms['Form group']['Form name'] = array('Option/path');
 *
 * You can assign default values set by special button ("set value: ..."), eg.:
 * 'Servers/1/pmadb' => 'phpmyadmin'
 *
 * To group options, use:
 * ':group:' . __('group name') // just define a group
 * or
 * 'option' => ':group' // group starting from this option
 * End group blocks with:
 * ':group:end'
 *
 * @package PhpMyAdmin
 */

$forms = array();
$forms['Features']['General'] = array(
    'VersionCheck',
    'NaturalOrder',
    'InitialSlidersState',
    'LoginCookieValidity',
    'Servers/1/only_db', // saves to Server/only_db
    'Servers/1/hide_db', // saves to Server/hide_db
    'SkipLockedTables',
    'DisableMultiTableMaintenance',
    'MaxDbList',
    'MaxTableList',
    'NumRecentTables',
    'NumFavoriteTables',
    'ShowHint',
    'SendErrorReports');
$forms['Features']['Text_fields'] = array(
    'CharEditing',
    'MinSizeForInputField',
    'MaxSizeForInputField',
    'CharTextareaCols',
    'CharTextareaRows',
    'TextareaCols',
    'TextareaRows',
    'LongtextDoubleTextarea');
$forms['Features']['Page_titles'] = array(
    'TitleDefault',
    'TitleTable',
    'TitleDatabase',
    'TitleServer');
$forms['Features']['Warnings'] = array(
    'ServerLibraryDifference_DisableWarning',
    'PmaNoRelation_DisableWarning',
    'SuhosinDisableWarning',
    'ReservedWordDisableWarning');
// settings from this form are treated specially,
// see prefs_forms.php and user_preferences.lib.php
$forms['Features']['Developer'] = array(
    'Error_Handler/display',
    'DBG/sql');
$forms['Sql_queries']['Sql_queries'] = array(
    'ShowSQL',
    'Confirm',
    'QueryHistoryMax',
    'IgnoreMultiSubmitErrors',
    'MaxCharactersInDisplayedSQL',
    'EditInWindow',
    //'QueryWindowWidth', // overridden in theme
    //'QueryWindowHeight',
    'QueryWindowDefTab',
    'RetainQueryBox',
    'CodemirrorEnable');
$forms['Sql_queries']['Sql_box'] = array(
    'SQLQuery/Edit',
    'SQLQuery/Explain',
    'SQLQuery/ShowAsPHP',
    'SQLQuery/Refresh');
$forms['Navi_panel']['Navi_panel'] = array(
    'NavigationDisplayLogo',
    'NavigationLogoLink',
    'NavigationLogoLinkWindow',
    'NavigationTreePointerEnable',
    'FirstLevelNavigationItems',
    'MaxNavigationItems',
    'NavigationTreeEnableGrouping',
    'NavigationTreeDisableDatabaseExpansion',
    'NavigationTreeDisplayItemFilterMinimum');
$forms['Navi_panel']['Navi_databases'] = array(
    'NavigationTreeDisplayDbFilterMinimum',
    'NavigationTreeDbSeparator');
$forms['Navi_panel']['Navi_tables'] = array(
    'NavigationTreeDefaultTabTable',
    'NavigationTreeTableSeparator',
    'NavigationTreeTableLevel',
);
$forms['Main_panel']['Startup'] = array(
    'ShowCreateDb',
    'ShowStats',
    'ShowServerInfo');
$forms['Main_panel']['DbStructure'] = array(
    'ShowDbStructureCreation',
    'ShowDbStructureLastUpdate',
    'ShowDbStructureLastCheck');
$forms['Main_panel']['TableStructure'] = array(
    'HideStructureActions');
$forms['Main_panel']['Browse'] = array(
    'TableNavigationLinksMode',
    'ActionLinksMode',
    'ShowAll',
    'MaxRows',
    'Order',
    'DisplayBinaryAsHex',
    'BrowsePointerEnable',
    'BrowseMarkerEnable',
    'GridEditing',
    'SaveCellsAtOnce',
    'ShowDisplayDirection',
    'RepeatCells',
    'LimitChars',
    'RowActionLinks',
    'DefaultDisplay',
    'RememberSorting');
$forms['Main_panel']['Edit'] = array(
    'ProtectBinary',
    'ShowFunctionFields',
    'ShowFieldTypesInDataEditView',
    'InsertRows',
    'ForeignKeyDropdownOrder',
    'ForeignKeyMaxLimit');
$forms['Main_panel']['Tabs'] = array(
    'TabsMode',
    'DefaultTabServer',
    'DefaultTabDatabase',
    'DefaultTabTable');
$forms['Main_panel']['DisplayRelationalSchema'] = array(
    'PDFDefaultPageSize');

$forms['Import']['Import_defaults'] = array(
    'Import/format',
    'Import/charset',
    'Import/allow_interrupt',
    'Import/skip_queries');
$forms['Import']['Sql'] = array(
    'Import/sql_compatibility',
    'Import/sql_no_auto_value_on_zero');
$forms['Import']['Csv'] = array(
    ':group:' . __('CSV'),
        'Import/csv_replace',
        'Import/csv_ignore',
        'Import/csv_terminated',
        'Import/csv_enclosed',
        'Import/csv_escaped',
        'Import/csv_col_names',
        ':group:end',
    ':group:' . __('CSV using LOAD DATA'),
        'Import/ldi_replace',
        'Import/ldi_ignore',
        'Import/ldi_terminated',
        'Import/ldi_enclosed',
        'Import/ldi_escaped',
        'Import/ldi_local_option');
$forms['Import']['Open_Document'] = array(
    ':group:' . __('OpenDocument Spreadsheet'),
        'Import/ods_col_names',
        'Import/ods_empty_rows',
        'Import/ods_recognize_percentages',
        'Import/ods_recognize_currency');
$forms['Export']['Export_defaults'] = array(
    'Export/method',
    ':group:' . __('Quick'),
        'Export/quick_export_onserver',
        'Export/quick_export_onserver_overwrite',
        ':group:end',
    ':group:' . __('Custom'),
        'Export/format',
        'Export/compression',
        'Export/charset',
        'Export/asfile' => ':group',
            'Export/onserver',
            'Export/onserver_overwrite',
            ':group:end',
        'Export/file_template_table',
        'Export/file_template_database',
        'Export/file_template_server');
$forms['Export']['Sql'] = array(
    'Export/sql_include_comments' => ':group',
        'Export/sql_dates',
        'Export/sql_relation',
        'Export/sql_mime',
        ':group:end',
    'Export/sql_use_transaction',
    'Export/sql_disable_fk',
    'Export/sql_views_as_tables',
    'Export/sql_compatibility',
    ':group:' . __('Database export options'),
        'Export/sql_drop_database',
        'Export/sql_structure_or_data',
        ':group:end',
    ':group:' . __('Structure'),
        'Export/sql_drop_table',
        'Export/sql_create_table',
        'Export/sql_create_view',
        'Export/sql_procedure_function',
        'Export/sql_create_trigger',
        'Export/sql_create_table_statements' => ':group',
            'Export/sql_if_not_exists',
            'Export/sql_auto_increment',
            ':group:end',
        'Export/sql_backquotes',
        ':group:end',
    ':group:' . __('Data'),
        'Export/sql_delayed',
        'Export/sql_ignore',
        'Export/sql_type',
        'Export/sql_insert_syntax',
        'Export/sql_max_query_size',
        'Export/sql_hex_for_binary',
        'Export/sql_utc_time');
$forms['Export']['CodeGen'] = array(
    'Export/codegen_format');
$forms['Export']['Csv'] = array(
    ':group:' . __('CSV'),
        'Export/csv_separator',
        'Export/csv_enclosed',
        'Export/csv_escaped',
        'Export/csv_terminated',
        'Export/csv_null',
        'Export/csv_removeCRLF',
        'Export/csv_columns',
        ':group:end',
    ':group:' . __('CSV for MS Excel'),
        'Export/excel_null',
        'Export/excel_removeCRLF',
        'Export/excel_columns',
        'Export/excel_edition');
$forms['Export']['Latex'] = array(
    'Export/latex_caption',
    'Export/latex_structure_or_data',
    ':group:' . __('Structure'),
        'Export/latex_structure_caption',
        'Export/latex_structure_continued_caption',
        'Export/latex_structure_label',
        'Export/latex_relation',
        'Export/latex_comments',
        'Export/latex_mime',
        ':group:end',
    ':group:' . __('Data'),
        'Export/latex_columns',
        'Export/latex_data_caption',
        'Export/latex_data_continued_caption',
        'Export/latex_data_label',
        'Export/latex_null');
$forms['Export']['Microsoft_Office'] = array(
    ':group:' . __('Microsoft Word 2000'),
        'Export/htmlword_structure_or_data',
        'Export/htmlword_null',
        'Export/htmlword_columns');
$forms['Export']['Open_Document'] = array(
    ':group:' . __('OpenDocument Spreadsheet'),
        'Export/ods_columns',
        'Export/ods_null',
        ':group:end',
    ':group:' . __('OpenDocument Text'),
        'Export/odt_structure_or_data',
        ':group:' . __('Structure'),
            'Export/odt_relation',
            'Export/odt_comments',
            'Export/odt_mime',
            ':group:end',
        ':group:' . __('Data'),
            'Export/odt_columns',
            'Export/odt_null');
$forms['Export']['Texy'] = array(
    'Export/texytext_structure_or_data',
    ':group:' . __('Data'),
        'Export/texytext_null',
        'Export/texytext_columns');
?>
