<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Routines management.
 *
 * @package phpMyAdmin
 */

/**
 * Include required files
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';
require_once './libraries/mysql_charsets.lib.php';
if (PMA_DRIZZLE) {
    require_once './libraries/data_drizzle.inc.php';
} else {
    require_once './libraries/data_mysql.inc.php';
}

/**
 * Include JavaScript libraries
 */
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'rte/common.js';
$GLOBALS['js_include'][] = 'rte/routines.js';

/**
 * Include all other files
 */
require_once './libraries/rte/rte_routines.lib.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'RTN';
require_once './libraries/rte/rte_main.inc.php';

?>
