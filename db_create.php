<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php');
$js_to_run = 'functions.js';
require('./header.inc.php');

if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php');
}

PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'main.php?' . PMA_generate_common_url();

/**
 * Ensures the db name is valid
 */
if (PMA_MYSQL_INT_VERSION < 32306) {
    PMA_checkReservedWords($db, $err_url);
}


/**
 * Executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_backquote($db);
$result      = PMA_mysql_query('CREATE DATABASE ' . PMA_backquote($db)) or PMA_mysqlDie('', $sql_query, FALSE, $err_url);


/**
 * Displays the result and calls default page
 */
$message = $strDatabase . ' ' . htmlspecialchars($db) . ' ' . $strHasBeenCreated;
require('./' . $cfg['DefaultTabDatabase']);

?>
