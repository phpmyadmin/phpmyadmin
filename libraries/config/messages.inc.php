<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Messages for phpMyAdmin.
 *
 * This file defines variables in a special format suited for the
 * configuration subsystem, with $strConfig as a prefix, _desc or _name
 * as a suffix, and the directive name in between.
 *
 * @package PhpMyAdmin
 */

if (!function_exists('__')) {
    exit();
}

$strConfigAllowArbitraryServer_desc = __(
    'If enabled, user can enter any MySQL server in login form for cookie auth.'
);
$strConfigAllowArbitraryServer_name = __('Allow login to any MySQL server');
$strConfigArbitraryServerRegexp_desc = __(
    'Restricts the MySQL servers the user can enter when a login to an arbitrary '
    . 'MySQL server is enabled by matching the IP or hostname of the MySQL server ' .
    'to the given regular expression.'
);
$strConfigArbitraryServerRegexp_name = __('Restrict login to MySQL server');
$strConfigAllowThirdPartyFraming_desc = __(
    'Enabling this allows a page located on a different domain to call phpMyAdmin '
    . 'inside a frame, and is a potential [strong]security hole[/strong] allowing '
    . 'cross-frame scripting (XSS) attacks.'
);
$strConfigAllowThirdPartyFraming_name = __('Allow third party framing');
$strConfigAllowUserDropDatabase_name
    = __('Show "Drop database" link to normal users');
$strConfigblowfish_secret_desc = __(
    'Secret passphrase used for encrypting cookies in [kbd]cookie[/kbd] '
    . 'authentication.'
);
$strConfigblowfish_secret_name = __('Blowfish secret');
$strConfigBrowseMarkerEnable_desc = __('Highlight selected rows.');
$strConfigBrowseMarkerEnable_name = __('Row marker');
$strConfigBrowsePointerEnable_desc = __(
    'Highlight row pointed by the mouse cursor.'
);
$strConfigBrowsePointerEnable_name = __('Highlight pointer');
$strConfigBZipDump_desc = __(
    'Enable bzip2 compression for'
    . ' import operations.'
);
$strConfigBZipDump_name = __('Bzip2');
$strConfigCharEditing_desc = __(
    'Defines which type of editing controls should be used for CHAR and VARCHAR '
    . 'columns; [kbd]input[/kbd] - allows limiting of input length, '
    . '[kbd]textarea[/kbd] - allows newlines in columns.'
);
$strConfigCharEditing_name = __('CHAR columns editing');
$strConfigCodemirrorEnable_desc = __(
    'Use user-friendly editor for editing SQL queries '
    . '(CodeMirror) with syntax highlighting and '
    . 'line numbers.'
);
$strConfigCodemirrorEnable_name = __('Enable CodeMirror');
$strConfigLintEnable_desc = __(
    'Find any errors in the query before executing it.'
    . ' Requires CodeMirror to be enabled.'
);
$strConfigLintEnable_name = __('Enable linter');
$strConfigMinSizeForInputField_desc = __(
    'Defines the minimum size for input fields generated for CHAR and VARCHAR '
    . 'columns.'
);
$strConfigMinSizeForInputField_name = __('Minimum size for input field');
$strConfigMaxSizeForInputField_desc = __(
    'Defines the maximum size for input fields generated for CHAR and VARCHAR '
    . 'columns.'
);
$strConfigMaxSizeForInputField_name = __('Maximum size for input field');
$strConfigCharTextareaCols_desc = __(
    'Number of columns for CHAR/VARCHAR textareas.'
);
$strConfigCharTextareaCols_name = __('CHAR textarea columns');
$strConfigCharTextareaRows_desc = __('Number of rows for CHAR/VARCHAR textareas.');
$strConfigCharTextareaRows_name = __('CHAR textarea rows');
$strConfigCheckConfigurationPermissions_name = __('Check config file permissions');
$strConfigCompressOnFly_desc = __(
    'Compress gzip exports on the fly without the need for much memory; if '
    . 'you encounter problems with created gzip files disable this feature.'
);
$strConfigCompressOnFly_name = __('Compress on the fly');
$strConfigConfigurationFile = __('Configuration file');
$strConfigConfirm_desc = __(
    'Whether a warning ("Are your really sure…") should be displayed '
    . 'when you\'re about to lose data.'
);
$strConfigConfirm_name = __('Confirm DROP queries');
$strConfigDBG_sql_desc = __(
    'Log SQL queries and their execution time, to be displayed in the console'
);
$strConfigDBG_sql_name = __('Debug SQL');
$strConfigDefaultTabDatabase_desc
    = __('Tab that is displayed when entering a database.');
$strConfigDefaultTabDatabase_name = __('Default database tab');
$strConfigDefaultTabServer_desc = __(
    'Tab that is displayed when entering a server.'
);
$strConfigDefaultTabServer_name = __('Default server tab');
$strConfigDefaultTabTable_desc = __('Tab that is displayed when entering a table.');
$strConfigDefaultTabTable_name = __('Default table tab');
$strConfigEnableAutocompleteForTablesAndColumns_desc = __(
    'Autocomplete of the table and column names in the SQL queries.'
);
$strConfigEnableAutocompleteForTablesAndColumns_name = __(
    'Enable autocomplete for table and column names'
);
$strConfigHideStructureActions_desc
    = __('Whether the table structure actions should be hidden.');
$strConfigShowColumnComments_name = __('Show column comments');
$strConfigShowColumnComments_desc
    = __('Whether column comments should be shown in table structure view');
$strConfigHideStructureActions_name = __('Hide table structure actions');
$strConfigDisplayServersList_desc
    = __('Show server listing as a list instead of a drop down.');
$strConfigDisplayServersList_name = __('Display servers as a list');
$strConfigDisableMultiTableMaintenance_desc = __(
    'Disable the table maintenance mass operations, like optimizing or repairing '
    . 'the selected tables of a database.'
);
$strConfigDisableMultiTableMaintenance_name = __('Disable multi table maintenance');
$strConfigExecTimeLimit_desc = __(
    'Set the number of seconds a script is allowed to run ([kbd]0[/kbd] for no '
    . 'limit).'
);
$strConfigExecTimeLimit_name = __('Maximum execution time');
$strConfigExport_lock_tables_name = sprintf(
    __('Use %s statement'), '<code>LOCK TABLES</code>'
);
$strConfigExport_asfile_name = __('Save as file');
$strConfigExport_charset_name = __('Character set of the file');
$strConfigExport_codegen_format_name = __('Format');
$strConfigExport_compression_name = __('Compression');
$strConfigExport_csv_columns_name = __('Put columns names in the first row');
$strConfigExport_csv_enclosed_name = __('Columns enclosed with');
$strConfigExport_csv_escaped_name = __('Columns escaped with');
$strConfigExport_csv_null_name = __('Replace NULL with');
$strConfigExport_csv_removeCRLF_name = __('Remove CRLF characters within columns');
$strConfigExport_csv_separator_name = __('Columns terminated with');
$strConfigExport_csv_terminated_name = __('Lines terminated with');
$strConfigExport_excel_columns_name = __('Put columns names in the first row');
$strConfigExport_excel_edition_name = __('Excel edition');
$strConfigExport_excel_null_name = __('Replace NULL with');
$strConfigExport_excel_removeCRLF_name = __('Remove CRLF characters within columns');
$strConfigExport_file_template_database_name = __('Database name template');
$strConfigExport_file_template_server_name = __('Server name template');
$strConfigExport_file_template_table_name = __('Table name template');
$strConfigExport_format_name = __('Format');
$strConfigExport_htmlword_columns_name = __('Put columns names in the first row');
$strConfigExport_htmlword_null_name = __('Replace NULL with');
$strConfigExport_htmlword_structure_or_data_name = __('Dump table');
$strConfigExport_latex_caption_name = __('Include table caption');
$strConfigExport_latex_columns_name = __('Put columns names in the first row');
$strConfigExport_latex_comments_name = __('Comments');
$strConfigExport_latex_data_caption_name = __('Table caption');
$strConfigExport_latex_data_continued_caption_name = __('Continued table caption');
$strConfigExport_latex_data_label_name = __('Label key');
$strConfigExport_latex_mime_name = __('MIME type');
$strConfigExport_latex_null_name = __('Replace NULL with');
$strConfigExport_latex_relation_name = __('Relationships');
$strConfigExport_latex_structure_caption_name = __('Table caption');
$strConfigExport_latex_structure_continued_caption_name
    = __('Continued table caption');
$strConfigExport_latex_structure_label_name = __('Label key');
$strConfigExport_latex_structure_or_data_name = __('Dump table');
$strConfigExport_method_name = __('Export method');
$strConfigExport_ods_columns_name = __('Put columns names in the first row');
$strConfigExport_ods_null_name = __('Replace NULL with');
$strConfigExport_odt_columns_name = __('Put columns names in the first row');
$strConfigExport_odt_comments_name = __('Comments');
$strConfigExport_odt_mime_name = __('MIME type');
$strConfigExport_odt_null_name = __('Replace NULL with');
$strConfigExport_odt_relation_name = __('Relationships');
$strConfigExport_odt_structure_or_data_name = __('Dump table');
$strConfigExport_onserver_name = __('Save on server');
$strConfigExport_onserver_overwrite_name = __('Overwrite existing file(s)');
$strConfigExport_as_separate_files_name = __('Export as separate files');
$strConfigExport_quick_export_onserver_name = __('Save on server');
$strConfigExport_quick_export_onserver_overwrite_name
    = __('Overwrite existing file(s)');
$strConfigExport_remember_file_template_name = __('Remember file name template');
$strConfigExport_sql_auto_increment_name = __('Add AUTO_INCREMENT value');
$strConfigExport_sql_backquotes_name
    = __('Enclose table and column names with backquotes');
$strConfigExport_sql_compatibility_name = __('SQL compatibility mode');
$strConfigExport_sql_dates_name = __('Creation/Update/Check dates');
$strConfigExport_sql_delayed_name = __('Use delayed inserts');
$strConfigExport_sql_disable_fk_name = __('Disable foreign key checks');
$strConfigExport_sql_views_as_tables_name = __('Export views as tables');
$strConfigExport_sql_metadata_name = __(
    'Export related metadata from phpMyAdmin configuration storage'
);
$strConfigExport_sql_create_database_name = sprintf(__('Add %s'), 'CREATE DATABASE / USE');
$strConfigExport_sql_drop_database_name = sprintf(__('Add %s'), 'DROP DATABASE');
$strConfigExport_sql_drop_table_name = sprintf(
    __('Add %s'), 'DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER'
);
$strConfigExport_sql_create_table_name = sprintf(__('Add %s'), 'CREATE TABLE');
$strConfigExport_sql_create_view_name = sprintf(__('Add %s'), 'CREATE VIEW');
$strConfigExport_sql_create_trigger_name
    = sprintf(__('Add %s'), 'CREATE TRIGGER');
$strConfigExport_sql_hex_for_binary_name = __('Use hexadecimal for BINARY & BLOB');
$strConfigExport_sql_if_not_exists_name = __(
    'Add IF NOT EXISTS (less efficient as indexes will be generated during'
    . ' table creation)'
);
$strConfigExport_sql_ignore_name = __('Use ignore inserts');
$strConfigExport_sql_include_comments_name = __('Comments');
$strConfigExport_sql_insert_syntax_name = __('Syntax to use when inserting data');
$strConfigExport_sql_max_query_size_name = __('Maximal length of created query');
$strConfigExport_sql_mime_name = __('MIME type');
$strConfigExport_sql_procedure_function_name
    = sprintf(__('Add %s'), 'CREATE PROCEDURE / FUNCTION / EVENT');
$strConfigExport_sql_relation_name = __('Relationships');
$strConfigExport_sql_structure_or_data_name = __('Dump table');
$strConfigExport_sql_type_name = __('Export type');
$strConfigExport_sql_use_transaction_name = __('Enclose export in a transaction');
$strConfigExport_sql_utc_time_name = __('Export time in UTC');
$strConfigExport_texytext_columns_name = __('Put columns names in the first row');
$strConfigExport_texytext_null_name = __('Replace NULL with');
$strConfigExport_texytext_structure_or_data_name = __('Dump table');
$strConfigForeignKeyDropdownOrder_desc = __(
    'Sort order for items in a foreign-key dropdown box; [kbd]content[/kbd] is '
    . 'the referenced data, [kbd]id[/kbd] is the key value.'
);
$strConfigForeignKeyDropdownOrder_name = __('Foreign key dropdown order');
$strConfigForeignKeyMaxLimit_desc
    = __('A dropdown will be used if fewer items are present.');
$strConfigForeignKeyMaxLimit_name = __('Foreign key limit');
$strConfigDefaultForeignKeyChecks_desc = __(
    'Default value for foreign key checks checkbox for some queries.'
);
$strConfigDefaultForeignKeyChecks_name = __('Foreign key checks');
$strConfigForm_Browse = __('Browse mode');
$strConfigForm_Browse_desc = __('Customize browse mode.');
$strConfigForm_CodeGen = 'CodeGen';
$strConfigForm_CodeGen_desc = __('Customize default options.');
$strConfigForm_Csv = __('CSV');
$strConfigForm_Csv_desc = __('Customize default options.');
$strConfigForm_Developer = __('Developer');
$strConfigForm_Developer_desc = __('Settings for phpMyAdmin developers.');
$strConfigForm_Edit = __('Edit mode');
$strConfigForm_Edit_desc = __('Customize edit mode.');
$strConfigForm_Export = __('Export');
$strConfigForm_Export_defaults = __('Export defaults');
$strConfigForm_Export_defaults_desc = __('Customize default export options.');
$strConfigForm_Features = __('Features');
$strConfigForm_General = __('General');
$strConfigForm_General_desc = __('Set some commonly used options.');
$strConfigForm_Import = __('Import');
$strConfigForm_Import_defaults = __('Import defaults');
$strConfigForm_Import_defaults_desc = __('Customize default common import options.');
$strConfigForm_Import_export = __('Import / export');
$strConfigForm_Import_export_desc
    = __('Set import and export directories and compression options.');
$strConfigForm_Latex = __('LaTeX');
$strConfigForm_Latex_desc = __('Customize default options.');
$strConfigForm_Navi_databases = __('Databases');
$strConfigForm_Navi_databases_desc = __('Databases display options.');
$strConfigForm_Navi_panel = __('Navigation panel');
$strConfigForm_Navi_panel_desc = __('Customize appearance of the navigation panel.');
$strConfigForm_Navi_tree = __('Navigation tree');
$strConfigForm_Navi_tree_desc = __('Customize the navigation tree.');
$strConfigForm_Navi_servers = __('Servers');
$strConfigForm_Navi_servers_desc = __('Servers display options.');
$strConfigForm_Navi_tables = __('Tables');
$strConfigForm_Navi_tables_desc = __('Tables display options.');
$strConfigForm_Main_panel = __('Main panel');
$strConfigForm_Microsoft_Office = __('Microsoft Office');
$strConfigForm_Microsoft_Office_desc = __('Customize default options.');
$strConfigForm_Open_Document = 'OpenDocument';
$strConfigForm_Open_Document_desc = __('Customize default options.');
$strConfigForm_Other_core_settings = __('Other core settings');
$strConfigForm_Other_core_settings_desc
    = __('Settings that didn\'t fit anywhere else.');
$strConfigForm_Page_titles = __('Page titles');
$strConfigForm_Page_titles_desc = __(
    'Specify browser\'s title bar text. Refer to '
    . '[doc@faq6-27]documentation[/doc] for magic strings that can be used '
    . 'to get special values.'
);
$strConfigForm_Security = __('Security');
$strConfigForm_Security_desc = __(
    'Please note that phpMyAdmin is just a user interface and its features do not '
    . 'limit MySQL.'
);
$strConfigForm_Server = __('Basic settings');
$strConfigForm_Server_auth = __('Authentication');
$strConfigForm_Server_auth_desc = __('Authentication settings.');
$strConfigForm_Server_config = __('Server configuration');
$strConfigForm_Server_config_desc = __(
    'Advanced server configuration, do not change these options unless you know '
    . 'what they are for.'
);
$strConfigForm_Server_desc = __('Enter server connection parameters.');
$strConfigForm_Server_pmadb = __('Configuration storage');
$strConfigForm_Server_pmadb_desc = __(
    'Configure phpMyAdmin configuration storage to gain access to additional '
    . 'features, see [doc@linked-tables]phpMyAdmin configuration storage[/doc] in '
    . 'documentation.'
);
$strConfigForm_Server_tracking = __('Changes tracking');
$strConfigForm_Server_tracking_desc = __(
    'Tracking of changes made in database. Requires the phpMyAdmin configuration '
    . 'storage.'
);
$strConfigFormset_Export = __('Customize export options');
$strConfigFormset_Features = __('Features');
$strConfigFormset_Import = __('Customize import defaults');
$strConfigFormset_Navi_panel = __('Customize navigation panel');
$strConfigFormset_Main_panel = __('Customize main panel');
$strConfigFormset_Sql_queries = __('SQL queries');
$strConfigForm_Sql = __('SQL');
$strConfigForm_Sql_box = __('SQL Query box');
$strConfigForm_Sql_box_desc = __('Customize links shown in SQL Query boxes.');
$strConfigForm_Sql_desc = __('Customize default options.');
$strConfigForm_Sql_queries = __('SQL queries');
$strConfigForm_Sql_queries_desc = __('SQL queries settings.');
$strConfigForm_Startup = __('Startup');
$strConfigForm_Startup_desc = __('Customize startup page.');
$strConfigForm_DbStructure = __('Database structure');
$strConfigForm_DbStructure_desc
    = __('Choose which details to show in the database structure (list of tables).');
$strConfigForm_TableStructure = __('Table structure');
$strConfigForm_TableStructure_desc
    = __('Settings for the table structure (list of columns).');
$strConfigForm_Tabs = __('Tabs');
$strConfigForm_Tabs_desc = __('Choose how you want tabs to work.');
$strConfigForm_DisplayRelationalSchema = __('Display relational schema');
$strConfigForm_DisplayRelationalSchema_desc = '';
$strConfigPDFDefaultPageSize_name = __('Paper size');
$strConfigPDFDefaultPageSize_desc = '';
$strConfigForm_Databases = __('Databases');
$strConfigForm_Text_fields = __('Text fields');
$strConfigForm_Text_fields_desc = __('Customize text input fields.');
$strConfigForm_Texy = __('Texy! text');
$strConfigForm_Texy_desc = __('Customize default options');
$strConfigForm_Warnings = __('Warnings');
$strConfigForm_Warnings_desc
    = __('Disable some of the warnings shown by phpMyAdmin.');
$strConfigGZipDump_desc = __(
    'Enable gzip compression for import '
    . 'and export operations.'
);
$strConfigGZipDump_name = __('GZip');
$strConfigIconvExtraParams_name = __('Extra parameters for iconv');
$strConfigIgnoreMultiSubmitErrors_desc = __(
    'If enabled, phpMyAdmin continues computing multiple-statement queries even if '
    . 'one of the queries failed.'
);
$strConfigIgnoreMultiSubmitErrors_name = __('Ignore multiple statement errors');
$strConfigImport_allow_interrupt_desc = __(
    'Allow interrupt of import in case script detects it is close to time limit. '
    . 'This might be a good way to import large files, however it can break '
    . 'transactions.'
);
$strConfigImport_allow_interrupt_name = __('Partial import: allow interrupt');
$strConfigImport_charset_name = __('Character set of the file');
$strConfigImport_csv_col_names_name = __('Lines terminated with');
$strConfigImport_csv_enclosed_name = __('Columns enclosed with');
$strConfigImport_csv_escaped_name = __('Columns escaped with');
$strConfigImport_csv_ignore_name = __('Do not abort on INSERT error');
$strConfigImport_csv_replace_name = __('Add ON DUPLICATE KEY UPDATE');
$strConfigImport_csv_replace_desc = __(
    'Update data when duplicate keys found on import'
);
$strConfigImport_csv_terminated_name = __('Columns terminated with');
$strConfigImport_format_desc = __(
    'Default format; be aware that this list depends on location (database, table) '
    . 'and only SQL is always available.'
);
$strConfigImport_format_name = __('Format of imported file');
$strConfigImport_ldi_enclosed_name = __('Columns enclosed with');
$strConfigImport_ldi_escaped_name = __('Columns escaped with');
$strConfigImport_ldi_ignore_name = __('Do not abort on INSERT error');
$strConfigImport_ldi_local_option_name = __('Use LOCAL keyword');
$strConfigImport_ldi_replace_name = __('Add ON DUPLICATE KEY UPDATE');
$strConfigImport_ldi_replace_desc = __(
    'Update data when duplicate keys found on import'
);
$strConfigImport_ldi_terminated_name = __('Columns terminated with');
$strConfigImport_ods_col_names_name = __('Column names in first row');
$strConfigImport_ods_empty_rows_name = __('Do not import empty rows');
$strConfigImport_ods_recognize_currency_name
    = __('Import currencies ($5.00 to 5.00)');
$strConfigImport_ods_recognize_percentages_name
    = __('Import percentages as proper decimals (12.00% to .12)');
$strConfigImport_skip_queries_desc = __('Number of queries to skip from start.');
$strConfigImport_skip_queries_name = __('Partial import: skip queries');
$strConfigImport_sql_compatibility_name = __('SQL compatibility mode');
$strConfigImport_sql_no_auto_value_on_zero_name
    = __('Do not use AUTO_INCREMENT for zero values');
$strConfigImport_sql_read_as_multibytes_name = __('Read as multibytes');
$strConfigInitialSlidersState_name = __('Initial state for sliders');
$strConfigInsertRows_desc = __('How many rows can be inserted at one time.');
$strConfigInsertRows_name = __('Number of inserted rows');
$strConfigLimitChars_desc = __(
    'Maximum number of characters shown in any non-numeric column on browse view.'
);
$strConfigLimitChars_name = __('Limit column characters');
$strConfigLoginCookieDeleteAll_desc = __(
    'If TRUE, logout deletes cookies for all servers; when set to FALSE, logout '
    . 'only occurs for the current server. Setting this to FALSE makes it easy to '
    . 'forget to log out from other servers when connected to multiple servers.'
);
$strConfigLoginCookieDeleteAll_name = __('Delete all cookies on logout');
$strConfigLoginCookieRecall_desc = __(
    'Define whether the previous login should be recalled or not in '
    . '[kbd]cookie[/kbd] authentication mode.'
);
$strConfigLoginCookieRecall_name = __('Recall user name');
$strConfigLoginCookieStore_desc = __(
    'Defines how long (in seconds) a login cookie should be stored in browser. '
    . 'The default of 0 means that it will be kept for the existing session only, '
    . 'and will be deleted as soon as you close the browser window. This is '
    . 'recommended for non-trusted environments.'
);
$strConfigLoginCookieStore_name = __('Login cookie store');
$strConfigLoginCookieValidity_desc
    = __('Define how long (in seconds) a login cookie is valid.');
$strConfigLoginCookieValidity_name = __('Login cookie validity');
$strConfigLongtextDoubleTextarea_desc
    = __('Double size of textarea for LONGTEXT columns.');
$strConfigLongtextDoubleTextarea_name = __('Bigger textarea for LONGTEXT');
$strConfigMaxCharactersInDisplayedSQL_desc
    = __('Maximum number of characters used when a SQL query is displayed.');
$strConfigMaxCharactersInDisplayedSQL_name = __('Maximum displayed SQL length');
$strConfigMaxDbList_cmt = __('Users cannot set a higher value');
$strConfigMaxDbList_desc
    = __('Maximum number of databases displayed in database list.');
$strConfigMaxDbList_name = __('Maximum databases');
$strConfigFirstLevelNavigationItems_desc = __(
    'The number of items that can be displayed on each page on the first level'
    . ' of the navigation tree.'
);
$strConfigFirstLevelNavigationItems_name = __('Maximum items on first level');
$strConfigMaxNavigationItems_desc = __(
    'The number of items that can be displayed on each page of the navigation tree.'
);
$strConfigMaxNavigationItems_name = __('Maximum items in branch');
$strConfigMaxRows_desc = __(
    'Number of rows displayed when browsing a result set. If the result set '
    . 'contains more rows, "Previous" and "Next" links will be '
    . 'shown.'
);
$strConfigMaxRows_name = __('Maximum number of rows to display');
$strConfigMaxTableList_cmt = __('Users cannot set a higher value');
$strConfigMaxTableList_desc = __(
    'Maximum number of tables displayed in table list.'
);
$strConfigMaxTableList_name = __('Maximum tables');
$strConfigMemoryLimit_desc = __(
    'The number of bytes a script is allowed to allocate, eg. [kbd]32M[/kbd] '
    . '([kbd]-1[/kbd] for no limit and [kbd]0[/kbd] for no change).'
);
$strConfigMemoryLimit_name = __('Memory limit');
$strConfigShowDatabasesNavigationAsTree_desc = __(
    'In the navigation panel, replaces the database tree with a selector'
);
$strConfigShowDatabasesNavigationAsTree_name = __(
    'Show databases navigation as tree'
);
$strConfigNavigationLinkWithMainPanel_desc = __(
    'Link with main panel by highlighting the current database or table.'
);
$strConfigNavigationLinkWithMainPanel_name = __('Link with main panel');
$strConfigNavigationDisplayLogo_desc = __('Show logo in navigation panel.');
$strConfigNavigationDisplayLogo_name = __('Display logo');
$strConfigNavigationLogoLink_desc
    = __('URL where logo in the navigation panel will point to.');
$strConfigNavigationLogoLink_name = __('Logo link URL');
$strConfigNavigationLogoLinkWindow_desc = __(
    'Open the linked page in the main window ([kbd]main[/kbd]) or in a new one '
    . '([kbd]new[/kbd]).'
);
$strConfigNavigationLogoLinkWindow_name = __('Logo link target');
$strConfigNavigationDisplayServers_desc
    = __('Display server choice at the top of the navigation panel.');
$strConfigNavigationDisplayServers_name = __('Display servers selection');
$strConfigNavigationTreeDefaultTabTable_name = __('Target for quick access icon');
$strConfigNavigationTreeDefaultTabTable2_name = __(
    'Target for second quick access icon'
);
$strConfigNavigationTreeDisplayItemFilterMinimum_desc = __(
    'Defines the minimum number of items (tables, views, routines and events) to '
    . 'display a filter box.'
);
$strConfigNavigationTreeDisplayItemFilterMinimum_name
    = __('Minimum number of items to display the filter box');
$strConfigNavigationTreeDisplayDbFilterMinimum_name
    = __('Minimum number of databases to display the database filter box');
$strConfigNavigationTreeEnableGrouping_desc = __(
    'Group items in the navigation tree (determined by the separator defined in ' .
    'the Databases and Tables tabs above).'
);
$strConfigNavigationTreeEnableGrouping_name = __('Group items in the tree');
$strConfigNavigationTreeDbSeparator_desc
    = __('String that separates databases into different tree levels.');
$strConfigNavigationTreeDbSeparator_name = __('Database tree separator');
$strConfigNavigationTreeTableSeparator_desc
    = __('String that separates tables into different tree levels.');
$strConfigNavigationTreeTableSeparator_name = __('Table tree separator');
$strConfigNavigationTreeTableLevel_name = __('Maximum table tree depth');
$strConfigNavigationTreePointerEnable_desc
    = __('Highlight server under the mouse cursor.');
$strConfigNavigationTreePointerEnable_name = __('Enable highlighting');
$strConfigNavigationTreeEnableExpansion_desc = __(
    'Whether to offer the possibility of tree expansion in the navigation panel.'
);
$strConfigNavigationTreeEnableExpansion_name
    = __('Enable navigation tree expansion');
$strConfigNavigationTreeShowTables_name = __('Show tables in tree');
$strConfigNavigationTreeShowTables_desc
    = __('Whether to show tables under database in the navigation tree');
$strConfigNavigationTreeShowViews_name = __('Show views in tree');
$strConfigNavigationTreeShowViews_desc
    = __('Whether to show views under database in the navigation tree');
$strConfigNavigationTreeShowFunctions_name = __('Show functions in tree');
$strConfigNavigationTreeShowFunctions_desc
    = __('Whether to show functions under database in the navigation tree');
$strConfigNavigationTreeShowProcedures_name = __('Show procedures in tree');
$strConfigNavigationTreeShowProcedures_desc
    = __('Whether to show procedures under database in the navigation tree');
$strConfigNavigationTreeShowEvents_name = __('Show events in tree');
$strConfigNavigationTreeShowEvents_desc
    = __('Whether to show events under database in the navigation tree');
$strConfigNumRecentTables_desc
    = __('Maximum number of recently used tables; set 0 to disable.');
$strConfigNumFavoriteTables_desc
    = __('Maximum number of favorite tables; set 0 to disable.');
$strConfigNumRecentTables_name = __('Recently used tables');
$strConfigNumFavoriteTables_name = __('Favorite tables');
$strConfigRowActionLinks_desc = __('These are Edit, Copy and Delete links.');
$strConfigRowActionLinks_name = __('Where to show the table row links');
$strConfigRowActionLinksWithoutUnique_desc = __(
    'Whether to show row links even in the absence of a unique key.'
);
$strConfigRowActionLinksWithoutUnique_name = __('Show row links anyway');
$strConfigDisableShortcutKeys_name = __('Disable shortcut keys');
$strConfigDisableShortcutKeys_desc = __('Disable shortcut keys');
$strConfigNaturalOrder_desc
    = __('Use natural order for sorting table and database names.');
$strConfigNaturalOrder_name = __('Natural order');
$strConfigTableNavigationLinksMode_desc = __('Use only icons, only text or both.');
$strConfigTableNavigationLinksMode_name = __('Table navigation bar');
$strConfigOBGzip_desc
    = __('Use GZip output buffering for increased speed in HTTP transfers.');
$strConfigOBGzip_name = __('GZip output buffering');
$strConfigOrder_desc = __(
    '[kbd]SMART[/kbd] - i.e. descending order for columns of type TIME, DATE, '
    . 'DATETIME and TIMESTAMP, ascending order otherwise.'
);
$strConfigOrder_name = __('Default sorting order');
$strConfigPersistentConnections_desc
    = __('Use persistent connections to MySQL databases.');
$strConfigPersistentConnections_name = __('Persistent connections');
$strConfigPmaNoRelation_DisableWarning_desc = __(
    'Disable the default warning that is displayed on the database details '
    . 'Structure page if any of the required tables for the phpMyAdmin '
    . 'configuration storage could not be found.'
);
$strConfigPmaNoRelation_DisableWarning_name
    = __('Missing phpMyAdmin configuration storage tables');
$strConfigReservedWordDisableWarning_desc = __(
    'Disable the default warning that is displayed on the Structure page if column '
    . 'names in a table are reserved MySQL words.'
);
$strConfigReservedWordDisableWarning_name = __('MySQL reserved word warning');
$strConfigTabsMode_desc = __('Use only icons, only text or both.');
$strConfigTabsMode_name = __('How to display the menu tabs');
$strConfigActionLinksMode_desc = __('Use only icons, only text or both.');
$strConfigActionLinksMode_name = __('How to display various action links');
$strConfigProtectBinary_desc = __('Disallow BLOB and BINARY columns from editing.');
$strConfigProtectBinary_name = __('Protect binary columns');
$strConfigQueryHistoryDB_desc = __(
    'Enable if you want DB-based query history (requires phpMyAdmin configuration '
    . 'storage). If disabled, this utilizes JS-routines to display query history '
    . '(lost by window close).'
);
$strConfigQueryHistoryDB_name = __('Permanent query history');
$strConfigQueryHistoryMax_cmt = __('Users cannot set a higher value');
$strConfigQueryHistoryMax_desc = __('How many queries are kept in history.');
$strConfigQueryHistoryMax_name = __('Query history length');
$strConfigRecodingEngine_desc
    = __('Select which functions will be used for character set conversion.');
$strConfigRecodingEngine_name = __('Recoding engine');
$strConfigRememberSorting_desc
    = __('When browsing tables, the sorting of each table is remembered.');
$strConfigRememberSorting_name = __('Remember table\'s sorting');
$strConfigTablePrimaryKeyOrder_desc = __(
    'Default sort order for tables with a primary key.'
);
$strConfigTablePrimaryKeyOrder_name = __('Primary key default sort order');
$strConfigRepeatCells_desc
    = __('Repeat the headers every X cells, [kbd]0[/kbd] deactivates this feature.');
$strConfigRepeatCells_name = __('Repeat headers');
$strConfigRestoreDefaultValue = __('Restore default value');
$strConfigGridEditing_name = __('Grid editing: trigger action');
$strConfigRelationalDisplay_name = __('Relational display');
$strConfigRelationalDisplay_desc = __('For display Options');
$strConfigSaveCellsAtOnce_name = __('Grid editing: save all edited cells at once');
$strConfigSaveDir_desc = __('Directory where exports can be saved on server.');
$strConfigSaveDir_name = __('Save directory');
$strConfigServers_AllowDeny_order_desc = __('Leave blank if not used.');
$strConfigServers_AllowDeny_order_name = __('Host authorization order');
$strConfigServers_AllowDeny_rules_desc = __('Leave blank for defaults.');
$strConfigServers_AllowDeny_rules_name = __('Host authorization rules');
$strConfigServers_AllowNoPassword_name = __('Allow logins without a password');
$strConfigServers_AllowRoot_name = __('Allow root login');
$strConfigServers_SessionTimeZone_name = __('Session timezone');
$strConfigServers_SessionTimeZone_desc = __(
    'Sets the effective timezone; possibly different than the one from your ' .
    'database server'
);
$strConfigServers_auth_http_realm_desc
    = __('HTTP Basic Auth Realm name to display when doing HTTP Auth.');
$strConfigServers_auth_http_realm_name = __('HTTP Realm');
$strConfigServers_auth_type_desc = __('Authentication method to use.');
$strConfigServers_auth_type_name = __('Authentication type');
$strConfigServers_bookmarktable_desc = __(
    'Leave blank for no [doc@bookmarks@]bookmark[/doc] '
    . 'support, suggested: [kbd]pma__bookmark[/kbd]'
);
$strConfigServers_bookmarktable_name = __('Bookmark table');
$strConfigServers_column_info_desc = __(
    'Leave blank for no column comments/mime types, suggested: '
    . '[kbd]pma__column_info[/kbd].'
);
$strConfigServers_column_info_name = __('Column information table');
$strConfigServers_compress_desc = __('Compress connection to MySQL server.');
$strConfigServers_compress_name = __('Compress connection');
$strConfigServers_controlpass_name = __('Control user password');
$strConfigServers_controluser_desc = __(
    'A special MySQL user configured with limited permissions, more information '
    . 'available on [doc@linked-tables]documentation[/doc].'
);
$strConfigServers_controluser_name = __('Control user');
$strConfigServers_controlhost_desc = __(
    'An alternate host to hold the configuration storage; leave blank to use the '
    . 'already defined host.'
);
$strConfigServers_controlhost_name = __('Control host');
$strConfigServers_controlport_desc = __(
    'An alternate port to connect to the host that holds the configuration storage; '
    . 'leave blank to use the default port, or the already defined port, if the '
    . 'controlhost equals host.'
);
$strConfigServers_controlport_name = __('Control port');
$strConfigServers_hide_db_desc
    = __('Hide databases matching regular expression (PCRE).');
$strConfigServers_DisableIS_desc = __(
    'More information on [a@https://sourceforge.net/p/phpmyadmin/bugs/2606/]PMA ' .
    'bug tracker[/a] and [a@https://bugs.mysql.com/19588]MySQL Bugs[/a]'
);
$strConfigServers_DisableIS_name = __('Disable use of INFORMATION_SCHEMA');
$strConfigServers_hide_db_name = __('Hide databases');
$strConfigServers_history_desc = __(
    'Leave blank for no SQL query history support, suggested: '
    . '[kbd]pma__history[/kbd].'
);
$strConfigServers_history_name = __('SQL query history table');
$strConfigServers_host_desc = __('Hostname where MySQL server is running.');
$strConfigServers_host_name = __('Server hostname');
$strConfigServers_LogoutURL_name = __('Logout URL');
$strConfigServers_MaxTableUiprefs_desc = __(
    'Limits number of table preferences which are stored in database, the oldest '
    . 'records are automatically removed.'
);
$strConfigServers_MaxTableUiprefs_name
    = __('Maximal number of table preferences to store');
$strConfigServers_savedsearches_name = __('QBE saved searches table');
$strConfigServers_savedsearches_desc = __(
    'Leave blank for no QBE saved searches support, suggested: '
    . '[kbd]pma__savedsearches[/kbd].'
);
$strConfigServers_export_templates_name = __('Export templates table');
$strConfigServers_export_templates_desc = __(
    'Leave blank for no export template support, suggested: '
    . '[kbd]pma__export_templates[/kbd].'
);
$strConfigServers_central_columns_name = __('Central columns table');
$strConfigServers_central_columns_desc = __(
    'Leave blank for no central columns support, suggested: '
    . '[kbd]pma__central_columns[/kbd].'
);
$strConfigServers_only_db_desc = __(
    'You can use MySQL wildcard characters (% and _), escape them if you want to '
    . 'use their literal instances, i.e. use [kbd]\'my\_db\'[/kbd] and not '
    . '[kbd]\'my_db\'[/kbd].'
);
$strConfigServers_only_db_name = __('Show only listed databases');
$strConfigServers_password_desc = __('Leave empty if not using config auth.');
$strConfigServers_password_name = __('Password for config auth');
$strConfigServers_pdf_pages_desc = __(
    'Leave blank for no PDF schema support, suggested: [kbd]pma__pdf_pages[/kbd].'
);
$strConfigServers_pdf_pages_name = __('PDF schema: pages table');
$strConfigServers_pmadb_desc = __(
    'Database used for relations, bookmarks, and PDF features. See '
    . '[doc@linked-tables]pmadb[/doc] for complete information. '
    . 'Leave blank for no support. Suggested: [kbd]phpmyadmin[/kbd].'
);
$strConfigServers_pmadb_name = __('Database name');
$strConfigServers_port_desc
    = __('Port on which MySQL server is listening, leave empty for default.');
$strConfigServers_port_name = __('Server port');
$strConfigServers_recent_desc = __(
    'Leave blank for no "persistent" recently used tables across sessions, '
    . 'suggested: [kbd]pma__recent[/kbd].'
);
$strConfigServers_recent_name = __('Recently used table');
$strConfigServers_favorite_desc = __(
    'Leave blank for no "persistent" favorite tables across sessions, '
    . 'suggested: [kbd]pma__favorite[/kbd].'
);
$strConfigServers_favorite_name = __('Favorites table');
$strConfigServers_relation_desc = __(
    'Leave blank for no '
    . '[doc@relations@]relation-links[/doc] support, '
    . 'suggested: [kbd]pma__relation[/kbd].'
);
$strConfigServers_relation_name = __('Relation table');
$strConfigServers_SignonSession_desc = __(
    'See [doc@authentication-modes]authentication '
    . 'types[/doc] for an example.'
);
$strConfigServers_SignonSession_name = __('Signon session name');
$strConfigServers_SignonURL_name = __('Signon URL');
$strConfigServers_socket_desc
    = __('Socket on which MySQL server is listening, leave empty for default.');
$strConfigServers_socket_name = __('Server socket');
$strConfigServers_ssl_desc = __('Enable SSL for connection to MySQL server.');
$strConfigServers_ssl_name = __('Use SSL');
$strConfigServers_table_coords_desc = __(
    'Leave blank for no PDF schema support, suggested: [kbd]pma__table_coords[/kbd].'
);
$strConfigServers_table_coords_name = __(
    'Designer and PDF schema: table coordinates'
);
$strConfigServers_table_info_desc = __(
    'Table to describe the display columns, leave blank for no support; '
    . 'suggested: [kbd]pma__table_info[/kbd].'
);
$strConfigServers_table_info_name = __('Display columns table');
$strConfigServers_table_uiprefs_desc = __(
    'Leave blank for no "persistent" tables\' UI preferences across sessions, '
    . 'suggested: [kbd]pma__table_uiprefs[/kbd].'
);
$strConfigServers_table_uiprefs_name = __('UI preferences table');
$strConfigServers_tracking_add_drop_database_desc = __(
    'Whether a DROP DATABASE IF EXISTS statement will be added as first line to '
    . 'the log when creating a database.'
);
$strConfigServers_tracking_add_drop_database_name = __('Add DROP DATABASE');
$strConfigServers_tracking_add_drop_table_desc = __(
    'Whether a DROP TABLE IF EXISTS statement will be added as first line to the '
    . 'log when creating a table.'
);
$strConfigServers_tracking_add_drop_table_name = __('Add DROP TABLE');
$strConfigServers_tracking_add_drop_view_desc = __(
    'Whether a DROP VIEW IF EXISTS statement will be added as first line to the '
    . 'log when creating a view.'
);
$strConfigServers_tracking_add_drop_view_name = __('Add DROP VIEW');
$strConfigServers_tracking_default_statements_desc
    = __('Defines the list of statements the auto-creation uses for new versions.');
$strConfigServers_tracking_default_statements_name = __('Statements to track');
$strConfigServers_tracking_desc = __(
    'Leave blank for no SQL query tracking support, suggested: '
    . '[kbd]pma__tracking[/kbd].'
);
$strConfigServers_tracking_name = __('SQL query tracking table');
$strConfigServers_tracking_version_auto_create_desc = __(
    'Whether the tracking mechanism creates versions for tables and views '
    . 'automatically.'
);
$strConfigServers_tracking_version_auto_create_name
    = __('Automatically create versions');
$strConfigServers_userconfig_desc = __(
    'Leave blank for no user preferences storage in database, suggested: ' .
    '[kbd]pma__userconfig[/kbd].'
);
$strConfigServers_userconfig_name = __('User preferences storage table');
$strConfigServers_users_desc = __(
    'Both this table and the user groups table are required to enable the ' .
    'configurable menus feature; leaving either one of them blank will disable ' .
    'this feature, suggested: [kbd]pma__users[/kbd].'
);
$strConfigServers_users_name = __('Users table');
$strConfigServers_usergroups_desc = __(
    'Both this table and the users table are required to enable the configurable ' .
    'menus feature; leaving either one of them blank will disable this feature, ' .
    'suggested: [kbd]pma__usergroups[/kbd].'
);
$strConfigServers_usergroups_name = __('User groups table');
$strConfigServers_navigationhiding_desc = __(
    'Leave blank to disable the feature to hide and show navigation items, ' .
    'suggested: [kbd]pma__navigationhiding[/kbd].'
);
$strConfigServers_navigationhiding_name = __('Hidden navigation items table');
$strConfigServers_user_desc = __('Leave empty if not using config auth.');
$strConfigServers_user_name = __('User for config auth');
$strConfigServers_verbose_desc = __(
    'A user-friendly description of this server. Leave blank to display the ' .
    'hostname instead.'
);
$strConfigServers_verbose_name = __('Verbose name of this server');
$strConfigShowAll_desc = __(
    'Whether a user should be displayed a "show all (rows)" button.'
);
$strConfigShowAll_name = __('Allow to display all the rows');
$strConfigShowChgPassword_desc = __(
    'Please note that enabling this has no effect with [kbd]config[/kbd] ' .
    'authentication mode because the password is hard coded in the configuration ' .
    'file; this does not limit the ability to execute the same command directly.'
);
$strConfigShowChgPassword_name = __('Show password change form');
$strConfigShowCreateDb_name = __('Show create database form');
$strConfigShowDbStructureComment_desc = __(
    'Show or hide a column displaying the comments for all tables.'
);
$strConfigShowDbStructureComment_name = __('Show table comments');
$strConfigShowDbStructureCreation_desc = __(
    'Show or hide a column displaying the Creation timestamp for all tables.'
);
$strConfigShowDbStructureCreation_name = __('Show creation timestamp');
$strConfigShowDbStructureLastUpdate_desc = __(
    'Show or hide a column displaying the Last update timestamp for all tables.'
);
$strConfigShowDbStructureLastUpdate_name = __('Show last update timestamp');
$strConfigShowDbStructureLastCheck_desc = __(
    'Show or hide a column displaying the Last check timestamp for all tables.'
);
$strConfigShowDbStructureLastCheck_name = __('Show last check timestamp');
$strConfigShowDbStructureCharset_desc = __(
    'Show or hide a column displaying the charset for all tables.'
);
$strConfigShowDbStructureCharset_name = __('Show table charset');
$strConfigShowFieldTypesInDataEditView_desc = __(
    'Defines whether or not type fields should be initially displayed in ' .
    'edit/insert mode.'
);
$strConfigShowFieldTypesInDataEditView_name = __('Show field types');
$strConfigShowFunctionFields_desc = __(
    'Display the function fields in edit/insert mode.'
);
$strConfigShowFunctionFields_name = __('Show function fields');
$strConfigShowHint_desc = __('Whether to show hint or not.');
$strConfigShowHint_name = __('Show hint');
$strConfigShowPhpInfo_desc = __(
    'Shows link to [a@https://php.net/manual/function.phpinfo.php]phpinfo()[/a] ' .
    'output.'
);
$strConfigShowPhpInfo_name = __('Show phpinfo() link');
$strConfigShowServerInfo_name = __('Show detailed MySQL server information');
$strConfigShowSQL_desc = __(
    'Defines whether SQL queries generated by phpMyAdmin should be displayed.'
);
$strConfigShowSQL_name = __('Show SQL queries');
$strConfigRetainQueryBox_desc = __(
    'Defines whether the query box should stay on-screen after its submission.'
);
$strConfigRetainQueryBox_name = __('Retain query box');
$strConfigShowStats_desc = __(
    'Allow to display database and table statistics (eg. space usage).'
);
$strConfigShowStats_name = __('Show statistics');
$strConfigSkipLockedTables_desc = __(
    'Mark used tables and make it possible to show databases with locked tables.'
);
$strConfigSkipLockedTables_name = __('Skip locked tables');
$strConfigSQLQuery_Edit_name = __('Edit');
$strConfigSQLQuery_Explain_name = __('Explain SQL');
$strConfigSQLQuery_Refresh_name = __('Refresh');
$strConfigSQLQuery_ShowAsPHP_name = __('Create PHP code');
$strConfigSuhosinDisableWarning_desc = __(
    'Disable the default warning that is displayed on the main page if Suhosin is ' .
    'detected.'
);
$strConfigSuhosinDisableWarning_name = __('Suhosin warning');
$strConfigLoginCookieValidityDisableWarning_desc = __(
    'Disable the default warning that is displayed on the main page if the value ' .
    'of the PHP setting session.gc_maxlifetime is less than the value of ' .
    '`LoginCookieValidity`.'
);
$strConfigLoginCookieValidityDisableWarning_name = __(
    'Login cookie validity warning'
);
$strConfigTextareaCols_desc = __(
    'Textarea size (columns) in edit mode, this value will be emphasized for SQL ' .
    'query textareas (*2).'
);
$strConfigTextareaCols_name = __('Textarea columns');
$strConfigTextareaRows_desc = __(
    'Textarea size (rows) in edit mode, this value will be emphasized for SQL ' .
    'query textareas (*2).'
);
$strConfigTextareaRows_name = __('Textarea rows');
$strConfigTitleDatabase_desc = __(
    'Title of browser window when a database is selected.'
);
$strConfigTitleDatabase_name = __('Database');
$strConfigTitleDefault_desc = __(
    'Title of browser window when nothing is selected.'
);
$strConfigTitleDefault_name = __('Default title');
$strConfigTitleServer_desc = __(
    'Title of browser window when a server is selected.'
);
$strConfigTitleServer_name = __('Server');
$strConfigTitleTable_desc = __('Title of browser window when a table is selected.');
$strConfigTitleTable_name = __('Table');
$strConfigTrustedProxies_desc = __(
    'Input proxies as [kbd]IP: trusted HTTP header[/kbd]. The following example ' .
    'specifies that phpMyAdmin should trust a HTTP_X_FORWARDED_FOR ' .
    '(X-Forwarded-For) header coming from the proxy 1.2.3.4:[br][kbd]1.2.3.4: ' .
    'HTTP_X_FORWARDED_FOR[/kbd].'
);
$strConfigTrustedProxies_name = __('List of trusted proxies for IP allow/deny');
$strConfigUploadDir_desc = __(
    'Directory on server where you can upload files for import.'
);
$strConfigUploadDir_name = __('Upload directory');
$strConfigUseDbSearch_desc = __('Allow for searching inside the entire database.');
$strConfigUseDbSearch_name = __('Use database search');
$strConfigUserprefsDeveloperTab_desc = __(
    'When disabled, users cannot set any of the options below, regardless of the ' .
    'checkbox on the right.'
);
$strConfigUserprefsDeveloperTab_name = __('Enable the Developer tab in settings');
$strConfigVersionCheckLink = __('Check for latest version');
$strConfigVersionCheck_desc = __(
    'Enables check for latest version on main phpMyAdmin page.'
);
$strConfigVersionCheck_name = __('Version check');
$strConfigProxyUrl_desc = __(
    'The url of the proxy to be used when retrieving the information about the ' .
    'latest version of phpMyAdmin or when submitting error reports. You need this ' .
    'if the server where phpMyAdmin is installed does not have direct access to ' .
    'the internet. The format is: "hostname:portnumber".'
);
$strConfigProxyUrl_name = __('Proxy url');
$strConfigProxyUser_desc = __(
    'The username for authenticating with the proxy. By default, no ' .
    'authentication is performed. If a username is supplied, Basic ' .
    'Authentication will be performed. No other types of authentication are ' .
    'currently supported.'
);
$strConfigProxyUser_name = __('Proxy username');
$strConfigProxyPass_desc = __('The password for authenticating with the proxy.');
$strConfigProxyPass_name = __('Proxy password');

$strConfigZipDump_desc = __(
    'Enable ZIP ' .
    'compression for import and export operations.'
);
$strConfigZipDump_name = __('ZIP');
$strConfigCaptchaLoginPublicKey_desc = __(
    'Enter your public key for your domain reCaptcha service.'
);
$strConfigCaptchaLoginPublicKey_name = __('Public key for reCaptcha');
$strConfigCaptchaLoginPrivateKey_desc = __(
    'Enter your private key for your domain reCaptcha service.'
);
$strConfigCaptchaLoginPrivateKey_name = __('Private key for reCaptcha');

$strConfigSendErrorReports_desc = __(
    'Choose the default action when sending error reports.'
);
$strConfigSendErrorReports_name = __('Send error reports');

$strConfigConsoleEnterExecutes_desc = __(
    'Queries are executed by pressing Enter (instead of Ctrl+Enter). New lines ' .
    'will be inserted with Shift+Enter.'
);
$strConfigConsoleEnterExecutes_name = __('Enter executes queries in console');

$strConfigZeroConf_desc = __(
    'Enable Zero Configuration mode which lets you setup phpMyAdmin '
    . 'configuration storage tables automatically.'
);
$strConfigZeroConf_name = __('Enable Zero Configuration mode');
