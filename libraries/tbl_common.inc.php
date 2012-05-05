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
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/bookmark.lib.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));

$db_is_information_schema = PMA_is_system_schema($db);

/**
 * Set parameters for links
 * @deprecated
 */
$url_query = PMA_generate_common_url($db, $table);

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
 * Ensures the database and the table exist (else move to the "parent" script)
 */
require_once './libraries/db_table_exists.lib.php';

/**
 * Displays headers
 */
require_once './libraries/header.inc.php';

if (PMA_Tracker::isActive() and PMA_Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])) {
    $msg = PMA_Message::notice('<a href="tbl_tracking.php?'.$url_query.'">'.sprintf(__('Tracking of %s is activated.'), htmlspecialchars($GLOBALS["db"] . '.' . $GLOBALS["table"])).'</a>');
    $msg->display();
}

?>
