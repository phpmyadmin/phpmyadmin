<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 
require("./header.inc.php3");
mysql_select_db($db);

if(isset($submit))
{
    if(!isset($query))
        $query = "";
    for($i=0; $i<count($field_name); $i++)
    {
	if (empty($field_name[$i])) {
                continue;
        }
        $query .= "$field_name[$i] $field_type[$i] ";
        if($field_length[$i] != "")
	  if(get_magic_quotes_gpc()) {
            $query .= "(".stripslashes($field_length[$i]).") ";
	  } else {
            $query .= "(".($field_length[$i]).") ";
	  }
        if($field_attribute[$i] != "")
            $query .= "$field_attribute[$i] ";
        if($field_default[$i] != "")
	  if(get_magic_quotes_gpc()) {
            $query .= "DEFAULT '".stripslashes($field_default[$i])."' ";
	  } else {
            $query .= "DEFAULT '".($field_default[$i])."' ";
	  }
        $query .= "$field_null[$i] $field_extra[$i], ";
    }
    $query = ereg_replace(", $", "", $query);

    if(!isset($primary))
        $primary = "";

    if(!isset($field_primary))
        $field_primary = array();

    for($i=0;$i<count($field_primary);$i++)
    {
        $j = $field_primary[$i];
	if (!empty($field_name[$j])) 
           $primary .= "$field_name[$j], ";
    }
    $primary = ereg_replace(", $", "", $primary);
    if(count($field_primary) > 0)
        $primary = ", PRIMARY KEY ($primary)";

    if(!isset($index))
        $index = "";

    if(!isset($field_index))
        $field_index = array();

    for($i=0;$i<count($field_index);$i++)
    {
        $j = $field_index[$i];
	if (!empty($field_name[$j]))
           $index .= "$field_name[$j], ";
    }
    $index = ereg_replace(", $", "", $index);
//    if(count($field_index) > 0)
	if(!empty($index))
           $index = ", INDEX ($index)";
    if(!isset($unique))
        $unique = "";

    if(!isset($field_unique))
        $field_unique = array();

    for($i=0;$i<count($field_unique);$i++)
    {
        $j = $field_unique[$i];
	if (!empty($field_name[$j])) 
           $unique .= "$field_name[$j], ";
    }
    $unique = ereg_replace(", $", "", $unique);
//    if(count($field_unique) > 0)
      if(!empty($unique))
        $unique = ", UNIQUE ($unique)";
    $query_keys = $primary.$index.$unique;
    $query_keys = ereg_replace(", $", "", $query_keys);

    // echo "$query $query_keys";
    $sql_query = "CREATE TABLE ".$table." (".$query." ".$query_keys.")";
    //BEGIN - Table Type - 2 May 2001 - Robbat2
    if(!empty($tbl_type) && ($tbl_type != "Default"))
	$sql_query .= " TYPE = $tbl_type";
    //END - Table Type - 2 May 2001 - Robbat2
    if(MYSQL_MAJOR_VERSION == "3.23" && !empty($comment))
        $sql_query .= " comment = '$comment'";
    $result = mysql_query($sql_query) or mysql_die();
    $message = "$strTable $table $strHasBeenCreated";
    include("./tbl_properties.php3");
    exit;
}
else
{
    $action = "tbl_create.php3";
    include("./tbl_properties.inc.php3");
}

require("./footer.inc.php3");
?>
