<?php
/* $Id$ */


/** 
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
require('./grab_globals.inc.php3');
if (!empty($db)) {
    $db_start = $db;
}
require('./lib.inc.php3');


/**
 * Send http headers
 */
// Don't use cache (required for Opera)
$now = gmdate('D, d M Y H:i:s') . ' GMT';
header('Expires: ' . $now);
header('Last-Modified: ' . $now);
header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// Define the charset to be used
header('Content-Type: text/html; charset=' . $charset);


/**
 * Displays the frame
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>

<head>
    <title>phpMyAdmin</title>
    <base target="phpmain" />
    <!-- Collapsible tables list scripts -->
    <script type="text/javascript" language="javascript1.2">
    <!--
    var isDOM      = (typeof(document.getElementsByTagName) != 'undefined') ? 1 : 0;
    var isIE4      = ((typeof(document.all) != 'undefined') && (parseInt(navigator.appVersion) >= 4)) ? 1 : 0;
    var isNS4      = (typeof(document.layers) != 'undefined') ? 1 : 0;
    var capable    = (isDOM || isIE4 || isNS4) ? 1 : 0;
    var fontFamily = '<?php echo (($charset == 'iso-8859-1') ? 'verdana, helvetica, arial, geneva, sans-serif' : 'sans-serif'); ?>';
    //-->
    </script>
    <script src="left.js" type="text/javascript" language="javascript1.2"></script>
    <style type="text/css">
    <!--
<?php
// Hard coded font name and size depends on charset. This is a temporary and
// uggly fix
$font_family = ($charset == 'iso-8859-1')
             ? 'helvetica, arial, geneva, sans-serif'
             : 'sans-serif';
?>
    body {font-family: <?php echo $font_family; ?>; font-size: 10pt}
    //-->
    </style>
</head>

<body bgcolor="#D0DCE0">
    <!-- Link to the welcome page -->
    <div id="el1Parent" class="parent">
        <a class="item" href="main.php3?server=<?php echo $server; ?>&lang=<?php echo $lang; ?>">
            <font color="black" class="heada"><?php echo $strHome; ?></font></a>
    </div>

   
    <!-- Databases and tables list -->
<?php
$selected_db = 0;
// Don't display database info if $server==0 (no server selected)
// This is the case when there are multiple servers and
// '$cfgServerDefault = 0' is set.  In that case, we want the welcome
// to appear with no database info displayed.
if ($server > 0) {
    // Get db list
    if (empty($dblist)) {
        $dbs     = mysql_list_dbs();
        $num_dbs = mysql_numrows($dbs);
    } else {
        $num_dbs = count($dblist);
    }

    // Get table list per db
    for ($i = 0; $i < $num_dbs; $i++) {
        if (empty($dblist)) {
            $db  = mysql_dbname($dbs, $i);
        } else {
            $db  = $dblist[$i];
        }
        $j = $i + 2;
        if (!empty($db_start) && $db == $db_start) {
            $selected_db = $j;
        }
        $tables           = mysql_list_tables($db);
       	$num_tables       = @mysql_numrows($tables);
		$common_url_query = "server=$server&lang=$lang&db=$db";
		
		echo "\n";
		echo '    <div id="el' . $j . 'Parent" class="parent">';

        if (!empty($num_tables)) {
            echo "\n";
            ?>
        <a class="item" href="db_details.php3?<?php echo $common_url_query; ?>" onclick="expandBase('el<?php echo $j; ?>', true); return false;">
            <img name="imEx" id="el<?php echo $j; ?>Img" src="images/plus.gif" border="0" width="9" height="9" alt="+" /></a>
            <?php
		} else {
            echo "\n";
            ?>
        <img name="imEx" src="images/minus.gif" border="0" width="9" height="9" />
            <?php
        }
        echo "\n";
		?>
        <a class="item" href="db_details.php3?<?php echo $common_url_query; ?>" onclick="expandBase('el<?php echo $j; ?>', false);">
            <font color="black" class="heada"><?php echo $db; ?></font></a>
    </div>
    <div id="el<?php echo $j;?>Child" class="child">
        <?php
        for ($j = 0; $j < $num_tables; $j++) {
            $table = mysql_tablename($tables, $j);
            echo "\n";
            ?>
        <nobr><img src="images/spacer.gif" border="0" width="9" height="9" alt="" />
        <a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&table=<?php echo urlencode($table); ?>&sql_query=<?php echo urlencode("SELECT * FROM $table"); ?>&pos=0&goto=tbl_properties.php3">
            <img src="images/browse.gif" border="0" alt="<?php echo "$strBrowse: $table"; ?>" /></a>&nbsp;
        <a class="item" target="phpmain" href="tbl_properties.php3?<?php echo $common_url_query; ?>&table=<?php echo urlencode($table); ?>">
            <?php echo $table; ?></a></nobr><br />
            <?php
        } // end for $j (tables list)
        echo "\n";
        ?>
    </div>
        <?php
        echo "\n";
    } // end for $t (db list)
    ?>


    <!-- Arrange collapsible/expandable db list at startup -->
    <script type="text/javascript" language="javascript1.2">
    <!--
    if (isNS4) {
      firstEl  = 'el1Parent';
      firstInd = nsGetIndex(firstEl);
      nsShowAll();
      nsArrangeList();
    }
    expandedDb = '<?php echo (empty($selected_db)) ? '' : 'el' . $selected_db . 'Child'; ?>';
    //-->
    </script>
    <?php
} // end if ($server > 0)
echo "\n";
?>
</body>
</html>
