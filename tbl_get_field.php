<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like fields/columns, indexes, size, rows
 * and allows manipulation of indexes and columns/fields
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

PMA_checkParameters(array('db', 'table', 'where_clause', 'transform_key'));

if (!PMA_DBI_get_columns($db, $table)) {
    PMA_mysqlDie($strInvalidTableName);
}

$result = PMA_DBI_fetch_value('SELECT ' . PMA_backquote($transform_key) . ' FROM ' . PMA_backquote($table) . ' WHERE ' . $where_clause . ';');

/* Avoid corrupting data */
@ini_set('url_rewriter.tags','');

header('Content-Type: application/octet-stream');
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Content-Disposition: attachment; filename="' . $table . '-' .  $transform_key . '.bin"');
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
