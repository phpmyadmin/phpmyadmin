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

unset($sql_query);
mysql_select_db($db);

// 'show table' works correct since 3.23.03
if(MYSQL_MAJOR_VERSION == "3.23" && intval(MYSQL_MINOR_VERSION)>=3)
{
	if(isset($submitcomment))
		$result = mysql_query("ALTER TABLE $table comment='$comment'") or mysql_die();
	if(isset($submittype)){
		$result = mysql_query("ALTER TABLE $table TYPE=$tbl_type") or mysql_die();
	}
	if(isset($submitorderby) && !empty($order_field)){
		$result = mysql_query("ALTER TABLE $table ORDER BY $order_field") or mysql_die();
	}

	$result = mysql_query("SHOW TABLE STATUS LIKE '$table'") or mysql_die();
	$showtable = mysql_fetch_array($result);
	$show_comment=$showtable['Comment'];

	if (!empty($showtable['Comment'])){
		echo "<i>" . $showtable['Comment'] . "</i><br><br>\n";
		$show_comment=$showtable['Comment'];
	}

	$tbl_type=strtoupper($showtable['Type']);
}

$result = mysql_query("SHOW KEYS FROM $table") or mysql_die();
$primary = "";

while($row = mysql_fetch_array($result))
    if ($row["Key_name"] == "PRIMARY")
        $primary .= "$row[Column_name], ";

$result = mysql_query("SHOW FIELDS FROM $table") or mysql_die();
?>
<table border=<?php echo $cfgBorder;?>>
<TR>
   <TH><?php echo $strField; ?></TH>
   <TH><?php echo $strType; ?></TH>
   <TH><?php echo $strAttr; ?></TH>
   <TH><?php echo $strNull; ?></TH>
   <TH><?php echo $strDefault; ?></TH>
   <TH><?php echo $strExtra; ?></TH>
   <?php if (!(isset($printer_friendly) && $printer_friendly)) { ?>
   <TH COLSPAN=5><?php echo $strAction; ?></TH>
   <?php } ?>
</TR>

<?php
$i=0;

$aryFields = array();

while($row= mysql_fetch_array($result))
{
    $aryFields[] = $row["Field"];
    $query = "server=$server&lang=$lang&db=$db&table=$table&goto=tbl_properties.php3";
    $bgcolor = $cfgBgcolorOne;
    $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
    $i++;
    ?>
         <tr bgcolor="<?php echo $bgcolor;?>">
         <td><?php echo $row["Field"];?>&nbsp;</td>
         <td>
    <?php
    if(get_magic_quotes_gpc()) {
      $Type = stripslashes($row["Type"]);
    } else {
      $Type = $row["Type"];
    }
   	// reformat mysql query output - staybyte - 9. June 2001
    $shorttype=substr($Type,0,3);
    if ($shorttype=="set" || $shorttype=="enu"){
    	$Type=eregi_replace (",",", ",$Type);
    }
    $Type = eregi_replace("BINARY", "", $Type);
    $Type = eregi_replace("ZEROFILL", "", $Type);
    $Type = eregi_replace("UNSIGNED", "", $Type);
    if (!empty($Type)) echo $Type;
    else echo "&nbsp;";
    ?></td><td>
    <?php
    $binary   = eregi("BINARY", $row["Type"], $test);
    $unsigned = eregi("UNSIGNED", $row["Type"], $test);
    $zerofill = eregi("ZEROFILL", $row["Type"], $test);
    $strAttribute="";
    if ($binary)
        $strAttribute="BINARY";
    if ($unsigned)
        $strAttribute="UNSIGNED";
    if ($zerofill)
        $strAttribute="UNSIGNED ZEROFILL";
    if (!empty($strAttribute)) echo $strAttribute;
    else echo "&nbsp;";
    $strAttribute="";
    ?>
</td>
<td><?php if ($row["Null"] == "") { echo $strNo;} else {echo $strYes;}?>&nbsp;</td>
         <td><?php if(isset($row["Default"])) echo $row["Default"];?>&nbsp;</td>
         <td><?php echo $row["Extra"];?>&nbsp;</td>

     <?php if (!(isset($printer_friendly) && $printer_friendly)) { ?>
         <td><a href="tbl_alter.php3?<?php echo $query;?>&field=<?php echo $row["Field"];?>"><?php echo $strChange; ?></a></td>
         <td><a href="sql.php3?<?php echo $query;?>&sql_query=<?php echo urlencode("ALTER TABLE ".$table." DROP ".$row["Field"]);?>&zero_rows=<?php echo urlencode($row["Field"]." ".$strHasBeenDropped);?>"><?php echo $strDrop; ?></a></td>
         <td><a href="sql.php3?<?php echo $query;?>&sql_query=<?php echo urlencode("ALTER TABLE ".$table." DROP PRIMARY KEY, ADD PRIMARY KEY($primary".$row["Field"].")");?>&zero_rows=<?php echo urlencode($strAPrimaryKey.$row["Field"]);?>"><?php echo $strPrimary; ?></a></td>
         <td><a href="sql.php3?<?php echo $query;?>&sql_query=<?php echo urlencode("ALTER TABLE ".$table." ADD INDEX(".$row["Field"].")");?>&zero_rows=<?php echo urlencode($strAnIndex.$row["Field"]);?>"><?php echo $strIndex; ?></a></td>
         <td><a href="sql.php3?<?php echo $query;?>&sql_query=<?php echo urlencode("ALTER TABLE ".$table." ADD UNIQUE(".$row["Field"].")");?>&zero_rows=<?php echo urlencode($strAnIndex.$row["Field"]);?>"><?php echo $strUnique; ?></a></td>
     <?php } ?>
         </tr>
    <?php
}
?>
</table>
<br>
<table border=0 cellspacing=0 cellpadding=0>
<tr>
<?php
$result = mysql_query("SHOW KEYS FROM ".$table) or mysql_die();
$indexcount=mysql_num_rows($result);
if($indexcount>0)
{
echo "<td valign=top align=left>\n";
if (!empty($strIndexes)) echo $strIndexes.":\n";
?>
<table border=<?php echo $cfgBorder;?>>
      <tr>
      <th><?php echo $strKeyname; ?></th>
      <th><?php echo $strUnique; ?></th>
      <th><?php echo $strField; ?></th>
      <th><?php echo $strAction; ?></th>
      </tr>
    <?php
    for($i=0 ; $i<$indexcount; $i++)
    {
        $row = mysql_fetch_array($result);
        echo "<tr>";
        if($row["Key_name"] == "PRIMARY")
        {
            $sql_query = urlencode("ALTER TABLE ".$table." DROP PRIMARY KEY");
            $zero_rows = urlencode($strPrimaryKey." ".$strHasBeenDropped);
        }
        else
        {
            $sql_query = urlencode("ALTER TABLE ".$table." DROP INDEX ".$row["Key_name"]);
            $zero_rows = urlencode($strIndex." ".$row["Key_name"]." ".$strHasBeenDropped);
        }

        ?>
          <td><?php echo $row["Key_name"];?></td>
          <td><?php
        if($row["Non_unique"]=="0")
            echo $strYes;
        else
            echo $strNo;
        ?></td>
        <td><?php echo $row["Column_name"];?></td>
        <td><?php echo "<a href=\"sql.php3?$query&sql_query=$sql_query&zero_rows=$zero_rows\">$strDrop</a>";?></td>
        <?php
        echo "</tr>";
    }
    print "</table>\n";
    print show_docu("manual_Performance.html#MySQL_indexes");
		echo "</td>\n";
}

// BEGIN - Calc Table Space - staybyte - 9 June 2001
if (MYSQL_MAJOR_VERSION == "3.23" && intval(MYSQL_MINOR_VERSION)>3 && $tbl_type!="INNODB" && isset($showtable)){
	if ($indexcount>0) echo "<td width=20>&nbsp;</td>\n";
	echo "<td";
	echo " valign=top>\n";
	if (!empty($strSpaceUsage)) echo $strSpaceUsage.":\n";
	echo "<a name=showusage><a><table border=$cfgBorder>\n";
	echo "<th>$strType</th>";
	echo '<th colspan=2 align="center">';
	if (!empty($strUsage)) echo $strUsage;
	echo "</th>";

	// Data
	echo "<tr bgcolor=$cfgBgcolorTwo>\n";
	list($size,$unit)=format_byte_down($showtable["Data_length"]);
	echo "<td style=\"padding-right:10px;\">";
	if (!empty($strData)) echo UCFirst($strData);
	echo "</td><td align=right>".$size."</td><td>".$unit."</td>";
	echo "</tr>\n";
	// Index
	echo "<tr bgcolor=$cfgBgcolorTwo>\n";
	list($size,$unit)=format_byte_down($showtable["Index_length"]);
	echo '<td style="padding-right:10px;">' . UCFirst($strIndex) . '</td><td align="right">' . $size . '</td><td>' . $unit . '</td>';
	echo "</tr>\n";
	// Overhead
	if (isset($showtable["Data_free"]) && $showtable["Data_free"]!=0){
		echo "<tr bgcolor=$cfgBgcolorTwo style=\"color:#bb0000;\">\n";
		list($size,$unit)=format_byte_down($showtable["Data_free"]);
		echo "<td style=\"padding-right:10px;\">";
		if (!empty($strOverhead)) echo UCFirst($strOverhead);
		echo '</td><td align="right">' . $size . '</td><td>' . $unit . '</td>';
		echo "</tr>\n";
		// Effective
		echo "<tr bgcolor=$cfgBgcolorOne>\n";
		list($size,$unit)=format_byte_down($showtable["Data_length"]+$showtable["Index_length"]-$showtable["Data_free"]);
		echo "<td style=\"padding-right:10px;\">";
		if (!empty($strOverhead)) echo UCFirst($strEffective);
		echo '</td><td align="right">' . $size . '</td><td>' . $unit . '</td>';
		echo "</tr>\n";
	}
	// Total
	echo "<tr bgcolor=$cfgBgcolorOne>\n";
	list($size,$unit)=format_byte_down($showtable["Data_length"]+$showtable["Index_length"]);
	echo '<td style="padding-right:10px;">' . UCFirst($strTotal) . '</td><td align="right">' . $size . '</td><td>' . $unit . '</td>';
	echo "</tr>\n";

	if (!empty($showtable["Data_free"])){
		echo "<tr>";
		echo "<td colspan=3 align=center>";
		$query = "server=$server&lang=$lang&db=$db&table=$table&goto=tbl_properties.php3";
		echo "<a href=\"sql.php3?sql_query=".urlencode("OPTIMIZE TABLE $table")."&pos=0&$query\">[$strOptimizeTable]</a>";
		echo "</td>";
		echo "<tr>\n";
	}
	echo "</table>\n";
	echo "</td>\n";

// Rows Statistic
	echo "<td width=20>&nbsp;</td>\n";
	echo "<td";
	echo " valign=top>\n";
	if (!empty($strRowsStatistic)) echo $strRowsStatistic.":\n";
	echo "<table border=$cfgBorder>\n";
	echo "<th>";
	if (!empty($strStatement)) echo $strStatement;
	echo "</th>";
	echo '<th align="center">';
	if (!empty($strValue)) echo $strValue;
	echo "</th>\n";

	$i=0;
	if (isset($showtable["Row_format"])){
		echo (++$i%2)?"<tr bgcolor=$cfgBgcolorTwo><td>":"<tr bgcolor=$cfgBgcolorOne><td>\n";
		if (!empty($strFormat)) echo UCFirst($strFormat);
		echo "</td><td>";
		if ($showtable["Row_format"]=="Fixed" && !empty($strFixed)) echo $strFixed;
		else if ($showtable["Row_format"]=="Dynamic" && !empty($strDynamic)) echo $strDynamic;
		else echo $showtable["Row_format"];
		echo "</td></tr>\n";
	}
	if (isset($showtable["Rows"])){
		echo (++$i%2)?"<tr bgcolor=$cfgBgcolorTwo><td>":"<tr bgcolor=$cfgBgcolorOne><td>\n";
		if (!empty($strRows)) echo UCFirst($strRows);
		echo '</td><td align="right">' . $showtable['Rows'] . '</td></tr>' . "\n";
	}
	if (isset($showtable["Avg_row_length"])){
		echo (++$i%2)?"<tr bgcolor=$cfgBgcolorTwo><td>":"<tr bgcolor=$cfgBgcolorOne><td>\n";
		if (!empty($strRowLength)) echo UCFirst($strRowLength);
		echo "&nbsp;&oslash;";
		echo '</td><td align="right">' . $showtable['Avg_row_length'] . '</td></tr>' . "\n";
	}
	if (isset($showtable["Auto_increment"])){
		echo (++$i%2)?"<tr bgcolor=$cfgBgcolorTwo><td>":"<tr bgcolor=$cfgBgcolorOne><td>\n";
		echo UCFirst($strNext) . ' Autoindex';
		echo '</td><td align="right">' . $showtable['Auto_increment'] . '</td></tr>' . "\n";
	}

	echo "</table>\n";
}
// END - Calc Table Space
?>
</tr></table>
<br>
<div align="left" style="padding-left:10px;">
<table border=0 cellspacing=0 cellpadding=0>
<tr><td valign=top><li>&nbsp;</td><td colspan=2><a href="tbl_printview.php3?<?php echo $query;?>"><?php echo $strPrintView; ?></a></td></tr>

<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<form method="post" action="db_readdump.php3">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="pos" value="0">
<input type="hidden" name="db" value="<?php echo $db;?>">
<input type="hidden" name="goto" value="db_details.php3">
<input type="hidden" name="zero_rows" value="<?php echo $strSuccess; ?>">
<?php echo $strRunSQLQuery.$db." ".show_docu("manual_Reference.html#SELECT");?>:<br>
<textarea name="sql_query" cols="40" rows="3" wrap="VIRTUAL" style="width: <?php
echo $cfgMaxInputsize;?>">select * from <?php echo $table?> where 1</textarea>
<br>
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
</td></tr>

<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<table border=0 cellspacing=0 cellpadding=0><tr><td>
 <a href="sql.php3?sql_query=<?php echo urlencode("SELECT * FROM $table");?>&pos=0&<?php echo $query;?>">
<?php echo "<b>" . $strBrowse. "<b>"; ?></a></td><td>&nbsp;-&nbsp;</td>
<td>
 <a href="tbl_select.php3?<?php echo $query;?>"><?php echo "<b>" . $strSelect . "</b>"; ?></a></td>
<td>&nbsp;-&nbsp;</td><td>
  <a href="tbl_change.php3?<?php echo $query;?>"><?php echo "<b>" . $strInsert. "</b>"; ?></a></td></tr></table></li>
</td></tr>
<tr><td>&nbsp;</tr>
<tr><td><li>&nbsp;</td><td>
<?php echo $strAddNewField; ?>:</td><td>
<form method="post" action="tbl_addfield.php3" style="margin:0px;">
<input name="num_fields" size=2 maxlength=2 value=1>
 <input type="hidden" name="server" value="<?php echo $server;?>">
 <input type="hidden" name="lang" value="<?php echo $lang;?>">
 <input type="hidden" name="db" value="<?php echo $db;?>">
 <input type="hidden" name="table" value="<?php echo $table;?>">
<?php
echo " ";
echo " <select name=\"after_field\">\n";
echo '  <option value="--end--">'.$strAtEndOfTable."</option>\n";
echo '  <option value="--first--">'.$strAtBeginningOfTable."</option>\n";
reset($aryFields);
while(list ($junk,$fieldname) = each($aryFields)) {
    echo '  <option value="'.$fieldname.'">'.$strAfter.' '.$fieldname."</option>\n";
}
echo " </select>\n";
?>
<input type="submit" value="<?php echo $strGo;?>">
</form>
</td></tr>
<?php if (MYSQL_MAJOR_VERSION>=3.23 && MYSQL_MINOR_VERSION>=34){ ?>
<tr><td><li>&nbsp;</td><td>
<?php echo $strAlterOrderBy; ?>:</td><td>
<form method="post" action="tbl_properties.php3" style="margin:0px;">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="db" value="<?php echo $db;?>">
<input type="hidden" name="table" value="<?php echo $table;?>">
<?php
echo " ";
echo " <select name=\"order_field\">\n";
reset($aryFields);
while(list ($junk,$fieldname) = each($aryFields)) {
    echo "<option value=\"".$fieldname."\">$fieldname</option>\n";
}
echo "</select>\n";
?>
<input type="submit" name="submitorderby" value="<?php echo $strGo;?>">
<?php
echo "&nbsp;$strSingly\n";
?>
</form>
</td></tr>
<?php } ?>
<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<a href="ldi_table.php3?<?php echo $query;?>"><?php echo $strInsertTextfiles; ?></a>
</td></tr>

<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<form method="post" action="tbl_dump.php3"><?php echo $strViewDump;?><br>
<table>
    <tr>
        <td>
            <input type="radio" name="what" value="structure" checked><?php echo $strStrucOnly;?>
        </td>
        <td>
            <input type="checkbox" name="drop" value="1"><?php echo $strStrucDrop;?>
        </td>
        <td colspan="3">
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
    <tr>
        <td>
            <input type="radio" name="what" value="csv"><?php echo $strStrucCSV;?>
        </td>
        <td>
            <?php echo $strFields . " ". $strTerminatedBy;?> <input type="text" name="separator" size=1 value=";">
        </td>
        <td>
            <?php echo $strLines . " ". $strTerminatedBy;?> <input type="text" name="add_character" size=1 value="">
        </td>
    </tr>
</table>

 <input type="hidden" name="server" value="<?php echo $server;?>">
 <input type="hidden" name="lang" value="<?php echo $lang;?>">
 <input type="hidden" name="db" value="<?php echo $db;?>">
 <input type="hidden" name="table" value="<?php echo $table;?>">
</form>
</td></tr>

<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<table border=0 cellspacing=0 cellpadding=0>
<tr><td valign=top>
<form method="post" action="tbl_rename.php3"><?php echo $strRenameTable;?>:<br>
<table border=0 cellspacing=0 cellpadding=0><tr><td>
 <input type="hidden" name="server" value="<?php echo $server;?>">
 <input type="hidden" name="lang" value="<?php echo $lang;?>">
 <input type="hidden" name="db" value="<?php echo $db;?>">
 <input type="hidden" name="table" value="<?php echo $table;?>">
 <input type="hidden" name="reload" value="true">
 <input type="text" name="new_name"></td></tr>
<tr><td align=right valign=bottom><input type="submit" value="<?php echo $strGo;?>"></td></tr></table>
</form>
</td><td width=25>&nbsp;</td>
<td valign=top>
<form method="post" action="tbl_copy.php3">
<table border=0 cellspacing=0 cellpadding=0>
<tr><td colspan=2><?php echo $strCopyTable;?>
 <input type="hidden" name="server" value="<?php echo $server;?>">
 <input type="hidden" name="lang" value="<?php echo $lang;?>">
 <input type="hidden" name="db" value="<?php echo $db;?>">
 <input type="hidden" name="table" value="<?php echo $table;?>">
 <input type="hidden" name="reload" value="true">
</td></tr>
<tr><td align=right colspan=2><input type="text" style="width:100%;" name="new_name"></td></tr>
<tr><td>
 <input type="radio" name="what" value="structure" checked><?php echo $strStrucOnly;?><br>
 <input type="radio" name="what" value="data"><?php echo $strStrucData;?>
</td>
<td align=right valign=top><input type="submit" value="<?php echo $strGo;?>"></td>
</tr>
</table>
</form>
</td></tr></table>
</td></tr>
<?php
if(MYSQL_MAJOR_VERSION == "3.23" && intval(MYSQL_MINOR_VERSION)>=22)
{
?>
<tr><td valign=top><li>&nbsp;</td><td colspan=2>
<table border=0 cellspacing=0 cellpadding=0><tr><td><?php echo $strTableMaintenance . ":"; ?>&nbsp;</td>
 <td><a href="sql.php3?sql_query=<?php echo urlencode("CHECK TABLE $table");?>&display=simple&<?php echo $query;?>">
        <?php echo $strCheckTable; ?></a>
        &nbsp;<?php echo show_docu("manual_Reference.html#CHECK_TABLE"); ?>
 </td><td>&nbsp;-&nbsp;</td>
 <td><a href="sql.php3?sql_query=<?php echo urlencode("ANALYZE TABLE $table");?>&display=simple&<?php echo $query;?>">
        <?php echo $strAnalyzeTable; ?>
        </a>&nbsp;<?php echo show_docu("manual_Reference.html#ANALYZE_TABLE");?>
 </td></tr> <tr> <td></td>
 <td> <a href="sql.php3?sql_query=<?php echo urlencode("REPAIR TABLE $table");?>&display=simple&<?php echo $query;?>">
        <?php echo $strRepairTable; ?>
        </a>&nbsp;<?php echo show_docu("manual_Reference.html#REPAIR_TABLE"); ?>
 </td><td>&nbsp;-&nbsp;</td>
<td><a href="sql.php3?sql_query=<?php echo urlencode("OPTIMIZE TABLE $table");?>&display=simple&<?php echo $query;?>">
        <?php echo $strOptimizeTable; ?>
        </a>&nbsp;<?php echo show_docu("manual_Reference.html#OPTIMIZE_TABLE");
?> </td> </tr> </table>
</td></tr>

<tr><td>&nbsp;</td></tr>

<tr><td><li>&nbsp;</td><td><?php echo "$strTableComments:&nbsp;";?></td>
<td>
<form method='post' action='tbl_properties.php3' style="margin:0px;">
    <input type="hidden" name="server" value="<?php echo $server;?>">
    <input type="hidden" name="lang" value="<?php echo $lang;?>">
    <input type="hidden" name="db" value="<?php echo $db;?>">
    <input type="hidden" name="table" value="<?php echo $table;?>">
    <?php
    //Fix to Comment editing so that you can add comments - 2 May 2001 - Robbat2
    echo "<input type='text' name='comment' maxlength=60 size=30 value='" . $show_comment . "'>&nbsp;<input type='submit' name='submitcomment' value='$strGo'></form>";
    ?>
</td></tr>
<tr><td><li>&nbsp;</td><td><?php echo "$strTableType:&nbsp;";?></td>
<td>
<?php
// modify robbat2 code - staybyte - 11. June 2001
	$query="SHOW VARIABLES like 'have_%'";
	$result=mysql_query($query);
	if ($result!=false && mysql_num_rows($result)>0){
		while($tmp=mysql_fetch_array($result)){
			if (isset($tmp["Variable_name"])) switch ($tmp["Variable_name"]){
				case 'have_bdb': if (isset($tmp["Variable_name"]) && $tmp["Value"]=='YES') $tbl_bdb=true; break;
				case 'have_gemini': if (isset($tmp["Variable_name"]) && $tmp["Value"]=='YES') $tbl_gemini=true; break;
				case 'have_innodb': if (isset($tmp["Variable_name"]) && $tmp["Value"]=='YES') $tbl_innodb=true; break;
				case 'have_isam': if (isset($tmp["Variable_name"]) && $tmp["Value"]=='YES') $tbl_isam=true; break;
			}
		}
	}
?>
<form method='post' action='tbl_properties.php3' style="margin:0px;">
    <input type="hidden" name="server" value="<?php echo $server;?>">
    <input type="hidden" name="lang" value="<?php echo $lang;?>">
    <input type="hidden" name="db" value="<?php echo $db;?>">
    <input type="hidden" name="table" value="<?php echo $table;?>">
    <select name='tbl_type'>
    <option <?php if($tbl_type == "MYISAM") echo 'selected';?> value="MYISAM">MyISAM</option>
    <option <?php if($tbl_type == "HEAP") echo 'selected';?> value="HEAP">Heap</option>
<?php if (isset($tbl_bdb)){ ?><option <?php if($tbl_type=="BERKELEYDB") echo 'selected';?> value="BDB">Berkeley DB</option><?php }?>
<?php if (isset($tbl_gemini)){ ?><option <?php if($tbl_type == "GEMINI") echo 'selected';?> value="GEMINI">Gemini</option><?php }?>
<?php if (isset($tbl_innodb)){ ?><option <?php if($tbl_type == "INNODB") echo 'selected';?> value="INNODB">INNO DB</option><?php }?>
<?php if (isset($tbl_isam)){ ?><option <?php if($tbl_type == "ISAM") echo 'selected';?> value="ISAM">ISAM</option><?php }?>
    <option <?php if($tbl_type == "MRG_MYISAM") echo 'selected';?> value="MERGE">Merge</option>
    </select>&nbsp;<input type='submit' name='submittype' value='<?php echo $strGo; ?>'></form>
    </td></tr>
<?php
}
else{ // MySQL < 3.23
?>
<tr><td><li>&nbsp;</td><td><?php echo "$strTableMaintenance:&nbsp;";?></td>
<td><a href="sql.php3?sql_query=<?php echo urlencode("OPTIMIZE TABLE $table");?>&display=simple&<?php echo $query;?>">
        <?php echo $strOptimizeTable; ?>
        </a>&nbsp;<?php echo show_docu("manual_Reference.html#OPTIMIZE_TABLE");?>
    </td></tr>
<?php
}
?>
</table>
<?php
echo "</div>";
require("./footer.inc.php3");
?>
