<!-- $Id$ */ -->

<form method="post" action="<?php echo $action;?>">
<input type="hidden" name="server" value="<?php echo $server;?>">
<input type="hidden" name="lang" value="<?php echo $lang;?>">
<input type="hidden" name="db" value="<?php echo $db;?>">
<input type="hidden" name="table" value="<?php echo $table;?>">
<?php
if($action == "tbl_create.php3")
{
    ?>
    <input type="hidden" name="reload" value="true">
    <?php
}
elseif($action == "tbl_addfield.php3")
{
    echo '<input type="hidden" name="after_field" value="'.$after_field."\">\n";
}

?>
<table border=<?php echo $cfgBorder;?>>
<tr>
<th><?php echo $strField; ?></th>
<th><?php echo $strType; ?></th>
<th><?php echo $strLengthSet; ?></th>
<th><?php echo $strAttr; ?></th>
<th><?php echo $strNull; ?></th>
<th><?php echo $strDefault; ?></th>
<th><?php echo $strExtra; ?></th>

<?php
if($action == "tbl_create.php3" || $action == "tbl_addfield.php3")
{ if (empty($num_indexes))
  {
    echo "<th>$strPrimary</th>";
    echo "<th>$strIndex</th>";
    echo "<th>$strUnique</th>\n";
  }
  else { for ($i=0; $i<$num_indexes; $i++) {
	  echo "<th>$strSequence</th>";
	  echo "<th>$strLength</th>\n";
         }
  }
}
?>
</tr>

<?php
for($i=0 ; $i<$num_fields; $i++)
{
    if(isset($result))
        $row = mysql_fetch_array($result);
    $bgcolor = $cfgBgcolorOne;
    $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;
    ?>
    <tr bgcolor="<?php echo $bgcolor;?>">
    <td>
      <input type="text" name="field_name[]" size="10" value="<?php if(isset($row) && isset($row["Field"])) echo $row["Field"];?>">
      <input type="hidden" name="field_orig[]" value="<?php if(isset($row) && isset($row["Field"])) echo $row["Field"];?>"></td>
    <td><select name="field_type[]">
    <?php
    $row["Type"] = empty($row["Type"]) ? '' : $row["Type"];
    if(get_magic_quotes_gpc()) {
      $Type = stripslashes($row["Type"]);
    } else {
      $Type = $row["Type"];
    }
    $Type = eregi_replace("BINARY", "", $Type);
    $Type = eregi_replace("ZEROFILL", "", $Type);
    $Type = eregi_replace("UNSIGNED", "", $Type);
//  $Length = eregi_replace("$Type|\\(|\\)", "", $Type);
//  Strange: $Type would always match $Type, so replaced with version below:
//  I Huneke (Logica) 29 September 1999
    #$Length = eregi_replace("\\(|\\)", "", $Type);
    $Length = $Type;
    $Type = eregi_replace("\\(.*\\)", "", $Type);
    $Type = chop($Type);
//  Add test to ensure we're not trying to replace blank with blank
//  I Huneke (Logica) 29 September 1999
    if(!empty($Type))
    {
        $Length = eregi_replace("^$Type\(", "", $Length);
        $Length = eregi_replace("\)$", "", trim($Length));
    }
    $Length = htmlspecialchars(chop($Length));
    if($Length == $Type)
        $Length = "";
    for($j=0;$j<count($cfgColumnTypes);$j++)
    {
        echo "<option value=$cfgColumnTypes[$j]";
        if(strtoupper($Type) == strtoupper($cfgColumnTypes[$j]))
            echo " selected";
        echo ">$cfgColumnTypes[$j]</option>\n";
    }
    ?>
     </select>
    </td>
    <td><input type="text" name="field_length[]" size="8" value="<?php echo $Length;?>"></td>
    <td><select name="field_attribute[]">
    <?php
    $binary   = eregi("BINARY", $row["Type"], $test_attribute1);
    $unsigned = eregi("UNSIGNED", $row["Type"], $test_attribute2);
    $zerofill = eregi("ZEROFILL", $row["Type"], $test_attribute3);
    $strAttribute = "";
    if($binary)
        $strAttribute="BINARY";
    if($unsigned)
        $strAttribute="UNSIGNED";
    if($zerofill)
        $strAttribute="UNSIGNED ZEROFILL";
    for($j=0;$j<count($cfgAttributeTypes);$j++)
    {
        echo "<option value=\"$cfgAttributeTypes[$j]\"";
        if(strtoupper($strAttribute) == strtoupper($cfgAttributeTypes[$j]))
            echo " selected";
        echo ">$cfgAttributeTypes[$j]</option>\n";
    }
    ?>
    </select></td>
    <td><select name="field_null[]">
    <?php
    if(!isset($row) || !isset($row["Null"]) || $row["Null"] == "")
    {
        ?>
        <option value=" not null">not null</option>
        <option value="">null</option>
        <?php
    }
    else
    {
        ?>
        <option value="">null</option>
        <option value="not null">not null</option>
        <?php
    }
    ?>
    </select></td>
    <td><input type="text" name="field_default[]" size="8" value="<?php if(isset($row) && isset($row["Default"])) echo $row["Default"];?>"></td>
    <td><select name="field_extra[]">
    <?php
    if(!isset($row) || !isset($row["Extra"]) || $row["Extra"] == "")
    {
        ?>
        <option value=""></option>
        <option value="AUTO_INCREMENT">auto_increment</option>
        <?php
    }
    else
    {
        ?>
        <option value="AUTO_INCREMENT">auto_increment</option>
        <option value=""></option>
        <?php
    }
    ?>
    </select></td>

    <?php
    if($action == "tbl_create.php3" || $action == "tbl_addfield.php3")
    {
       if (empty($num_indexes))
        {
        ?>
        <td align="center">
        <input type="checkbox" name="field_primary[]" value="<?php echo $i;?>"
        <?php
        if(isset($row) && isset($row["Key"]) && $row["Key"] == "PRI")
            echo "checked";
        ?>
        >
        </td>
        <td align="center">
        <input type="checkbox" name="field_index[]" value="<?php echo $i;?>"
        <?php
        if(isset($row) && isset($row["Key"]) && $row["Key"] == "MUL")
            echo "checked";
        ?>
        >
        </td>
        <td align="center">
            <input type="checkbox" name="field_unique[]" value="<?php echo $i;?>"
        <?php
        if(isset($row) && isset($row["Key"]) && $row["Key"] == "UNI")
            echo "checked";
        ?>
        >
        </td>
        <?php
     }
     else {
     }
    }
    ?>
    </tr>
    <?php
}
?>
</table>
<?php
if($action == "tbl_create.php3" && MYSQL_MAJOR_VERSION == "3.23")
{
    echo "$strTableComments:<br>";
    ?>
    <input type="text" name="comment" style="width: <?php echo $cfgMaxInputsize;?>" maxlength="80">
    <?php
}
//BEGIN - Table Type - 2 May 2001 - Robbat2
if($action == "tbl_create.php3")
{
echo $strTableType.":"; ?>
<select name="tbl_type">
<option>Default</option>
<option value="BDB">BerkeleyDB</option>
<?php // Not yet in MySQL <option value="GEMINI">Gemini</option> ?>
<option value="HEAP">Heap</option>
<option value="ISAM">ISAM</option>
<?php // Not yet in MySQL <option value="InnoDB">InnoDB</option> ?>
<option value="MERGE">Merge</option>
<option value="MYISAM">MyISAM</option>
</select>
<?php
}
//END - Table Type - 2 May 2001 - Robbat2
?>
<p>
<input type="submit" name="submit" value="<?php echo $strSave;?>">
</p>
</form>
<center><?php print show_docu("manual_Reference.html#Create_table");?></center>
