<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package phpMyAdmin
 */

$forms = array();
$forms['Features']['General'] = array(
    'DefaultLang',
    'DefaultConnectionCollation',
    'ThemeDefault',
    'NaturalOrder',
    'InitialSlidersState',
    'ErrorIconic',
    'ReplaceHelpImg');
$forms['Features']['Text_fields'] = array(
    'CharEditing',
    'CharTextareaCols',
    'CharTextareaRows',
    'TextareaCols',// [s-]
    'TextareaRows',// [s-]
    'LongtextDoubleTextarea');// [s-]
$forms['Sql_queries']['Sql_queries'] = array(
    'ShowSQL',
    'Confirm',
    'IgnoreMultiSubmitErrors',
    'VerboseMultiSubmit',
    'MaxCharactersInDisplayedSQL',// [s-]
    'SQLQuery/Edit',// [s-]
    'SQLQuery/Explain',// [s-]
    'SQLQuery/ShowAsPHP',// [s-]
    'SQLQuery/Validate',// [false or no override]
    'SQLQuery/Refresh',// [s-]
    'EditInWindow',// [s-]
    'QueryWindowWidth',// [s-]
    'QueryWindowHeight',// [s-]
    'QueryWindowDefTab');// [s-]
$forms['Sql_queries']['Sql_box'] = array(
    'Edit',
    'Explain',
    'ShowAsPHP',
    'Validate',// (false or no override)
    'Refresh');
$forms['Features']['Page_titles'] = array(
    'TitleTable',// [s-]
    'TitleDatabase',// [s-]
    'TitleServer',// [s-]
    'TitleDefault');// [s-]
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
    'LeftFrameTableLevel',
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
    'RepeatCells',// [s-]
    'LimitChars',// [s-]
    'ModifyDeleteAtLeft',// [s-]
    'ModifyDeleteAtRight',// [s-]
    'DefaultDisplay');// [s-]
$forms['Main_frame']['Edit'] = array(
    'ProtectBinary',
    'ShowFunctionFields',
    'ShowFieldTypesInDataEditView',// [s-]
    'InsertRows',
    'ForeignKeyDropdownOrder',// [s, ? custom text value]
    'ForeignKeyMaxLimit',
    'CtrlArrowsMoving',
    'DefaultPropDisplay');// [s-]
$forms['Main_frame']['Tabs'] = array(
    'LightTabs',
    'PropertiesIconic',
    'DefaultTabServer',
    'DefaultTabDatabase',
    'DefaultTabTable');
$forms['Import']['Import_defaults'] = array();
$forms['Export']['Export_defaults'] = array();
?>