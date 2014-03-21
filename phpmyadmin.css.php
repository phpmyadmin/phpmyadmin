<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main stylesheet loader
 *
 * @package PhpMyAdmin
 */

/**
 *
 */

define('PMA_MINIMUM_COMMON', true);
require_once 'libraries/common.inc.php';

// MSIE 6 (at least some unpatched versions) has problems loading CSS
// when zlib_compression is on
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER == '6'
    && (ini_get('zlib.output_compression'))
) {
    @ini_set('zlib.output_compression', 'Off');
}

// Send correct type:
header('Content-Type: text/css; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

$_SESSION['PMA_Theme_Manager']->printCss();
?>
