<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 
require("./header.inc.php3");

$result = mysql_query("CREATE DATABASE `$db`") or mysql_die();

$message = "$strDatabase $db $strHasBeenCreated";
require("./db_details.php3");

?>
