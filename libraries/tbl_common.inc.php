<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common includes for the table level views
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $db, $table;

// Check parameters
PhpMyAdmin\Util::checkParameters(['db', 'table']);

$db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($db);

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
$err_url_0 = PhpMyAdmin\Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabDatabase'],
    'database'
)
    . Url::getCommon(['db' => $db]);

$err_url = PhpMyAdmin\Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabTable'],
    'table'
)
    . Url::getCommon($url_params);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 * Skip test if we are exporting as we can't tell whether a table name is an alias (which would fail the test).
 */
if (basename($_SERVER['PHP_SELF']) != 'tbl_export.php') {
    require_once ROOT_PATH . 'libraries/db_table_exists.inc.php';
}
