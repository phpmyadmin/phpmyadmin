<?php
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */

/**
 * Gets core libraries and defines some variables
 */
define('PHPMYADMIN', true);
require_once './libraries/core.lib.php';

if (! PMA_isValid($_GET['url']) || ! preg_match('/^https?:\/\/[^\n\r]*$/', $_GET['url'])) {
    header('Location: ' . $cfg['PmaAbsoluteUri']);
} else {
    header('Location: ' . $_GET['url']);
}
die();
?>
