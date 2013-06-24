<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display list of server engines and additional information about them
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/StorageEngine.class.php';
require 'libraries/server_engines.lib.php';

/**
 * Displays the sub-page heading
 */
$html = '<h2>' . "\n"
    . PMA_Util::getImage('b_engine.png')
    . "\n" . __('Storage Engines') . "\n"
    . '</h2>' . "\n";
        
/**
 * start output
 */
$response = PMA_Response::getInstance();
$response->addHTML($html);
$response->addHTML(PMA_getHtmlForServerEngines());

exit;

?>
