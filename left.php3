<?php
/* $Id$ */
 
require("./lib.inc.php3");
?>

<html>
<head>
<title>phpMyAdmin</title>
<base target="phpmain" />
<!-- Collapsible tables list -->
<script type="text/javascript" language="javascript1.2">
<!--
var isDOM        = (typeof(document.getElementsByTagName) != 'undefined') ? 1 : 0;
var isIE4        = ((typeof(document.all) != 'undefined') && (parseInt(navigator.appVersion) >= 4)) ? 1 : 0;
var isNS4        = (typeof(document.layers) != 'undefined') ? 1 : 0;
var capable      = (isDOM || isIE4 || isNS4) ? 1 : 0;
//-->
</script>
<script src="left.js" type="text/javascript" language="javascript1.2"></script>
<style type="text/css">
<!--
body {  font-family: Arial, Helvetica, sans-serif; font-size: 10pt}
//-->
</style>
</head>

<body bgcolor="#D0DCE0">
 <DIV ID="el1Parent" CLASS="parent">
      <A class="item" HREF="main.php3?server=<?php echo $server; ?>&lang=<?php echo $lang; ?>">
      <FONT color="black" class="heada">
      <?php echo $strHome;?>   </FONT></A>
      </DIV>
<?php
// Don't display database info if $server==0 (no server selected)
// This is the case when there are multiple servers and
// '$cfgServerDefault = 0' is set.  In that case, we want the welcome
// to appear with no database info displayed.
if($server > 0)
{
    if(empty($dblist))
    {
        $dbs = mysql_list_dbs();
        $num_dbs = mysql_numrows($dbs);
    }
    else
    {
        $num_dbs = count($dblist);
    }

    for($i=0; $i<$num_dbs; $i++)
    {
        if (empty($dblist))
            $db = mysql_dbname($dbs, $i);
        else
            $db = $dblist[$i];
    $j = $i + 2;
    ?>
      <div ID="el<?php echo $j;?>Parent" CLASS="parent">
      <a class="item" HREF="db_details.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=<?php echo $db;?>" onClick="expandBase('el<?php echo $j;?>', true); return false;">
      <img NAME="imEx" SRC="images/plus.gif" BORDER="0" ALT="+" width="9" height="9" ID="el<?php echo $j;?>Img"></a>
      <a class="item" HREF="db_details.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=<?php echo $db;?>" onClick="expandBase('el<?php echo $j;?>', false);">
      <font color="black" class="heada">
    <?php echo $db;?>
      </font></a>
      </div>
      <div ID="el<?php echo $j;?>Child" CLASS="child">
    <?php
    $tables = mysql_list_tables($db);
    $num_tables = @mysql_numrows($tables);

    for($j=0; $j<$num_tables; $j++)
    {
        $table = mysql_tablename($tables, $j);
        ?>
            <nobr>&nbsp;&nbsp;&nbsp;&nbsp;<a target="phpmain" href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=<?php echo $db;?>&table=<?php echo urlencode($table);?>&sql_query=<?php echo urlencode("SELECT * FROM $table");?>&pos=0&goto=tbl_properties.php3"><img src="images/browse.gif" border="0" alt="<?php echo $strBrowse.": ".$table;?>"></a>&nbsp;<a class="item" target="phpmain" HREF="tbl_properties.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=<?php echo $db;?>&table=<?php echo urlencode($table);?>"><?php echo $table;?></a></nobr><br>
        <?php
    }

        echo "</div>\n";
}
    ?>
    <script type="text/javascript" language="javascript1.2">
    <!--
    if (isNS4) {
      firstEl  = 'el1Parent';
      firstInd = nsGetIndex(firstEl);
      nsShowAll();
      nsArrangeList();
    }
    //-->
    </script>
    <?php
}
?>
</body>
</html>
