<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * finishes HTML output
 *
 * updates javascript variables in index.php for correct working with querywindow
 * and navigation frame refreshing
 *
 * send buffered data if buffered
 *
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 *
 * always use $GLOBALS, as this script is also included by functions
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * for PMA_setHistory()
 */
if (! PMA_isValid($_REQUEST['no_history']) && empty($GLOBALS['error_message'])
    && ! empty($GLOBALS['sql_query'])
) {
    PMA_setHistory(
        PMA_ifSetOr($GLOBALS['db'], ''),
        PMA_ifSetOr($GLOBALS['table'], ''),
        $GLOBALS['cfg']['Server']['user'],
        $GLOBALS['sql_query']
    );
}

$footer = PMA_Footer::getInstance()->display();
?>
