<?php
/* $Id$ */


require("grab_globals.inc.php3");
 
require("lib.inc.php3");


if($goto == "sql.php3")
{
    $goto = "sql.php3?server=$server&lang=$lang&db=$db&table=$table&pos=$pos&sql_query=".urlencode($sql_query);

}

reset($fields);
reset($funcs);

if(isset($primary_key) && ($submit_type != $strInsertNewRow)) {
  if(get_magic_quotes_gpc()) {
    $primary_key = stripslashes($primary_key);
  } else {
    $primary_key = $primary_key;
  }
  $valuelist = '';
  while(list($key, $val) = each($fields)) {
    switch (strtolower($val)) {
    case 'null':
      break;
    case '$set$':
      // if we have a set, then construct the value
      $f = "field_$key";
      if(get_magic_quotes_gpc()) {
	$val = "'".($$f?implode(',',$$f):'')."'";
      } else {
	$val = "'".addslashes($$f?implode(',',$$f):'')."'";
      }
      break;
    default:
      if(get_magic_quotes_gpc()) {
	$val = "'".$val."'";
      } else {
	$val = "'".addslashes($val)."'";
      }
      break;
    }
        
    if(empty($funcs[$key])) {
      $valuelist .= "$key = $val, ";
    } else {
      $valuelist .= "$key = $funcs[$key]($val), ";
    }
  }
  $valuelist = ereg_replace(', $', '', $valuelist);
  $query = "UPDATE $table SET $valuelist WHERE $primary_key";
} else {
  $fieldlist = '';
  $valuelist = '';
  while(list($key, $val) = each($fields)) {
    $fieldlist .= "$key, ";
    switch (strtolower($val)) {
    case 'null':
      break;
    case '$set$':
      $f = "field_$key";
      if(get_magic_quotes_gpc()) {
	$val = "'".($$f?implode(',',$$f):'')."'";
      } else {
	$val = "'".addslashes($$f?implode(',',$$f):'')."'";
      }
      break;
    default:
      if(get_magic_quotes_gpc()) {
	$val = "'".$val."'";
      } else {
	$val = "'".addslashes($val)."'";
      }
      break;
    }
    if(empty($funcs[$key])) {
      $valuelist .= "$val, ";
    } else {
      $valuelist .= "$funcs[$key]($val), ";
    }
  }
  $fieldlist = ereg_replace(', $', '', $fieldlist);
  $valuelist = ereg_replace(', $', '', $valuelist);
  $query = "INSERT INTO $table ($fieldlist) VALUES ($valuelist)";
}

$sql_query = $query;
$result = mysql_db_query($db, $query);

if(!$result) {
  $error = mysql_error();
  include("header.inc.php3");
  mysql_die($error);
} else {
  if(file_exists("./$goto")) {
    include("header.inc.php3");
    $message = $strModifications;
    include(preg_replace('/\.\.*/', '.', $goto));
  } else {
    Header("Location: $goto");
  }
  exit;
}
?>
