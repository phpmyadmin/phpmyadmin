<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
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

if ($db === 'information_schema') {
    $db_is_information_schema = true;
} else {
    $db_is_information_schema = false;
}

/**
 * Set parameters for links
 * @deprecated
 */
$url_query = PMA_generate_common_url($db, $table);

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

?>
