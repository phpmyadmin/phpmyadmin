<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 * @package PhpMyAdmin
 */

/**
 * Common functions.
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mime.lib.php';

$common_functions = PMA_CommonFunctions::getInstance();

/* Check parameters */
$common_functions->checkParameters(
    array('db', 'table', 'where_clause', 'transform_key')
);

/* Select database */
if (!PMA_DBI_select_db($db)) {
    $common_functions->mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '', ''
    );
}

/* Check if table exists */
if (!PMA_DBI_get_columns($db, $table)) {
    $common_functions->mysqlDie(__('Invalid table name'));
}

/* Grab data */
$sql = 'SELECT ' . $common_functions->backquote($transform_key)
    . ' FROM ' . $common_functions->backquote($table)
    . ' WHERE ' . $where_clause . ';';
$result = PMA_DBI_fetch_value($sql);

/* Check return code */
if ($result === false) {
    $common_functions->mysqlDie(__('MySQL returned an empty result set (i.e. zero rows).'), $sql);
}

/* Avoid corrupting data */
@ini_set('url_rewriter.tags', '');

PMA_downloadHeader(
    $table . '-' .  $transform_key . '.bin',
    PMA_detectMIME($result),
    strlen($result)
);
echo $result;
?>
