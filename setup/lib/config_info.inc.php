<?php
/**
 * Description of options with non-standard values, list of persistent options
 * and validator assignments.
 *
 * By default data types are taken from config.default.php, here we define
 * only allowed values for select fields and type overrides.
 *
 * @package    phpMyAdmin-setup
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load config value database ($cfg_db)
 */
require './libraries/config.values.php';

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
