<?php
/* $Id$ */


require("grab_globals.inc.php3");
 
require("header.inc.php3");

$table_def = mysql_db_query($db, "SHOW FIELDS FROM $table");

if(isset($primary_key)) {
  if(get_magic_quotes_gpc()) {
    $primary_key = stripslashes($primary_key);
  }
  $result = mysql_db_query($db, "SELECT * FROM $table WHERE $primary_key");
  $row = mysql_fetch_array($result);
}
else
{
    $result = mysql_db_query($db, "SELECT * FROM $table LIMIT 1");
}

?>
<form method="post" action="tbl_replace.php3">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="db" value="<?php echo $db;?>">
<input type="hidden" name="table" value="<?php echo $table;?>">
<input type="hidden" name="goto" value="<?php echo $goto;?>">
<input type="hidden" name="sql_query" value="<?php echo isset($sql_query) ? $sql_query : "";?>">
<input type="hidden" name="pos" value="<?php echo isset($pos) ? $pos : 0;?>">
<?php

if(isset($primary_key))
    echo '<input type="hidden" name="primary_key" value="' . htmlspecialchars($primary_key) . '">' . "\n";
?>
<table border="<?php echo $cfgBorder;?>">
<tr>
<th><?php echo $strField; ?></th>
<th><?php echo $strType; ?></th>
<th><?php echo $strFunction; ?></th>
<th><?php echo $strValue; ?></th>
</tr>
<?php

for($i=0;$i<mysql_num_rows($table_def);$i++)
{
    $row_table_def = mysql_fetch_array($table_def);
    $field = $row_table_def["Field"];
    if(($row_table_def['Type']  == "datetime") AND ($row[$field] == ""))
        $row[$field] = date("Y-m-d H:i:s", time());
    $len = @mysql_field_len($result,$i);

    $bgcolor = $cfgBgcolorOne;
    $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
    echo "<tr bgcolor=".$bgcolor.">\n";
    echo "<td>$field</td>\n";
    switch (ereg_replace("\\(.*", "", $row_table_def['Type']))
    {
        case "set":
            $type = "set";
            break;
        case "enum":
            $type = "enum";
            break;
        default:
            $type = $row_table_def['Type'];
            break;
    }
    echo "<td>$type</td>\n";
    echo "<td><select name=\"funcs[$field]\"><option>\n";
    for($j=0; $j<count($cfgFunctions); $j++)
        echo "<option>$cfgFunctions[$j]\n";
    echo "</select></td>\n";
    if(isset($row) && isset($row[$field]))
    {
        $special_chars = htmlspecialchars($row[$field]);
        $data = $row[$field];
    }
    else
    {
        $data = $special_chars = "";
    }

    if(strstr($row_table_def["Type"], "text"))
    {
        echo "<td><textarea name=fields[$field] style=\"width:$cfgMaxInputsize;\" rows=5>$special_chars</textarea></td>\n";
    }
    elseif(strstr($row_table_def["Type"], "enum"))
    {
        $set = str_replace("enum(", "", $row_table_def["Type"]);
        $set = ereg_replace("\\)$", "", $set);
        $set = explode(",", $set);

		// show dropdown or radio depend on length
		if (strlen($row_table_def["Type"]) > 20) {
			echo "<td><select name=fields[$field]>\n";
			echo "<option value=\"\">\n";
			for($j=0; $j<count($set);$j++)
			{
				echo '<option value="'.substr($set[$j], 1, -1).'"';
				if($data == substr($set[$j], 1, -1) || ($data == "" && substr($set[$j], 1, -1) == $row_table_def["Default"]))
					echo " selected";
				echo ">".htmlspecialchars(substr($set[$j], 1, -1))."\n";
			}
			echo "</select></td>";
		}
		else {
			echo "<td>\n";
			for($j=0; $j<count($set);$j++)
			{
				echo "<input type=radio name=fields[$field] ";
				echo 'value="'.substr($set[$j], 1, -1).'"';
				if($data == substr($set[$j], 1, -1) || ($data == "" && substr($set[$j], 1, -1) == $row_table_def["Default"]))
					echo " checked";
				echo ">".htmlspecialchars(substr($set[$j], 1, -1))."\n";
			}
			echo "</td>";
		}

    }
    elseif(strstr($row_table_def["Type"], "set"))
    {
        $set = str_replace("set(", "", $row_table_def["Type"]);
        $set = ereg_replace("\)$", "", $set);

        $set = explode(",",$set);
        for($vals = explode(",", $data); list($t, $k) = each($vals);)
            $vset[$k] = 1;
        $size = min(4, count($set));
        echo "<td><input type=\"hidden\" name=\"fields[$field]\" value=\"\$set\$\">";
        echo "<select name=field_${field}[] size=$size multiple>\n";
        for($j=0; $j<count($set);$j++)
        {
            echo '<option value="'.htmlspecialchars(substr($set[$j], 1, -1)).'"';
            if($vset[substr($set[$j], 1, -1)])
                echo " selected";
            echo ">".htmlspecialchars(substr($set[$j], 1, -1))."\n";
        }
        echo "</select></td>";
    }
    else
    {
        echo "<td><input type=text name=fields[$field] value=\"".$special_chars."\" style=\"width:$cfgMaxInputsize;\" maxlength=$len></td>";
    }
    echo "</tr>\n";
}

echo "</table>";

?>
  <p>
  <input type="submit" name="submit_type" value="<?php echo $strSave; ?>">
  <input type="submit" name="submit_type" value="<?php echo $strInsertNewRow; ?>">
  </form>

<?php
require ("footer.inc.php3");
?>
