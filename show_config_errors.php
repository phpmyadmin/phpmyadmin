<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple wrapper just to enable error reporting and include config
 *
 * @package PhpMyAdmin
 */

// rfc2616 - Section 14.21
header('Expires: ' . date(DATE_RFC1123));
// HTTP/1.1
header(
    'Cache-Control: no-store, no-cache, must-revalidate,'
    . '  pre-check=0, post-check=0, max-age=0'
);
if (isset($_SERVER['HTTP_USER_AGENT'])
    && stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE')
) {

    /* FIXME: Why is this special case for IE needed? */
    header('Pragma: public');
} else {
    header('Pragma: no-cache'); // HTTP/1.0
    // test case: exporting a database into a .gz file with Safari
    // would produce files not having the current time
    // (added this header for Safari but should not harm other browsers)
    header('Last-Modified: ' . date(DATE_RFC1123));
}
header('Content-Type: text/html; charset=utf-8');

require 'libraries/vendor_config.php';

error_reporting(E_ALL);
/**
 * Read config file.
 */
if (is_readable(CONFIG_FILE)) {
    include CONFIG_FILE;
}
?>
