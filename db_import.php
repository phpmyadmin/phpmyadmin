<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database import page
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Gets tables informations and displays top links
 */
require 'libraries/db_common.inc.php';
require 'libraries/db_info.inc.php';

$import_type = 'database';
require 'libraries/display_import.inc.php';

?>
