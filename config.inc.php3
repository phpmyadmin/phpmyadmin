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
 * Server(s) configuration
 */
// The $cfgServers array starts with $cfgServers[1].  Do not use $cfgServers[0].
// You can disable a server config entry by setting host to ''.
$cfgServers[1]['host']          = 'localhost'; // MySQL hostname
$cfgServers[1]['port']          = '';          // MySQL port - leave blank for default port
$cfgServers[1]['adv_auth']      = FALSE;       // Use advanced authentication?
$cfgServers[1]['stduser']       = '';          // MySQL standard user (only needed with advanced auth)
$cfgServers[1]['stdpass']       = '';          // MySQL standard password (only needed with advanced auth)
$cfgServers[1]['user']          = 'root';      // MySQL user (only needed with basic auth)
$cfgServers[1]['password']      = '';          // MySQL password (only needed with basic auth)
$cfgServers[1]['only_db']       = '';          // If set to a db-name, only this db is accessible
                                               // It may also be an array of db-names
$cfgServers[1]['verbose']       = '';          // Verbose name for this host - leave blank to show the hostname
$cfgServers[1]['bookmarkdb']    = '';          // Bookmark db - leave blank for no bookmark support
$cfgServers[1]['bookmarktable'] = '';          // Bookmark table - leave blank for no bookmark support

$cfgServers[2]['host']          = '';
$cfgServers[2]['port']          = '';
$cfgServers[2]['adv_auth']      = FALSE;
$cfgServers[2]['stduser']       = '';
$cfgServers[2]['stdpass']       = '';
$cfgServers[2]['user']          = 'root';
$cfgServers[2]['password']      = '';
$cfgServers[2]['only_db']       = '';
$cfgServers[2]['verbose']       = '';
$cfgServers[2]['bookmarkdb']    = '';
$cfgServers[2]['bookmarktable'] = '';

$cfgServers[3]['host']          = '';
$cfgServers[3]['port']          = '';
$cfgServers[3]['adv_auth']      = FALSE;
$cfgServers[3]['stduser']       = '';
$cfgServers[3]['stdpass']       = '';
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
$cfgConfirm               = TRUE;   // confirm 'DROP TABLE' & 'DROP DATABASE'
$cfgPersistentConnections = FALSE;  // use persistent connections to MySQL database
$cfgShowBlob              = FALSE;  // display blob field contents in browse mode
$cfgProtectBlob		      = FALSE;  // disallow editing of blob fields in edit mode
$cfgShowSQL               = TRUE;   // show SQL queries as run
$cfgSkipLockedTables      = FALSE;  // mark used tables, make possible to show
                                    // locked tables (since MySQL 3.23.30)
$cfgMaxRows               = 30;     // maximum number of rows to display in browse mode
$cfgOrder                 = 'ASC';  // default for 'ORDER BY' clause
$cfgOBGzip 		          = TRUE;   // GZIP output buffering
$cfgGZipDump              = TRUE;   // Allow the use of gzip/bzip compression
$cfgBZipDump              = TRUE;   // for dump files


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
// Force: always use this language - must be defined in select_lang.inc.php3
// $cfgLang     = 'en';
// Loads language file
require('./select_lang.inc.php3');


/**
 * Customization & design
 */
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
   'ENCRYPT',
   'LCASE',
   'UCASE',
   'NOW',
   'PASSWORD',
   'ENCODE',
   'DECODE',
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
   'USER',
   'WEEKDAY'
);


/**
 * Unset magic_quotes_runtime - do not change!
 */
set_magic_quotes_runtime(0);
?>
