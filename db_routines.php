<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Routines management.
 *
 * @package PhpMyAdmin
 */

/**
 * Include required files
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';

/**
 * Include all other files
 */
require_once 'libraries/check_user_privileges.lib.php';
require_once 'libraries/rte/rte_routines.lib.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'RTN';
require_once 'libraries/rte/rte_main.inc.php';
