<?php
/* $Id$ */


/**
 * phpMyAdmin Configuration File
 *
 * All directives are explained in Documentation.html
 */


/**
 * Bookmark Table Structure
 *
 * CREATE TABLE bookmark (
 *  id int(11) DEFAULT '0' NOT NULL auto_increment,
 *  dbase varchar(255) NOT NULL,
 *  user varchar(255) NOT NULL,
 *  label varchar(255) NOT NULL,
 *  query text NOT NULL,
 *  PRIMARY KEY (id)
 * );
 *
 */


/**
 * Your phpMyAdmin url
 *
 * Complete the variable below with the full url ie
 *    http://www.your_web.net/path_to_your_phpMyAdmin_directory/
 */
$cfgPmaAbsoluteUri = '';


/**
 * Server(s) configuration
 */
$i = 0;
// The $cfgServers array starts with $cfgServers[1].  Do not use $cfgServers[0].
// You can disable a server config entry by setting host to ''.
$i++;
$cfgServers[$i]['host']          = 'localhost'; // MySQL hostname
$cfgServers[$i]['port']          = '';          // MySQL port - leave blank for default port
$cfgServers[$i]['socket']        = '';          // Path to the socket - leave blank for default socket
$cfgServers[$i]['connect_type']  = 'tcp';       // How to connect to MySQL server ('tcp' or 'socket')
$cfgServers[$i]['controluser']   = '';          // MySQL control user settings
                                                // (this user must have read-only
$cfgServers[$i]['controlpass']   = '';          // access to the "mysql/user"
                                                // and "mysql/db" tables)
$cfgServers[$i]['auth_type']     = 'config';    // Authentication method (config, http or cookie based)?
$cfgServers[$i]['user']          = 'root';      // MySQL user
$cfgServers[$i]['password']      = '';          // MySQL password (only needed
                                                // with 'config' auth_type)
$cfgServers[$i]['only_db']       = '';          // If set to a db-name, only
                                                // this db is displayed
                                                // at left frame
                                                // It may also be an array
                                                // of db-names
$cfgServers[$i]['verbose']       = '';          // Verbose name for this host - leave blank to show the hostname
$cfgServers[$i]['bookmarkdb']    = '';          // Bookmark db - leave blank for no bookmark support
$cfgServers[$i]['bookmarktable'] = '';          // Bookmark table - leave blank for no bookmark support
$cfgServers[$i]['relation']      = '';          // table to describe the relation between links (see doc)
                                                //   - leave blank for no relation-links support

$i++;
$cfgServers[$i]['host']          = '';
$cfgServers[$i]['port']          = '';
$cfgServers[$i]['socket']        = '';
$cfgServers[$i]['connect_type']  = 'tcp';
$cfgServers[$i]['controluser']   = '';
$cfgServers[$i]['controlpass']   = '';
$cfgServers[$i]['auth_type']     = 'config';
$cfgServers[$i]['user']          = 'root';
$cfgServers[$i]['password']      = '';
$cfgServers[$i]['only_db']       = '';
$cfgServers[$i]['verbose']       = '';
$cfgServers[$i]['bookmarkdb']    = '';
$cfgServers[$i]['bookmarktable'] = '';
$cfgServers[$i]['relation']      = '';

$i++;
$cfgServers[$i]['host']          = '';
$cfgServers[$i]['port']          = '';
$cfgServers[$i]['socket']        = '';
$cfgServers[$i]['connect_type']  = 'tcp';
$cfgServers[$i]['controluser']   = '';
$cfgServers[$i]['controlpass']   = '';
$cfgServers[$i]['auth_type']     = 'config';
$cfgServers[$i]['user']          = 'root';
$cfgServers[$i]['password']      = '';
$cfgServers[$i]['only_db']       = '';
$cfgServers[$i]['verbose']       = '';
$cfgServers[$i]['bookmarkdb']    = '';
$cfgServers[$i]['bookmarktable'] = '';
$cfgServers[$i]['relation']      = '';

// If you have more than one server configured, you can set $cfgServerDefault
// to any one of them to autoconnect to that server when phpMyAdmin is started,
// or set it to 0 to be given a list of servers without logging in
// If you have only one server configured, $cfgServerDefault *MUST* be
// set to that server.
$cfgServerDefault = 1;                         // Default server (0 = no default server)
$cfgServer        = '';
unset($cfgServers[0]);


/**
 * Other core phpMyAdmin settings
 */
$cfgOBGzip                = TRUE;   // use GZIP output buffering if possible
$cfgPersistentConnections = FALSE;  // use persistent connections to MySQL database
$cfgExecTimeLimit         = 300;    // maximum execution time in seconds (0 for no limit)
$cfgSkipLockedTables      = FALSE;  // mark used tables, make possible to show
                                    // locked tables (since MySQL 3.23.30)
$cfgShowSQL               = TRUE;   // show SQL queries as run
$cfgAllowUserDropDatabase = FALSE;  // show a 'Drop database' link to normal users
$cfgConfirm               = TRUE;   // confirm 'DROP TABLE' & 'DROP DATABASE'
$cfgLoginCookieRecall     = TRUE;   // recall previous login in cookie auth. mode or not

// Left frame setup
$cfgLeftFrameLight        = TRUE;   // use a select-based menu and display only the
                                    // current tables in the left frame.
$cfgShowTooltip           = TRUE;   // display table comment as tooltip in left frame

// In the main frame, at startup...
$cfgShowStats             = TRUE;   // allow to display statistics and space usage in
                                    // the pages about database details and table
                                    // properties
$cfgShowMysqlInfo         = FALSE;  // whether to display the "MySQL runtime
$cfgShowMysqlVars         = FALSE;  // information", "MySQL system variables", "PHP
$cfgShowPhpInfo           = FALSE;  // information" and "change password" links for
$cfgShowChgPassword       = FALSE;  // simple users or not

// In browse mode...
$cfgShowBlob              = FALSE;  // display blob field contents
$cfgNavigationBarIconic   = TRUE;   // do not display text inside navigation bar buttons
$cfgShowAll               = FALSE;  // allows to display all the rows
$cfgMaxRows               = 30;     // maximum number of rows to display
$cfgOrder                 = 'ASC';  // default for 'ORDER BY' clause (valid
                                    // values are 'ASC', 'DESC' or 'SMART' -ie
                                    // descending order for fields of type
                                    // TIME, DATE, DATETIME & TIMESTAMP,
                                    // ascending order else-)

// In edit mode...
$cfgProtectBinary         = 'blob'; // disallow editing of binary fields
                                    // valid values are:
                                    //   FALSE  allow editing
                                    //   'blob' allow editing except for BLOB fields
                                    //   'all'  disallow editing
$cfgShowFunctionFields    = TRUE;   // Display the function fields in edit/insert mode

// For the export features...
$cfgZipDump               = TRUE;   // Allow the use of zip/gzip/bzip
$cfgGZipDump              = TRUE;   // compression for
$cfgBZipDump              = TRUE;   // dump files


/**
 * Link to the official MySQL documentation
 * Be sure to include no trailing slash on the path
 */
$cfgManualBaseShort = 'http://www.mysql.com/doc';


/**
 * Language settings
 */
// Default language to use, if not browser-defined or user-defined
$cfgDefaultLang = 'en';
// Force: always use this language - must be defined in
//        libraries/select_lang.lib.php3
// $cfgLang     = 'en';
// Loads language file
require('./libraries/select_lang.lib.php3');


/**
 * Customization & design
 */
$cfgLeftWidth           = 150;          // left frame width
$cfgLeftBgColor         = '#D0DCE0';    // background color for the left frame
$cfgLeftPointerColor    = '#CCFFCC';    // color of the pointer in left frame
                                        // (blank for no pointer)
$cfgRightBgColor        = '#F5F5F5';    // background color for the right frame
$cfgBorder              = 0;            // border width on tables
$cfgThBgcolor           = '#D3DCE3';    // table header row colour
$cfgBgcolorOne          = '#CCCCCC';    // table data row colour
$cfgBgcolorTwo          = '#DDDDDD';    // table data row colour, alternate
$cfgBrowsePointerColor  = '#CCFFCC';    // color of the pointer in browse mode
                                        // (blank for no pointer)
$cfgBrowseMarkerColor   = '#FFCC99';    // color of the marker (visually marks row
                                        // by clicking on it) in browse mode
                                        // (blank for no marker)
$cfgTextareaCols        = 40;           // textarea size (columns) in edit mode
$cfgTextareaRows        = 7;            // textarea size (rows) in edit mode
$cfgLimitChars          = 50;           // max field data length in browse mode
$cfgModifyDeleteAtLeft  = TRUE;         // show edit/delete links on left side of browse
                                        // (or at the top with vertical browse)
$cfgModifyDeleteAtRight = FALSE;        // show edit/delete links on right side of browse
                                        // (or at the bottom with vertical browse)
$cfgDefaultDisplay      = 'horizontal'; // default display direction (horizontal|vertical)
$cfgRepeatCells         = 100;          // repeat header names every X cells? (0 = deactivate)


/**
 * MySQL settings
 */
// Column types;
// varchar, tinyint, text and date are listed first, based on estimated popularity
$cfgColumnTypes = array(
   'VARCHAR',
   'TINYINT',
   'TEXT',
   'DATE',
   'SMALLINT',
   'MEDIUMINT',
   'INT',
   'BIGINT',
   'FLOAT',
   'DOUBLE',
   'DECIMAL',
   'DATETIME',
   'TIMESTAMP',
   'TIME',
   'YEAR',
   'CHAR',
   'TINYBLOB',
   'TINYTEXT',
   'BLOB',
   'MEDIUMBLOB',
   'MEDIUMTEXT',
   'LONGBLOB',
   'LONGTEXT',
   'ENUM',
   'SET'
);

// Atributes
$cfgAttributeTypes = array(
   '',
   'BINARY',
   'UNSIGNED',
   'UNSIGNED ZEROFILL'
);

// Available functions
if ($cfgShowFunctionFields) {
    $cfgFunctions = array(
       'ASCII',
       'CHAR',
       'SOUNDEX',
       'LCASE',
       'UCASE',
       'NOW',
       'PASSWORD',
       'MD5',
       'ENCRYPT',
       'RAND',
       'LAST_INSERT_ID',
       'COUNT',
       'AVG',
       'SUM',
       'CURDATE',
       'CURTIME',
       'FROM_DAYS',
       'FROM_UNIXTIME',
       'PERIOD_ADD',
       'PERIOD_DIFF',
       'TO_DAYS',
       'UNIX_TIMESTAMP',
       'USER',
       'WEEKDAY'
    );
} // end if


/**
 * Unset magic_quotes_runtime - do not change!
 */
set_magic_quotes_runtime(0);
?>
