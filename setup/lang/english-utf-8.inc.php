<?php
/**
 * Setup script language file: English
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @copyright  Copyright (c) 2008, Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

// page titles
$str['page_servers_add'] = 'Add a new server';
$str['page_servers_edit'] = 'Edit server';
$str['Formset_features'] = 'Features';
$str['Formset_left_frame'] = 'Customize navigation frame';
$str['Formset_main_frame'] = 'Customize main frame';
$str['Formset_import'] = 'Customize import defaults';
$str['Formset_export'] = 'Customize export options';

// forms
$str['true'] = 'yes';
$str['false'] = 'no';
$str['Display'] = 'Display';
$str['Download'] = 'Download';
$str['Clear'] = 'Clear';
$str['Load'] = 'Load';
$str['Restore_default'] = 'Restore default value';
$str['Set_value'] = 'Set value: %s';
$str['Warning'] = 'Warning';
$str['Ignore_errors'] = 'Ignore errors';
$str['Revert_erroneous_fields'] = 'Try to revert erroneous fields to their default values';
$str['Show_form'] = 'Show form';

// main page
$str['Overview'] = 'Overview';
$str['Show_hidden_messages'] = 'Show hidden messages (#MSG_COUNT)';
$str['No_servers'] = 'There are no configured servers';
$str['New_server'] = 'New server';
$str['Default_language'] = 'Default language';
$str['Default_server'] = 'Default server';
$str['let_the_user_choose'] = 'let the user choose';
$str['-none-'] = '- none -';
$str['End_of_lne'] = 'End of line';
$str['Configuration_file'] = 'Configuration file';
$str['Homepage_link'] = 'phpMyAdmin homepage';
$str['Donate_link'] = 'Donate';
$str['Version_check_link'] = 'Check for latest version';

// main page messages
$str['Cannot_load_config'] = 'Cannot load or save configuration';
$str['Cannot_load_config_desc'] = 'Please create web server writable folder [em]config[/em] in phpMyAdmin top level directory as described in [a@../Documentation.html#setup_script]documentation[/a]. Otherwise you will be only able to download or display it.';
$str['Insecure_connection'] = 'Insecure connection';
$str['Insecure_connection_desc1'] = 'You are not using a secure connection, all data (including sensitive, like passwords) is transferred unencrypted!';
$str['Insecure_connection_desc2'] = 'If your server is also configured to accept HTTPS requests follow [a@%s]this link[/a] to use a secure connection.';
$str['Version_check'] = 'Version check';
$str['Version_check_wrapper_error'] = 'Neither URL wrapper nor CURL is available. Version check is not possible.';
$str['Version_check_data_error'] = 'Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.';
$str['Version_check_invalid'] = 'Got invalid version string from server';
$str['Version_check_unparsable'] = 'Unparsable version string';
$str['Version_check_new_available'] = 'New version of phpMyAdmin is available, you should consider upgrade. New version is %s, released on %s.';
$str['Version_check_new_available_svn'] = 'You are using subversion version, run [kbd]svn update[/kbd] :-).[br]The latest stable version is %s, released on %s.';
$str['Version_check_none'] = 'No newer stable version is available';
$str['Server_security_info_msg'] = 'If you feel this is necessary, use additional protection settings - [a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server_config]host authentication[/a] settings and [a@?page=form&amp;formset=features#tab_Security]trusted proxies list[/a]. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.';
$str['Server_ssl_msg'] = 'You should use SSL connections if your web server supports it';
$str['Server_extension_msg'] = 'You should use mysqli for performance reasons';
$str['Server_auth_config_msg'] = 'You set [kbd]config[/kbd] authentication type and included username and password for auto-login, which is not a desirable option for live hosts. Anyone who knows phpMyAdmin URL can directly access your phpMyAdmin panel. Set [a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server]authentication type[/a] to [kbd]cookie[/kbd] or [kbd]http[/kbd].';
$str['Server_no_password_root_msg'] = 'You allow for connecting to the server as root without a passowrd.';
$str['blowfish_secret_msg'] = 'You didn\'t have blowfish secret set and enabled cookie authentication so the key was generated for you. It is used to encrypt cookies.';
$str['blowfish_secret_length_msg'] = 'Key is too short, it should have at least 8 characters';
$str['blowfish_secret_chars_msg'] = 'Key should contain alphanumerics, letters [em]and[/em] special characters';
$str['ForceSSL_msg'] = 'This [a@?page=form&amp;formset=features#tab_Security]option[/a] should be enabled if your web server supports it';
$str['AllowArbitraryServer_msg'] = 'This [a@?page=form&amp;formset=features#tab_Security]option[/a] should be disabled as it allows attackers to bruteforce login to any MySQL server. If you feel this is necessary, use [a@?page=form&amp;formset=features#tab_Security]trusted proxies list[/a]. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.';
$str['LoginCookieValidity_msg'] = '[a@?page=form&formset=features#tab_Security]Login cookie validity[/a] should be should be set to 1800 seconds (30 minutes) at most. Values larger than 1800 may pose a security risk such as impersonation.';
$str['Directory_notice'] = 'This value should be double checked to ensure that this directory is neither world accessible nor readable or writable by other users on your server.';

// form errors
$str['error_form'] = 'Submitted form contains errors';
$str['error_missing_field_data'] = 'Missing data for %s';
$str['error_incorrect_port'] = 'Not a valid port number';
$str['error_incorrect_value'] = 'Incorrect value';
$str['error_incorrect_ip_address'] = 'Incorrect IP address: %s';
$str['error_nan_p'] = 'Not a positive number';
$str['error_nan_nneg'] = 'Not a non-negative number';
$str['error_empty_pmadb_user'] = 'Empty phpMyAdmin control user while using pmadb';
$str['error_empty_pmadb_password'] = 'Empty phpMyAdmin control user password while using pmadb';
$str['error_empty_user_for_config_auth'] = 'Empty username while using config authentication method';
$str['error_empty_signon_session'] = 'Empty signon session name while using signon authentication method';
$str['error_empty_signon_url'] = 'Empty signon URL while using signon authentication method';
$str['error_connection'] = 'Could not connect to MySQL server';

// form names
$str['Form_Server'] = 'Basic settings';
$str['Form_Server_desc'] = 'Enter server connection parameters';
$str['Form_Server_login_options'] = 'Signon login options';
$str['Form_Server_login_options_desc'] = 'Enter login options for signon authentication';
$str['Form_Server_config'] = 'Server configuration';
$str['Form_Server_config_desc'] = 'Advanced server configuration, do not change these options unless you know what they are for';
$str['Form_Server_pmadb'] = 'PMA database';
$str['Form_Server_pmadb_desc'] = 'Configure phpMyAdmin database to gain access to additional features, see [a@../Documentation.html#linked-tables]linked-tables infrastructure[/a] in documentation';
$str['Form_Import_export'] = 'Import / export';
$str['Form_Import_export_desc'] = 'Set import and export directories and compression options';
$str['Form_Security'] = 'Security';
$str['Form_Security_desc'] = 'Please note that phpMyAdmin is just a user interface and its features do not limit MySQL';
$str['Form_Sql_queries'] = 'SQL queries';
$str['Form_Sql_queries_desc'] = 'SQL queries settings, for SQL Query box options see [a@?page=form&amp;formset=main_frame#tab_Sql_box]Navigation frame[/a] settings';
$str['Form_Other_core_settings'] = 'Other core settings';
$str['Form_Other_core_settings_desc'] = 'Settings that didn\'t fit enywhere else';
$str['Form_Left_frame'] = 'Navigation frame';
$str['Form_Left_frame_desc'] = 'Customize appearance of the navigation frame';
$str['Form_Left_servers'] = 'Servers';
$str['Form_Left_servers_desc'] = 'Servers display options';
$str['Form_Left_databases'] = 'Databases';
$str['Form_Left_databases_desc'] = 'Databases display options';
$str['Form_Left_tables'] = 'Tables';
$str['Form_Left_tables_desc'] = 'Tables display options';
$str['Form_Main_frame'] = 'Main frame';
$str['Form_Startup'] = 'Startup';
$str['Form_Startup_desc'] = 'Customize startup page';
$str['Form_Browse'] = 'Browse mode';
$str['Form_Browse_desc'] = 'Customize browse mode';
$str['Form_Edit'] = 'Edit mode';
$str['Form_Edit_desc'] = 'Customize edit mode';
$str['Form_Tabs'] = 'Tabs display';
$str['Form_Tabs_desc'] = 'Choose how you want tabs to work';
$str['Form_Sql_box'] = 'SQL Query box';
$str['Form_Sql_box_desc'] = 'Customize links shown in SQL Query boxes';
$str['Form_Import'] = $GLOBALS['strImport'];
$str['Form_Import_desc'] = 'Customize default common import options';
$str['Form_Import_sql'] = $GLOBALS['strSQL'];
$str['Form_Import_sql_desc'] = 'Customize default SQL import options';
$str['Form_Import_csv'] = $GLOBALS['strCSV'];
$str['Form_Import_csv_desc'] = 'Customize default CSV import options';
$str['Form_Import_ldi'] = $GLOBALS['strLDI'];
$str['Form_Import_ldi_desc'] = 'Customize default CSV using LOAD DATA import options';
$str['Form_Export'] = $GLOBALS['strExport'];
$str['Form_Export_defaults'] = 'Defaults';
$str['Form_Export_defaults_desc'] = 'Customize default export options';

// Form: Server
$str['Servers/verbose_name'] = 'Verbose name of this server';
$str['Servers/verbose_desc'] = 'Hostname where MySQL server is running';
$str['Servers/host_name'] = 'Server hostname';
$str['Servers/host_desc'] = '';
$str['Servers/port_name'] = 'Server port';
$str['Servers/port_desc'] = 'Port on which MySQL server is listening, leave empty for default';
$str['Servers/socket_name'] = 'Server socket';
$str['Servers/socket_desc'] = 'Socket on which MySQL server is listening, leave empty for default';
$str['Servers/ssl_name'] = 'Use SSL';
$str['Servers/ssl_desc'] = '';
$str['Servers/connect_type_name'] = 'Connection type';
$str['Servers/connect_type_desc'] = 'How to connect to server, keep tcp if unsure';
$str['Servers/extension_name'] = 'PHP extension to use';
$str['Servers/extension_desc'] = 'What PHP extension to use, use mysqli if supported';
$str['Servers/compress_name'] = 'Compress connection';
$str['Servers/compress_desc'] = 'Compress connection to MySQL server';
$str['Servers/auth_type_name'] = 'Authentication type';
$str['Servers/auth_type_desc'] = 'Authentication method to use';
$str['Servers/user_name'] = 'User for config auth';
$str['Servers/user_desc'] = 'Leave empty if not using config auth';
$str['Servers/password_name'] = 'Password for config auth';
$str['Servers/password_desc'] = 'Leave empty if not using config auth';
$str['Servers/nopassword_name'] = 'Connect without password';
$str['Servers/nopassword_desc'] = 'Try to connect without password';

// Form: Server_login_options
$str['Servers/SignonSession_name'] = 'Signon session name';
$str['Servers/SignonSession_desc'] = 'See [a@http://wiki.cihar.com/pma/auth_types#signon]authentication types[/a] for an example';
$str['Servers/SignonURL_name'] = 'Signon URL';
$str['Servers/LogoutURL_name'] = 'Logout URL';
$str['Servers/auth_swekey_config_name'] = 'SweKey config file';
$str['Servers/auth_swekey_config_desc'] = 'Config file for [a@http://swekey.com]SweKey hardware authentication[/a], relative to phpMyAdmin root directory, eg. ./swekey.conf';

// Form: Server_config
$str['Servers/only_db_name'] = 'Show only listed databases';
$str['Servers/only_db_desc'] = 'You can use MySQL wildcard characters (% and _), escape them if you want to use their literal instances, i.e. use \'my\_db\' and not \'my_db\'';
$str['Servers/hide_db_name'] = 'Hide databases';
$str['Servers/hide_db_desc'] = 'Hide databases matching regular expression (PCRE)';
$str['Servers/AllowRoot_name'] = 'Allow root login';
$str['Servers/AllowNoPasswordRoot_name'] = 'Allow root without password';
$str['Servers/DisableIS_name'] = 'Disable use of INFORMATION_SCHEMA';
$str['Servers/DisableIS_desc'] = 'More information on [a@http://sf.net/support/tracker.php?aid=1849494]PMA bug tracker[/a] and [a@http://bugs.mysql.com/19588]MySQL Bugs[/a]';
$str['Servers/AllowDeny/order_name'] = 'Host authentication order';
$str['Servers/AllowDeny/order_desc'] = 'Leave blank if not used';
$str['Servers/AllowDeny/rules_name'] = 'Host authentication rules';
$str['Servers/AllowDeny/rules_desc'] = 'Leave blank for defaults';
$str['Servers/ShowDatabasesCommand_name'] = 'SHOW DATABASES command';
$str['Servers/ShowDatabasesCommand_desc'] = 'SQL command to fetch available databases';
$str['Servers/CountTables_name'] = 'Count tables';
$str['Servers/CountTables_desc'] = 'Count tables when showing database list';

// Form: Server_pmadb
$str['Servers/pmadb_name'] = 'PMA database';
$str['Servers/pmadb_desc'] = 'Database used for relations, bookmarks, and PDF features. See [a@http://wiki.cihar.com/pma/pmadb]pmadb[/a] for complete information. Leave blank for no support. Default: [kbd]phpmyadmin[/kbd]';
$str['Servers/controluser_name'] = 'Control user';
$str['Servers/controluser_desc'] = 'A special MySQL user configured with limited permissions, more information available on [a@http://wiki.cihar.com/pma/controluser]wiki[/a]';
$str['Servers/controlpass_name'] = 'Control user password';
$str['Servers/verbose_check_name'] = 'Verbose check';
$str['Servers/verbose_check_desc'] = 'Disable if you know that your pma_* tables are up to date. This prevents compatibility checks and thereby increases performance';
$str['Servers/bookmarktable_name'] = 'Bookmark table';
$str['Servers/bookmarktable_desc'] = 'Leave blank for no [a@http://wiki.cihar.com/pma/bookmark]bookmark[/a] support, default: [kbd]pma_bookmark[/kbd]';
$str['Servers/relation_name'] = 'Relation table';
$str['Servers/relation_desc'] = 'Leave blank for no [a@http://wiki.cihar.com/pma/relation]relation-links[/a] support, default: [kbd]pma_relation[/kbd]';
$str['Servers/table_info_name'] = 'Display fields table';
$str['Servers/table_info_desc'] = 'Table to describe the display fields, leave blank for no support; default: [kbd]pma_table_info[/kbd]';
$str['Servers/table_coords_name'] = 'PDF schema: table coordinates';
$str['Servers/table_coords_desc'] = 'Leave blank for no PDF schema support, default: [kbd]pma_table_coords[/kbd]';
$str['Servers/pdf_pages_name'] = 'PDF schema: pages table';
$str['Servers/pdf_pages_desc'] = 'Leave blank for no PDF schema support, default: [kbd]pma_pdf_pages[/kbd]';
$str['Servers/column_info_name'] = 'Column information table';
$str['Servers/column_info_desc'] = 'Leave blank for no column comments/mime types, default: [kbd]pma_column_info[/kbd]';
$str['Servers/history_name'] = 'SQL query history table';
$str['Servers/history_desc'] = 'Leave blank for no SQL query history support, default: [kbd]pma_history[/kbd]';
$str['Servers/designer_coords_name'] = 'Designer table';
$str['Servers/designer_coords_desc'] = 'Leave blank for no Designer support, default: [kbd]designer_coords[/kbd]';

// Form: Import_export
$str['UploadDir_name'] = 'Upload directory';
$str['UploadDir_desc'] = 'Directory on server where you can upload files for import';
$str['SaveDir_name'] = 'Save directory';
$str['SaveDir_desc'] = 'Directory where exports can be saved on server';
$str['AllowAnywhereRecoding_name'] = 'Allow character set conversion';
$str['DefaultCharset_name'] = 'Default character set';
$str['DefaultCharset_desc'] = 'Default character set used for conversions';
$str['RecodingEngine_name'] = 'Recoding engine';
$str['RecodingEngine_desc'] = 'Select which functions will be used for character set conversion';
$str['IconvExtraParams_name'] = 'Extra parameters for iconv';
$str['ZipDump_name'] = 'ZIP';
$str['ZipDump_desc'] = 'Enable [a@http://en.wikipedia.org/wiki/ZIP_(file_format)]ZIP[/a] compression for import and export operations';
$str['GZipDump_name'] = 'GZip';
$str['GZipDump_desc'] = 'Enable [a@http://en.wikipedia.org/wiki/Gzip]gzip[/a] compression for import and export operations';
$str['BZipDump_name'] = 'Bzip2';
$str['BZipDump_desc'] = 'Enable [a@http://en.wikipedia.org/wiki/Bzip2]bzip2[/a] compression for import and export operations';
$str['CompressOnFly_name'] = 'Compress on the fly';
$str['CompressOnFly_desc'] = 'Compress gzip/bzip2 exports on the fly without the need for much memory; if you encounter problems with created gzip/bzip2 files disable this feature';

// Form: Security
$str['blowfish_secret_name'] = 'Blowfish secret';
$str['blowfish_secret_desc'] = 'Secret passphrase used for encrypting cookies in [kbd]cookie[/kbd] authentication';
$str['ForceSSL_name'] = 'Force SSL connection';
$str['ForceSSL_desc'] = 'Force secured connection while using phpMyAdmin';
$str['CheckConfigurationPermissions_name'] = 'Check config file permissions';
$str['TrustedProxies_name'] = 'List of trusted proxies for IP allow/deny';
$str['TrustedProxies_desc'] = 'Input proxies as [kbd]IP: trusted HTTP header[/kbd]. The following example specifies that phpMyAdmin should trust a HTTP_X_FORWARDED_FOR (X-Forwarded-For) header coming from the proxy 1.2.3.4:[br][kbd]1.2.3.4: HTTP_X_FORWARDED_FOR[/kbd]';
$str['AllowUserDropDatabase_name'] = 'Show &quot;Drop database&quot; link to normal users';
$str['AllowArbitraryServer_name'] = 'Allow login to any MySQL server';
$str['AllowArbitraryServer_desc'] = 'If enabled user can enter any MySQL server in login form for cookie auth';
$str['LoginCookieRecall_name'] = 'Recall user name';
$str['LoginCookieRecall_desc'] = 'Define whether the previous login should be recalled or not in cookie authentication mode';
$str['LoginCookieValidity_name'] = 'Login cookie validity';
$str['LoginCookieValidity_desc'] = 'Define how long (in seconds) a login cookie is valid';
$str['LoginCookieStore_name'] = 'Login cookie store';
$str['LoginCookieStore_desc'] = 'Define how long (in seconds) a login cookie should be stored in browser. Default 0 means that it will be kept for existing session only, that is it will be deleted as soon as you close the browser window. This is recommended for non-trusted environments.';
$str['LoginCookieDeleteAll_name'] = 'Delete all cookies on logout';
$str['LoginCookieDeleteAll_desc'] = 'If enabled logout deletes cookies for all servers, otherwise only for current one. Setting this to FALSE makes it easy to forget to log out from other server, when you are using more of them.';

// Form: Sql_queries
$str['ShowSQL_name'] = 'Show SQL queries';
$str['ShowSQL_desc'] = 'Defines whether SQL queries generated by phpMyAdmin should be displayed';
$str['Confirm_name'] = 'Confirm DROP queries';
$str['Confirm_desc'] = 'Whether a warning (&quot;Are your really sure...&quot;) should be displayed when you\'re about to lose data';
$str['QueryHistoryDB_name'] = 'Permanent query history';
$str['QueryHistoryDB_desc'] = 'Enable if you want DB-based query history (requires pmadb). If disabled, this utilizes JS-routines to display query history (lost by window close).';
$str['QueryHistoryMax_name'] = 'Query history length';
$str['QueryHistoryMax_desc'] = 'How many queries are kept in history';
$str['IgnoreMultiSubmitErrors_name'] = 'Ignore multiple statement errors';
$str['IgnoreMultiSubmitErrors_desc'] = 'If enabled PMA continues computing multiple-statement queries even if one of the queries failed';
$str['VerboseMultiSubmit_name'] = 'Verbose multiple statements';
$str['VerboseMultiSubmit_desc'] = 'Show affected rows of each statement on multiple-statement queries. See libraries/import.lib.php for defaults on how many queries a statement may contain.';

// Form: Other_core_options
$str['MaxDbList_name'] = 'Maximum databases';
$str['MaxDbList_desc'] = 'Maximum number of databases displayed in left frame and database list';
$str['MaxTableList_name'] = 'Maximum tables';
$str['MaxTableList_desc'] = 'Maximum number of tables displayed in table list';
$str['MaxCharactersInDisplayedSQL_name'] = 'Maximum displayed SQL length';
$str['MaxCharactersInDisplayedSQL_desc'] = 'Maximum number of characters used when a SQL query is displayed';
$str['OBGzip_name'] = 'GZip output buffering';
$str['OBGzip_desc'] = 'use GZip output buffering for increased speed in HTTP transfers';
$str['PersistentConnections_name'] = 'Persistent connections';
$str['PersistentConnections_desc'] = 'Use persistent connections to MySQL databases';
$str['ExecTimeLimit_name'] = 'Maximum execution time';
$str['ExecTimeLimit_desc'] = 'Set the number of seconds a script is allowed to run ([kbd]0[/kbd] for no limit)';
$str['MemoryLimit_name'] = 'Memory limit';
$str['MemoryLimit_desc'] = 'The number of bytes a script is allowed to allocate, eg. [kbd]32M[/kbd] ([kbd]0[/kbd] for no limit)';
$str['SkipLockedTables_name'] = 'Skip locked tables';
$str['SkipLockedTables_desc'] = 'Mark used tables and make it possible to show databases with locked tables';
$str['UseDbSearch_name'] = 'Use database search';
$str['UseDbSearch_desc'] = 'Allow for searching inside the entire database';

// Form: Left_frame
$str['LeftFrameLight_name'] = 'Use light version';
$str['LeftFrameLight_desc'] = 'Disable this if you want to see all databases at once';
$str['LeftDisplayLogo_name'] = 'Display logo';
$str['LeftDisplayLogo_desc'] = 'Show logo in left frame';
$str['LeftLogoLink_name'] = 'Logo link URL';
$str['LeftLogoLinkWindow_name'] = 'Logo link target';
$str['LeftLogoLinkWindow_desc'] = 'Open the linked page in the main window ([kbd]main[/kbd]) or in a new one ([kbd]new[/kbd])';
$str['LeftDefaultTabTable_name'] = 'Target for quick access icon';
$str['LeftPointerEnable_name'] = 'Enable highlighting';
$str['LeftPointerEnable_desc'] = 'Highlight server under the mouse cursor';

// Form: Left_servers
$str['LeftDisplayServers_name'] = 'Display servers selection';
$str['LeftDisplayServers_desc'] = 'Display server choice at the top of the left frame';
$str['DisplayServersList_name'] = 'Display servers as a list';
$str['DisplayServersList_desc'] = 'Show server listing as a list instead of a drop down';

// Form: Left_databases
$str['DisplayDatabasesList_name'] = 'Display databases as a list';
$str['DisplayDatabasesList_desc'] = 'Show database listing as a list instead of a drop down';
$str['LeftFrameDBTree_name'] = 'Display databases in a tree';
$str['LeftFrameDBTree_desc'] = 'Only light version; display databases in a tree (determined by the separator defined below)';
$str['LeftFrameDBSeparator_name'] = 'Database tree separator';
$str['LeftFrameDBSeparator_desc'] = 'String that separates databases into different tree levels';
$str['ShowTooltipAliasDB_name'] = 'Display database comment instead of its name';
$str['ShowTooltipAliasDB_desc'] = 'If tooltips are enabled and a database comment is set, this will flip the comment and the real name';

// Form: Left_tables
$str['LeftFrameTableSeparator_name'] = 'Table tree separator';
$str['LeftFrameTableSeparator_desc'] = 'String that separates tables into different tree levels';
$str['LeftFrameTableLevel_name'] = 'Maximum table tree depth';
$str['ShowTooltip_name'] = 'Display table comments in tooltips';
$str['ShowTooltipAliasTB_name'] = 'Display table comment instead of its name';
$str['ShowTooltipAliasTB_desc'] = 'When setting this to [kbd]nested[/kbd], the alias of the table name is only used to split/nest the tables according to the $cfg[\'LeftFrameTableSeparator\'] directive, so only the folder is called like the alias, the table name itself stays unchanged';

// Form: Startup
$str['ShowStats_name'] = 'Show statistics';
$str['ShowStats_desc'] = 'Allow to display database and table statistics (eg. space usage)';
$str['ShowPhpInfo_name'] = 'Show phpinfo() link';
$str['ShowPhpInfo_desc'] = 'Shows link to [a@http://php.net/manual/function.phpinfo.php]phpinfo()[/a] output';
$str['ShowServerInfo_name'] = 'Show detailed MySQL server information';
$str['ShowChgPassword_name'] = 'Show password change form';
$str['ShowChgPassword_desc'] = 'Please note that enabling this has no effect with [kbd]config[/kbd] authentication mode because the password is hard coded in the configuration file; this does not limit the ability to execute the same command directly';
$str['ShowCreateDb_name'] = 'Show create database form';
$str['SuggestDBName_name'] = 'Suggest new database name';
$str['SuggestDBName_desc'] = 'Suggest a database name on the &quot;Create Database&quot; form (if possible) or keep the text field empty';

// Form: Browse
$str['NavigationBarIconic_name'] = 'Iconic navigation bar';
$str['NavigationBarIconic_desc'] = 'Use only icons, only text or both';
$str['ShowAll_name'] = 'Allow to display all the rows';
$str['ShowAll_desc'] = 'Whether a user should be displayed a &quot;show all (records)&quot; button';
$str['MaxRows_name'] = 'Maximum number of rows to display';
$str['MaxRows_desc'] = 'Number of rows displayed when browsing a result set. If the result set contains more rows, &quot;Previous&quot; and &quot;Next&quot; links will be shown.';
$str['Order_name'] = 'Default sorting order';
$str['Order_desc'] = '[kbd]SMART[/kbd] - i.e. descending order for fields of type TIME, DATE, DATETIME and TIMESTAMP, ascending order otherwise';
$str['BrowsePointerEnable_name'] = 'Highlight pointer';
$str['BrowsePointerEnable_desc'] = 'Highlight row pointed by the mouse cursor';
$str['BrowseMarkerEnable_name'] = 'Row marker';
$str['BrowseMarkerEnable_desc'] = 'Highlight selected rows';

// Form: Edit
$str['ProtectBinary_name'] = 'Protect binary fields';
$str['ProtectBinary_desc'] = 'Disallow BLOB or BLOB and BINARY fields from editing';
$str['ShowFunctionFields_name'] = 'Show function fields';
$str['ShowFunctionFields_desc'] = 'Display the function fields in edit/insert mode';
$str['CharEditing_name'] = 'CHAR fields editing';
$str['CharEditing_desc'] = 'Defines which type of editing controls should be used for CHAR and VARCHAR fields; [kbd]input[/kbd] - allows limiting of input length, [kbd]textarea[/kbd] - allows newlines in fields';
$str['CharTextareaCols_name'] = 'CHAR textarea columns';
$str['CharTextareaCols_desc'] = 'Number of columns for textareas, this value will be emphasized (*2) for SQL query textareas and (*1.25) for SQL textareas inside the query window';
$str['CharTextareaRows_name'] = 'CHAR textarea rows';
$str['CharTextareaRows_desc'] = 'Number of rows for textareas, this value will be emphasized (*2) for SQL query textareas and (*1.25) for SQL textareas inside the query window';
$str['InsertRows_name'] = 'Number of inserted rows';
$str['InsertRows_desc'] = 'How many rows can be inserted at one time';
$str['ForeignKeyDropdownOrder_name'] = 'Foreign key dropdown order';
$str['ForeignKeyDropdownOrder_desc'] = 'Sort order for items in a foreign-key dropdown box; [kbd]content[/kbd] is the referenced data, [kbd]id[/kbd] is the key value';
$str['ForeignKeyMaxLimit_name'] = 'Foreign key limit';
$str['ForeignKeyMaxLimit_desc'] = 'A dropdown will be used if fewer items are present';

// Form: Tabs
$str['LightTabs_name'] = 'Light tabs';
$str['LightTabs_desc'] = 'Use less graphically intense tabs';
$str['PropertiesIconic_name'] = 'Iconic table operations';
$str['PropertiesIconic_desc'] = 'Use only icons, only text or both';
$str['DefaultTabServer_name'] = 'Default server tab';
$str['DefaultTabServer_desc'] = 'Tab that is displayed when entering a server';
$str['DefaultTabDatabase_name'] = 'Default database tab';
$str['DefaultTabDatabase_desc'] = 'Tab that is displayed when entering a database';
$str['DefaultTabTable_name'] = 'Default table tab';
$str['DefaultTabTable_desc'] = 'Tab that is displayed when entering a table';

// Form: Sql_Box
$str['SQLQuery/Edit_name'] = $GLOBALS['strEdit'];
$str['SQLQuery/Explain_name'] = $GLOBALS['strExplain'];
$str['SQLQuery/ShowAsPHP_name'] = $GLOBALS['strPhp'];
$str['SQLQuery/Validate_name'] = $GLOBALS['strValidateSQL'];
$str['SQLQuery/Refresh_name'] = $GLOBALS['strRefresh'];

// Form: Import
$str['Import/format_name'] = $GLOBALS['strImportFormat'];
$str['Import/format_desc'] = 'Default format, mind that this list depends on location (database, table) and only SQL is always avaiable';
$str['Import/allow_interrupt_name'] = 'Partial import: allow interrupt';
$str['Import/allow_interrupt_desc'] = $GLOBALS['strAllowInterrupt'];
$str['Import/skip_queries_name'] = 'Partial import: skip queries';
$str['Import/skip_queries_desc'] = $GLOBALS['strSkipQueries'];

// Form: Import_sql
$str['Import/sql_compatibility_name'] = $GLOBALS['strSQLCompatibility'];
$str['Import/sql_compatibility_desc'] = 'You can find more information on SQL compatibility modes in [a@http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html]MySQL Reference Manual[/a]';
// Form: Import_csv
$str['Import/csv_replace_name'] = $GLOBALS['strReplaceTable'];
$str['Import/csv_terminated_name'] = $GLOBALS['strFieldsTerminatedBy'];
$str['Import/csv_enclosed_name'] = $GLOBALS['strFieldsEnclosedBy'];
$str['Import/csv_escaped_name'] = $GLOBALS['strFieldsEscapedBy'];
$str['Import/csv_new_line_name'] = $GLOBALS['strLinesTerminatedBy'];
$str['Import/csv_columns_name'] = $GLOBALS['strColumnNames'];

// Form: Import_ldi
$str['Import/ldi_replace_name'] = $GLOBALS['strReplaceTable'];
$str['Import/ldi_terminated_name'] = $GLOBALS['strFieldsTerminatedBy'];
$str['Import/ldi_enclosed_name'] = $GLOBALS['strFieldsEnclosedBy'];
$str['Import/ldi_escaped_name'] = $GLOBALS['strFieldsEscapedBy'];
$str['Import/ldi_new_line_name'] = $GLOBALS['strLinesTerminatedBy'];
$str['Import/ldi_columns_name'] = $GLOBALS['strColumnNames'];
$str['Import/ldi_local_option_name'] = $GLOBALS['strLDILocal'];

// Form: Export_defaults
$str['Export/format_name'] = 'Format';
$str['Export/compression_name'] = $GLOBALS['strCompression'];
$str['Export/asfile_name'] = $GLOBALS['strSend'];
$str['Export/charset_name'] = $GLOBALS['strCharsetOfFile'];
$str['Export/onserver_name'] = 'Save on server';
$str['Export/onserver_overwrite_name'] = $GLOBALS['strOverwriteExisting'];
$str['Export/remember_file_template_name'] = 'Remember file name template';
$str['Export/file_template_table_name'] = 'Table name template';
$str['Export/file_template_database_name'] = 'Database name template';
$str['Export/file_template_server_name'] = 'Server name template';
?>