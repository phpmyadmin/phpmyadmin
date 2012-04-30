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
 * Does the common work
 */
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';

require_once 'libraries/server_common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm();

/**
 * Displays the footer
 */
require 'libraries/footer.inc.php';
?>
