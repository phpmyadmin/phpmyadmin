<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * Runs common work
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('functions.js');
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');

require 'libraries/db_common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'db_sql.php';
$back = 'db_sql.php';

/**
 * Sets globals from $_GET
 */

$get_params = array(
    'db_query_force'
);

foreach ($get_params as $one_get_param) {
    if (isset($_GET[$one_get_param])) {
        $GLOBALS[$one_get_param] = $_GET[$one_get_param];
    }
}

/**
 * Gets informations about the database and, if it is empty, move to the
 * "db_structure.php" script where table can be created
 */
require 'libraries/db_info.inc.php';
if ($num_tables == 0 && empty($db_query_force)) {
    $sub_part   = '';
    $is_info    = true;
    include 'db_structure.php';
    exit();
}

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm(
    true, false,
    isset($_REQUEST['delimiter']) ? htmlspecialchars($_REQUEST['delimiter']) : ';'
);

?>
