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
if(MYSQL_MAJOR_VERSION == "3.23")
    {   
    $result = mysql_db_query($db, "SHOW TABLE STATUS LIKE '$table'") or mysql_die();
    $row = mysql_fetch_array($result);
    if(!empty($row["Comment"]))
    {
        echo "$strTableComments: $row[Comment]";
    }
}

$result = mysql_db_query($db, "SHOW KEYS FROM $table") or mysql_die();
$primary = "";

while($row = mysql_fetch_array($result))
    if ($row["Key_name"] == "PRIMARY")
        $primary .= "$row[Column_name], ";

$result = mysql_db_query($db, "SHOW FIELDS FROM $table") or mysql_die();

?>
<table border=<?php echo $cfgBorder;?>>
<TR>
   <TH><?php echo $strField; ?></TH>
   <TH><?php echo $strType; ?></TH>
   <TH><?php echo $strAttr; ?></TH>
   <TH><?php echo $strNull; ?></TH>
   <TH><?php echo $strDefault; ?></TH>
   <TH><?php echo $strExtra; ?></TH>
</TR>

<?php
$i=0;

while($row= mysql_fetch_array($result))
{
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
    $Type = eregi_replace("BINARY", "", $Type);
    $Type = eregi_replace("ZEROFILL", "", $Type);
    $Type = eregi_replace("UNSIGNED", "", $Type);
	echo $Type;
	?>&nbsp;</td>
         <td>
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
	echo $strAttribute;
    $strAttribute="";
	?>
	 &nbsp;</td>
	 <td><?php if ($row["Null"] == "") { echo $strNo;} else {echo $strYes;}?>&nbsp;</td>
         <td><?php if(isset($row["Default"])) echo $row["Default"];?>&nbsp;</td>
         <td><?php echo $row["Extra"];?>&nbsp;</td>
         </tr>
    <?php
}
?>
</table>
<?php

$result = mysql_db_query($db, "SHOW KEYS FROM ".$table) or mysql_die();
if(mysql_num_rows($result)>0)
{
    ?>
    <br>
    <table border=<?php echo $cfgBorder;?>>
      <tr>
      <th><?php echo $strKeyname; ?></th>
      <th><?php echo $strUnique; ?></th>
      <th><?php echo $strField; ?></th>
      </tr>
    <?php
    for($i=0 ; $i<mysql_num_rows($result); $i++)
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
        <?php
        echo "</tr>";
    }
    print "</table>\n";
}

require("./footer.inc.php3");
?>
