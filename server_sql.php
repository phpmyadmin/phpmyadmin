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
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');

require_once 'libraries/server_common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

/**
 * Query box, bookmark, insert data from textfile
 */
PMA_sqlQueryForm();

?>
