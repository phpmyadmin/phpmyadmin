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
if ($cfg['OBGzip']) {
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
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0
// Define the charset to be used
header('Content-Type: text/html; charset=' . $charset);


/**
 * Displays the frame
 */
// Gets the font sizes to use
PMA_setFontSizes();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
    <base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> target="phpmain" />
<?php
// Expandable/collapsible databases list is only used if there is more than one
// database to display
if ($num_dbs > 1 && !$cfg['LeftFrameLight']) {
    echo "\n";
    ?>
    <!-- Collapsible tables list scripts -->
    <script type="text/javascript" language="javascript">
    <!--
    var isDOM      = (typeof(document.getElementsByTagName) != 'undefined'
                      && typeof(document.createElement) != 'undefined')
                   ? 1 : 0;
    var isIE4      = (typeof(document.all) != 'undefined'
                      && parseInt(navigator.appVersion) >= 4)
                   ? 1 : 0;
    var isNS4      = (typeof(document.layers) != 'undefined')
                   ? 1 : 0;
    var capable    = (isDOM || isIE4 || isNS4)
                   ? 1 : 0;
    // Uggly fix for Opera and Konqueror 2.2 that are half DOM compliant
    if (capable) {
        if (typeof(window.opera) != 'undefined') {
            capable = 0;
        }
        else if (typeof(navigator.userAgent) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if (browserName.indexOf('konqueror') > 0) {
                capable = 0;
            }
        } // end if... else if...
    } // end if
    var fontFamily  = '<?php echo $left_font_family; ?>';
    var fontSize    = '<?php echo $font_size; ?>';
    var fontBig     = '<?php echo $font_bigger; ?>';
    var fontSmall   = '<?php echo $font_smaller; ?>';
    var isServer    = <?php echo ($server > 0) ? 'true' : 'false'; ?>;
    //-->
    </script>
    <script src="libraries/left.js" type="text/javascript" language="javascript1.2"></script>
    <noscript>
        <style type="text/css">
        <!--
        div {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
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
    div {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
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
    body    {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
    div     {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
    input   {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
    select  {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; background-color: #ffffff; color: #000000}
    .heada  {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
    .parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
    .item, .item:active, .item:hover, .tblItem, .tblItem:active {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
    .tblItem:hover {color: #FF0000; text-decoration: underline}
    //-->
    </style>
    <?php
} // end if ($num_dbs < 1)

echo "\n";
?>
</head>

<body bgcolor="<?php echo $cfg['LeftBgColor']; ?>">
    <!-- Link to the welcome page -->
    <div id="el1Parent" class="parent" style="margin-bottom: 5px">
        <?php if(isset($cfg['LeftDisplayLogo']) && $cfg['LeftDisplayLogo']) { ?>
            <a href="http://www.phpmyadmin.net" target="_blank"><img src="./images/pma_logo.png" width="88" height="31" border="0" alt="phpMyAdmin" /></a>
        <?php } ?>

        <nobr><a class="item" href="main.php3?lang=<?php echo $lang; ?>&amp;convcharset=<?php echo $convcharset; ?>&amp;server=<?php echo $server; ?>"><span class="heada"><b><?php echo $strHome; ?></b></span></a></nobr>
    </div>


    <!-- Databases and tables list -->
<?php
// Don't display expansible/collapsible database info if:
// 1. $server == 0 (no server selected)
//    This is the case when there are multiple servers and
//    '$cfg['ServerDefault'] = 0' is set. In that case, we want the welcome
//    screen to appear with no database info displayed.
// 2. there is only one database available (ie either only one database exists
//    or $cfg['Servers']['only_db'] is defined and is not an array)
//    In this case, the database should not be collapsible/expandable
if ($num_dbs > 1) {

    // Light mode -> beginning of the select combo for databases
    if ($cfg['LeftFrameLight']) {
        echo '    <form method="post" action="index.php3" name="left" target="_parent">' . "\n";
        echo '        <input type="hidden" name="lang" value="' . $lang . '" />' . "\n";
        echo '        <input type="hidden" name="convcharset" value="' . $convcharset . '" />' . "\n";
        echo '        <input type="hidden" name="server" value="' . $server . '" />' . "\n";
        echo '        <select name="lightm_db" onchange="this.form.submit()">' . "\n";
        echo '            <option value="">(' . $strDatabases . ') ...</option>' . "\n";
        $table_list = '';
        $table_list_header = '';
        $db_name    = '';
    }

    $selected_db = 0;

    // Gets the tables list per database
    for ($i = 0; $i < $num_dbs; $i++) {
        $db = $dblist[$i];
        $j  = $i + 2;
        if (!empty($db_start) && $db == $db_start) {
            $selected_db = $j;
        }
        $tables              = @PMA_mysql_list_tables($db);
        $num_tables          = ($tables) ? @mysql_numrows($tables) : 0;
        $common_url_query    = 'lang=' . $lang
                             . '&amp;convcharset=' . $convcharset
                             . '&amp;server=' . $server
                             . '&amp;db=' . urlencode($db);
        if ($num_tables) {
            $num_tables_disp = $num_tables;
        } else {
            $num_tables_disp = '-';
        }

        // Get additional infomation about tables for tooltip
        if ($cfg['ShowTooltip'] && PMA_MYSQL_INT_VERSION >= 32303
            && $num_tables
            && (!$cfg['LeftFrameLight'] || $selected_db == $j)) {
            $tooltip = array();
            $result  = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
            while ($tmp = PMA_mysql_fetch_array($result)) {
                $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                       . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
            } // end while
        } // end if

        // No light mode -> displays the expandible/collapsible db list
        if ($cfg['LeftFrameLight'] == FALSE) {

            // Displays the database name
            $on_mouse = (($cfg['LeftPointerColor'] == '') ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $j . '\', \'' . $cfg['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $j . '\', \'' . $cfg['LeftBgColor'] . '\')}"');

            echo "\n";
            echo '    <div id="el' . $j . 'Parent" class="parent"' . $on_mouse . '>';

            if (!empty($num_tables)) {
                echo "\n";
                ?>
        <nobr><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" onclick="if (capable) {expandBase('el<?php echo $j; ?>', true); return false} else {return true}">
            <img name="imEx" id="el<?php echo $j; ?>Img" src="images/plus.gif" border="0" width="9" height="9" alt="+" /></a>
                <?php
            } else {
                echo "\n";
                ?>
        <nobr><img name="imEx" src="images/minus.gif" border="0" width="9" height="9" alt="-" />
                <?php
            }
            echo "\n";
            ?>
        <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" onclick="if (capable) {expandBase('el<?php echo $j; ?>', false)}">
            <span class="heada"><?php echo $db; ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>

    <div id="el<?php echo $j;?>Child" class="child" style="margin-bottom: 5px"<?php echo $on_mouse; ?>>

            <?php
            // Displays the list of tables from the current database
            for ($t = 0; $t < $num_tables; $t++) {
                $table     = PMA_mysql_tablename($tables, $t);
                $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                           ? str_replace('"', '&quot;', $tooltip[$table])
                           : '';
                echo "\n";
                ?>
        <nobr><img src="images/spacer.gif" border="0" width="9" height="9" alt="" />
        <a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>">
            <img src="images/browse.gif" width="8" height="8" border="0" alt="<?php echo "$strBrowse: $table"; ?>" title="<?php echo "$strBrowse: $table"; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
        <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
            <?php echo $table; ?></a></nobr><br />
                <?php
            } // end for $t (tables list)
            echo "\n";
            ?>
    </div>
            <?php
            echo "\n";

        }

        // Light mode -> displays the select combo with databases names and the
        // list of tables contained in the current database
        else {
            echo "\n";

            // Builds the databases' names list
            if (!empty($db_start) && $db == $db_start) {
                // Gets the list of tables from the current database
                for ($t = 0; $t < $num_tables; $t++) {
                    $table      = PMA_mysql_tablename($tables, $t);
                    $url_title  = (!empty($tooltip) && isset($tooltip[$table]))
                                ? str_replace('"', '&quot;', $tooltip[$table])
                                : '';
                    $table_list .= '    <nobr><a target="phpmain" href="sql.php3?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . urlencode('SELECT * FROM ' . PMA_backquote($table)) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '">' . "\n";
                    $table_list .= '              <img src="images/browse.gif" width="8" height="8" border="0" alt="' . $strBrowse . ': ' . $table . '" title="' . $strBrowse . ': ' . $table . '" /></a><bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
                    if (PMA_USR_BROWSER_AGENT == 'IE') {
                        $table_list .= '          <span class="tblItem"><a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . $table . '</a></span></nobr><br />' . "\n";
                    } else {
                        $table_list .= '          <a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . $table . '</a></nobr><br />' . "\n";
                    }
                } // end for $t (tables list)

                if (!$table_list) {
                    $table_list = '    <br /><br />' . "\n"
                                . '    <div>' . $strNoTablesFound . '</div>' . "\n";
                }
                $selected = ' selected="selected"';

                $table_list_header .= '    <a class="item" target="phpmain" href="' . $cfg['DefaultTabDatabase'] . '?' . $common_url_query . '">' . "\n";
                $table_list_header .= '        <span class="heada"><b>' . $db . '</b><bdo dir="' . $text_dir . '">&nbsp;&nbsp;</bdo></span></a><br />' . "\n\n";
            } else {
                $selected = '';
            } // end if... else...

            if (!empty($num_tables)) {
                echo '            <option value="' . urlencode($db) . '"' . $selected . '>' . $db . ' (' . $num_tables . ')</option>' . "\n";
            } else {
                echo '            <option value="' . urlencode($db) . '"' . $selected . '>' . $db . ' (-)</option>' . "\n";
            } // end if... else...

        } // end if (light mode)

    } // end for $i (db list)

    // Light mode -> end of the select combo for databases and table list for
    // the current database
    if ($cfg['LeftFrameLight']) {
        echo '        </select>' . "\n";
        echo '        <noscript><input type="submit" name="Go" value="' . $strGo . '" /></noscript>' . "\n";
        echo '    </form>' . "\n";

        if (!$table_list) {
            $table_list = '    <div>' . $strSelectADb . '</div>' . "\n";
        }

        // Displays the current database name and the list of tables it
        // contains
        echo "\n" . '    <hr noshade="noshade" />' . "\n\n";
        echo $table_list_header;
        echo $table_list;
        echo "\n" . '    <hr noshade="noshade" />' . "\n";
    }

    // No light mode -> initialize some js variables for the
    // expandible/collapsible stuff
    else {
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
    var expandedDb = '<?php echo (empty($selected_db)) ? '' : 'el' . $selected_db . 'Child'; ?>';
    //-->
    </script>
        <?php

    } // end if... else... (light mode)

} // end if ($server > 1)


// Case where only one database has to be displayed
else if ($num_dbs == 1) {
    $db                  = $dblist[0];
    $tables              = @PMA_mysql_list_tables($db);
    $num_tables          = ($tables) ? @mysql_numrows($tables) : 0;
    $common_url_query    = 'lang=' . $lang
                         . '&amp;server=' . $server
                         . '&amp;db=' . urlencode($db);
    if ($num_tables) {
        $num_tables_disp = $num_tables;
    } else {
        $num_tables_disp = '-';
    }

    // Get additional infomation about tables for tooltip
    if ($cfg['ShowTooltip'] && PMA_MYSQL_INT_VERSION >= 32303
        && $num_tables) {
        $tooltip = array();
        $result  = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
        while ($tmp = PMA_mysql_fetch_array($result)) {
            $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                   . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
        } // end while
    } // end if

    // Displays the database name
    echo "\n";
    ?>
    <div id="el2Parent" class="parent">
        <nobr><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>">
            <span class="heada"><?php echo $db; ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>
    <div id="el2Child" class="child" style="margin-bottom: 5px">
    <?php
    // Displays the list of tables from the current database
    for ($j = 0; $j < $num_tables; $j++) {
        $table     = PMA_mysql_tablename($tables, $j);
        $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                   ? str_replace('"', '&quot;', $tooltip[$table])
                   : '';
        echo "\n";
        ?>
        <nobr><a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table)); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>">
                  <img src="images/browse.gif" width="8" height="8" border="0" alt="<?php echo "$strBrowse: $table"; ?>" title="<?php echo "$strBrowse: $table"; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
              <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
                  <?php echo $table; ?></a></nobr><br />
        <?php
    } // end for $j (tables list)
    echo "\n";
    ?>
    </div>
    <?php
} // end if ($num_dbs == 1)


// Case where no database has to be displayed
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
if (isset($cfg['OBGzip']) && $cfg['OBGzip']
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
