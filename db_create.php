<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
$js_to_run = 'functions.js';
require_once('./header.inc.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');


PMA_checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'main.php?' . PMA_generate_common_url();

/**
 * Builds and executes the db creation sql query
 */
$sql_query = 'CREATE DATABASE ' . PMA_backquote($db);
if (isset($db_charset) && isset($mysql_charsets) && in_array($db_charset, $mysql_charsets)) {
    $sql_query .= ' DEFAULT CHARACTER SET ' . $db_charset;
}
$sql_query .= ';';

$result = PMA_DBI_query($sql_query);


/**
 * Displays the result and calls default page
 */
$message = $strDatabase . ' ' . htmlspecialchars($db) . ' ' . $strHasBeenCreated;
require_once('./' . $cfg['DefaultTabDatabase']);

?>
