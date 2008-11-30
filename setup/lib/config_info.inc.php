<?php
/**
 * Description of options with non-standard values, list of persistent options
 * and validator assignments.
 *
 * By default data types are taken from config.default.php, here we define
 * only allowed values for select fields and type overrides.
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load paths.
 */
require_once('./libraries/vendor_config.php');

$cfg_db = array();

// path to config file, relative to phpMyAdmin's root path
$cfg_db['_config_file_path'] = SETUP_CONFIG_FILE;

/**
 * Value meaning:
 * o array - select field, array contains allowed values
 * o string - type override
 *
 * Use normal array, paths won't be expanded
 */
$cfg_db['Servers'] = array(1 => array(
    'port'         => 'integer',
    'connect_type' => array('tcp', 'socket'),
    'extension'    => array('mysql', 'mysqli'),
    'auth_type'    => array('config', 'http', 'signon', 'cookie'),
    'AllowDeny'    => array(
        'order'    => array('', 'deny,allow', 'allow,deny', 'explicit')),
    'only_db'      => 'array'));
$cfg_db['RecodingEngine'] = array('auto', 'iconv', 'recode');
$cfg_db['DefaultCharset'] = $GLOBALS['cfg']['AvailableCharsets'];
$cfg_db['OBGzip'] = array('auto', true, false);
$cfg_db['ShowTooltipAliasTB'] = array('nested', true, false);
$cfg_db['DisplayDatabasesList'] = array('auto', true, false);
$cfg_db['LeftLogoLinkWindow'] = array('main', 'new');
$cfg_db['LeftDefaultTabTable'] = array(
    'tbl_structure.php', // fields list
    'tbl_sql.php',       // SQL form
    'tbl_select.php',    // search page
    'tbl_change.php',    // insert row page
    'sql.php');          // browse page
$cfg_db['NavigationBarIconic'] = array(true, false, 'both');
$cfg_db['Order'] = array('ASC', 'DESC', 'SMART');
$cfg_db['ProtectBinary'] = array(false, 'blob', 'all');
$cfg_db['CharEditing'] = array('input', 'textarea');
$cfg_db['PropertiesIconic'] = array(true, false, 'both');
$cfg_db['DefaultTabServer'] = array(
    'main.php',                // the welcome page (recommended for multiuser setups)
    'server_databases.php',    // list of databases
    'server_status.php',       // runtime information
    'server_variables.php',    // MySQL server variables
    'server_privileges.php',   // user management
    'server_processlist.php'); // process list
$cfg_db['DefaultTabDatabase'] = array(
    'db_structure.php',   // tables list
    'db_sql.php',         // SQL form
    'db_search.php',      // search query
    'db_operations.php'); // operations on database
$cfg_db['DefaultTabTable'] = array(
    'tbl_structure.php', // fields list
    'tbl_sql.php',       // SQL form
    'tbl_select.php',    // search page
    'tbl_change.php',    // insert row page
    'sql.php');          // browse page
$cfg_db['QueryWindowDefTab'] = array(
	'sql',     // SQL
	'files',   // Import files
	'history', // SQL history
	'full');   // All (SQL and SQL history)
$cfg_db['Import']['format'] = array(
    'csv',    // CSV
    'docsql', // DocSQL
    'ldi',    // CSV using LOAD DATA
    'sql');   // SQL
$cfg_db['Import']['sql_compatibility'] = array(
    'NONE', 'ANSI', 'DB2', 'MAXDB', 'MYSQL323', 'MYSQL40', 'MSSQL', 'ORACLE',
    // removed; in MySQL 5.0.33, this produces exports that
    // can't be read by POSTGRESQL (see our bug #1596328)
    //'POSTGRESQL',
    'TRADITIONAL');
$cfg_db['Import']['ldi_local_option'] = array('auto', true, false);
$cfg_db['Export']['format'] = array('codegen', 'csv', 'excel', 'htmlexcel',
    'htmlword', 'latex', 'ods', 'odt', 'pdf', 'sql', 'texytext', 'xls', 'xml',
    'yaml');
$cfg_db['Export']['compression'] = array('none', 'zip', 'gzip', 'bzip2');
$cfg_db['Export']['charset'] = array_merge(array(''), $GLOBALS['cfg']['AvailableCharsets']);

/**
 * Config options which will be placed in config file even if they are set
 * to their default values (use only full paths)
 */
$persist_keys = array(
    'DefaultLang',
    'ServerDefault',
    'UploadDir',
    'SaveDir',
    'Servers/1/verbose',
    'Servers/1/host',
    'Servers/1/port',
    'Servers/1/socket',
    'Servers/1/extension',
    'Servers/1/connect_type',
    'Servers/1/auth_type',
    'Servers/1/user',
    'Servers/1/password');

/**
 * Default values overrides
 * Use only full paths
 */
$cfg_db['_overrides'] = array();
$cfg_db['_overrides']['Servers/1/extension'] = extension_loaded('mysqli')
    ? 'mysqli' : 'mysql';

/**
 * Validator assignments (functions from validate.lib.php and 'validators'
 * object in scripts.js)
 * Use only full paths and form ids
 */
$cfg_db['_validators'] = array(
    'Server' => 'validate_server',
    'Server_pmadb' => 'validate_pmadb',
    'Servers/1/port' => 'validate_port_number',
    'Servers/1/hide_db' => 'validate_regex',
    'TrustedProxies' => 'validate_trusted_proxies',
    'LoginCookieValidity' => 'validate_positive_number',
    'LoginCookieStore' => 'validate_non_negative_number',
    'QueryHistoryMax' => 'validate_positive_number',
    'LeftFrameTableLevel' => 'validate_positive_number',
    'MaxRows' => 'validate_positive_number',
    'CharTextareaCols' => 'validate_positive_number',
    'CharTextareaRows' => 'validate_positive_number',
    'InsertRows' => 'validate_positive_number',
    'ForeignKeyMaxLimit' => 'validate_positive_number',
    'Import/skip_queries' => 'validate_non_negative_number');
?>
