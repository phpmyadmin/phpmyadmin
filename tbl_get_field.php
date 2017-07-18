<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;

/**
 * Common functions.
 */
// we don't want the usual PhpMyAdmin\Response-generated HTML above the column's
// data
define('PMA_BYPASS_GET_INSTANCE', 1);
require_once 'libraries/common.inc.php';
require_once 'libraries/mime.lib.php';

/* Check parameters */
PhpMyAdmin\Util::checkParameters(
    array('db', 'table')
);

/* Select database */
if (!$GLOBALS['dbi']->selectDb($db)) {
    PhpMyAdmin\Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '', false
    );
}

/* Check if table exists */
if (!$GLOBALS['dbi']->getColumns($db, $table)) {
    PhpMyAdmin\Util::mysqlDie(__('Invalid table name'));
}

/* Grab data */
$sql = 'SELECT ' . PhpMyAdmin\Util::backquote($_GET['transform_key'])
    . ' FROM ' . PhpMyAdmin\Util::backquote($table)
    . ' WHERE ' . $_GET['where_clause'] . ';';
$result = $GLOBALS['dbi']->fetchValue($sql);

/* Check return code */
if ($result === false) {
    PhpMyAdmin\Util::mysqlDie(
        __('MySQL returned an empty result set (i.e. zero rows).'), $sql
    );
}

/* Avoid corrupting data */
@ini_set('url_rewriter.tags', '');

Core::downloadHeader(
    $table . '-' .  $_GET['transform_key'] . '.bin',
    PMA_detectMIME($result),
    strlen($result)
);
echo $result;
