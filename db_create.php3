<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * Executes the db creation sql query
 */
$result = mysql_query('CREATE DATABASE ' . backquote($db)) or mysql_die();


/**
 * Displays the result and moves back to the calling page
 */
$message = $strDatabase . ' ' . htmlspecialchars($db) . ' ' . $strHasBeenCreated;
require('./db_details.php3');

?>
