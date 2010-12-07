<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';


/**
 * Displays PHP information
 */
if ($GLOBALS['cfg']['ShowPhpInfo']) {
    phpinfo();
}
?>
