<?php
/* $Id$ */

@set_time_limit(10000);


require("grab_globals.inc.php3");
 

include("lib.inc.php3");

if(!empty($sql_file) && $sql_file != "none" && ereg("^php[0-9A-Za-z_.-]+$", basename($sql_file))) {
  $sql_query = fread(fopen($sql_file, "r"), filesize($sql_file));
}

$pieces  = split_string($sql_query, ";");

if (count($pieces) == 1 && !empty($pieces[0])) {
  if(!get_magic_quotes_gpc()) {
    $sql_query = addslashes(trim($pieces[0]));
  } else {
    $sql_query = trim($pieces[0]);
  }
  include ("sql.php3");
  exit;
}

include("header.inc.php3");
for ($i=0; $i<count($pieces); ++$i) {
  $pieces[$i] = trim($pieces[$i]);
  if(!empty($pieces[$i])) {
    $result = mysql_db_query ($db, $pieces[$i]) or mysql_die();
  }
}

$sql_query = stripslashes($sql_query);
$message = $strSuccess;

include("db_details.php3");

?>
