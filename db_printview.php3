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

if($num_tables == 0)
{
    echo $strNoTablesFound;
}
else
{
    $i = 0;
    
    echo "<table border=$cfgBorder>\n";
    echo "<th>$strTable</th>";
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
           <td align="right">&nbsp;<?php count_records($db,$table) ?></td>
         </tr>
        <?php
        $i++;
    }
    
    echo "</table>\n";
}

require("./footer.inc.php3");
?>
