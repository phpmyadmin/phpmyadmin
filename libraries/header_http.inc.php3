<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Sends http headers
 */
// Don't use cache (required for Opera)
$ctype = (isset($ctype) ? $ctype : 'html');
if ($ctype == 'css') {
        header('Content-Type: text/css; charset=ISO-8859-1');
} else {
    $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
    header('Expires: ' . $GLOBALS['now']); // rfc2616 - Section 14.21
    header('Last-Modified: ' . $GLOBALS['now']);
    header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
    header('Pragma: no-cache'); // HTTP/1.0
    if (!isset($is_transformation_wrapper)) {
        // Define the charset to be used
        header('Content-Type: text/' . $ctype . '; charset=' . $GLOBALS['charset']);
    }
}
?>