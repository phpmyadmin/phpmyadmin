<?php
/* $Id$ */


/**
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
require('./libraries/grab_globals.lib.php3');
if (!empty($db)) {
    $db_start = $db;
}


/**
 * Gets a core script and starts output buffering work 
 */
require('./libraries/common.lib.php3');
require('./libraries/ob.lib.php3');
if ($cfgOBGzip) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}


/**
 * Get the list and number of available databases.
 * Skipped if no server selected: in this case no database should be displayed
 * before the user choose among available ones at the welcome screen.
 */
if ($server > 0) {
    PMA_availableDatabases(); // this function is defined in "common.lib.php3"
} else {
    $num_dbs = 0;
}


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
// Gets the font sizes to use
PMA_setFontSizes();
?>
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
    <title>phpMyAdmin</title>
    <base target="phpmain" />
<?php
// Expandable/collapsible databases list is only used if there is more than one
// database to display
if ($num_dbs > 1) {
    echo "\n";
    ?>
    <!-- Collapsible tables list scripts -->
    <script type="text/javascript" language="javascript">
    <!--
    var fontFamily = '<?php echo $left_font_family; ?>';
    var fontSize   = '<?php echo $font_size; ?>';
    var fontBig    = '<?php echo $font_bigger; ?>';
    var fontSmall  = '<?php echo $font_smaller; ?>';
    var isServer   = <?php echo ($server > 0) ? 'true' : 'false'; ?>;
    //-->
    </script>
    <noscript>
        <style type="text/css">
        <!--
        div {color: #000000}
        .heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
        .headaCnt {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #000000}
        .parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
        .child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
        .item, .item:active, .item:hover, .tblItem, .tblItem:active {color: #333399; text-decoration: none}
        .tblItem:hover {color: #FF0000; text-decoration: underline}
        //-->
        </style>
    </noscript>

    <style type="text/css">
    <!--
    body {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
    //-->
    </style>
    <?php
} // end if ($num_dbs > 1)

else if ($num_dbs == 1) {
    echo "\n";
    ?>
    <style type="text/css">
    <!--
    body {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
    div {color: #000000}
    .heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
    .headaCnt {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #000000}
    .parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
    .child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
    .item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
    .tblItem:hover {color: #FF0000; text-decoration: underline}
    //-->
    </style>
    <?php
} // end if ($num_dbs == 1)

else {
    echo "\n";
    ?>
    <style type="text/css">
    <!--
    body {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
    div {color: #000000}
    .heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
    .parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
    .item, .item:active, .item:hover {color: #333399; text-decoration: none}
    //-->
    </style>
    <?php
} // end if ($num_dbs < 1)

echo "\n";
?>
</head>

<body bgcolor="<?php echo $cfgLeftBgColor; ?>">
    <!-- Link to the welcome page -->
    <div id="el1Parent" class="parent" style="margin-bottom: 5px">
        <nobr><a class="item" href="main.php3?lang=<?php echo $lang; ?>&amp;server=<?php echo $server; ?>">
            <span class="heada"><b><?php echo $strHome; ?></b></span></a></nobr>
    </div>
   
    <!-- Databases list -->
<?php
// Don't display expansible/collapsible database info if:
// 1. $server == 0 (no server selected)
//    This is the case when there are multiple servers and
//    '$cfgServerDefault = 0' is set. In that case, we want the welcome screen
//    to appear with no database info displayed.
// 2. there is only one database available (ie either only one database exists
//    or $cfgServers['only_db'] is defined and is not an array)
//    In this case, the database should not be collapsible/expandable
if ($num_dbs > 1) {

    echo '<form action="leftlight.php3" method="get" name="left" target="nav">';
    echo '<input type="hidden" name="lang" value="' . $lang . '">';
    echo '<input type="hidden" name="server" value="' . $server . '">';
    echo '<select name="db" onchange="this.form.submit();">';
    $table_list = "";
    $db_name = "";

    // Gets the tables list per database
    for ($i = 0; $i < $num_dbs; $i++) {
        $db = $dblist[$i];
        $j  = $i + 2;

        $tables              = @mysql_list_tables($db);
        $num_tables          = @mysql_numrows($tables);
        $common_url_query    = 'lang=' . $lang
                             . '&amp;server=' . $server
                             . '&amp;db=' . urlencode($db);
        if ($num_tables) {
            $num_tables_disp = $num_tables;
        } else {
            $num_tables_disp = '-';
        }

        // Get additional infomation about tables for tooltip
        if ($cfgShowTooltip && PMA_MYSQL_INT_VERSION >= 32303
            && $num_tables) {
            $tooltip = array();
            $result  = mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
            while ($tmp = mysql_fetch_array($result)) {
                $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                       . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
            } // end while
        } // end if


        // Displays the database name
        echo "\n";        

        if (!empty($db_start) && $db == $db_start) {
                // Displays the list of tables from the current database
                for ($j = 0; $j < $num_tables; $j++) {
                    $table = mysql_tablename($tables, $j);
                    $table_list .= '<nobr><img src="images/spacer.gif" border="0" width="9" height="9" alt="" />';
                    $table_list .= '<a target="phpmain" href="sql.php3?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($table)) . '&amp;pos=0&amp;goto=tbl_properties.php3">';
                    $table_list .= '<img src="images/browse.gif" border="0" alt="' . $strBrowse . ': ' . $table . '" /></a>';
                    $table_list .= '<bdo dir="' . $text_dir . '">&nbsp;</bdo>';
                    $table_list .= '<nobr><a class="tblItem" title="' . str_replace('"', '&quot;', $tooltip[$table]) . '" target="phpmain" href="tbl_properties.php3?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . $table . '</a></nobr><br />' . "\n";
                } // end for $j (tables list)

                if (!$table_list) { 
                    $table_list = "no tables in this db";
                }
            $selected = ' SELECTED';

            $db_name = '<a class="item" href="db_details.php3?' . $common_url_query .'">';
            $db_name .= '<span class="heada">' . $db . '<bdo dir="' . $text_dir . '">&nbsp;&nbsp;</bdo></span>';
            $db_name .= '<span class="headaCnt">(' . $num_tables_disp . ')</span></a></nobr>';
      
        } else {
            $selected = '';
        }

        if (!empty($num_tables)) {
            echo "<option value=\"" . urlencode($db) . "\"$selected>$db ($num_tables)</option>";
        } else {
            echo "<option value=\"" . urlencode($db) . "\"$selected>$db (-)</option>";
        }
        
        ?>
        <?php
    } // end for $t (db list)

    echo '</select>';
    echo '<noscript><input type="submit" name="Go" value="Go"></noscript>';
    echo '</form>';

    if (!$table_list) { 
        $table_list = "please select a db";
    } 

    echo "$db_name<hr noshade>$table_list<hr noshade>";

} // end if ($server > 1)

// Case where only one database has to be displayed
else if ($num_dbs == 1) {
    $db                  = $dblist[0];
    $tables              = @mysql_list_tables($db);
    $num_tables          = @mysql_numrows($tables);
    $common_url_query    = 'lang=' . $lang
                         . '&amp;server=' . $server
                         . '&amp;db=' . urlencode($db);
    if ($num_tables) {
        $num_tables_disp = $num_tables;
    } else {
        $num_tables_disp = '-';
    }

    // Get additional infomation about tables for tooltip
    if ($cfgShowTooltip && PMA_MYSQL_INT_VERSION >= 32303
        && $num_tables) {
        $tooltip = array();
        $result  = mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
        while ($tmp = mysql_fetch_array($result)) {
            $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                   . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
        } // end while
    } // end if

    // Displays the database name
    echo "\n";
    ?>
    <div id="el2Parent" class="parent">
        <nobr><a class="item" href="db_details.php3?<?php echo $common_url_query; ?>">
            <span class="heada"><?php echo $db; ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>
    <div id="el2Child" class="child" style="margin-bottom: 5px">
    <?php
    // Displays the list of tables from the current database
    for ($j = 0; $j < $num_tables; $j++) {
        $table = mysql_tablename($tables, $j);
        echo "\n";
        ?>
        <nobr><a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0&amp;goto=tbl_properties.php3">
                  <img src="images/browse.gif" border="0" alt="<?php echo "$strBrowse: $table"; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
              <a class="tblItem" title="<?php echo str_replace('"', '&quot;', $tooltip[$table]); ?>" target="phpmain" href="tbl_properties.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
                  <?php echo $table; ?></a></nobr><br />
        <?php
    } // end for $j (tables list)
    echo "\n";
    ?>
    </div>
    <?php
} // end if ($num_dbs == 1)
else {
    echo "\n";
    echo '<p>' . $strNoDatabases . '</p>';
} // end if ($num_dbs == 0)
echo "\n";
?>

</body>
</html>

<?php
/**
 * Close MySql connections
 */
if (isset($dbh) && $dbh) {
    @mysql_close($dbh);
}
if (isset($userlink) && $userlink) {
    @mysql_close($userlink);
}


/**
 * Sends bufferized data
 */
if (isset($cfgOBGzip) && $cfgOBGzip
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
