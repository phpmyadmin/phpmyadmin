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
$strSetupServersAdd = 'Add a new server';
$strSetupServersEdit = 'Edit server';
$strSetupFormset_features = 'Features';
$strSetupFormset_left_frame = 'Customize navigation frame';
$strSetupFormset_main_frame = 'Customize main frame';
$strSetupFormset_import = 'Customize import defaults';
$strSetupFormset_export = 'Customize export options';
$strSetupFormset_customization = 'Customization';

// forms
$strSetupTrue = 'yes';
$strSetupFalse = 'no';
$strSetupDisplay = 'Display';
$strSetupDownload = 'Download';
$strSetupClear = 'Clear';
$strSetupLoad = 'Load';
$strSetupRestoreDefaultValue = 'Restore default value';
$strSetupSetValue = 'Set value: %s';
$strSetupWarning = 'Warning';
$strSetupIgnoreErrors = 'Ignore errors';
$strSetupRevertErroneousFields = 'Try to revert erroneous fields to their default values';
$strSetupShowForm = 'Show form';

// main page
$strSetupOverview = 'Overview';
$strSetupShowHiddenMessages = 'Show hidden messages (#MSG_COUNT)';
$strSetupNoServers = 'There are no configured servers';
$strSetupNewServer = 'New server';
$strSetupDefaultLanguage = 'Default language';
$strSetupDefaultServer = 'Default server';
$strSetupLetUserChoose = 'let the user choose';
$strSetupOptionNone = '- none -';
$strSetupEndOfLine = 'End of line';
$strSetupConfigurationFile = 'Configuration file';
$strSetupHomepageLink = 'phpMyAdmin homepage';
$strSetupDonateLink = 'Donate';
$strSetupVersionCheckLink = 'Check for latest version';

// main page messages
$strSetupCannotLoadConfig = 'Cannot load or save configuration';
$strSetupCannotLoadConfigMsg = 'Please create web server writable folder [em]config[/em] in phpMyAdmin top level directory as described in [a@../Documentation.html#setup_script]documentation[/a]. Otherwise you will be only able to download or display it.';
$strSetupInsecureConnection = 'Insecure connection';
$strSetupInsecureConnectionMsg1 = 'You are not using a secure connection, all data (including sensitive, like passwords) is transferred unencrypted!';
$strSetupInsecureConnectionMsg2 = 'If your server is also configured to accept HTTPS requests follow [a@%s]this link[/a] to use a secure connection.';
$strSetupVersionCheck = 'Version check';
$strSetupVersionCheckWrapperError = 'Neither URL wrapper nor CURL is available. Version check is not possible.';
$strSetupVersionCheckDataError = 'Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.';
$strSetupVersionCheckInvalid = 'Got invalid version string from server';
$strSetupVersionCheckUnparsable = 'Unparsable version string';
$strSetupVersionCheckNewAvailable = 'New version of phpMyAdmin is available, you should consider upgrade. New version is %s, released on %s.';
$strSetupVersionCheckNewAvailableSvn = 'You are using subversion version, run [kbd]svn update[/kbd] :-)[br]The latest stable version is %s, released on %s.';
$strSetupVersionCheckNone = 'No newer stable version is available';
$strSetupServerSecurityInfoMsg = 'If you feel this is necessary, use additional protection settings - [a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server_config]host authentication[/a] settings and [a@?page=form&amp;formset=features#tab_Security]trusted proxies list[/a]. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.';
$strSetupServerSslMsg = 'You should use SSL connections if your web server supports it';
$strSetupServerExtensionMsg = 'You should use mysqli for performance reasons';
$strSetupServerAuthConfigMsg = 'You set [kbd]config[/kbd] authentication type and included username and password for auto-login, which is not a desirable option for live hosts. Anyone who knows phpMyAdmin URL can directly access your phpMyAdmin panel. Set [a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server]authentication type[/a] to [kbd]cookie[/kbd] or [kbd]http[/kbd].';
$strSetupServerNoPasswordRootMsg = 'You allow for connecting to the server as root without a passowrd.';
$strSetupBlowfishSecretMsg = 'You didn\'t have blowfish secret set and enabled cookie authentication so the key was generated for you. It is used to encrypt cookies.';
$strSetupBlowfishSecretLengthMsg = 'Key is too short, it should have at least 8 characters';
$strSetupBlowfishSecretCharsMsg = 'Key should contain alphanumerics, letters [em]and[/em] special characters';
$strSetupForceSSLMsg = 'This [a@?page=form&amp;formset=features#tab_Security]option[/a] should be enabled if your web server supports it';
$strSetupAllowArbitraryServerMsg = 'This [a@?page=form&amp;formset=features#tab_Security]option[/a] should be disabled as it allows attackers to bruteforce login to any MySQL server. If you feel this is necessary, use [a@?page=form&amp;formset=features#tab_Security]trusted proxies list[/a]. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.';
$strSetupLoginCookieValidityMsg = '[a@?page=form&formset=features#tab_Security]Login cookie validity[/a] should be should be set to 1800 seconds (30 minutes) at most. Values larger than 1800 may pose a security risk such as impersonation.';
$strSetupDirectoryNotice = 'This value should be double checked to ensure that this directory is neither world accessible nor readable or writable by other users on your server.';
$strSetupGZipDumpWarning = '[a@?page=form&amp;formset=features#tab_Import_export]GZip compression and decompression[/a] requires functions (%s) which are unavailable on this system.';
$strSetupBZipDumpWarning = '[a@?page=form&amp;formset=features#tab_Import_export]Bzip2 compression and decompression[/a] requires functions (%s) which are unavailable on this system.';
$strSetupZipDumpImportWarning = '[a@?page=form&amp;formset=features#tab_Import_export]Zip decompression[/a] requires functions (%s) which are unavailable on this system.';
$strSetupZipDumpExportWarning = '[a@?page=form&amp;formset=features#tab_Import_export]Zip compression[/a] requires functions (%s) which are unavailable on this system.';

// form errors
$strSetuperror_form = 'Submitted form contains errors';
$strSetuperror_missing_field_data = 'Missing data for %s';
$strSetuperror_incorrect_port = 'Not a valid port number';
$strSetuperror_incorrect_value = 'Incorrect value';
$strSetuperror_incorrect_ip_address = 'Incorrect IP address: %s';
$strSetuperror_nan_p = 'Not a positive number';
$strSetuperror_nan_nneg = 'Not a non-negative number';
$strSetuperror_empty_pmadb_user = 'Empty phpMyAdmin control user while using pmadb';
$strSetuperror_empty_pmadb_password = 'Empty phpMyAdmin control user password while using pmadb';
$strSetuperror_empty_user_for_config_auth = 'Empty username while using config authentication method';
$strSetuperror_empty_signon_session = 'Empty signon session name while using signon authentication method';
$strSetuperror_empty_signon_url = 'Empty signon URL while using signon authentication method';
$strSetuperror_connection = 'Could not connect to MySQL server';

// form names
$strSetupForm_Server = 'Basic settings';
$strSetupForm_Server_desc = 'Enter server connection parameters';
$strSetupForm_Server_login_options = 'Signon login options';
$strSetupForm_Server_login_options_desc = 'Enter login options for signon authentication';
$strSetupForm_Server_config = 'Server configuration';
$strSetupForm_Server_config_desc = 'Advanced server configuration, do not change these options unless you know what they are for';
$strSetupForm_Server_pmadb = 'PMA database';
$strSetupForm_Server_pmadb_desc = 'Configure phpMyAdmin database to gain access to additional features, see [a@../Documentation.html#linked-tables]linked-tables infrastructure[/a] in documentation';
$strSetupForm_Import_export = 'Import / export';
$strSetupForm_Import_export_desc = 'Set import and export directories and compression options';
$strSetupForm_Security = 'Security';
$strSetupForm_Security_desc = 'Please note that phpMyAdmin is just a user interface and its features do not limit MySQL';
$strSetupForm_Sql_queries = 'SQL queries';
$strSetupForm_Sql_queries_desc = 'SQL queries settings, for SQL Query box options see [a@?page=form&amp;formset=main_frame#tab_Sql_box]Navigation frame[/a] settings';
$strSetupForm_Other_core_settings = 'Other core settings';
$strSetupForm_Other_core_settings_desc = 'Settings that didn\'t fit enywhere else';
$strSetupForm_Left_frame = 'Navigation frame';
$strSetupForm_Left_frame_desc = 'Customize appearance of the navigation frame';
$strSetupForm_Left_servers = 'Servers';
$strSetupForm_Left_servers_desc = 'Servers display options';
$strSetupForm_Left_databases = 'Databases';
$strSetupForm_Left_databases_desc = 'Databases display options';
$strSetupForm_Left_tables = 'Tables';
$strSetupForm_Left_tables_desc = 'Tables display options';
$strSetupForm_Main_frame = 'Main frame';
$strSetupForm_Startup = 'Startup';
$strSetupForm_Startup_desc = 'Customize startup page';
$strSetupForm_Browse = 'Browse mode';
$strSetupForm_Browse_desc = 'Customize browse mode';
$strSetupForm_Edit = 'Edit mode';
$strSetupForm_Edit_desc = 'Customize edit mode';
$strSetupForm_Tabs = 'Tabs';
$strSetupForm_Tabs_desc = 'Choose how you want tabs to work';
$strSetupForm_Sql_box = 'SQL Query box';
$strSetupForm_Sql_box_desc = 'Customize links shown in SQL Query boxes';
$strSetupForm_Import_defaults = 'Import defaults';
$strSetupForm_Import_defaults_desc = 'Customize default common import options';
$strSetupForm_Export_defaults = 'Export defaults';
$strSetupForm_Export_defaults_desc = 'Customize default export options';
$strSetupForm_Query_window = 'Query window';
$strSetupForm_Query_window_desc = 'Customize query window options';

// Form: Server
$strSetupServers_verbose_name = 'Verbose name of this server';
$strSetupServers_verbose_desc = 'Hostname where MySQL server is running';
$strSetupServers_host_name = 'Server hostname';
$strSetupServers_host_desc = '';
$strSetupServers_port_name = 'Server port';
$strSetupServers_port_desc = 'Port on which MySQL server is listening, leave empty for default';
$strSetupServers_socket_name = 'Server socket';
$strSetupServers_socket_desc = 'Socket on which MySQL server is listening, leave empty for default';
$strSetupServers_ssl_name = 'Use SSL';
$strSetupServers_ssl_desc = '';
$strSetupServers_connect_type_name = 'Connection type';
$strSetupServers_connect_type_desc = 'How to connect to server, keep tcp if unsure';
$strSetupServers_extension_name = 'PHP extension to use';
$strSetupServers_extension_desc = 'What PHP extension to use, use mysqli if supported';
$strSetupServers_compress_name = 'Compress connection';
$strSetupServers_compress_desc = 'Compress connection to MySQL server';
$strSetupServers_auth_type_name = 'Authentication type';
$strSetupServers_auth_type_desc = 'Authentication method to use';
$strSetupServers_user_name = 'User for config auth';
$strSetupServers_user_desc = 'Leave empty if not using config auth';
$strSetupServers_password_name = 'Password for config auth';
$strSetupServers_password_desc = 'Leave empty if not using config auth';
$strSetupServers_nopassword_name = 'Connect without password';
$strSetupServers_nopassword_desc = 'Try to connect without password';

// Form: Server_login_options
$strSetupServers_SignonSession_name = 'Signon session name';
$strSetupServers_SignonSession_desc = 'See [a@http://wiki.cihar.com/pma/auth_types#signon]authentication types[/a] for an example';
$strSetupServers_SignonURL_name = 'Signon URL';
$strSetupServers_LogoutURL_name = 'Logout URL';
$strSetupServers_auth_swekey_config_name = 'SweKey config file';
$strSetupServers_auth_swekey_config_desc = 'Config file for [a@http://swekey.com]SweKey hardware authentication[/a], relative to phpMyAdmin root directory, eg. ./swekey.conf';

// Form: Server_config
$strSetupServers_only_db_name = 'Show only listed databases';
$strSetupServers_only_db_desc = 'You can use MySQL wildcard characters (% and _), escape them if you want to use their literal instances, i.e. use \'my\_db\' and not \'my_db\'';
$strSetupServers_hide_db_name = 'Hide databases';
$strSetupServers_hide_db_desc = 'Hide databases matching regular expression (PCRE)';
$strSetupServers_AllowRoot_name = 'Allow root login';
$strSetupServers_AllowNoPasswordRoot_name = 'Allow root without password';
$strSetupServers_DisableIS_name = 'Disable use of INFORMATION_SCHEMA';
$strSetupServers_DisableIS_desc = 'More information on [a@http://sf.net/support/tracker.php?aid=1849494]PMA bug tracker[/a] and [a@http://bugs.mysql.com/19588]MySQL Bugs[/a]';
$strSetupServers_AllowDeny_order_name = 'Host authentication order';
$strSetupServers_AllowDeny_order_desc = 'Leave blank if not used';
$strSetupServers_AllowDeny_rules_name = 'Host authentication rules';
$strSetupServers_AllowDeny_rules_desc = 'Leave blank for defaults';
$strSetupServers_ShowDatabasesCommand_name = 'SHOW DATABASES command';
$strSetupServers_ShowDatabasesCommand_desc = 'SQL command to fetch available databases';
$strSetupServers_CountTables_name = 'Count tables';
$strSetupServers_CountTables_desc = 'Count tables when showing database list';

// Form: Server_pmadb
$strSetupServers_pmadb_name = 'PMA database';
$strSetupServers_pmadb_desc = 'Database used for relations, bookmarks, and PDF features. See [a@http://wiki.cihar.com/pma/pmadb]pmadb[/a] for complete information. Leave blank for no support. Default: [kbd]phpmyadmin[/kbd]';
$strSetupServers_controluser_name = 'Control user';
$strSetupServers_controluser_desc = 'A special MySQL user configured with limited permissions, more information available on [a@http://wiki.cihar.com/pma/controluser]wiki[/a]';
$strSetupServers_controlpass_name = 'Control user password';
$strSetupServers_verbose_check_name = 'Verbose check';
$strSetupServers_verbose_check_desc = 'Disable if you know that your pma_* tables are up to date. This prevents compatibility checks and thereby increases performance';
$strSetupServers_bookmarktable_name = 'Bookmark table';
$strSetupServers_bookmarktable_desc = 'Leave blank for no [a@http://wiki.cihar.com/pma/bookmark]bookmark[/a] support, default: [kbd]pma_bookmark[/kbd]';
$strSetupServers_relation_name = 'Relation table';
$strSetupServers_relation_desc = 'Leave blank for no [a@http://wiki.cihar.com/pma/relation]relation-links[/a] support, default: [kbd]pma_relation[/kbd]';
$strSetupServers_table_info_name = 'Display fields table';
$strSetupServers_table_info_desc = 'Table to describe the display fields, leave blank for no support; default: [kbd]pma_table_info[/kbd]';
$strSetupServers_table_coords_name = 'PDF schema: table coordinates';
$strSetupServers_table_coords_desc = 'Leave blank for no PDF schema support, default: [kbd]pma_table_coords[/kbd]';
$strSetupServers_pdf_pages_name = 'PDF schema: pages table';
$strSetupServers_pdf_pages_desc = 'Leave blank for no PDF schema support, default: [kbd]pma_pdf_pages[/kbd]';
$strSetupServers_column_info_name = 'Column information table';
$strSetupServers_column_info_desc = 'Leave blank for no column comments/mime types, default: [kbd]pma_column_info[/kbd]';
$strSetupServers_history_name = 'SQL query history table';
$strSetupServers_history_desc = 'Leave blank for no SQL query history support, default: [kbd]pma_history[/kbd]';
$strSetupServers_designer_coords_name = 'Designer table';
$strSetupServers_designer_coords_desc = 'Leave blank for no Designer support, default: [kbd]designer_coords[/kbd]';

// Form: Import_export
$strSetupUploadDir_name = 'Upload directory';
$strSetupUploadDir_desc = 'Directory on server where you can upload files for import';
$strSetupSaveDir_name = 'Save directory';
$strSetupSaveDir_desc = 'Directory where exports can be saved on server';
$strSetupAllowAnywhereRecoding_name = 'Allow character set conversion';
$strSetupDefaultCharset_name = 'Default character set';
$strSetupDefaultCharset_desc = 'Default character set used for conversions';
$strSetupRecodingEngine_name = 'Recoding engine';
$strSetupRecodingEngine_desc = 'Select which functions will be used for character set conversion';
$strSetupIconvExtraParams_name = 'Extra parameters for iconv';
$strSetupZipDump_name = 'ZIP';
$strSetupZipDump_desc = 'Enable [a@http://en.wikipedia.org/wiki/ZIP_(file_format)]ZIP[/a] compression for import and export operations';
$strSetupGZipDump_name = 'GZip';
$strSetupGZipDump_desc = 'Enable [a@http://en.wikipedia.org/wiki/Gzip]gzip[/a] compression for import and export operations';
$strSetupBZipDump_name = 'Bzip2';
$strSetupBZipDump_desc = 'Enable [a@http://en.wikipedia.org/wiki/Bzip2]bzip2[/a] compression for import and export operations';
$strSetupCompressOnFly_name = 'Compress on the fly';
$strSetupCompressOnFly_desc = 'Compress gzip/bzip2 exports on the fly without the need for much memory; if you encounter problems with created gzip/bzip2 files disable this feature';

// Form: Security
$strSetupblowfish_secret_name = 'Blowfish secret';
$strSetupblowfish_secret_desc = 'Secret passphrase used for encrypting cookies in [kbd]cookie[/kbd] authentication';
$strSetupForceSSL_name = 'Force SSL connection';
$strSetupForceSSL_desc = 'Force secured connection while using phpMyAdmin';
$strSetupCheckConfigurationPermissions_name = 'Check config file permissions';
$strSetupTrustedProxies_name = 'List of trusted proxies for IP allow/deny';
$strSetupTrustedProxies_desc = 'Input proxies as [kbd]IP: trusted HTTP header[/kbd]. The following example specifies that phpMyAdmin should trust a HTTP_X_FORWARDED_FOR (X-Forwarded-For) header coming from the proxy 1.2.3.4:[br][kbd]1.2.3.4: HTTP_X_FORWARDED_FOR[/kbd]';
$strSetupAllowUserDropDatabase_name = 'Show &quot;Drop database&quot; link to normal users';
$strSetupAllowArbitraryServer_name = 'Allow login to any MySQL server';
$strSetupAllowArbitraryServer_desc = 'If enabled user can enter any MySQL server in login form for cookie auth';
$strSetupLoginCookieRecall_name = 'Recall user name';
$strSetupLoginCookieRecall_desc = 'Define whether the previous login should be recalled or not in cookie authentication mode';
$strSetupLoginCookieValidity_name = 'Login cookie validity';
$strSetupLoginCookieValidity_desc = 'Define how long (in seconds) a login cookie is valid';
$strSetupLoginCookieStore_name = 'Login cookie store';
$strSetupLoginCookieStore_desc = 'Define how long (in seconds) a login cookie should be stored in browser. Default 0 means that it will be kept for existing session only, that is it will be deleted as soon as you close the browser window. This is recommended for non-trusted environments.';
$strSetupLoginCookieDeleteAll_name = 'Delete all cookies on logout';
$strSetupLoginCookieDeleteAll_desc = 'If enabled logout deletes cookies for all servers, otherwise only for current one. Setting this to FALSE makes it easy to forget to log out from other server, when you are using more of them.';

// Form: Sql_queries
$strSetupShowSQL_name = 'Show SQL queries';
$strSetupShowSQL_desc = 'Defines whether SQL queries generated by phpMyAdmin should be displayed';
$strSetupConfirm_name = 'Confirm DROP queries';
$strSetupConfirm_desc = 'Whether a warning (&quot;Are your really sure...&quot;) should be displayed when you\'re about to lose data';
$strSetupQueryHistoryDB_name = 'Permanent query history';
$strSetupQueryHistoryDB_desc = 'Enable if you want DB-based query history (requires pmadb). If disabled, this utilizes JS-routines to display query history (lost by window close).';
$strSetupQueryHistoryMax_name = 'Query history length';
$strSetupQueryHistoryMax_desc = 'How many queries are kept in history';
$strSetupIgnoreMultiSubmitErrors_name = 'Ignore multiple statement errors';
$strSetupIgnoreMultiSubmitErrors_desc = 'If enabled PMA continues computing multiple-statement queries even if one of the queries failed';
$strSetupVerboseMultiSubmit_name = 'Verbose multiple statements';
$strSetupVerboseMultiSubmit_desc = 'Show affected rows of each statement on multiple-statement queries. See libraries/import.lib.php for defaults on how many queries a statement may contain.';

// Form: Other_core_options
$strSetupMaxDbList_name = 'Maximum databases';
$strSetupMaxDbList_desc = 'Maximum number of databases displayed in left frame and database list';
$strSetupMaxTableList_name = 'Maximum tables';
$strSetupMaxTableList_desc = 'Maximum number of tables displayed in table list';
$strSetupMaxCharactersInDisplayedSQL_name = 'Maximum displayed SQL length';
$strSetupMaxCharactersInDisplayedSQL_desc = 'Maximum number of characters used when a SQL query is displayed';
$strSetupOBGzip_name = 'GZip output buffering';
$strSetupOBGzip_desc = 'use GZip output buffering for increased speed in HTTP transfers';
$strSetupPersistentConnections_name = 'Persistent connections';
$strSetupPersistentConnections_desc = 'Use persistent connections to MySQL databases';
$strSetupExecTimeLimit_name = 'Maximum execution time';
$strSetupExecTimeLimit_desc = 'Set the number of seconds a script is allowed to run ([kbd]0[/kbd] for no limit)';
$strSetupMemoryLimit_name = 'Memory limit';
$strSetupMemoryLimit_desc = 'The number of bytes a script is allowed to allocate, eg. [kbd]32M[/kbd] ([kbd]0[/kbd] for no limit)';
$strSetupSkipLockedTables_name = 'Skip locked tables';
$strSetupSkipLockedTables_desc = 'Mark used tables and make it possible to show databases with locked tables';
$strSetupUseDbSearch_name = 'Use database search';
$strSetupUseDbSearch_desc = 'Allow for searching inside the entire database';

// Form: Left_frame
$strSetupLeftFrameLight_name = 'Use light version';
$strSetupLeftFrameLight_desc = 'Disable this if you want to see all databases at once';
$strSetupLeftDisplayLogo_name = 'Display logo';
$strSetupLeftDisplayLogo_desc = 'Show logo in left frame';
$strSetupLeftLogoLink_name = 'Logo link URL';
$strSetupLeftLogoLinkWindow_name = 'Logo link target';
$strSetupLeftLogoLinkWindow_desc = 'Open the linked page in the main window ([kbd]main[/kbd]) or in a new one ([kbd]new[/kbd])';
$strSetupLeftDefaultTabTable_name = 'Target for quick access icon';
$strSetupLeftPointerEnable_name = 'Enable highlighting';
$strSetupLeftPointerEnable_desc = 'Highlight server under the mouse cursor';

// Form: Left_servers
$strSetupLeftDisplayServers_name = 'Display servers selection';
$strSetupLeftDisplayServers_desc = 'Display server choice at the top of the left frame';
$strSetupDisplayServersList_name = 'Display servers as a list';
$strSetupDisplayServersList_desc = 'Show server listing as a list instead of a drop down';

// Form: Left_databases
$strSetupDisplayDatabasesList_name = 'Display databases as a list';
$strSetupDisplayDatabasesList_desc = 'Show database listing as a list instead of a drop down';
$strSetupLeftFrameDBTree_name = 'Display databases in a tree';
$strSetupLeftFrameDBTree_desc = 'Only light version; display databases in a tree (determined by the separator defined below)';
$strSetupLeftFrameDBSeparator_name = 'Database tree separator';
$strSetupLeftFrameDBSeparator_desc = 'String that separates databases into different tree levels';
$strSetupShowTooltipAliasDB_name = 'Display database comment instead of its name';
$strSetupShowTooltipAliasDB_desc = 'If tooltips are enabled and a database comment is set, this will flip the comment and the real name';

// Form: Left_tables
$strSetupLeftFrameTableSeparator_name = 'Table tree separator';
$strSetupLeftFrameTableSeparator_desc = 'String that separates tables into different tree levels';
$strSetupLeftFrameTableLevel_name = 'Maximum table tree depth';
$strSetupShowTooltip_name = 'Display table comments in tooltips';
$strSetupShowTooltipAliasTB_name = 'Display table comment instead of its name';
$strSetupShowTooltipAliasTB_desc = 'When setting this to [kbd]nested[/kbd], the alias of the table name is only used to split/nest the tables according to the $cfg[\'LeftFrameTableSeparator\'] directive, so only the folder is called like the alias, the table name itself stays unchanged';

// Form: Startup
$strSetupShowStats_name = 'Show statistics';
$strSetupShowStats_desc = 'Allow to display database and table statistics (eg. space usage)';
$strSetupShowPhpInfo_name = 'Show phpinfo() link';
$strSetupShowPhpInfo_desc = 'Shows link to [a@http://php.net/manual/function.phpinfo.php]phpinfo()[/a] output';
$strSetupShowServerInfo_name = 'Show detailed MySQL server information';
$strSetupShowChgPassword_name = 'Show password change form';
$strSetupShowChgPassword_desc = 'Please note that enabling this has no effect with [kbd]config[/kbd] authentication mode because the password is hard coded in the configuration file; this does not limit the ability to execute the same command directly';
$strSetupShowCreateDb_name = 'Show create database form';
$strSetupSuggestDBName_name = 'Suggest new database name';
$strSetupSuggestDBName_desc = 'Suggest a database name on the &quot;Create Database&quot; form (if possible) or keep the text field empty';

// Form: Browse
$strSetupNavigationBarIconic_name = 'Iconic navigation bar';
$strSetupNavigationBarIconic_desc = 'Use only icons, only text or both';
$strSetupShowAll_name = 'Allow to display all the rows';
$strSetupShowAll_desc = 'Whether a user should be displayed a &quot;show all (records)&quot; button';
$strSetupMaxRows_name = 'Maximum number of rows to display';
$strSetupMaxRows_desc = 'Number of rows displayed when browsing a result set. If the result set contains more rows, &quot;Previous&quot; and &quot;Next&quot; links will be shown.';
$strSetupOrder_name = 'Default sorting order';
$strSetupOrder_desc = '[kbd]SMART[/kbd] - i.e. descending order for fields of type TIME, DATE, DATETIME and TIMESTAMP, ascending order otherwise';
$strSetupBrowsePointerEnable_name = 'Highlight pointer';
$strSetupBrowsePointerEnable_desc = 'Highlight row pointed by the mouse cursor';
$strSetupBrowseMarkerEnable_name = 'Row marker';
$strSetupBrowseMarkerEnable_desc = 'Highlight selected rows';

// Form: Edit
$strSetupProtectBinary_name = 'Protect binary fields';
$strSetupProtectBinary_desc = 'Disallow BLOB or BLOB and BINARY fields from editing';
$strSetupShowFunctionFields_name = 'Show function fields';
$strSetupShowFunctionFields_desc = 'Display the function fields in edit/insert mode';
$strSetupCharEditing_name = 'CHAR fields editing';
$strSetupCharEditing_desc = 'Defines which type of editing controls should be used for CHAR and VARCHAR fields; [kbd]input[/kbd] - allows limiting of input length, [kbd]textarea[/kbd] - allows newlines in fields';
$strSetupCharTextareaCols_name = 'CHAR textarea columns';
$strSetupCharTextareaCols_desc = 'Number of columns for CHAR/VARCHAR textareas';
$strSetupCharTextareaRows_name = 'CHAR textarea rows';
$strSetupCharTextareaRows_desc = 'Number of rows for CHAR/VARCHAR textareas';
$strSetupInsertRows_name = 'Number of inserted rows';
$strSetupInsertRows_desc = 'How many rows can be inserted at one time';
$strSetupForeignKeyDropdownOrder_name = 'Foreign key dropdown order';
$strSetupForeignKeyDropdownOrder_desc = 'Sort order for items in a foreign-key dropdown box; [kbd]content[/kbd] is the referenced data, [kbd]id[/kbd] is the key value';
$strSetupForeignKeyMaxLimit_name = 'Foreign key limit';
$strSetupForeignKeyMaxLimit_desc = 'A dropdown will be used if fewer items are present';

// Form: Tabs
$strSetupLightTabs_name = 'Light tabs';
$strSetupLightTabs_desc = 'Use less graphically intense tabs';
$strSetupPropertiesIconic_name = 'Iconic table operations';
$strSetupPropertiesIconic_desc = 'Use only icons, only text or both';
$strSetupDefaultTabServer_name = 'Default server tab';
$strSetupDefaultTabServer_desc = 'Tab that is displayed when entering a server';
$strSetupDefaultTabDatabase_name = 'Default database tab';
$strSetupDefaultTabDatabase_desc = 'Tab that is displayed when entering a database';
$strSetupDefaultTabTable_name = 'Default table tab';
$strSetupDefaultTabTable_desc = 'Tab that is displayed when entering a table';
$strSetupQueryWindowDefTab_name = 'Default query window tab';
$strSetupQueryWindowDefTab_desc = 'Tab displayed when opening a new query window';

// Form: Sql_Box
$strSetupSQLQuery_Edit_name = 'Edit';
$strSetupSQLQuery_Explain_name = 'Explain SQL';
$strSetupSQLQuery_ShowAsPHP_name = 'Create PHP Code';
$strSetupSQLQuery_Validate_name = 'Validate SQL';
$strSetupSQLQuery_Refresh_name = 'Refresh';

// Form: Import_defaults
$strSetupImport_format_name = 'Format of imported file';
$strSetupImport_format_desc = 'Default format, mind that this list depends on location (database, table) and only SQL is always avaiable';
$strSetupImport_allow_interrupt_name = 'Partial import: allow interrupt';
$strSetupImport_allow_interrupt_desc = 'Allow interrupt of import in case script detects it is close to time limit. This might be good way to import large files, however it can break transactions.';
$strSetupImport_skip_queries_name = 'Partial import: skip queries';
$strSetupImport_skip_queries_desc = 'Number of records (queries) to skip from start';

// Form: Export_defaults
$strSetupExport_format_name = 'Format';
$strSetupExport_compression_name = 'Compression';
$strSetupExport_asfile_name = 'Save as file';
$strSetupExport_charset_name = 'Character set of the file';
$strSetupExport_onserver_name = 'Save on server';
$strSetupExport_onserver_overwrite_name = 'Overwrite existing file(s)';
$strSetupExport_remember_file_template_name = 'Remember file name template';
$strSetupExport_file_template_table_name = 'Table name template';
$strSetupExport_file_template_database_name = 'Database name template';
$strSetupExport_file_template_server_name = 'Server name template';
?>