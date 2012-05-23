<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Triggers management.
 *
 * @package PhpMyAdmin
 */

/**
 * Include required files
 */
require_once 'libraries/common.inc.php';

/**
 * Include JavaScript libraries
 */
$scripts = PMA_Header::getInstance()->getScripts();
$scripts->addFile('rte/common.js');
$scripts->addFile('rte/triggers.js');

/**
 * Include all other files
 */
require_once 'libraries/rte/rte_triggers.lib.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'TRI';
require_once 'libraries/rte/rte_main.inc.php';

?>
