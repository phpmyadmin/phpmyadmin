<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Runs common work
 */
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'codemirror/lib/codemirror.js';
$GLOBALS['js_include'][] = 'codemirror/mode/mysql/mysql.js';

require './libraries/db_common.inc.php';
require_once './libraries/sql_query_form.lib.php';

// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'db_sql.php';
$back = 'db_sql.php';

/**
 * Gets informations about the database and, if it is empty, move to the
 * "db_structure.php" script where table can be created
 */
require './libraries/db_info.inc.php';
if ($num_tables == 0 && empty($db_query_force)) {
    $sub_part   = '';
    $is_info    = true;
    include './db_structure.php';
    exit();
}

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm(true, false, isset($_REQUEST['delimiter']) ? htmlspecialchars($_REQUEST['delimiter']) : ';');

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
