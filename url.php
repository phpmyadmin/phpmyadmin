<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 *
 * @package PhpMyAdmin
 */

/**
 * Gets core libraries and defines some variables
 */
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
/**
 * JavaScript escaping.
 */
require_once './libraries/js_escape.lib.php';

if (! PMA_isValid($_REQUEST['url'])
    || ! preg_match('/^https?:\/\/[^\n\r]*$/', $_REQUEST['url'])
    || ! PMA_isAllowedDomain($_REQUEST['url'])
) {
    header('Location: ' . $cfg['PmaAbsoluteUri']);
} else {
    // JavaScript redirection is necessary. Because if header() is used
    //  then web browser sometimes does not change the HTTP_REFERER
    //  field and so with old URL as Referer, token also goes to
    //  external site.
    echo "<script type='text/javascript'>
            window.onload=function(){
                window.location='" . PMA_escapeJsString($_REQUEST['url']) . "';
            }
        </script>";
    // Display redirecting msg on screen.
    printf(__('Taking you to %s.'), htmlspecialchars($_REQUEST['url']));
}
die();
