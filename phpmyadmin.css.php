<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
// sometimes, we lose $_REQUEST['js_frame']
define('PMA_FRAME', (! empty($_REQUEST['js_frame']) && is_string($_REQUEST['js_frame'])) ? $_REQUEST['js_frame'] : 'right');

define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
require_once './libraries/sqlparser.lib.php';

// MSIE 6 (at least some unpatched versions) has problems loading CSS
// when zlib_compression is on
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER == '6'
 && (ini_get('zlib.output_compression'))) {
    @ini_set('zlib.output_compression', 'Off');
}

if ($GLOBALS['text_dir'] === 'ltr') {
    $right = 'right';
    $left = 'left';
} else {
    $right = 'left';
    $left = 'right';
}

// Send correct type:
header('Content-Type: text/css; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

$_SESSION['PMA_Theme_Manager']->printCss(PMA_FRAME);
?>
