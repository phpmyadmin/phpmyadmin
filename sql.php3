<?php
/* $Id$ */;


require("./grab_globals.inc.php3");
 
require("./lib.inc.php3");

if(isset($goto) && $goto == "sql.php3")
{
    $goto = "sql.php3?server=$server&lang=$lang&db=$db&table=$table&pos=$pos&sql_query=".urlencode($sql_query);
}

// Go back to further page if table should not be dropped


if(isset($btnDrop) && $btnDrop == $strNo) {
  if(file_exists("./$goto")) {
    include('./' . preg_replace('/\.\.*/', '.', $goto));
  } else {
    Header("Location: $goto");
  }
  exit;
}

// Check if table should be dropped
$is_drop_sql_query = eregi("DROP +(TABLE|DATABASE)|ALTER TABLE +[[:alnum:]_]* +DROP|DELETE FROM", $sql_query); // Get word "drop"

if(!$cfgConfirm)
    $btnDrop = $strYes;

if($is_drop_sql_query && !isset($btnDrop)) {
  if(get_magic_quotes_gpc()) {
    $stripped_sql_query = stripslashes($sql_query);
  } else {
    $stripped_sql_query = $sql_query;
  }
    include("./header.inc.php3");
    echo $strDoYouReally.$stripped_sql_query."?<br>";
    ?>
    <form action="sql.php3" method="post" enctype="application/x-www-form-urlencoded">
    <input type="hidden" name="sql_query" value="<?php echo $stripped_sql_query; ?>">
    <input type="hidden" name="server" value="<?php echo $server ?>">
    <input type="hidden" name="lang" value="<?php echo $lang;?>">
    <input type="hidden" name="db" value="<?php echo $db ?>">
    <input type="hidden" name="zero_rows" value="<?php echo isset($zero_rows) ? $zero_rows : "";?>">
    <input type="hidden" name="table" value="<?php echo isset($table) ? $table : "";?>">
    <input type="hidden" name="goto" value="<?php echo isset($goto) ? $goto : "";?>">
    <input type="hidden" name="reload" value="<?php echo isset($reload) ? $reload : "";?>">
    <input type="Submit" name="btnDrop" value="<?php echo $strYes; ?>">
    <input type="Submit" name="btnDrop" value="<?php echo $strNo; ?>">
    </form>
    <?php
}
// if table should be dropped or other queries should be perfomed
//elseif (!$is_drop_sql_query || $btnDrop == $strYes)
else {
  if(get_magic_quotes_gpc()) {
    $sql_query = isset($sql_query) ? stripslashes($sql_query) : '';
    $sql_order = isset($sql_order) ? stripslashes($sql_order) : '';
  }
  else {
  	if (!isset($sql_query)) $sql_query = '';
  	if (!isset($sql_order)) $sql_order = '';
  }
    // loic1: A table have to be created -> left frame should be reloaded
    if (!empty($reload) && eregi("^CREATE TABLE (.*)", $sql_query))
        $reload = true;
    if(isset($sessionMaxRows))
        $cfgMaxRows = $sessionMaxRows;
    $sql_limit = (isset($pos) && eregi("^SELECT", $sql_query) && !eregi("LIMIT[ 0-9,]+$", $sql_query)) ? " LIMIT $pos, $cfgMaxRows" : '';
    mysql_select_db($db);

    $result = mysql_query($sql_query.$sql_order.$sql_limit);

    // the same SELECT without LIMIT
    if(eregi("^SELECT", $sql_query))
    {
        $array=split(' from | FROM ',$sql_query,2); //read only the from-part of the query
		$count_query="select count(*) as count from $array[1]"; //and make a count(*) to count the entries
		$OPresult = mysql_query($count_query);
		if ($OPresult) {
		    $SelectNumRows = mysql_result($OPresult,'0','count');
		}
    }

    if(!$result)
    {
        $error = mysql_error();
        include("./header.inc.php3");
        mysql_die($error);
    }

    $num_rows = @mysql_num_rows($result);

    if($num_rows < 1)
    {
        if(file_exists("./$goto"))
        {
            if(isset($zero_rows) && !empty($zero_rows))
                $message = $zero_rows;
            else
                $message = $strEmptyResultSet;
            $goto = preg_replace('/\.\.*/', '.', $goto);
 			if ($goto != "main.php3")
 			{
 				include("./header.inc.php3");
 			}
 			include('./' . $goto);
        }
        else
        {
            $message = $zero_rows;
            Header("Location: $goto");
        }
        exit;
    }
    else
    {
        include("./header.inc.php3");
        // Define the display mode if it wasn't passed by url
        if (!isset($display)) {
        	$display = eregi('^((SHOW (VARIABLES|PROCESSLIST|STATUS))|((CHECK|ANALYZE|REPAIR|OPTIMIZE) TABLE ))', $sql_query, $which);
            if (!empty($which[2]) && !empty($which[3])) {
                $display = 'simple';
            } else if (!empty($which[4]) && !empty($which[5])) {
                $display = 'bkmOnly';
            }
        }

        display_table($result, ($display == 'simple' || $display == 'bkmOnly'));
        if ($display != 'simple')
        {
        	if ($display != 'bkmOnly') {
	            echo "<p><a href=\"tbl_change.php3?server=$server&lang=$lang&db=$db&table=$table&pos=$pos&goto=$goto&sql_query=".urlencode($sql_query)."\"> $strInsertNewRow</a></p>";
	        }

            // Bookmark Support

            if($cfgBookmark['db'] && $cfgBookmark['table'] && $db!=$cfgBookmark['db'] && empty($id_bookmark))
            {
                echo "<form method=\"post\" action=\"tbl_replace.php3\">\n";
               	if ($display != 'bkmOnly') {
                    echo "<i>$strOr</i>";
                }
                echo "<br><br>\n";
                echo $strBookmarkLabel.":\n";
                $goto="sql.php3?server=$server&lang=$lang&db=$db&table=$table&pos=$pos&id_bookmark=1&sql_query=".urlencode($sql_query);
            ?>
            <input type="hidden" name="server" value="<?php echo $server;?>">
            <input type="hidden" name="lang" value="<?php echo $lang;?>">
            <input type="hidden" name="db" value="<?php echo $cfgBookmark['db'];?>">
            <input type="hidden" name="table" value="<?php echo $cfgBookmark['table'] ;?>">
            <input type="hidden" name="goto" value="<?php echo $goto;?>">
            <input type="hidden" name="pos" value="<?php echo isset($pos) ? $pos : 0;?>">
            <input type="hidden" name="funcs[id]" value="NULL"?>
            <input type="hidden" name="fields[dbase]" value="<?php echo $db;?>">
            <input type="hidden" name="fields[query]" value="<?php echo isset($sql_query) ? $sql_query : "";?>">
            <input type="text" name="fields[label]" value="">
            <input type="hidden" name="sql_query" value="">
            <input type="submit" value="<?php echo $strBookmarkThis; ?>">
            </form>
            <?php
			}
            echo "</p>";
        }
    }
} //ne drop query
require("./footer.inc.php3");
?>
