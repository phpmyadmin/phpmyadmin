<?php
/**
 * List of avaible forms, each form is described as an array of fields to display.
 * Fields MUST have their counterparts in the $cfg array.
 *
 * To define form field, use the notatnion below:
 * $forms['Form group']['Form name'] = array('Servers/1/host');
 *
 * You can assign default values set by special button ("set value: ..."), eg.:
 * $forms['Server']['pmadb form'] = array('Servers/1/pmadb' => 'phpmyadmin');
 *
 * @package phpMyAdmin
 */

$forms = array();
$forms['Features']['General'] = array(
    'NaturalOrder',
    'InitialSlidersState',
    'ErrorIconic',
    'ReplaceHelpImg');
$forms['Features']['Text_fields'] = array(
    'CharEditing',
    'CharTextareaCols',
    'CharTextareaRows',
    'TextareaCols',
    'TextareaRows',
    'LongtextDoubleTextarea');
$forms['Sql_queries']['Sql_queries'] = array(
    'ShowSQL',
    'Confirm',
    'IgnoreMultiSubmitErrors',
    'VerboseMultiSubmit',
    'MaxCharactersInDisplayedSQL',
    'EditInWindow',
    //'QueryWindowWidth', // overridden in theme
    //'QueryWindowHeight',
    'QueryWindowDefTab');
$forms['Sql_queries']['Sql_box'] = array(
    'SQLQuery/Edit',
    'SQLQuery/Explain',
    'SQLQuery/ShowAsPHP',
    'SQLQuery/Validate',// [false or no override]
    'SQLQuery/Refresh');
$forms['Features']['Page_titles'] = array(
    'TitleDefault',
    'TitleTable',
    'TitleDatabase',
    'TitleServer');
$forms['Left_frame']['Left_frame'] = array(
    'LeftFrameLight',
    'LeftDisplayLogo',
    'LeftLogoLink',
    'LeftLogoLinkWindow',
    'LeftPointerEnable');
$forms['Left_frame']['Left_servers'] = array(
    'LeftDisplayServers',
    'DisplayServersList');
$forms['Left_frame']['Left_databases'] = array(
    'DisplayDatabasesList',
    'LeftFrameDBTree',
    'LeftFrameDBSeparator',
    'ShowTooltipAliasDB');
$forms['Left_frame']['Left_tables'] = array(
    'LeftDefaultTabTable',
    'LeftFrameTableSeparator',
    'LeftFrameTableLevel',
    'ShowTooltip',
    'ShowTooltipAliasTB');
$forms['Main_frame']['Startup'] = array(
    'MainPageIconic',
    'SuggestDBName');
$forms['Main_frame']['Browse'] = array(
    'NavigationBarIconic',
    'ShowAll',
    'MaxRows',
    'Order',
    'DisplayBinaryAsHex',
    'BrowsePointerEnable',
    'BrowseMarkerEnable',
    'RepeatCells',
    'LimitChars',
    'ModifyDeleteAtLeft',
    'ModifyDeleteAtRight',
    'DefaultDisplay');
$forms['Main_frame']['Edit'] = array(
    'ProtectBinary',
    'ShowFunctionFields',
    'ShowFieldTypesInDataEditView',
    'InsertRows',
    'ForeignKeyDropdownOrder',// [s, ? custom text value]
    'ForeignKeyMaxLimit',
    'CtrlArrowsMoving',
    'DefaultPropDisplay');
$forms['Main_frame']['Tabs'] = array(
    'LightTabs',
    'PropertiesIconic',
    'DefaultTabServer',
    'DefaultTabDatabase',
    'DefaultTabTable');
$forms['Import']['Import_defaults'] = array(
    'Import/format',
    'Import/allow_interrupt',
    'Import/skip_queries');
$forms['Import']['Sql'] = array(
    'Import/sql_compatibility',
    'Import/sql_no_auto_value_on_zero');
$forms['Import']['Csv'] = array(
    'Import/csv_replace',
    'Import/csv_ignore',
    'Import/csv_terminated',
    'Import/csv_enclosed',
    'Import/csv_escaped',
    'Import/csv_col_names');
$forms['Import']['Ldi'] = array(
    'Import/ldi_replace',
    'Import/ldi_ignore',
    'Import/ldi_terminated',
    'Import/ldi_enclosed',
    'Import/ldi_escaped',
    'Import/ldi_local_option');
$forms['Import']['Excel'] = array(
    'Import/xls_col_names',
    'Import/xlsx_col_names');
$forms['Import']['Ods'] = array(
    'Import/ods_col_names',
    'Import/ods_empty_rows',
    'Import/ods_recognize_percentages',
    'Import/ods_recognize_currency');
$forms['Export']['Export_defaults'] = array(
    'Export/format',
    'Export/compression',
    'Export/remember_file_template',
    'Export/file_template_table',
    'Export/file_template_database',
    'Export/file_template_server');
?>