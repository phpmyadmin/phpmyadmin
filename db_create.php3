<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
$js_to_run = 'functions.js';
require('./header.inc.php3');


/**
 * Ensures the db name is valid
 */
if (get_magic_quotes_gpc()) {
    $db = stripslashes($db);
}
if (MYSQL_INT_VERSION < 32306) {
    check_reserved_words($db);
}


/**
 * Executes the db creation sql query
 */
$local_query = 'CREATE DATABASE ' . backquote($db);
$result       = mysql_query('CREATE DATABASE ' . backquote($db)) or mysql_die('', $local_query, FALSE);


/**
 * Displays the result and moves back to the calling page
 */
$message = $strDatabase . ' ' . htmlspecialchars($db) . ' ' . $strHasBeenCreated;
require('./db_details.php3');

?>
