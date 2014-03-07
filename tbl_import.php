<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table import
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('import.js');

/**
 * Gets tables informations and displays top links
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_import.php&amp;back=tbl_import.php';

require_once 'libraries/tbl_info.inc.php';

$import_type = 'table';
require_once 'libraries/display_import.inc.php';

?>

