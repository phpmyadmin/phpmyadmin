<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common includes for the table level views
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\URL;

if (! defined('PHPMYADMIN')) {
    exit;
}

// Check parameters
PMA\libraries\Util::checkParameters(array('db', 'table'));

$db_is_system_schema = $GLOBALS['dbi']->isSystemSchema($db);

/**
 * Set parameters for links
 * @deprecated
 */
$url_query = URL::getCommon(array('db' => $db, 'table' => $table));

/**
 * Set parameters for links
 */
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = PMA\libraries\Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
)
    . URL::getCommon(array('db' => $db));

$err_url = PMA\libraries\Util::getScriptNameForOption(
    $GLOBALS['cfg']['DefaultTabTable'], 'table'
)
    . URL::getCommon($url_params);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 * Skip test if we are exporting as we can't tell whether a table name is an alias (which would fail the test).
 */
if (basename($_SERVER['PHP_SELF']) != 'tbl_export.php') {
    require_once './libraries/db_table_exists.inc.php';
}
