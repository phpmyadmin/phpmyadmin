<?php
/**
 * Common includes for the table level views
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $table, $db_is_system_schema, $url_query, $url_params, $cfg, $dbi;

// Check parameters
Util::checkParameters(['db', 'table']);

$db_is_system_schema = $dbi->isSystemSchema($db);

/**
 * Set parameters for links
 * @deprecated
 */
$url_query = Url::getCommon(['db' => $db, 'table' => $table]);

/**
 * Set parameters for links
 */
$url_params = [];
$url_params['db']    = $db;
$url_params['table'] = $table;

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = Util::getScriptNameForOption(
    $cfg['DefaultTabDatabase'],
    'database'
);
$err_url_0 .= Url::getCommon(['db' => $db], strpos($err_url_0, '?') === false ? '?' : '&');

$err_url = Util::getScriptNameForOption(
    $cfg['DefaultTabTable'],
    'table'
);
$err_url .= Url::getCommon($url_params, strpos($err_url, '?') === false ? '?' : '&');

/**
 * Ensures the database and the table exist (else move to the "parent" script)
 * Skip test if we are exporting as we can't tell whether a table name is an alias (which would fail the test).
 */
if (($_GET['route'] ?? $_POST['route'] ?? '') === '/table/export') {
    require_once ROOT_PATH . 'libraries/db_table_exists.inc.php';
}
