<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Response;

/**
 * Gets core libraries and defines some variables
 */
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';

// Only output the http headers
$response = Response::getInstance();
$response->getHeader()->sendHttpHeaders();
$response->disable();

if (! Core::isValid($_GET['url'])
    || ! preg_match('/^https:\/\/[^\n\r]*$/', $_GET['url'])
    || ! Core::isAllowedDomain($_GET['url'])
) {
    Core::sendHeaderLocation('./');
} else {
    // JavaScript redirection is necessary. Because if header() is used
    //  then web browser sometimes does not change the HTTP_REFERER
    //  field and so with old URL as Referer, token also goes to
    //  external site.
    echo "<script type='text/javascript'>
            window.onload=function(){
                window.location='" , Sanitize::escapeJsString($_GET['url']) , "';
            }
        </script>";
    // Display redirecting msg on screen.
    // Do not display the value of $_GET['url'] to avoid showing injected content
    echo __('Taking you to the target site.');
}
die();
