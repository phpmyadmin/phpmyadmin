<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 
require("./header.inc.php3");
mysql_select_db($db);

function my_handler($sql_insert)
{
    global $table, $db, $new_name;

    $sql_insert = ereg_replace("INSERT INTO $table", "INSERT INTO $new_name", $sql_insert);
    $result = mysql_query($sql_insert) or mysql_die();
    $sql_query = $sql_insert;
}

if (isset($new_name)) $new_name=trim($new_name); // Cleanup to suppress '' tables
if (isset($new_name) && $new_name!=""){
	$sql_structure = get_table_def($db, $table, "\n");
	// speedup copy table - staybyte - 22. Juni 2001
	if(MYSQL_MAJOR_VERSION >= 3.23){
		$sql_structure = ereg_replace("CREATE TABLE `$table`", "CREATE TABLE `$new_name`", $sql_structure);
		$result = mysql_query($sql_structure) or mysql_die();
		if($what == "data"){
			$query="INSERT INTO $new_name SELECT * FROM $table";
			$result = mysql_query($query) or mysql_die();
		}
	}
	else{
		$sql_structure = ereg_replace("CREATE TABLE `$table`", "CREATE TABLE $new_name", $sql_structure);
		$result = mysql_query($sql_structure) or mysql_die();
		if ($result!=false && $what == "data"){
			get_table_content($db, $table, 0, 0, "my_handler");
		}
	}
}
else mysql_die($strTableEmpty);

if (isset($sql_query))
    $sql_query .= "\n$sql_structure";
else
    $sql_query = "$sql_structure";

eval("\$message = \"$strCopyTableOK\";");
require("./db_details.php3");
?>
