<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Verbose descriptions for settings.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config;

use PhpMyAdmin\Sanitize;

/**
 * Base class for forms, loads default configuration options, checks allowed
 * values etc.
 *
 * @package PhpMyAdmin
 */
class Descriptions
{
    /**
     * Return
     * Return name or description for a configuration path.
     *
     * @param string $path Path of configuration
     * @param string $type Type of message, either 'name', 'cmt' or 'desc'
     *
     * @return string
     */
    public static function get($path, $type = 'name')
    {
        $key = str_replace(
            array('Servers/1/', '/'),
            array('Servers/', '_'),
            $path
        );
        $value = self::getString($key, $type);

        /* Fallback to path for name and empty string for description and comment */
        if (is_null($value)) {
            if ($type == 'name') {
                $value = $path;
            } else {
                $value = '';
            }
        }

        return Sanitize::sanitize($value);
    }

    /**
     * Return name or description for a cleaned up configuration path.
     *
     * @param string $path Path of configuration
     * @param string $type Type of message, either 'name', 'cmt' or 'desc'
     *
     * @return string|null Null if not found
     */
    public static function getString($path, $type = 'name')
    {
        switch ($path . '_' . $type) {
            case 'AllowArbitraryServer_desc':
                return __('If enabled, user can enter any MySQL server in login form for cookie auth.');
            case 'AllowArbitraryServer_name':
                return __('Allow login to any MySQL server');
            case 'ArbitraryServerRegexp_desc':
                return __(
                    'Restricts the MySQL servers the user can enter when a login to an arbitrary '
                    . 'MySQL server is enabled by matching the IP or hostname of the MySQL server ' .
                    'to the given regular expression.'
                );
            case 'ArbitraryServerRegexp_name':
                return __('Restrict login to MySQL server');
            case 'AllowThirdPartyFraming_desc':
                return __(
                    'Enabling this allows a page located on a different domain to call phpMyAdmin '
                    . 'inside a frame, and is a potential [strong]security hole[/strong] allowing '
                    . 'cross-frame scripting (XSS) attacks.'
                );
            case 'AllowThirdPartyFraming_name':
                return __('Allow third party framing');
            case 'AllowUserDropDatabase_name':
                return __('Show "Drop database" link to normal users');
            case 'blowfish_secret_desc':
                return __(
                    'Secret passphrase used for encrypting cookies in [kbd]cookie[/kbd] '
                    . 'authentication.'
                );
            case 'blowfish_secret_name':
                return __('Blowfish secret');
            case 'BrowseMarkerEnable_desc':
                return __('Highlight selected rows.');
            case 'BrowseMarkerEnable_name':
                return __('Row marker');
            case 'BrowsePointerEnable_desc':
                return __('Highlight row pointed by the mouse cursor.');
            case 'BrowsePointerEnable_name':
                return __('Highlight pointer');
            case 'BZipDump_desc':
                return __(
                    'Enable bzip2 compression for'
                    . ' import operations.'
                );
            case 'BZipDump_name':
                return __('Bzip2');
            case 'CharEditing_desc':
                return __(
                    'Defines which type of editing controls should be used for CHAR and VARCHAR '
                    . 'columns; [kbd]input[/kbd] - allows limiting of input length, '
                    . '[kbd]textarea[/kbd] - allows newlines in columns.'
                );
            case 'CharEditing_name':
                return __('CHAR columns editing');
            case 'CodemirrorEnable_desc':
                return __(
                    'Use user-friendly editor for editing SQL queries '
                    . '(CodeMirror) with syntax highlighting and '
                    . 'line numbers.'
                );
            case 'CodemirrorEnable_name':
                return __('Enable CodeMirror');
            case 'LintEnable_desc':
                return __(
                    'Find any errors in the query before executing it.'
                    . ' Requires CodeMirror to be enabled.'
                );
            case 'LintEnable_name':
                return __('Enable linter');
            case 'MinSizeForInputField_desc':
                return __(
                    'Defines the minimum size for input fields generated for CHAR and VARCHAR '
                    . 'columns.'
                );
            case 'MinSizeForInputField_name':
                return __('Minimum size for input field');
            case 'MaxSizeForInputField_desc':
                return __(
                    'Defines the maximum size for input fields generated for CHAR and VARCHAR '
                    . 'columns.'
                );
            case 'MaxSizeForInputField_name':
                return __('Maximum size for input field');
            case 'CharTextareaCols_desc':
                return __('Number of columns for CHAR/VARCHAR textareas.');
            case 'CharTextareaCols_name':
                return __('CHAR textarea columns');
            case 'CharTextareaRows_desc':
                return __('Number of rows for CHAR/VARCHAR textareas.');
            case 'CharTextareaRows_name':
                return __('CHAR textarea rows');
            case 'CheckConfigurationPermissions_name':
                return __('Check config file permissions');
            case 'CompressOnFly_desc':
                return __(
                    'Compress gzip exports on the fly without the need for much memory; if '
                    . 'you encounter problems with created gzip files disable this feature.'
                );
            case 'CompressOnFly_name':
                return __('Compress on the fly');
            case 'Confirm_desc':
                return __(
                    'Whether a warning ("Are your really sure…") should be displayed '
                    . 'when you\'re about to lose data.'
                );
            case 'Confirm_name':
                return __('Confirm DROP queries');
            case 'DBG_sql_desc':
                return __('Log SQL queries and their execution time, to be displayed in the console');
            case 'DBG_sql_name':
                return __('Debug SQL');
            case 'DefaultTabDatabase_desc':
                return __('Tab that is displayed when entering a database.');
            case 'DefaultTabDatabase_name':
                return __('Default database tab');
            case 'DefaultTabServer_desc':
                return __('Tab that is displayed when entering a server.');
            case 'DefaultTabServer_name':
                return __('Default server tab');
            case 'DefaultTabTable_desc':
                return __('Tab that is displayed when entering a table.');
            case 'DefaultTabTable_name':
                return __('Default table tab');
            case 'EnableAutocompleteForTablesAndColumns_desc':
                return __('Autocomplete of the table and column names in the SQL queries.');
            case 'EnableAutocompleteForTablesAndColumns_name':
                return __('Enable autocomplete for table and column names');
            case 'HideStructureActions_desc':
                return __('Whether the table structure actions should be hidden.');
            case 'ShowColumnComments_name':
                return __('Show column comments');
            case 'ShowColumnComments_desc':
                return __('Whether column comments should be shown in table structure view');
            case 'HideStructureActions_name':
                return __('Hide table structure actions');
            case 'DefaultTransformations_Hex_name':
                return __('Default transformations for Hex');
            case 'DefaultTransformations_Hex_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_Substring_name':
                return __('Default transformations for Substring');
            case 'DefaultTransformations_Substring_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_Bool2Text_name':
                return __('Default transformations for Bool2Text');
            case 'DefaultTransformations_Bool2Text_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_External_name':
                return __('Default transformations for External');
            case 'DefaultTransformations_External_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_PreApPend_name':
                return __('Default transformations for PreApPend');
            case 'DefaultTransformations_PreApPend_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_DateFormat_name':
                return __('Default transformations for DateFormat');
            case 'DefaultTransformations_DateFormat_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_Inline_name':
                return __('Default transformations for Inline');
            case 'DefaultTransformations_Inline_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_TextImageLink_name':
                return __('Default transformations for TextImageLink');
            case 'DefaultTransformations_TextImageLink_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');
            case 'DefaultTransformations_TextLink_name':
                return __('Default transformations for TextLink');
            case 'DefaultTransformations_TextLink_desc':
                return __('Values for options list for default transformations. These will be overwritten if transformation is filled in at table structure page.');

            case 'DisplayServersList_desc':
                return __('Show server listing as a list instead of a drop down.');
            case 'DisplayServersList_name':
                return __('Display servers as a list');
            case 'DisableMultiTableMaintenance_desc':
                return __(
                    'Disable the table maintenance mass operations, like optimizing or repairing '
                    . 'the selected tables of a database.'
                );
            case 'DisableMultiTableMaintenance_name':
                return __('Disable multi table maintenance');
            case 'ExecTimeLimit_desc':
                return __(
                    'Set the number of seconds a script is allowed to run ([kbd]0[/kbd] for no '
                    . 'limit).'
                );
            case 'ExecTimeLimit_name':
                return __('Maximum execution time');
            case 'Export_lock_tables_name':
                return sprintf(
                    __('Use %s statement'), '<code>LOCK TABLES</code>'
                );
            case 'Export_asfile_name':
                return __('Save as file');
            case 'Export_charset_name':
                return __('Character set of the file');
            case 'Export_codegen_format_name':
                return __('Format');
            case 'Export_compression_name':
                return __('Compression');
            case 'Export_csv_columns_name':
                return __('Put columns names in the first row');
            case 'Export_csv_enclosed_name':
                return __('Columns enclosed with');
            case 'Export_csv_escaped_name':
                return __('Columns escaped with');
            case 'Export_csv_null_name':
                return __('Replace NULL with');
            case 'Export_csv_removeCRLF_name':
                return __('Remove CRLF characters within columns');
            case 'Export_csv_separator_name':
                return __('Columns terminated with');
            case 'Export_csv_terminated_name':
                return __('Lines terminated with');
            case 'Export_excel_columns_name':
                return __('Put columns names in the first row');
            case 'Export_excel_edition_name':
                return __('Excel edition');
            case 'Export_excel_null_name':
                return __('Replace NULL with');
            case 'Export_excel_removeCRLF_name':
                return __('Remove CRLF characters within columns');
            case 'Export_file_template_database_name':
                return __('Database name template');
            case 'Export_file_template_server_name':
                return __('Server name template');
            case 'Export_file_template_table_name':
                return __('Table name template');
            case 'Export_format_name':
                return __('Format');
            case 'Export_htmlword_columns_name':
                return __('Put columns names in the first row');
            case 'Export_htmlword_null_name':
                return __('Replace NULL with');
            case 'Export_htmlword_structure_or_data_name':
                return __('Dump table');
            case 'Export_latex_caption_name':
                return __('Include table caption');
            case 'Export_latex_columns_name':
                return __('Put columns names in the first row');
            case 'Export_latex_comments_name':
                return __('Comments');
            case 'Export_latex_data_caption_name':
                return __('Table caption');
            case 'Export_latex_data_continued_caption_name':
                return __('Continued table caption');
            case 'Export_latex_data_label_name':
                return __('Label key');
            case 'Export_latex_mime_name':
                return __('MIME type');
            case 'Export_latex_null_name':
                return __('Replace NULL with');
            case 'Export_latex_relation_name':
                return __('Relationships');
            case 'Export_latex_structure_caption_name':
                return __('Table caption');
            case 'Export_latex_structure_continued_caption_name':
                return __('Continued table caption');
            case 'Export_latex_structure_label_name':
                return __('Label key');
            case 'Export_latex_structure_or_data_name':
                return __('Dump table');
            case 'Export_method_name':
                return __('Export method');
            case 'Export_ods_columns_name':
                return __('Put columns names in the first row');
            case 'Export_ods_null_name':
                return __('Replace NULL with');
            case 'Export_odt_columns_name':
                return __('Put columns names in the first row');
            case 'Export_odt_comments_name':
                return __('Comments');
            case 'Export_odt_mime_name':
                return __('MIME type');
            case 'Export_odt_null_name':
                return __('Replace NULL with');
            case 'Export_odt_relation_name':
                return __('Relationships');
            case 'Export_odt_structure_or_data_name':
                return __('Dump table');
            case 'Export_onserver_name':
                return __('Save on server');
            case 'Export_onserver_overwrite_name':
                return __('Overwrite existing file(s)');
            case 'Export_as_separate_files_name':
                return __('Export as separate files');
            case 'Export_quick_export_onserver_name':
                return __('Save on server');
            case 'Export_quick_export_onserver_overwrite_name':
                return __('Overwrite existing file(s)');
            case 'Export_remember_file_template_name':
                return __('Remember file name template');
            case 'Export_sql_auto_increment_name':
                return __('Add AUTO_INCREMENT value');
            case 'Export_sql_backquotes_name':
                return __('Enclose table and column names with backquotes');
            case 'Export_sql_compatibility_name':
                return __('SQL compatibility mode');
            case 'Export_sql_dates_name':
                return __('Creation/Update/Check dates');
            case 'Export_sql_delayed_name':
                return __('Use delayed inserts');
            case 'Export_sql_disable_fk_name':
                return __('Disable foreign key checks');
            case 'Export_sql_views_as_tables_name':
                return __('Export views as tables');
            case 'Export_sql_metadata_name':
                return __('Export related metadata from phpMyAdmin configuration storage');
            case 'Export_sql_create_database_name':
                return sprintf(__('Add %s'), 'CREATE DATABASE / USE');
            case 'Export_sql_drop_database_name':
                return sprintf(__('Add %s'), 'DROP DATABASE');
            case 'Export_sql_drop_table_name':
                return sprintf(
                    __('Add %s'), 'DROP TABLE / VIEW / PROCEDURE / FUNCTION / EVENT / TRIGGER'
                );
            case 'Export_sql_create_table_name':
                return sprintf(__('Add %s'), 'CREATE TABLE');
            case 'Export_sql_create_view_name':
                return sprintf(__('Add %s'), 'CREATE VIEW');
            case 'Export_sql_create_trigger_name':
                return sprintf(__('Add %s'), 'CREATE TRIGGER');
            case 'Export_sql_hex_for_binary_name':
                return __('Use hexadecimal for BINARY & BLOB');
            case 'Export_sql_if_not_exists_name':
                return __(
                    'Add IF NOT EXISTS (less efficient as indexes will be generated during'
                    . ' table creation)'
                );
            case 'Export_sql_ignore_name':
                return __('Use ignore inserts');
            case 'Export_sql_include_comments_name':
                return __('Comments');
            case 'Export_sql_insert_syntax_name':
                return __('Syntax to use when inserting data');
            case 'Export_sql_max_query_size_name':
                return __('Maximal length of created query');
            case 'Export_sql_mime_name':
                return __('MIME type');
            case 'Export_sql_procedure_function_name':
                return sprintf(__('Add %s'), 'CREATE PROCEDURE / FUNCTION / EVENT');
            case 'Export_sql_relation_name':
                return __('Relationships');
            case 'Export_sql_structure_or_data_name':
                return __('Dump table');
            case 'Export_sql_type_name':
                return __('Export type');
            case 'Export_sql_use_transaction_name':
                return __('Enclose export in a transaction');
            case 'Export_sql_utc_time_name':
                return __('Export time in UTC');
            case 'Export_texytext_columns_name':
                return __('Put columns names in the first row');
            case 'Export_texytext_null_name':
                return __('Replace NULL with');
            case 'Export_texytext_structure_or_data_name':
                return __('Dump table');
            case 'ForeignKeyDropdownOrder_desc':
                return __(
                    'Sort order for items in a foreign-key dropdown box; [kbd]content[/kbd] is '
                    . 'the referenced data, [kbd]id[/kbd] is the key value.'
                );
            case 'ForeignKeyDropdownOrder_name':
                return __('Foreign key dropdown order');
            case 'ForeignKeyMaxLimit_desc':
                return __('A dropdown will be used if fewer items are present.');
            case 'ForeignKeyMaxLimit_name':
                return __('Foreign key limit');
            case 'DefaultForeignKeyChecks_desc':
                return __('Default value for foreign key checks checkbox for some queries.');
            case 'DefaultForeignKeyChecks_name':
                return __('Foreign key checks');
            case 'Form_Browse_name':
                return __('Browse mode');
            case 'Form_Browse_desc':
                return __('Customize browse mode.');
            case 'Form_CodeGen_name':
                return 'CodeGen';
            case 'Form_CodeGen_desc':
                return __('Customize default options.');
            case 'Form_Csv_name':
                return __('CSV');
            case 'Form_Csv_desc':
                return __('Customize default options.');
            case 'Form_Developer_name':
                return __('Developer');
            case 'Form_Developer_desc':
                return __('Settings for phpMyAdmin developers.');
            case 'Form_Edit_name':
                return __('Edit mode');
            case 'Form_Edit_desc':
                return __('Customize edit mode.');
            case 'Form_Export_defaults_name':
                return __('Export defaults');
            case 'Form_Export_defaults_desc':
                return __('Customize default export options.');
            case 'Form_General_name':
                return __('General');
            case 'Form_General_desc':
                return __('Set some commonly used options.');
            case 'Form_Import_defaults_name':
                return __('Import defaults');
            case 'Form_Import_defaults_desc':
                return __('Customize default common import options.');
            case 'Form_Import_export_name':
                return __('Import / export');
            case 'Form_Import_export_desc':
                return __('Set import and export directories and compression options.');
            case 'Form_Latex_name':
                return __('LaTeX');
            case 'Form_Latex_desc':
                return __('Customize default options.');
            case 'Form_Navi_databases_name':
                return __('Databases');
            case 'Form_Navi_databases_desc':
                return __('Databases display options.');
            case 'Form_Navi_panel_name':
                return __('Navigation panel');
            case 'Form_Navi_panel_desc':
                return __('Customize appearance of the navigation panel.');
            case 'Form_Navi_tree_name':
                return __('Navigation tree');
            case 'Form_Navi_tree_desc':
                return __('Customize the navigation tree.');
            case 'Form_Navi_servers_name':
                return __('Servers');
            case 'Form_Navi_servers_desc':
                return __('Servers display options.');
            case 'Form_Navi_tables_name':
                return __('Tables');
            case 'Form_Navi_tables_desc':
                return __('Tables display options.');
            case 'Form_Main_panel_name':
                return __('Main panel');
            case 'Form_Microsoft_Office_name':
                return __('Microsoft Office');
            case 'Form_Microsoft_Office_desc':
                return __('Customize default options.');
            case 'Form_Open_Document_name':
                return 'OpenDocument';
            case 'Form_Open_Document_desc':
                return __('Customize default options.');
            case 'Form_Other_core_settings_name':
                return __('Other core settings');
            case 'Form_Other_core_settings_desc':
                return __('Settings that didn\'t fit anywhere else.');
            case 'Form_Page_titles_name':
                return __('Page titles');
            case 'Form_Page_titles_desc':
                return __(
                    'Specify browser\'s title bar text. Refer to '
                    . '[doc@faq6-27]documentation[/doc] for magic strings that can be used '
                    . 'to get special values.'
                );
            case 'Form_Security_name':
                return __('Security');
            case 'Form_Security_desc':
                return __(
                    'Please note that phpMyAdmin is just a user interface and its features do not '
                    . 'limit MySQL.'
                );
            case 'Form_Server_name':
                return __('Basic settings');
            case 'Form_Server_auth_name':
                return __('Authentication');
            case 'Form_Server_auth_desc':
                return __('Authentication settings.');
            case 'Form_Server_config_name':
                return __('Server configuration');
            case 'Form_Server_config_desc':
                return __(
                    'Advanced server configuration, do not change these options unless you know '
                    . 'what they are for.'
                );
            case 'Form_Server_desc':
                return __('Enter server connection parameters.');
            case 'Form_Server_pmadb_name':
                return __('Configuration storage');
            case 'Form_Server_pmadb_desc':
                return __(
                    'Configure phpMyAdmin configuration storage to gain access to additional '
                    . 'features, see [doc@linked-tables]phpMyAdmin configuration storage[/doc] in '
                    . 'documentation.'
                );
            case 'Form_Server_tracking_name':
                return __('Changes tracking');
            case 'Form_Server_tracking_desc':
                return __(
                    'Tracking of changes made in database. Requires the phpMyAdmin configuration '
                    . 'storage.'
                );
            case 'Form_Sql_name':
                return __('SQL');
            case 'Form_Sql_box_name':
                return __('SQL Query box');
            case 'Form_Sql_box_desc':
                return __('Customize links shown in SQL Query boxes.');
            case 'Form_Sql_desc':
                return __('Customize default options.');
            case 'Form_Sql_queries_name':
                return __('SQL queries');
            case 'Form_Sql_queries_desc':
                return __('SQL queries settings.');
            case 'Form_Startup_name':
                return __('Startup');
            case 'Form_Startup_desc':
                return __('Customize startup page.');
            case 'Form_DbStructure_name':
                return __('Database structure');
            case 'Form_DbStructure_desc':
                return __('Choose which details to show in the database structure (list of tables).');
            case 'Form_TableStructure_name':
                return __('Table structure');
            case 'Form_TableStructure_desc':
                return __('Settings for the table structure (list of columns).');
            case 'Form_Tabs_name':
                return __('Tabs');
            case 'Form_Tabs_desc':
                return __('Choose how you want tabs to work.');
            case 'Form_DisplayRelationalSchema_name':
                return __('Display relational schema');
            case 'Form_DisplayRelationalSchema_desc':
                return '';
            case 'PDFDefaultPageSize_name':
                return __('Paper size');
            case 'PDFDefaultPageSize_desc':
                return '';
            case 'Form_Databases_name':
                return __('Databases');
            case 'Form_Text_fields_name':
                return __('Text fields');
            case 'Form_Text_fields_desc':
                return __('Customize text input fields.');
            case 'Form_Texy_name':
                return __('Texy! text');
            case 'Form_Texy_desc':
                return __('Customize default options');
            case 'Form_Warnings_name':
                return __('Warnings');
            case 'Form_Warnings_desc':
                return __('Disable some of the warnings shown by phpMyAdmin.');
            case 'Form_Console_name':
                return __('Console');
            case 'GZipDump_desc':
                return __(
                    'Enable gzip compression for import '
                    . 'and export operations.'
                );
            case 'GZipDump_name':
                return __('GZip');
            case 'IconvExtraParams_name':
                return __('Extra parameters for iconv');
            case 'IgnoreMultiSubmitErrors_desc':
                return __(
                    'If enabled, phpMyAdmin continues computing multiple-statement queries even if '
                    . 'one of the queries failed.'
                );
            case 'IgnoreMultiSubmitErrors_name':
                return __('Ignore multiple statement errors');
            case 'Import_allow_interrupt_desc':
                return __(
                    'Allow interrupt of import in case script detects it is close to time limit. '
                    . 'This might be a good way to import large files, however it can break '
                    . 'transactions.'
                );
            case 'Import_allow_interrupt_name':
                return __('Partial import: allow interrupt');
            case 'Import_charset_name':
                return __('Character set of the file');
            case 'Import_csv_col_names_name':
                return __('Lines terminated with');
            case 'Import_csv_enclosed_name':
                return __('Columns enclosed with');
            case 'Import_csv_escaped_name':
                return __('Columns escaped with');
            case 'Import_csv_ignore_name':
                return __('Do not abort on INSERT error');
            case 'Import_csv_replace_name':
                return __('Add ON DUPLICATE KEY UPDATE');
            case 'Import_csv_replace_desc':
                return __('Update data when duplicate keys found on import');
            case 'Import_csv_terminated_name':
                return __('Columns terminated with');
            case 'Import_format_desc':
                return __(
                    'Default format; be aware that this list depends on location (database, table) '
                    . 'and only SQL is always available.'
                );
            case 'Import_format_name':
                return __('Format of imported file');
            case 'Import_ldi_enclosed_name':
                return __('Columns enclosed with');
            case 'Import_ldi_escaped_name':
                return __('Columns escaped with');
            case 'Import_ldi_ignore_name':
                return __('Do not abort on INSERT error');
            case 'Import_ldi_local_option_name':
                return __('Use LOCAL keyword');
            case 'Import_ldi_replace_name':
                return __('Add ON DUPLICATE KEY UPDATE');
            case 'Import_ldi_replace_desc':
                return __('Update data when duplicate keys found on import');
            case 'Import_ldi_terminated_name':
                return __('Columns terminated with');
            case 'Import_ods_col_names_name':
                return __('Column names in first row');
            case 'Import_ods_empty_rows_name':
                return __('Do not import empty rows');
            case 'Import_ods_recognize_currency_name':
                return __('Import currencies ($5.00 to 5.00)');
            case 'Import_ods_recognize_percentages_name':
                return __('Import percentages as proper decimals (12.00% to .12)');
            case 'Import_skip_queries_desc':
                return __('Number of queries to skip from start.');
            case 'Import_skip_queries_name':
                return __('Partial import: skip queries');
            case 'Import_sql_compatibility_name':
                return __('SQL compatibility mode');
            case 'Import_sql_no_auto_value_on_zero_name':
                return __('Do not use AUTO_INCREMENT for zero values');
            case 'Import_sql_read_as_multibytes_name':
                return __('Read as multibytes');
            case 'InitialSlidersState_name':
                return __('Initial state for sliders');
            case 'InsertRows_desc':
                return __('How many rows can be inserted at one time.');
            case 'InsertRows_name':
                return __('Number of inserted rows');
            case 'LimitChars_desc':
                return __('Maximum number of characters shown in any non-numeric column on browse view.');
            case 'LimitChars_name':
                return __('Limit column characters');
            case 'LoginCookieDeleteAll_desc':
                return __(
                    'If TRUE, logout deletes cookies for all servers; when set to FALSE, logout '
                    . 'only occurs for the current server. Setting this to FALSE makes it easy to '
                    . 'forget to log out from other servers when connected to multiple servers.'
                );
            case 'LoginCookieDeleteAll_name':
                return __('Delete all cookies on logout');
            case 'LoginCookieRecall_desc':
                return __(
                    'Define whether the previous login should be recalled or not in '
                    . '[kbd]cookie[/kbd] authentication mode.'
                );
            case 'LoginCookieRecall_name':
                return __('Recall user name');
            case 'LoginCookieStore_desc':
                return __(
                    'Defines how long (in seconds) a login cookie should be stored in browser. '
                    . 'The default of 0 means that it will be kept for the existing session only, '
                    . 'and will be deleted as soon as you close the browser window. This is '
                    . 'recommended for non-trusted environments.'
                );
            case 'LoginCookieStore_name':
                return __('Login cookie store');
            case 'LoginCookieValidity_desc':
                return __('Define how long (in seconds) a login cookie is valid.');
            case 'LoginCookieValidity_name':
                return __('Login cookie validity');
            case 'LongtextDoubleTextarea_desc':
                return __('Double size of textarea for LONGTEXT columns.');
            case 'LongtextDoubleTextarea_name':
                return __('Bigger textarea for LONGTEXT');
            case 'MaxCharactersInDisplayedSQL_desc':
                return __('Maximum number of characters used when a SQL query is displayed.');
            case 'MaxCharactersInDisplayedSQL_name':
                return __('Maximum displayed SQL length');
            case 'MaxDbList_cmt':
                return __('Users cannot set a higher value');
            case 'MaxDbList_desc':
                return __('Maximum number of databases displayed in database list.');
            case 'MaxDbList_name':
                return __('Maximum databases');
            case 'FirstLevelNavigationItems_desc':
                return __(
                    'The number of items that can be displayed on each page on the first level'
                    . ' of the navigation tree.'
                );
            case 'FirstLevelNavigationItems_name':
                return __('Maximum items on first level');
            case 'MaxNavigationItems_desc':
                return __('The number of items that can be displayed on each page of the navigation tree.');
            case 'MaxNavigationItems_name':
                return __('Maximum items in branch');
            case 'MaxRows_desc':
                return __(
                    'Number of rows displayed when browsing a result set. If the result set '
                    . 'contains more rows, "Previous" and "Next" links will be '
                    . 'shown.'
                );
            case 'MaxRows_name':
                return __('Maximum number of rows to display');
            case 'MaxTableList_cmt':
                return __('Users cannot set a higher value');
            case 'MaxTableList_desc':
                return __('Maximum number of tables displayed in table list.');
            case 'MaxTableList_name':
                return __('Maximum tables');
            case 'MemoryLimit_desc':
                return __(
                    'The number of bytes a script is allowed to allocate, eg. [kbd]32M[/kbd] '
                    . '([kbd]-1[/kbd] for no limit and [kbd]0[/kbd] for no change).'
                );
            case 'MemoryLimit_name':
                return __('Memory limit');
            case 'ShowDatabasesNavigationAsTree_desc':
                return __('In the navigation panel, replaces the database tree with a selector');
            case 'ShowDatabasesNavigationAsTree_name':
                return __('Show databases navigation as tree');
            case 'NavigationWidth_name':
                return __('Navigation panel width');
            case 'NavigationWidth_desc':
                return __('Set to 0 to collapse navigation panel.');
            case 'NavigationLinkWithMainPanel_desc':
                return __('Link with main panel by highlighting the current database or table.');
            case 'NavigationLinkWithMainPanel_name':
                return __('Link with main panel');
            case 'NavigationDisplayLogo_desc':
                return __('Show logo in navigation panel.');
            case 'NavigationDisplayLogo_name':
                return __('Display logo');
            case 'NavigationLogoLink_desc':
                return __('URL where logo in the navigation panel will point to.');
            case 'NavigationLogoLink_name':
                return __('Logo link URL');
            case 'NavigationLogoLinkWindow_desc':
                return __(
                    'Open the linked page in the main window ([kbd]main[/kbd]) or in a new one '
                    . '([kbd]new[/kbd]).'
                );
            case 'NavigationLogoLinkWindow_name':
                return __('Logo link target');
            case 'NavigationDisplayServers_desc':
                return __('Display server choice at the top of the navigation panel.');
            case 'NavigationDisplayServers_name':
                return __('Display servers selection');
            case 'NavigationTreeDefaultTabTable_name':
                return __('Target for quick access icon');
            case 'NavigationTreeDefaultTabTable2_name':
                return __('Target for second quick access icon');
            case 'NavigationTreeDisplayItemFilterMinimum_desc':
                return __(
                    'Defines the minimum number of items (tables, views, routines and events) to '
                    . 'display a filter box.'
                );
            case 'NavigationTreeDisplayItemFilterMinimum_name':
                return __('Minimum number of items to display the filter box');
            case 'NavigationTreeDisplayDbFilterMinimum_name':
                return __('Minimum number of databases to display the database filter box');
            case 'NavigationTreeEnableGrouping_desc':
                return __(
                    'Group items in the navigation tree (determined by the separator defined in ' .
                    'the Databases and Tables tabs above).'
                );
            case 'NavigationTreeEnableGrouping_name':
                return __('Group items in the tree');
            case 'NavigationTreeDbSeparator_desc':
                return __('String that separates databases into different tree levels.');
            case 'NavigationTreeDbSeparator_name':
                return __('Database tree separator');
            case 'NavigationTreeTableSeparator_desc':
                return __('String that separates tables into different tree levels.');
            case 'NavigationTreeTableSeparator_name':
                return __('Table tree separator');
            case 'NavigationTreeTableLevel_name':
                return __('Maximum table tree depth');
            case 'NavigationTreePointerEnable_desc':
                return __('Highlight server under the mouse cursor.');
            case 'NavigationTreePointerEnable_name':
                return __('Enable highlighting');
            case 'NavigationTreeEnableExpansion_desc':
                return __('Whether to offer the possibility of tree expansion in the navigation panel.');
            case 'NavigationTreeEnableExpansion_name':
                return __('Enable navigation tree expansion');
            case 'NavigationTreeShowTables_name':
                return __('Show tables in tree');
            case 'NavigationTreeShowTables_desc':
                return __('Whether to show tables under database in the navigation tree');
            case 'NavigationTreeShowViews_name':
                return __('Show views in tree');
            case 'NavigationTreeShowViews_desc':
                return __('Whether to show views under database in the navigation tree');
            case 'NavigationTreeShowFunctions_name':
                return __('Show functions in tree');
            case 'NavigationTreeShowFunctions_desc':
                return __('Whether to show functions under database in the navigation tree');
            case 'NavigationTreeShowProcedures_name':
                return __('Show procedures in tree');
            case 'NavigationTreeShowProcedures_desc':
                return __('Whether to show procedures under database in the navigation tree');
            case 'NavigationTreeShowEvents_name':
                return __('Show events in tree');
            case 'NavigationTreeShowEvents_desc':
                return __('Whether to show events under database in the navigation tree');
            case 'NumRecentTables_desc':
                return __('Maximum number of recently used tables; set 0 to disable.');
            case 'NumFavoriteTables_desc':
                return __('Maximum number of favorite tables; set 0 to disable.');
            case 'NumRecentTables_name':
                return __('Recently used tables');
            case 'NumFavoriteTables_name':
                return __('Favorite tables');
            case 'RowActionLinks_desc':
                return __('These are Edit, Copy and Delete links.');
            case 'RowActionLinks_name':
                return __('Where to show the table row links');
            case 'RowActionLinksWithoutUnique_desc':
                return __('Whether to show row links even in the absence of a unique key.');
            case 'RowActionLinksWithoutUnique_name':
                return __('Show row links anyway');
            case 'DisableShortcutKeys_name':
                return __('Disable shortcut keys');
            case 'DisableShortcutKeys_desc':
                return __('Disable shortcut keys');
            case 'NaturalOrder_desc':
                return __('Use natural order for sorting table and database names.');
            case 'NaturalOrder_name':
                return __('Natural order');
            case 'TableNavigationLinksMode_desc':
                return __('Use only icons, only text or both.');
            case 'TableNavigationLinksMode_name':
                return __('Table navigation bar');
            case 'OBGzip_desc':
                return __('Use GZip output buffering for increased speed in HTTP transfers.');
            case 'OBGzip_name':
                return __('GZip output buffering');
            case 'Order_desc':
                return __(
                    '[kbd]SMART[/kbd] - i.e. descending order for columns of type TIME, DATE, '
                    . 'DATETIME and TIMESTAMP, ascending order otherwise.'
                );
            case 'Order_name':
                return __('Default sorting order');
            case 'PersistentConnections_desc':
                return __('Use persistent connections to MySQL databases.');
            case 'PersistentConnections_name':
                return __('Persistent connections');
            case 'PmaNoRelation_DisableWarning_desc':
                return __(
                    'Disable the default warning that is displayed on the database details '
                    . 'Structure page if any of the required tables for the phpMyAdmin '
                    . 'configuration storage could not be found.'
                );
            case 'PmaNoRelation_DisableWarning_name':
                return __('Missing phpMyAdmin configuration storage tables');
            case 'ReservedWordDisableWarning_desc':
                return __(
                    'Disable the default warning that is displayed on the Structure page if column '
                    . 'names in a table are reserved MySQL words.'
                );
            case 'ReservedWordDisableWarning_name':
                return __('MySQL reserved word warning');
            case 'TabsMode_desc':
                return __('Use only icons, only text or both.');
            case 'TabsMode_name':
                return __('How to display the menu tabs');
            case 'ActionLinksMode_desc':
                return __('Use only icons, only text or both.');
            case 'ActionLinksMode_name':
                return __('How to display various action links');
            case 'ProtectBinary_desc':
                return __('Disallow BLOB and BINARY columns from editing.');
            case 'ProtectBinary_name':
                return __('Protect binary columns');
            case 'QueryHistoryDB_desc':
                return __(
                    'Enable if you want DB-based query history (requires phpMyAdmin configuration '
                    . 'storage). If disabled, this utilizes JS-routines to display query history '
                    . '(lost by window close).'
                );
            case 'QueryHistoryDB_name':
                return __('Permanent query history');
            case 'QueryHistoryMax_cmt':
                return __('Users cannot set a higher value');
            case 'QueryHistoryMax_desc':
                return __('How many queries are kept in history.');
            case 'QueryHistoryMax_name':
                return __('Query history length');
            case 'RecodingEngine_desc':
                return __('Select which functions will be used for character set conversion.');
            case 'RecodingEngine_name':
                return __('Recoding engine');
            case 'RememberSorting_desc':
                return __('When browsing tables, the sorting of each table is remembered.');
            case 'RememberSorting_name':
                return __('Remember table\'s sorting');
            case 'TablePrimaryKeyOrder_desc':
                return __('Default sort order for tables with a primary key.');
            case 'TablePrimaryKeyOrder_name':
                return __('Primary key default sort order');
            case 'RepeatCells_desc':
                return __('Repeat the headers every X cells, [kbd]0[/kbd] deactivates this feature.');
            case 'RepeatCells_name':
                return __('Repeat headers');
            case 'GridEditing_name':
                return __('Grid editing: trigger action');
            case 'RelationalDisplay_name':
                return __('Relational display');
            case 'RelationalDisplay_desc':
                return __('For display Options');
            case 'SaveCellsAtOnce_name':
                return __('Grid editing: save all edited cells at once');
            case 'SaveDir_desc':
                return __('Directory where exports can be saved on server.');
            case 'SaveDir_name':
                return __('Save directory');
            case 'Servers_AllowDeny_order_desc':
                return __('Leave blank if not used.');
            case 'Servers_AllowDeny_order_name':
                return __('Host authorization order');
            case 'Servers_AllowDeny_rules_desc':
                return __('Leave blank for defaults.');
            case 'Servers_AllowDeny_rules_name':
                return __('Host authorization rules');
            case 'Servers_AllowNoPassword_name':
                return __('Allow logins without a password');
            case 'Servers_AllowRoot_name':
                return __('Allow root login');
            case 'Servers_SessionTimeZone_name':
                return __('Session timezone');
            case 'Servers_SessionTimeZone_desc':
                return __(
                    'Sets the effective timezone; possibly different than the one from your '
                    .  'database server'
                );
            case 'Servers_auth_http_realm_desc':
                return __('HTTP Basic Auth Realm name to display when doing HTTP Auth.');
            case 'Servers_auth_http_realm_name':
                return __('HTTP Realm');
            case 'Servers_auth_type_desc':
                return __('Authentication method to use.');
            case 'Servers_auth_type_name':
                return __('Authentication type');
            case 'Servers_bookmarktable_desc':
                return __(
                    'Leave blank for no [doc@bookmarks@]bookmark[/doc] '
                    . 'support, suggested: [kbd]pma__bookmark[/kbd]'
                );
            case 'Servers_bookmarktable_name':
                return __('Bookmark table');
            case 'Servers_column_info_desc':
                return __(
                    'Leave blank for no column comments/mime types, suggested: '
                    . '[kbd]pma__column_info[/kbd].'
                );
            case 'Servers_column_info_name':
                return __('Column information table');
            case 'Servers_compress_desc':
                return __('Compress connection to MySQL server.');
            case 'Servers_compress_name':
                return __('Compress connection');
            case 'Servers_controlpass_name':
                return __('Control user password');
            case 'Servers_controluser_desc':
                return __(
                    'A special MySQL user configured with limited permissions, more information '
                    . 'available on [doc@linked-tables]documentation[/doc].'
                );
            case 'Servers_controluser_name':
                return __('Control user');
            case 'Servers_controlhost_desc':
                return __(
                    'An alternate host to hold the configuration storage; leave blank to use the '
                    . 'already defined host.'
                );
            case 'Servers_controlhost_name':
                return __('Control host');
            case 'Servers_controlport_desc':
                return __(
                    'An alternate port to connect to the host that holds the configuration storage; '
                    . 'leave blank to use the default port, or the already defined port, if the '
                    . 'controlhost equals host.'
                );
            case 'Servers_controlport_name':
                return __('Control port');
            case 'Servers_hide_db_desc':
                return __('Hide databases matching regular expression (PCRE).');
            case 'Servers_DisableIS_desc':
                return __(
                    'More information on [a@https://github.com/phpmyadmin/phpmyadmin/issues/8970]phpMyAdmin '
                    .  'issue tracker[/a] and [a@https://bugs.mysql.com/19588]MySQL Bugs[/a]'
                );
            case 'Servers_DisableIS_name':
                return __('Disable use of INFORMATION_SCHEMA');
            case 'Servers_hide_db_name':
                return __('Hide databases');
            case 'Servers_history_desc':
                return __(
                    'Leave blank for no SQL query history support, suggested: '
                    . '[kbd]pma__history[/kbd].'
                );
            case 'Servers_history_name':
                return __('SQL query history table');
            case 'Servers_host_desc':
                return __('Hostname where MySQL server is running.');
            case 'Servers_host_name':
                return __('Server hostname');
            case 'Servers_LogoutURL_name':
                return __('Logout URL');
            case 'Servers_MaxTableUiprefs_desc':
                return __(
                    'Limits number of table preferences which are stored in database, the oldest '
                    . 'records are automatically removed.'
                );
            case 'Servers_MaxTableUiprefs_name':
                return __('Maximal number of table preferences to store');
            case 'Servers_savedsearches_name':
                return __('QBE saved searches table');
            case 'Servers_savedsearches_desc':
                return __(
                    'Leave blank for no QBE saved searches support, suggested: '
                    . '[kbd]pma__savedsearches[/kbd].'
                );
            case 'Servers_export_templates_name':
                return __('Export templates table');
            case 'Servers_export_templates_desc':
                return __(
                    'Leave blank for no export template support, suggested: '
                    . '[kbd]pma__export_templates[/kbd].'
                );
            case 'Servers_central_columns_name':
                return __('Central columns table');
            case 'Servers_central_columns_desc':
                return __(
                    'Leave blank for no central columns support, suggested: '
                    . '[kbd]pma__central_columns[/kbd].'
                );
            case 'Servers_only_db_desc':
                return __(
                    'You can use MySQL wildcard characters (% and _), escape them if you want to '
                    . 'use their literal instances, i.e. use [kbd]\'my\_db\'[/kbd] and not '
                    . '[kbd]\'my_db\'[/kbd].'
                );
            case 'Servers_only_db_name':
                return __('Show only listed databases');
            case 'Servers_password_desc':
                return __('Leave empty if not using config auth.');
            case 'Servers_password_name':
                return __('Password for config auth');
            case 'Servers_pdf_pages_desc':
                return __('Leave blank for no PDF schema support, suggested: [kbd]pma__pdf_pages[/kbd].');
            case 'Servers_pdf_pages_name':
                return __('PDF schema: pages table');
            case 'Servers_pmadb_desc':
                return __(
                    'Database used for relations, bookmarks, and PDF features. See '
                    . '[doc@linked-tables]pmadb[/doc] for complete information. '
                    . 'Leave blank for no support. Suggested: [kbd]phpmyadmin[/kbd].'
                );
            case 'Servers_pmadb_name':
                return __('Database name');
            case 'Servers_port_desc':
                return __('Port on which MySQL server is listening, leave empty for default.');
            case 'Servers_port_name':
                return __('Server port');
            case 'Servers_recent_desc':
                return __(
                    'Leave blank for no "persistent" recently used tables across sessions, '
                    . 'suggested: [kbd]pma__recent[/kbd].'
                );
            case 'Servers_recent_name':
                return __('Recently used table');
            case 'Servers_favorite_desc':
                return __(
                    'Leave blank for no "persistent" favorite tables across sessions, '
                    . 'suggested: [kbd]pma__favorite[/kbd].'
                );
            case 'Servers_favorite_name':
                return __('Favorites table');
            case 'Servers_relation_desc':
                return __(
                    'Leave blank for no '
                    . '[doc@relations@]relation-links[/doc] support, '
                    . 'suggested: [kbd]pma__relation[/kbd].'
                );
            case 'Servers_relation_name':
                return __('Relation table');
            case 'Servers_SignonSession_desc':
                return __(
                    'See [doc@authentication-modes]authentication '
                    . 'types[/doc] for an example.'
                );
            case 'Servers_SignonSession_name':
                return __('Signon session name');
            case 'Servers_SignonURL_name':
                return __('Signon URL');
            case 'Servers_socket_desc':
                return __('Socket on which MySQL server is listening, leave empty for default.');
            case 'Servers_socket_name':
                return __('Server socket');
            case 'Servers_ssl_desc':
                return __('Enable SSL for connection to MySQL server.');
            case 'Servers_ssl_name':
                return __('Use SSL');
            case 'Servers_table_coords_desc':
                return __('Leave blank for no PDF schema support, suggested: [kbd]pma__table_coords[/kbd].');
            case 'Servers_table_coords_name':
                return __('Designer and PDF schema: table coordinates');
            case 'Servers_table_info_desc':
                return __(
                    'Table to describe the display columns, leave blank for no support; '
                    . 'suggested: [kbd]pma__table_info[/kbd].'
                );
            case 'Servers_table_info_name':
                return __('Display columns table');
            case 'Servers_table_uiprefs_desc':
                return __(
                    'Leave blank for no "persistent" tables\' UI preferences across sessions, '
                    . 'suggested: [kbd]pma__table_uiprefs[/kbd].'
                );
            case 'Servers_table_uiprefs_name':
                return __('UI preferences table');
            case 'Servers_tracking_add_drop_database_desc':
                return __(
                    'Whether a DROP DATABASE IF EXISTS statement will be added as first line to '
                    . 'the log when creating a database.'
                );
            case 'Servers_tracking_add_drop_database_name':
                return __('Add DROP DATABASE');
            case 'Servers_tracking_add_drop_table_desc':
                return __(
                    'Whether a DROP TABLE IF EXISTS statement will be added as first line to the '
                    . 'log when creating a table.'
                );
            case 'Servers_tracking_add_drop_table_name':
                return __('Add DROP TABLE');
            case 'Servers_tracking_add_drop_view_desc':
                return __(
                    'Whether a DROP VIEW IF EXISTS statement will be added as first line to the '
                    . 'log when creating a view.'
                );
            case 'Servers_tracking_add_drop_view_name':
                return __('Add DROP VIEW');
            case 'Servers_tracking_default_statements_desc':
                return __('Defines the list of statements the auto-creation uses for new versions.');
            case 'Servers_tracking_default_statements_name':
                return __('Statements to track');
            case 'Servers_tracking_desc':
                return __(
                    'Leave blank for no SQL query tracking support, suggested: '
                    . '[kbd]pma__tracking[/kbd].'
                );
            case 'Servers_tracking_name':
                return __('SQL query tracking table');
            case 'Servers_tracking_version_auto_create_desc':
                return __(
                    'Whether the tracking mechanism creates versions for tables and views '
                    . 'automatically.'
                );
            case 'Servers_tracking_version_auto_create_name':
                return __('Automatically create versions');
            case 'Servers_userconfig_desc':
                return __(
                    'Leave blank for no user preferences storage in database, suggested: '
                    .  '[kbd]pma__userconfig[/kbd].'
                );
            case 'Servers_userconfig_name':
                return __('User preferences storage table');
            case 'Servers_users_desc':
                return __(
                    'Both this table and the user groups table are required to enable the ' .
                    'configurable menus feature; leaving either one of them blank will disable ' .
                    'this feature, suggested: [kbd]pma__users[/kbd].'
                );
            case 'Servers_users_name':
                return __('Users table');
            case 'Servers_usergroups_desc':
                return __(
                    'Both this table and the users table are required to enable the configurable ' .
                    'menus feature; leaving either one of them blank will disable this feature, ' .
                    'suggested: [kbd]pma__usergroups[/kbd].'
                );
            case 'Servers_usergroups_name':
                return __('User groups table');
            case 'Servers_navigationhiding_desc':
                return __(
                    'Leave blank to disable the feature to hide and show navigation items, ' .
                    'suggested: [kbd]pma__navigationhiding[/kbd].'
                );
            case 'Servers_navigationhiding_name':
                return __('Hidden navigation items table');
            case 'Servers_user_desc':
                return __('Leave empty if not using config auth.');
            case 'Servers_user_name':
                return __('User for config auth');
            case 'Servers_verbose_desc':
                return __(
                    'A user-friendly description of this server. Leave blank to display the ' .
                    'hostname instead.'
                );
            case 'Servers_verbose_name':
                return __('Verbose name of this server');
            case 'ShowAll_desc':
                return __('Whether a user should be displayed a "show all (rows)" button.');
            case 'ShowAll_name':
                return __('Allow to display all the rows');
            case 'ShowChgPassword_desc':
                return __(
                    'Please note that enabling this has no effect with [kbd]config[/kbd] ' .
                    'authentication mode because the password is hard coded in the configuration ' .
                    'file; this does not limit the ability to execute the same command directly.'
                );
            case 'ShowChgPassword_name':
                return __('Show password change form');
            case 'ShowCreateDb_name':
                return __('Show create database form');
            case 'ShowDbStructureComment_desc':
                return __('Show or hide a column displaying the comments for all tables.');
            case 'ShowDbStructureComment_name':
                return __('Show table comments');
            case 'ShowDbStructureCreation_desc':
                return __('Show or hide a column displaying the Creation timestamp for all tables.');
            case 'ShowDbStructureCreation_name':
                return __('Show creation timestamp');
            case 'ShowDbStructureLastUpdate_desc':
                return __('Show or hide a column displaying the Last update timestamp for all tables.');
            case 'ShowDbStructureLastUpdate_name':
                return __('Show last update timestamp');
            case 'ShowDbStructureLastCheck_desc':
                return __('Show or hide a column displaying the Last check timestamp for all tables.');
            case 'ShowDbStructureLastCheck_name':
                return __('Show last check timestamp');
            case 'ShowDbStructureCharset_desc':
                return __('Show or hide a column displaying the charset for all tables.');
            case 'ShowDbStructureCharset_name':
                return __('Show table charset');
            case 'ShowFieldTypesInDataEditView_desc':
                return __(
                    'Defines whether or not type fields should be initially displayed in ' .
                    'edit/insert mode.'
                );
            case 'ShowFieldTypesInDataEditView_name':
                return __('Show field types');
            case 'ShowFunctionFields_desc':
                return __('Display the function fields in edit/insert mode.');
            case 'ShowFunctionFields_name':
                return __('Show function fields');
            case 'ShowHint_desc':
                return __('Whether to show hint or not.');
            case 'ShowHint_name':
                return __('Show hint');
            case 'ShowPhpInfo_desc':
                return __(
                    'Shows link to [a@https://php.net/manual/function.phpinfo.php]phpinfo()[/a] ' .
                    'output.'
                );
            case 'ShowPhpInfo_name':
                return __('Show phpinfo() link');
            case 'ShowServerInfo_name':
                return __('Show detailed MySQL server information');
            case 'ShowSQL_desc':
                return __('Defines whether SQL queries generated by phpMyAdmin should be displayed.');
            case 'ShowSQL_name':
                return __('Show SQL queries');
            case 'RetainQueryBox_desc':
                return __('Defines whether the query box should stay on-screen after its submission.');
            case 'RetainQueryBox_name':
                return __('Retain query box');
            case 'ShowStats_desc':
                return __('Allow to display database and table statistics (eg. space usage).');
            case 'ShowStats_name':
                return __('Show statistics');
            case 'SkipLockedTables_desc':
                return __('Mark used tables and make it possible to show databases with locked tables.');
            case 'SkipLockedTables_name':
                return __('Skip locked tables');
            case 'SQLQuery_Edit_name':
                return __('Edit');
            case 'SQLQuery_Explain_name':
                return __('Explain SQL');
            case 'SQLQuery_Refresh_name':
                return __('Refresh');
            case 'SQLQuery_ShowAsPHP_name':
                return __('Create PHP code');
            case 'SuhosinDisableWarning_desc':
                return __(
                    'Disable the default warning that is displayed on the main page if Suhosin is ' .
                    'detected.'
                );
            case 'SuhosinDisableWarning_name':
                return __('Suhosin warning');
            case 'LoginCookieValidityDisableWarning_desc':
                return __(
                    'Disable the default warning that is displayed on the main page if the value ' .
                    'of the PHP setting session.gc_maxlifetime is less than the value of ' .
                    '`LoginCookieValidity`.'
                );
            case 'LoginCookieValidityDisableWarning_name':
                return __('Login cookie validity warning');
            case 'TextareaCols_desc':
                return __(
                    'Textarea size (columns) in edit mode, this value will be emphasized for SQL ' .
                    'query textareas (*2).'
                );
            case 'TextareaCols_name':
                return __('Textarea columns');
            case 'TextareaRows_desc':
                return __(
                    'Textarea size (rows) in edit mode, this value will be emphasized for SQL ' .
                    'query textareas (*2).'
                );
            case 'TextareaRows_name':
                return __('Textarea rows');
            case 'TitleDatabase_desc':
                return __('Title of browser window when a database is selected.');
            case 'TitleDatabase_name':
                return __('Database');
            case 'TitleDefault_desc':
                return __('Title of browser window when nothing is selected.');
            case 'TitleDefault_name':
                return __('Default title');
            case 'TitleServer_desc':
                return __('Title of browser window when a server is selected.');
            case 'TitleServer_name':
                return __('Server');
            case 'TitleTable_desc':
                return __('Title of browser window when a table is selected.');
            case 'TitleTable_name':
                return __('Table');
            case 'TrustedProxies_desc':
                return __(
                    'Input proxies as [kbd]IP: trusted HTTP header[/kbd]. The following example ' .
                    'specifies that phpMyAdmin should trust a HTTP_X_FORWARDED_FOR ' .
                    '(X-Forwarded-For) header coming from the proxy 1.2.3.4:[br][kbd]1.2.3.4: ' .
                    'HTTP_X_FORWARDED_FOR[/kbd].'
                );
            case 'TrustedProxies_name':
                return __('List of trusted proxies for IP allow/deny');
            case 'UploadDir_desc':
                return __('Directory on server where you can upload files for import.');
            case 'UploadDir_name':
                return __('Upload directory');
            case 'UseDbSearch_desc':
                return __('Allow for searching inside the entire database.');
            case 'UseDbSearch_name':
                return __('Use database search');
            case 'UserprefsDeveloperTab_desc':
                return __(
                    'When disabled, users cannot set any of the options below, regardless of the ' .
                    'checkbox on the right.'
                );
            case 'UserprefsDeveloperTab_name':
                return __('Enable the Developer tab in settings');
            case 'VersionCheck_desc':
                return __('Enables check for latest version on main phpMyAdmin page.');
            case 'VersionCheck_name':
                return __('Version check');
            case 'ProxyUrl_desc':
                return __(
                    'The url of the proxy to be used when retrieving the information about the ' .
                    'latest version of phpMyAdmin or when submitting error reports. You need this ' .
                    'if the server where phpMyAdmin is installed does not have direct access to ' .
                    'the internet. The format is: "hostname:portnumber".'
                );
            case 'ProxyUrl_name':
                return __('Proxy url');
            case 'ProxyUser_desc':
                return __(
                    'The username for authenticating with the proxy. By default, no ' .
                    'authentication is performed. If a username is supplied, Basic ' .
                    'Authentication will be performed. No other types of authentication are ' .
                    'currently supported.'
                );
            case 'ProxyUser_name':
                return __('Proxy username');
            case 'ProxyPass_desc':
                return __('The password for authenticating with the proxy.');
            case 'ProxyPass_name':
                return __('Proxy password');

            case 'ZipDump_desc':
                return __('Enable ZIP compression for import and export operations.');
            case 'ZipDump_name':
                return __('ZIP');
            case 'CaptchaLoginPublicKey_desc':
                return __('Enter your public key for your domain reCaptcha service.');
            case 'CaptchaLoginPublicKey_name':
                return __('Public key for reCaptcha');
            case 'CaptchaLoginPrivateKey_desc':
                return __('Enter your private key for your domain reCaptcha service.');
            case 'CaptchaLoginPrivateKey_name':
                return __('Private key for reCaptcha');

            case 'SendErrorReports_desc':
                return __('Choose the default action when sending error reports.');
            case 'SendErrorReports_name':
                return __('Send error reports');

            case 'ConsoleEnterExecutes_desc':
                return __(
                    'Queries are executed by pressing Enter (instead of Ctrl+Enter). New lines ' .
                    'will be inserted with Shift+Enter.'
                );
            case 'ConsoleEnterExecutes_name':
                return __('Enter executes queries in console');

            case 'ZeroConf_desc':
                return __(
                    'Enable Zero Configuration mode which lets you setup phpMyAdmin '
                    . 'configuration storage tables automatically.'
                );
            case 'ZeroConf_name':
                return __('Enable Zero Configuration mode');
            case 'Console_StartHistory_name':
                return __('Show query history at start');
            case 'Console_AlwaysExpand_name':
                return __('Always expand query messages');
            case 'Console_CurrentQuery_name':
                return __('Show current browsing query');
            case 'Console_EnterExecutes_name':
                return __('Execute queries on Enter and insert new line with Shift + Enter');
            case 'Console_DarkTheme_name':
                return __('Switch to dark theme');
            case 'Console_Height_name':
                return __('Console height');
            case 'Console_Mode_name':
                return __('Console mode');
            case 'Console_GroupQueries_name':
                return __('Group queries');
            case 'Console_Order_name':
                return __('Order');
            case 'Console_OrderBy_name':
                return __('Order by');
            case 'FontSize_name':
                return __('Font size');
            case 'DefaultConnectionCollation_name':
                return __('Server connection collation');
        }
        return null;
    }
}

