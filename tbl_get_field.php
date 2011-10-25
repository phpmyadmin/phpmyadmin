<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 * @package PhpMyAdmin
 */

/**
 * Common functions.
 */
require_once './libraries/common.inc.php';
require_once './libraries/mime.lib.php';

/* Check parameters */
PMA_checkParameters(array('db', 'table', 'where_clause', 'transform_key'));

/* Select database */
if (!PMA_DBI_select_db($db)) {
    PMA_mysqlDie(sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '', '');
}

/* Check if table exists */
if (!PMA_DBI_get_columns($db, $table)) {
    PMA_mysqlDie(__('Invalid table name'));
}

/* Grab data */
$sql = 'SELECT ' . PMA_backquote($transform_key) . ' FROM ' . PMA_backquote($table) . ' WHERE ' . $where_clause . ';';
$result = PMA_DBI_fetch_value($sql);

/* Check return code */
if ($result === false) {
    PMA_mysqlDie(__('MySQL returned an empty result set (i.e. zero rows).'), $sql);
}

/* Avoid corrupting data */
@ini_set('url_rewriter.tags', '');

PMA_download_header(
    $table . '-' .  $transform_key . '.bin',
    PMA_detectMIME($result),
    strlen($result)
    );
echo $result;
?>
