<?php
/* $Id$ */

@set_time_limit(10000);

require("./grab_globals.inc.php3");

require("./lib.inc.php3");


// set up default values
$view_bookmark = 0;
$sql_bookmark  = isset($sql_bookmark) ? $sql_bookmark : "";
$sql_query     = isset($sql_query)    ? $sql_query    : "";
$sql_file      = isset($sql_file)     ? $sql_file     : "none";


// Bookmark Support
if(!empty($id_bookmark)) {
  switch($action_bookmark) {
    case 0:
      $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
      break;
   
    case 1:
      $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
      $view_bookmark = 1;
      break;
  
    case 2:
      $sql_query = delete_bookmarks($db, $cfgBookmark, $id_bookmark);
      break;
  }
}


if($sql_file != "none") {
  // do file upload
  if(ereg("^php[0-9A-Za-z_.-]+$", basename($sql_file))) {
    $sql_query = fread(fopen($sql_file, "r"), filesize($sql_file));
    if (get_magic_quotes_runtime() == 1) $sql_query = stripslashes($sql_query);
  }
}
else if (get_magic_quotes_gpc() == 1) {
	$sql_query = stripslashes($sql_query);
}

$sql_query = trim($sql_query);
$sql_query_cpy = $sql_query; // copy the query, used for display purposes only

if($sql_query != "") {
  $sql_query = remove_remarks($sql_query);
  $pieces    = split_sql_file($sql_query,";");
  $piecescount=count($pieces);

  if (count($pieces) == 1 && !empty($pieces[0]) && $view_bookmark == 0) {
    $sql_query = trim($pieces[0]);
    if (eregi('^CREATE TABLE (.+)', $sql_query))  $reload = "true";

// sql.php3 will stripslash the query if get_magic_quotes_gpc
    if (get_magic_quotes_gpc() == 1) $sql_query = addslashes($sql_query);

    include("./sql.php3");
    exit;
  }
 
  if(mysql_select_db($db)) {
    // run multiple queries
    for ($i=0; $i<$piecescount; $i++) {
      $sql = trim($pieces[$i]);
      if(!empty($sql) and $sql[0] != "#") $result = mysql_query($sql) or mysql_die2($sql);
      if (!isset($reload) && eregi('^CREATE TABLE (.+)', $pieces[$i])) $reload = "true";
    }
  }
}

// copy the original query back for display purposes
include("./header.inc.php3");
$sql_query = $sql_query_cpy;
$message = $strSuccess;
require("./db_details.php3");
?>
