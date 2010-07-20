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
    'ReplaceHelpImg',
    'SkipLockedTables',
    'MaxDbList',
    'MaxTableList');
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
    'SQLQuery/Validate',
    'SQLQuery/Refresh');
$forms['Sql_queries']['Sql_validator'] = array('SQLValidator' => array(
    'use',
    'username',
    'password'));
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
// pmadb is unavailable when these settings are used
/*$forms['Left_frame']['Left_servers'] = array(
    'LeftDisplayServers',
    'DisplayServersList');*/
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
    ':group:' . __('CSV using LOAD DATA'),
    'Import/ldi_replace',
    'Import/ldi_ignore',
    'Import/ldi_terminated',
    'Import/ldi_enclosed',
    'Import/ldi_escaped',
    'Import/ldi_local_option');
$forms['Import']['Microsoft_Office'] = array(
    ':group:' . __('Excel 97-2003 XLS Workbook'),
    'Import/xls_col_names',
    ':group:' . __('Excel 2007 XLSX Workbook'),
    'Import/xlsx_col_names');
$forms['Import']['Open_Document'] = array(
    ':group:' . __('Open Document Spreadsheet'),
    'Import/ods_col_names',
    'Import/ods_empty_rows',
    'Import/ods_recognize_percentages',
    'Import/ods_recognize_currency');
$forms['Export']['Export_defaults'] = array(
    'Export/format',
    'Export/compression',
    'Export/charset',
    'Export/asfile',
    'Export/remember_file_template',
    'Export/file_template_table',
    'Export/file_template_database',
    'Export/file_template_server');
$forms['Export']['Sql'] = array(
    'Export/sql_include_comments',
    'Export/sql_use_transaction',
    'Export/sql_disable_fk',
    'Export/sql_compatibility',
    ':group:' . __('Database export options'),
    'Export/sql_drop_database',
    'Export/sql_structure' => ':group',
    'Export/sql_drop_table',
    'Export/sql_if_not_exists',
    'Export/sql_auto_increment',
    'Export/sql_backquotes',
    'Export/sql_procedure_function',
    ':group:' . __('Add into comments'),
    'Export/sql_dates',
    'Export/sql_relation',
    'Export/sql_mime',
    'Export/sql_data' => ':group',
    'Export/sql_columns',
    'Export/sql_extended',
    'Export/sql_max_query_size',
    'Export/sql_delayed',
    'Export/sql_ignore',
    'Export/sql_hex_for_blob',
    'Export/sql_utc_time',
    'Export/sql_type');
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
    ':group:' . __('CSV for MS Excel'),
    'Export/excel_null',
    'Export/excel_removeCRLF',
    'Export/excel_columns',
    'Export/excel_edition');
$forms['Export']['Latex'] = array(
    'Export/latex_caption',
    'Export/latex_structure' => ':group',
    'Export/latex_structure_caption',
    'Export/latex_structure_continued_caption',
    'Export/latex_structure_label',
    'Export/latex_relation',
    'Export/latex_comments',
    'Export/latex_mime',
    'Export/latex_data' => ':group',
    'Export/latex_columns',
    'Export/latex_data_caption',
    'Export/latex_data_continued_caption',
    'Export/latex_data_label',
    'Export/latex_null');
$forms['Export']['Microsoft_Office'] = array(
    ':group:' . __('Excel 97-2003 XLS Workbook'),
    'Export/xls_null',
    'Export/xls_columns',
    ':group:' . __('Excel 2007 XLSX Workbook'),
    'Export/xlsx_null',
    'Export/xlsx_columns',
    ':group:' . __('Microsoft Word 2000'),
    'Export/htmlword_structure',
    'Export/htmlword_data',
    'Export/htmlword_null',
    'Export/htmlword_columns');
$forms['Export']['Open_Document'] = array(
    ':group:' . __('Open Document Spreadsheet'),
    'Export/ods_null',
    'Export/ods_columns',
    ':group:' . __('Open Document Text'),
    'Export/odt_structure' => ':group',
    'Export/odt_relation',
    'Export/odt_comments',
    'Export/odt_mime',
    'Export/odt_data' => ':group',
    'Export/odt_columns',
    'Export/odt_null');
$forms['Export']['Texy'] = array(
    'Export/texytext_structure',
    'Export/texytext_data' => ':group',
    'Export/texytext_null',
    'Export/texytext_columns');
?>