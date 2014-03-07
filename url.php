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

if (! PMA_isValid($_GET['url'])
    || ! preg_match('/^https?:\/\/[^\n\r]*$/', $_GET['url'])
) {
    header('Location: ' . $cfg['PmaAbsoluteUri']);
} else {
    header('Location: ' . $_GET['url']);
}
die();
?>
