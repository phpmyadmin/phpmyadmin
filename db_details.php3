<?php
/* $Id$ */

require("./grab_globals.inc.php3");

if(!isset($message))
{
    include("./header.inc.php3");
}
else
{
    show_message($message);
}

$tables = mysql_list_tables($db);
$num_tables = @mysql_numrows($tables);

// speedup view on locked tables - staybyte - 11 June 2001
if ($num_tables>0 && MYSQL_MAJOR_VERSION>=3.23 && intval(MYSQL_MINOR_VERSION)>=3){
	// Special speedup for newer MySQL Versions
	if ($cfgSkipLockedTables==true && MYSQL_MAJOR_VERSION==3.23 && intval(MYSQL_MINOR_VERSION)>=30){ // in 4.0 format changed
		$query="SHOW OPEN TABLES from " . db_name($db);
		$result=mysql_query($query);
		// Blending out tables in use
		if ($result!=false && mysql_num_rows($result)>0){
			while ($tmp=mysql_fetch_array($result)){
				if (preg_match("/in_use=[1-9]+/",$tmp['Comment'])){ // in use?
					// memorize tablename
					$sot_cache[$tmp[0]]=true;
				}
			}
			mysql_free_result($result);

			if (isset($sot_cache)){
				$query="show tables from " . db_name($db);
				$result=mysql_query($query);
				if ($result!=false && mysql_num_rows($result)>0){
					while ($tmp=mysql_fetch_array($result)){
						if (!isset($sot_cache[$tmp[0]])){
							$sts_result=mysql_query("show table status from " . db_name($db) . " like '".AddSlashes($tmp[0])."'");
							$sts_tmp=mysql_fetch_array($sts_result);
							$tbl_cache[]=$sts_tmp;
						}
						else{ // table in use
							
							$tbl_cache[]=array("Name"=>$tmp[0]);
						}
					}
					mysql_free_result($result);
					$sot_ready=true;
				}
			}
		}
	}
	if (!isset($sot_ready)){
		$result=mysql_query("show table status from " .db_name($db));
		if ($result!=false && mysql_num_rows($result)>0){
			while ($sts_tmp=mysql_fetch_array($result)){
				$tbl_cache[]=$sts_tmp;
			}
			mysql_free_result($result);
		}
	}
}

if($num_tables == 0)
{
    echo $strNoTablesFound;
}
// show table size on mysql >= 3.23 - staybyte - 11 June 2001
else if (MYSQL_MAJOR_VERSION>=3.23 && isset($tbl_cache)){
	echo "<table border=$cfgBorder>\n";
	echo "<th>".UCFirst($strTable)."</th>";
	echo "<th colspan=6>$strAction</th>";
	echo "<th>$strRecords</th>";
	// temporary
	if (!empty($strSize)) echo "<th>$strSize</th>";
	else echo "<th>&nbsp;</th>";
	$i=$sum_entries=$sum_size=0;
	while (list($keyname,$sts_data)=each($tbl_cache)){
		$table=$sts_data["Name"];
		$query = "?server=$server&lang=$lang&db=$db&table=$table&goto=db_details.php3";
		$bgcolor = $cfgBgcolorOne;
		$i++ % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
		echo "<tr bgcolor=$bgcolor>\n";
?>
           <td class=data><b><?php echo $table;?></b></td>
           <td><a href="sql.php3<?php echo $query;?>&sql_query=<?php echo urlencode("SELECT * FROM $table");?>&pos=0"><?php echo $strBrowse; ?></a></td>
           <td><a href="tbl_select.php3<?php echo $query;?>"><?php echo $strSelect; ?></a></td>
           <td><a href="tbl_change.php3<?php echo $query;?>"><?php echo $strInsert; ?></a></td>
           <td><a href="tbl_properties.php3<?php echo $query;?>"><?php echo $strProperties; ?></a></td>
           <td><a href="sql.php3<?php echo $query;?>&reload=true&sql_query=<?php echo urlencode("DROP TABLE $table");?>&zero_rows=<?php echo urlencode($strTable." ".$table." ".$strHasBeenDropped);?>"><?php echo $strDrop; ?></a></td>
           <td><a href="sql.php3<?php echo $query;?>&sql_query=<?php echo urlencode("DELETE FROM $table");?>&zero_rows=<?php echo urlencode($strTable." ".$table." ".$strHasBeenEmptied);?>"><?php echo $strEmpty; ?></a></td>
<?php
		if (isset($sts_data["Rows"])){
			echo "<td align=right>".number_format($sts_data["Rows"],0,',','.')."</td>\n";
			$tblsize=$sts_data["Data_length"]+$sts_data["Index_length"];
			$sum_size+=$tblsize;
			$sum_entries+=$sts_data["Rows"];
			list($formated_size,$unit)=format_byte_down($tblsize,3,1);
			echo "<td align=right>&nbsp;&nbsp;";
			echo "<a href=\"tbl_properties.php3$query#showusage\">";
			echo "$formated_size $unit</a></td>\n";
		}
		else{
			echo "<td colspan=3 align=center>";
			if (!empty($strInUse)) echo $strInUse;
			echo "</td>\n";
		}
		echo "</tr>\n";
	}
	// Show Summary
	echo "<tr bgcolor=$cfgThBgcolor>\n";
	echo "<td colspan=7 align=center>";
	if (!empty($strSum)) echo $strSum;
	echo "</td>\n";
	list ($sum_formated,$unit)=format_byte_down($sum_size,3,1);
	echo "<td align=right>".number_format($sum_entries,0,',','.')."</td>\n";
	echo "<td align=right>$sum_formated $unit</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
}
else
{
    $i = 0;

    echo "<table border=$cfgBorder>\n";
    echo "<th>".UCFirst($strTable)."</th>";
    echo "<th colspan=6>$strAction</th>";
    echo "<th>$strRecords</th>";
    while($i < $num_tables)
    {
        $table = mysql_tablename($tables, $i);
        $query = "?server=$server&lang=$lang&db=$db&table=$table&goto=db_details.php3";
        $bgcolor = $cfgBgcolorOne;
        $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
        ?>
           <tr bgcolor="<?php echo $bgcolor;?>">

           <td class=data><b><?php echo $table;?></b></td>
           <td><a href="sql.php3<?php echo $query;?>&sql_query=<?php echo urlencode("SELECT * FROM $table");?>&pos=0"><?php echo $strBrowse; ?></a></td>
           <td><a href="tbl_select.php3<?php echo $query;?>"><?php echo $strSelect; ?></a></td>
           <td><a href="tbl_change.php3<?php echo $query;?>"><?php echo $strInsert; ?></a></td>
           <td><a href="tbl_properties.php3<?php echo $query;?>"><?php echo $strProperties; ?></a></td>
           <td><a href="sql.php3<?php echo $query;?>&reload=true&sql_query=<?php echo urlencode("DROP TABLE $table");?>&zero_rows=<?php echo urlencode($strTable." ".$table." ".$strHasBeenDropped);?>"><?php echo $strDrop; ?></a></td>
           <td><a href="sql.php3<?php echo $query;?>&sql_query=<?php echo urlencode("DELETE FROM $table");?>&zero_rows=<?php echo urlencode($strTable." ".$table." ".$strHasBeenEmptied);?>"><?php echo $strEmpty; ?></a></td>
           <td align="right">&nbsp;<?php count_records($db,$table) ?></td>
         </tr>
        <?php
        $i++;
    }

    echo "</table>\n";
}
$query = "?server=$server&lang=$lang&db=$db&goto=db_details.php3";
?>
<hr>
<div align="left">
<ul>
<li><a href="db_printview.php3<?php echo $query;?>"><?php echo $strPrintView;?></a>
<li>
<form method="post" action="db_readdump.php3" enctype="multipart/form-data">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="pos" value="0">
<input type="hidden" name="db" value="<?php echo $db;?>">
<input type="hidden" name="goto" value="db_details.php3">
<input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>">
<?php echo $strRunSQLQuery.$db." ".show_docu("manual_Reference.html#SELECT");?>:<br>
<textarea name="sql_query" cols="40" rows="3" wrap="VIRTUAL" style="width: <?php
echo $cfgMaxInputsize;?>"><?php echo (isset($sql_query) ? $sql_query : '');?></textarea><br>
<?php echo "<i>$strOr</i> $strLocationTextfile";?>:<br>
<input type="file" name="sql_file"><br>
<?php
// Bookmark Support

if($cfgBookmark['db'] && $cfgBookmark['table'])
{
    if(($bookmark_list=list_bookmarks($db, $cfgBookmark)) && count($bookmark_list)>0)
    {
        echo "<i>$strOr</i> $strBookmarkQuery:<br>\n";
        echo "<select name=\"id_bookmark\">\n";
        echo "<option value=\"\"></option>\n";
        while(list($key,$value)=each($bookmark_list)) {
            echo "<option value=\"".htmlentities($value)."\">".htmlentities($key)."</option>\n";
        }
        echo "</select>\n";
        echo "<input type=\"radio\" name=\"action_bookmark\" value=\"0\" checked>".$strSubmit;
        echo "<input type=\"radio\" name=\"action_bookmark\" value=\"1\">".$strBookmarkView;
        echo "<input type=\"radio\" name=\"action_bookmark\" value=\"2\">".$strDelete;
        echo "<br>\n";
    }
}
?>
<input type="submit" name="SQL" value="<?php echo $strGo; ?>">
</form>
<li><a href="tbl_qbe.php3<?php echo $query;?>"><?php echo $strQBE;?></a>
<li><form method="post" action="tbl_dump.php3"><?php echo $strViewDumpDB;?><br>
<table>
    <?php
        $tables = mysql_list_tables($db);
        $num_tables = @mysql_numrows($tables);
        if($num_tables>1) {
            print "<tr>\n";
            print "\t<td colspan=\"2\">\n";
            print "\t\t<select name=\"table_select[]\" size=\"5\" multiple>\n";

            $i=0;
   
            while($i < $num_tables) {
                $table = mysql_tablename($tables, $i);
                echo "\t\t\t<option value=\"".$table."\">".$table."</option>\n";
                $i++;
            }
            echo "\t\t</select>\n";
            echo "\t</td>\n";
            echo "</tr>\n";
        }
    ?>
    <tr>
        <td>
            <input type="radio" name="what" value="structure" checked><?php echo $strStrucOnly;?>
        </td>
        <td>
            <input type="checkbox" name="drop" value="1"><?php echo $strStrucDrop;?>
        </td>
        <td colspan="2">
            <input type="submit" value="<?php echo $strGo;?>">
        </td>
    </tr>
    <tr>
        <td>
            <input type="radio" name="what" value="data"><?php echo $strStrucData;?>
        </td>
        <td>
            <input type="checkbox" name="asfile" value="sendit"><?php echo $strSend;?>
        </td>
    </tr>
    <tr>
        <td>
           <input type="radio" name="what" value="dataonly"><?php echo $strDataOnly; ?>
        </td>
        <td>
           <input type="checkbox" name="showcolumns" value="yes"><?php echo $strCompleteInserts; ?>
        </td>
    </tr>
</table>
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="db" value="<?php echo $db;?>">
</form>
<li>
<form method="post" action="tbl_create.php3">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="db" value="<?php echo $db;?>">
<?php echo $strCreateNewTable.$db;?>:<br>
<?php 
	echo $strName.":"; 
	echo "<input type=\"text\" name=\"table\">";
//	echo $strNumberIndexes.":";
//	echo "<input type=\"text\" name=\"num_indexes\" size=2>";
	echo "<br>";
	echo $strFields.":"; 
	echo "<input type=\"text\" name=\"num_fields\" size=2>";
	echo "<input type=\"submit\" value=\"" . $strGo . "\">";
?>
</form>

<li>
<a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=<?php echo $db;?>&sql_query=<?php echo urlencode("DROP DATABASE " .db_name($db));?>&zero_rows=<?php echo urlencode($strDatabase." ".db_name($db)." ".$strHasBeenDropped);?>&goto=main.php3&reload=true"><?php echo $strDropDB." ".$db;?></a> <?php print show_docu("manual_Reference.html#DROP_DATABASE");?>
</ul>
</div>
<?php
require("./footer.inc.php3");
?>
