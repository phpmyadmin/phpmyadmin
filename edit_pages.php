<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
include 'libraries/common.inc.php';
include 'libraries/designer/edit_pages.lib.php';


$html = PMA_getHtmlForEditPages();

$response = PMA_Response::getInstance();
$response->addHTML($html);

?>