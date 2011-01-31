<?php
/**
 * URL redirector to avoid leaking Referer with some sensitive information.
 */

define('PMA_MINIMUM_COMMON', TRUE);

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';

if (empty($GLOBALS['url']) || ! preg_match('/^https?:\/\/[^\n\r]*$/', $GLOBALS['url'])) {
    header('Location: ' . $cfg['PmaAbsoluteUri']);
} else {
    header('Location: ' . $GLOBALS['url']);
}
?>
