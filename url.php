<?php
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';

PMA_checkParameters(array('url'));

if (! preg_match('/^https?:\/\/[^\n\r]*$/', $GLOBALS['url'])) {
    header('Location: ' . $cfg['PmaAbsoluteUri']);
} else {
    header('Location: ' . $GLOBALS['url']);
}
?>
