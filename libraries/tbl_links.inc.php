<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
PMA_checkParameters(array('db', 'table'));

/**
 * Prepares links
 */
require_once './libraries/bookmark.lib.php';


/**
 * Set parameters for links
 */
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase'] . PMA_generate_common_url(array('db' => $db,));
$err_url   = $cfg['DefaultTabTable'] . PMA_generate_common_url($url_params);

/**
 * Displays headers
 */
require_once './libraries/header.inc.php';

if (PMA_Tracker::isActive() and PMA_Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])) {
    $msg = PMA_Message::notice('<a href="tbl_tracking.php?'.$url_query.'">'.sprintf(__('Tracking of %s is activated.'), htmlspecialchars($GLOBALS["db"] . '.' . $GLOBALS["table"])).'</a>');
    $msg->display();
}
?>
