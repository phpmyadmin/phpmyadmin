<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 
require("./header.inc.php3");

if(isset($submit))
{
    if(!isset($query)) 
        $query = "";
    $query .= " $field_orig[0] $field_name[0] $field_type[0] ";
    if($field_length[0] != "")
        $query .= "($field_length[0]) ";
    if($field_attribute[0] != "")
        $query .= "$field_attribute[0] ";
    if($field_default[0] != "")
        $query .= "DEFAULT '$field_default[0]' ";

   $query .= "$field_null[0] $field_extra[0]";
   if(get_magic_quotes_gpc()) {
     $query = stripslashes($query);
   }
   //optimization fix - 2 May 2001 - Robbat2
   $sql_query = "ALTER TABLE ".db_name($db).".$table CHANGE $query";
   $result = mysql_query($sql_query) or mysql_die();
   $message = "$strTable $table $strHasBeenAltered";
   include("./tbl_properties.php3");
   exit;
}
else
{
    $result = mysql_query("SHOW FIELDS FROM ".db_name($db).".$table LIKE '$field'") or mysql_die();
    $num_fields = mysql_num_rows($result);
    $action = "tbl_alter.php3";
    include("./tbl_properties.inc.php3");
}

require("./footer.inc.php3");
?>
