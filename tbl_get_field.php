<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 *
 * @package PhpMyAdmin
 */

/**
 * Common functions.
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mime.lib.php';

/**
 * Sets globals from $_GET
 */
$get_params = array(
    'where_clause',
    'transform_key'
);

foreach ($get_params as $one_get_param) {
    if (isset($_GET[$one_get_param])) {
        $GLOBALS[$one_get_param] = $_GET[$one_get_param];
    }
}

/* Check parameters */
PMA_Util::checkParameters(
    array('db', 'table', 'where_clause', 'transform_key')
);

/* Select database */
if (!PMA_DBI_select_db($db)) {
    PMA_Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '', ''
    );
}

/* Check if table exists */
if (!PMA_DBI_get_columns($db, $table)) {
    PMA_Util::mysqlDie(__('Invalid table name'));
}

/* Grab data */
$sql = 'SELECT ' . PMA_Util::backquote($transform_key)
    . ' FROM ' . PMA_Util::backquote($table)
    . ' WHERE ' . $where_clause . ';';
$result = PMA_DBI_fetch_value($sql);

/* Check return code */
if ($result === false) {
    PMA_Util::mysqlDie(__('MySQL returned an empty result set (i.e. zero rows).'), $sql);
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
