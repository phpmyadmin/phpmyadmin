<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 

$old_name = $table;
$table = $new_name;
require("./header.inc.php3");

mysql_select_db($db);
$result = mysql_query("ALTER TABLE $old_name RENAME $new_name") or mysql_die();
$table = $old_name;
eval("\$message =  \"$strRenameTableOK\";");
$table = $new_name;
require("./tbl_properties.php3");
?>
