<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 * @package phpMyAdmin
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

header('Content-Type: ' . PMA_detectMIME($result));
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
$filename = PMA_sanitize_filename($table . '-' .  $transform_key . '.bin');
header('Content-Disposition: attachment; filename="' . $filename . '"');
if (PMA_USR_BROWSER_AGENT == 'IE') {
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
} else {
    header('Pragma: no-cache');
    // test case: exporting a database into a .gz file with Safari
    // would produce files not having the current time
    // (added this header for Safari but should not harm other browsers)
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}
echo $result;
?>
