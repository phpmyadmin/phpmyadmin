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

if ($GLOBALS['error_handler']->hasDisplayErrors()) {
    echo '<div class="clearfloat">';
    $GLOBALS['error_handler']->dispErrors();
    echo '</div>';
}

if (! empty($_SESSION['debug'])) {
    $sum_time = 0;
    $sum_exec = 0;
    foreach ($_SESSION['debug']['queries'] as $query) {
        $sum_time += $query['count'] * $query['time'];
        $sum_exec += $query['count'];
    }

    echo '<div>';
    echo count($_SESSION['debug']['queries']) . ' queries executed '
        . $sum_exec . ' times in ' . $sum_time . ' seconds';
    echo '<pre>';
    print_r($_SESSION['debug']);
    echo '</pre>';
    echo '</div>';
    $_SESSION['debug'] = array();
}

$footer = PMA_Footer::getInstance()->display();
?>
