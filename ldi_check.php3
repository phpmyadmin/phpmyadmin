<?php
/* $Id$ */


/* This file checks and builds the sql-string for
LOAD DATA INFILE 'file_name.txt' [REPLACE | IGNORE] INTO TABLE table_name
    [FIELDS
        [TERMINATED BY '\t']
        [OPTIONALLY] ENCLOSED BY "]
        [ESCAPED BY '\\' ]]
    [LINES TERMINATED BY '\n']
    [(column_name,...)]
*/



require("./grab_globals.inc.php3");
 

if (isset($btnLDI) && ($textfile != "none"))
{
    if(!isset($replace))
        $replace = "";

    $textfile=addslashes($textfile); 
    
    
    if(get_magic_quotes_gpc()) {
      $stripped_field_terminater = stripslashes($field_terminater);
      $stripped_escaped          = stripslashes($escaped);
      $stripped_line_terminator  = stripslashes($line_terminator);
    } else {
      $stripped_field_terminater = $field_terminater;
      $stripped_escaped          = $escaped;
      $stripped_line_terminator  = $line_terminator;
    }
    $query = "LOAD DATA LOCAL INFILE '$textfile' $replace INTO TABLE $into_table ";
    if (isset($field_terminater))
    {    $query = $query . "FIELDS TERMINATED BY '".$stripped_field_terminater."' ";
    }

    if (isset($enclose_option) && strlen($enclose_option)>0)
    {    $query = $query . "OPTIONALLY ";
    }

    if (strlen($enclosed)>0)
    {    $query = $query . "ENCLOSED BY '$enclosed' ";
    }

    if (strlen($escaped)>0)
    {    $query = $query . "ESCAPED BY '".$stripped_escaped."' ";
    }

    if (strlen($line_terminator)>0)
    {    $query = $query . "LINES TERMINATED BY '".$stripped_line_terminator."' ";
    }

    if (strlen($column_name)>0)
    {    $query = $query . "($column_name)";
    }

    if(get_magic_quotes_gpc()) {
        $sql_query = addslashes($query);
    }
    include("./sql.php3");
}
else
{
    include("./ldi_table.php3");
}
?>
