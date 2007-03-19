<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* $Id$ */


/**
 * Gets core libraries and defines some variables
 */
define( 'PMA_MINIMUM_COMMON', true );
require_once('./libraries/common.lib.php');


/**
 * Displays PHP information
 */
if ( $GLOBALS['cfg']['ShowPhpInfo'] ) {
    phpinfo();
}
?>
