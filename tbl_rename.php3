<?php
/* $Id$ */


require("./grab_globals.inc.php3");

if (isset($new_name)) $new_name=trim($new_name); // Cleanup to suppress '' tables
if (isset($new_name) && $new_name!=""){

	$old_name = $table;
	$table = $new_name;

	include("./header.inc.php3");

	mysql_select_db($db);
	$result = mysql_query("ALTER TABLE $old_name RENAME $new_name") or mysql_die();
	$table = $old_name;
	eval("\$message =  \"$strRenameTableOK\";");
	$table = $new_name;
}
else{
	include("./header.inc.php3");
	mysql_die($strTableEmpty);
}

require("./tbl_properties.php3");
?>
