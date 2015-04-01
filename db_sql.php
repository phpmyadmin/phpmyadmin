<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database SQL executor
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
$scripts->addFile('jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

require 'libraries/db_common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'db_sql.php';
$back = 'db_sql.php';

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML(
    PMA_getHtmlForSqlQueryForm(
        true, false,
        isset($_REQUEST['delimiter'])
        ? htmlspecialchars($_REQUEST['delimiter'])
        : ';'
    )
);

?>
