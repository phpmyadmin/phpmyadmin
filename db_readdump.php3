<?php
/* $Id$ */

@set_time_limit(10000);


require("grab_globals.inc.php3");
 

include("lib.inc.php3");

// Bookmark Support

// Bookmark Support

if(!empty($id_bookmark)) {
    if(!empty($action_bookmark)) {
        switch($action_bookmark) {
            case 0:
                $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
                break;
            case 1:
                $sql_query = delete_bookmarks($db, $cfgBookmark, $id_bookmark);
                break;
        }
    }
    else
        $sql_query = query_bookmarks($db, $cfgBookmark, $id_bookmark);
}

//

if(!empty($sql_file) && $sql_file != "none" && ereg("^php[0-9A-Za-z_.-]+$", basename($sql_file))) {
  $sql_query = fread(fopen($sql_file, "r"), filesize($sql_file));
}
else if (get_magic_quotes_gpc()) {
  $sql_query = stripslashes($sql_query);
}

$pieces  = split_string($sql_query, ";");

if (count($pieces) == 1 && !empty($pieces[0]) && empty($action_bookmark)) {
  $sql_query = addslashes(trim($pieces[0]));
  // Enforce reloading of the left frame when a table has to be created 
  if (eregi('^CREATE TABLE (.+)', $sql_query)) {
    $reload = "true";
  }
  include ("sql.php3");
  exit;
}

include("header.inc.php3");
for ($i=0; $i<count($pieces); ++$i) {
  $pieces[$i] = trim($pieces[$i]);
  if(!empty($pieces[$i])) {
    $result = mysql_db_query ($db, $pieces[$i]) or mysql_die();
    // Enforce reloading of the left frame when a table has to be created 
    if (!isset($reload) && eregi('^CREATE TABLE (.+)', $pieces[$i])) {
      $reload = "true";
    }
  }
}

//$sql_query = stripslashes($sql_query);
$sql_query = $sql_query;
$message = $strSuccess;

include("db_details.php3");

?>
