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
// The $cfgServers array starts with $cfgServers[1].  Do not use $cfgServers[0].
// You can disable a server config entry by setting host to ''.
$cfgServers[1]['host']          = 'localhost'; // MySQL hostname
$cfgServers[1]['port']          = '';          // MySQL port - leave blank for default port
$cfgServers[1]['socket']        = '';          // Path to the socket - leave blank for default socket
$cfgServers[1]['connect_type']  = 'tcp';       // How to connect to MySQL server ('tcp' or 'socket')
$cfgServers[1]['stduser']       = '';          // MySQL standard user settings (this user must have read-only
$cfgServers[1]['stdpass']       = '';          //   access to the "mysql/user" and "mysql/db" tables)
$cfgServers[1]['adv_auth']      = FALSE;       // Use advanced authentication?
$cfgServers[1]['user']          = 'root';      // MySQL user (always required)
$cfgServers[1]['password']      = '';          // MySQL password (only needed with basic auth)
$cfgServers[1]['only_db']       = '';          // If set to a db-name, only this db is displayed at left frame
                                               // It may also be an array of db-names
$cfgServers[1]['verbose']       = '';          // Verbose name for this host - leave blank to show the hostname
$cfgServers[1]['bookmarkdb']    = '';          // Bookmark db - leave blank for no bookmark support
$cfgServers[1]['bookmarktable'] = '';          // Bookmark table - leave blank for no bookmark support

$cfgServers[2]['host']          = '';
$cfgServers[2]['port']          = '';
$cfgServers[2]['socket']        = '';
$cfgServers[2]['connect_type']  = 'tcp';
$cfgServers[2]['stduser']       = '';
$cfgServers[2]['stdpass']       = '';
$cfgServers[2]['adv_auth']      = FALSE;
$cfgServers[2]['user']          = 'root';
$cfgServers[2]['password']      = '';
$cfgServers[2]['only_db']       = '';
$cfgServers[2]['verbose']       = '';
$cfgServers[2]['bookmarkdb']    = '';
$cfgServers[2]['bookmarktable'] = '';

$cfgServers[3]['host']          = '';
$cfgServers[3]['port']          = '';
$cfgServers[3]['socket']        = '';
$cfgServers[3]['connect_type']  = 'tcp';
$cfgServers[3]['stduser']       = '';
$cfgServers[3]['stdpass']       = '';
$cfgServers[3]['adv_auth']      = FALSE;
$cfgServers[3]['user']          = 'root';
$cfgServers[3]['password']      = '';
$cfgServers[3]['only_db']       = '';
$cfgServers[3]['verbose']       = '';
$cfgServers[3]['bookmarkdb']    = '';
$cfgServers[3]['bookmarktable'] = '';

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
$cfgOBGzip 		          = TRUE;   // use GZIP output buffering if possible
$cfgPersistentConnections = FALSE;  // use persistent connections to MySQL database
$cfgSkipLockedTables      = FALSE;  // mark used tables, make possible to show
                                    // locked tables (since MySQL 3.23.30)
$cfgShowSQL               = TRUE;   // show SQL queries as run
$cfgAllowUserDropDatabase = FALSE;  // disallow users to delete their own database
$cfgConfirm               = TRUE;   // confirm 'DROP TABLE' & 'DROP DATABASE'

$cfgShowStats             = TRUE;   // allow to display statistics and space usage in
                                    // the pages about database details and table
                                    // properties
$cfgShowTooltip	          = TRUE;	// display table comment as tooltip in left frame

$cfgShowBlob              = FALSE;  // display blob field contents in browse mode
$cfgShowAll               = FALSE;  // allows to display all the rows in browse mode
$cfgMaxRows               = 30;     // maximum number of rows to display in browse mode
$cfgOrder                 = 'ASC';  // default for 'ORDER BY' clause (valid
                                    // values are 'ASC', 'DESC' or 'SMART' -ie
                                    // descending order for fields of type
                                    // TIME, DATE, DATETIME & TIMESTAMP,
                                    // ascending order else-)

$cfgProtectBinary         = 'blob'; // disallow editing of binary fields in edit mode
                                    // valid values are:
                                    //   FALSE  allow editing
                                    //   'blob' allow editing except for BLOB fields
                                    //   'all'  disallow editing

$cfgZipDump               = TRUE;   // Allow the use of zip/gzip/bzip
$cfgGZipDump              = TRUE;   // compression for
$cfgBZipDump              = TRUE;   // dump files


/**
 * Link to the official MySQL documentation
 * Be sure to include no trailing slash on the path
 */
$cfgManualBase = 'http://www.mysql.com/documentation/mysql/bychapter';


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
$cfgLeftBgColor         = '#D0DCE0';    // background color for the left frame
$cfgRightBgColor        = '#F5F5F5';    // background color for the right frame
$cfgBorder              = 0;            // border width on tables
$cfgThBgcolor           = '#D3DCE3';    // table header row colour
$cfgBgcolorOne          = '#CCCCCC';    // table data row colour
$cfgBgcolorTwo          = '#DDDDDD';    // table data row colour, alternate
$cfgTextareaCols        = 40;           // textarea size (columns) in edit mode
$cfgTextareaRows        = 7;            // textarea size (rows) in edit mode
$cfgLimitChars          = 50;           // max field data length in browse mode
$cfgModifyDeleteAtLeft  = TRUE;         // show edit/delete links on left side of browse
$cfgModifyDeleteAtRight = FALSE;        // show edit/delete links on right side of browse
$cfgLeftWidth           = 150;          // left frame width


/**
 * MySQL settings
 */
// Column types
$cfgColumnTypes = array(
   'TINYINT',
   'SMALLINT',
   'MEDIUMINT',
   'INT',
   'BIGINT',
   'FLOAT',
   'DOUBLE',
   'DECIMAL',
   'DATE',
   'DATETIME',
   'TIMESTAMP',
   'TIME',
   'YEAR',
   'CHAR',
   'VARCHAR',
   'TINYBLOB',
   'TINYTEXT',
   'TEXT',
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
$cfgFunctions = array(
   'ASCII',
   'CHAR',
   'SOUNDEX',
   'LCASE',
   'UCASE',
   'NOW',
   'PASSWORD',
   'MD5',
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


/**
 * Unset magic_quotes_runtime - do not change!
 */
set_magic_quotes_runtime(0);
?>
