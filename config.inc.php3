<?php
/* $Id$ */


/**
 * phpMyAdmin Configuration File
 *
 * All directives are explained in Documentation.html
 */

/**
 * Sets the php error reporting - Please do not change this line!
 */
$old_error_rep = error_reporting(E_ALL);


/**
 * Your phpMyAdmin url
 *
 * Complete the variable below with the full url ie
 *    http://www.your_web.net/path_to_your_phpMyAdmin_directory/
 *
 * It must contain characters that are valid for a URL, and the path is
 * case sensitive on some Web servers, for example Unix-based servers.
 */
$cfg['PmaAbsoluteUri'] = '';


/**
 * Server(s) configuration
 */
$i = 0;
// The $cfg['Servers'] array starts with $cfg['Servers'][1].  Do not use $cfg['Servers'][0].
// You can disable a server config entry by setting host to ''.
$i++;
$cfg['Servers'][$i]['host']          = 'localhost'; // MySQL hostname
$cfg['Servers'][$i]['port']          = '';          // MySQL port - leave blank for default port
$cfg['Servers'][$i]['socket']        = '';          // Path to the socket - leave blank for default socket
$cfg['Servers'][$i]['connect_type']  = 'tcp';       // How to connect to MySQL server ('tcp' or 'socket')
$cfg['Servers'][$i]['controluser']   = '';          // MySQL control user settings
                                                    // (this user must have read-only
$cfg['Servers'][$i]['controlpass']   = '';          // access to the "mysql/user"
                                                    // and "mysql/db" tables)
$cfg['Servers'][$i]['auth_type']     = 'config';    // Authentication method (config, http or cookie based)?
$cfg['Servers'][$i]['user']          = 'root';      // MySQL user
$cfg['Servers'][$i]['password']      = '';          // MySQL password (only needed
                                                    // with 'config' auth_type)
$cfg['Servers'][$i]['only_db']       = '';          // If set to a db-name, only
                                                    // this db is displayed
                                                    // at left frame
                                                    // It may also be an array
                                                    // of db-names
$cfg['Servers'][$i]['verbose']       = '';          // Verbose name for this host - leave blank to show the hostname
$cfg['Servers'][$i]['bookmarkdb']    = '';          // Bookmark db - leave blank for no bookmark support
$cfg['Servers'][$i]['bookmarktable'] = '';          // Bookmark table - leave blank for no bookmark support
$cfg['Servers'][$i]['relation']      = '';          // table to describe the relation between links (see doc)
                                                    //   - leave blank for no relation-links support
$cfg['Servers'][$i]['table_info']    = '';          // table to describe the display fields
                                                    //   - leave blank for no display fields support
$cfg['Servers'][$i]['table_coords']  = '';          // table to describe the tables position for the PDF
                                                    //   schema - leave blank for no PDF schema support
$cfg['Servers'][$i]['pdf_pages']     = '';          // table to describe pages of relationpdf
$cfg['Servers'][$i]['AllowDeny']['order']           // Host authentication order, leave blank to not use
                                     = '';
$cfg['Servers'][$i]['AllowDeny']['rules']           // Host authentication rules, leave blank for defaults
                                     = array();


$i++;
$cfg['Servers'][$i]['host']          = '';
$cfg['Servers'][$i]['port']          = '';
$cfg['Servers'][$i]['socket']        = '';
$cfg['Servers'][$i]['connect_type']  = 'tcp';
$cfg['Servers'][$i]['controluser']   = '';
$cfg['Servers'][$i]['controlpass']   = '';
$cfg['Servers'][$i]['auth_type']     = 'config';
$cfg['Servers'][$i]['user']          = 'root';
$cfg['Servers'][$i]['password']      = '';
$cfg['Servers'][$i]['only_db']       = '';
$cfg['Servers'][$i]['verbose']       = '';
$cfg['Servers'][$i]['bookmarkdb']    = '';
$cfg['Servers'][$i]['bookmarktable'] = '';
$cfg['Servers'][$i]['relation']      = '';
$cfg['Servers'][$i]['table_info']    = '';
$cfg['Servers'][$i]['table_coords']  = '';
$cfg['Servers'][$i]['pdf_pages']     = '';
$cfg['Servers'][$i]['AllowDeny']['order']
                                     = '';
$cfg['Servers'][$i]['AllowDeny']['rules']
                                     = array();

$i++;
$cfg['Servers'][$i]['host']          = '';
$cfg['Servers'][$i]['port']          = '';
$cfg['Servers'][$i]['socket']        = '';
$cfg['Servers'][$i]['connect_type']  = 'tcp';
$cfg['Servers'][$i]['controluser']   = '';
$cfg['Servers'][$i]['controlpass']   = '';
$cfg['Servers'][$i]['auth_type']     = 'config';
$cfg['Servers'][$i]['user']          = 'root';
$cfg['Servers'][$i]['password']      = '';
$cfg['Servers'][$i]['only_db']       = '';
$cfg['Servers'][$i]['verbose']       = '';
$cfg['Servers'][$i]['bookmarkdb']    = '';
$cfg['Servers'][$i]['bookmarktable'] = '';
$cfg['Servers'][$i]['relation']      = '';
$cfg['Servers'][$i]['table_info']    = '';
$cfg['Servers'][$i]['table_coords']  = '';
$cfg['Servers'][$i]['pdf_pages']     = '';
$cfg['Servers'][$i]['AllowDeny']['order']
                                     = '';
$cfg['Servers'][$i]['AllowDeny']['rules']
                                     = array();

// If you have more than one server configured, you can set $cfg['ServerDefault']
// to any one of them to autoconnect to that server when phpMyAdmin is started,
// or set it to 0 to be given a list of servers without logging in
// If you have only one server configured, $cfg['ServerDefault'] *MUST* be
// set to that server.
$cfg['ServerDefault'] = 1;              // Default server (0 = no default server)
$cfg['Server']        = '';
unset($cfg['Servers'][0]);


/**
 * Other core phpMyAdmin settings
 */
$cfg['OBGzip']                = TRUE;   // use GZIP output buffering if possible
$cfg['PersistentConnections'] = FALSE;  // use persistent connections to MySQL database
$cfg['ExecTimeLimit']         = 300;    // maximum execution time in seconds (0 for no limit)
$cfg['SkipLockedTables']      = FALSE;  // mark used tables, make possible to show
                                        // locked tables (since MySQL 3.23.30)
$cfg['ShowSQL']               = TRUE;   // show SQL queries as run
$cfg['AllowUserDropDatabase'] = FALSE;  // show a 'Drop database' link to normal users
$cfg['Confirm']               = TRUE;   // confirm 'DROP TABLE' & 'DROP DATABASE'
$cfg['LoginCookieRecall']     = TRUE;   // recall previous login in cookie auth. mode or not
$cfg['UseDbSearch']           = TRUE;   // whether to enable the "database search" feature
                                        // or not

// Left frame setup
$cfg['LeftFrameLight']        = TRUE;   // use a select-based menu and display only the
                                        // current tables in the left frame.
$cfg['ShowTooltip']           = TRUE;   // display table comment as tooltip in left frame

// In the main frame, at startup...
$cfg['ShowStats']             = TRUE;   // allow to display statistics and space usage in
                                        // the pages about database details and table
                                        // properties
$cfg['ShowMysqlInfo']         = FALSE;  // whether to display the "MySQL runtime
$cfg['ShowMysqlVars']         = FALSE;  // information", "MySQL system variables", "PHP
$cfg['ShowPhpInfo']           = FALSE;  // information" and "change password" links for
$cfg['ShowChgPassword']       = FALSE;  // simple users or not

// In browse mode...
$cfg['ShowBlob']              = FALSE;  // display blob field contents
$cfg['NavigationBarIconic']   = TRUE;   // do not display text inside navigation bar buttons
$cfg['ShowAll']               = FALSE;  // allows to display all the rows
$cfg['MaxRows']               = 30;     // maximum number of rows to display
$cfg['Order']                 = 'ASC';  // default for 'ORDER BY' clause (valid
                                        // values are 'ASC', 'DESC' or 'SMART' -ie
                                        // descending order for fields of type
                                        // TIME, DATE, DATETIME & TIMESTAMP,
                                        // ascending order else-)

// In edit mode...
$cfg['ProtectBinary']         = 'blob'; // disallow editing of binary fields
                                        // valid values are:
                                        //   FALSE  allow editing
                                        //   'blob' allow editing except for BLOB fields
                                        //   'all'  disallow editing
$cfg['ShowFunctionFields']    = TRUE;   // Display the function fields in edit/insert mode

// For the export features...
$cfg['ZipDump']               = TRUE;   // Allow the use of zip/gzip/bzip
$cfg['GZipDump']              = TRUE;   // compression for
$cfg['BZipDump']              = TRUE;   // dump files


/**
 * Link to the official MySQL documentation
 * Be sure to include no trailing slash on the path
 */
$cfg['ManualBaseShort'] = 'http://www.mysql.com/doc';


/**
 * Language settings
 */
// Default language to use, if not browser-defined or user-defined
$cfg['DefaultLang'] = 'en';
// Force: always use this language - must be defined in
//        libraries/select_lang.lib.php3
// $cfg['Lang']     = 'en';
// Loads language file
require('./libraries/select_lang.lib.php3');


/**
 * Customization & design
 */
$cfg['LeftWidth']           = 150;          // left frame width
$cfg['LeftBgColor']         = '#D0DCE0';    // background color for the left frame
$cfg['LeftPointerColor']    = '#CCFFCC';    // color of the pointer in left frame
                                            // (blank for no pointer)
$cfg['RightBgColor']        = '#F5F5F5';    // background color for the right frame
$cfg['RightBgImage']        = '';           // path to a background image for the right frame
                                            // (leave blank for no background image)
$cfg['Border']              = 0;            // border width on tables
$cfg['ThBgcolor']           = '#D3DCE3';    // table header row colour
$cfg['BgcolorOne']          = '#CCCCCC';    // table data row colour
$cfg['BgcolorTwo']          = '#DDDDDD';    // table data row colour, alternate
$cfg['BrowsePointerColor']  = '#CCFFCC';    // color of the pointer in browse mode
                                            // (blank for no pointer)
$cfg['BrowseMarkerColor']   = '#FFCC99';    // color of the marker (visually marks row
                                            // by clicking on it) in browse mode
                                            // (blank for no marker)
$cfg['TextareaCols']        = 40;           // textarea size (columns) in edit mode
                                            // (this value will be emphasized (*2) for sql
                                            // query textareas)
$cfg['TextareaRows']        = 7;            // textarea size (rows) in edit mode
$cfg['LimitChars']          = 50;           // max field data length in browse mode
$cfg['ModifyDeleteAtLeft']  = TRUE;         // show edit/delete links on left side of browse
                                            // (or at the top with vertical browse)
$cfg['ModifyDeleteAtRight'] = FALSE;        // show edit/delete links on right side of browse
                                            // (or at the bottom with vertical browse)
$cfg['DefaultDisplay']      = 'horizontal'; // default display direction (horizontal|vertical)
$cfg['RepeatCells']         = 100;          // repeat header names every X cells? (0 = deactivate)

$cfg['UseSyntaxColoring']   = TRUE;         // use syntaxcoloring on output of SQL, might be a little slower
//  Colors used for Syntaxcoloring of SQL Statements
$cfg['colorFunctions']      = 'red';
$cfg['colorKeywords']       = 'blue';
$cfg['colorStrings']        = 'green';
$cfg['colorColType']        = '#FF9900';
$cfg['colorAdd']            = '#9999CC';


/**
 * MySQL settings
 */
// Column types;
// varchar, tinyint, text and date are listed first, based on estimated popularity
$cfg['ColumnTypes'] = array(
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
$cfg['AttributeTypes'] = array(
   '',
   'BINARY',
   'UNSIGNED',
   'UNSIGNED ZEROFILL'
);

// Available functions
if ($cfg['ShowFunctionFields']) {
    $cfg['Functions'] = array(
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
       'WEEKDAY',
       'CONCAT'
    );
} // end if

if($cfg['UseSyntaxColoring']) {
    $cfg['keywords']=array(
        'SELECT',
        'INSERT',
        'LEFT',
        'UPDATE',
        'REPLACE',
        'EXPLAIN',
        'FROM',
        'WHERE',
        'LIMIT',
        'INTO',
        'ALTER',
        'ADD',
        'DROP',
        'GROUP',
        'ORDER',
        'CHANGE',
        'CREATE',
        'DELETE'
    );
} // end if
if($cfg['UseSyntaxColoring']) {
    $cfg['additional']=array(
        'TABLE',
        'DEFAULT',
        'NULL',
        'NOT',
        'INDEX',
        'PRIMARY',
        'KEY',
        'UNIQUE',
        'BINARY',
        'UNSIGNED',
        'ZEROFILL',
        'AUTO_INCREMENT',
        'AND',
        'OR',
        'DISTINCT',
        'DISTINCTROW',
        'BY',
        'ON',
        'JOIN',
        'BETWEEN',
        'BETWEEN',
        'IN',
        'IF',
        'ELSE',
        'SET'
    );
}   
/**
 * Unset magic_quotes_runtime - do not change!
 */
set_magic_quotes_runtime(0);

/**
 * Restore old error_reporting mode - do not change either!
 */
error_reporting($old_error_rep);
unset($old_error_rep);
?>
