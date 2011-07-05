<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Triggers management.
 *
 * @package phpMyAdmin
 */

/**
 * Include required files
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';

/**
 * Include JavaScript libraries
 */
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'rte_common.js';
$GLOBALS['js_include'][] = 'db_triggers.js';

/**
 * Include all other files
 */
require_once './libraries/rte/rte_triggers.lib.php';
require_once './libraries/rte/rte_common.lib.php';

/**
 * Do the magic
 */
define('ITEM', 'triggers');
require_once './libraries/rte/rte_main.inc.php';

?>
