<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Events management.
 *
 * @package PhpMyAdmin
 */

/**
 * Include required files
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/CommonFunctions.class.php';

/**
 * Include JavaScript libraries
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/timepicker.js');
$scripts->addFile('rte/common.js');
$scripts->addFile('rte/events.js');

/**
 * Include all other files
 */
require_once 'libraries/rte/rte_events.lib.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'EVN';
require_once 'libraries/rte/rte_main.inc.php';

?>
