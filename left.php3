<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


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

include('./libraries/bookmark.lib.php3');
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

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


// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by header.inc.php3,
// queryframe.php3, querywindow.php3.

include('./libraries/header_http.inc.php3');

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

    var isServer    = <?php echo ($server > 0) ? 'true' : 'false'; ?>;

    document.writeln('<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?lang=<?php echo $lang; ?>&amp;js_frame=left&amp;js_capable=' + capable + '&amp;js_isDOM=' + isDOM + '&amp;js_isIE4=' + isIE4 + '" />');
    //-->
    </script>
    <noscript>
        <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?lang=<?php echo $lang; ?>&amp;js_frame=left&amp;js_capable=0&amp;js_isDOM=0&amp;js_isIE4=0" />
    </noscript>

    <script src="libraries/left.js" type="text/javascript" language="javascript1.2"></script>
    <?php
} // end if ($num_dbs > 1)

else if ($num_dbs == 1) {
    echo "\n";
    ?>
    <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?js_frame=left&amp;js_capable=0&amp;js_isDOM=0&amp;js_isIE4=0" />
    <?php
} // end if ($num_dbs == 1)

else {
    echo "\n";
    ?>
    <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?js_frame=left&amp;num_dbs=0" />
    <?php
} // end if ($num_dbs < 1)

echo "\n";
?>
</head>

<body bgcolor="<?php echo $cfg['LeftBgColor']; ?>">

<?php
if ($cfg['LeftDisplayLogo']) {
    ?>
    <!-- phpMyAdmin logo -->
    <a href="http://www.phpmyadmin.net" target="_blank"><img src="./images/pma_logo.png" width="88" height="31" border="0" alt="phpMyAdmin" /></a>
    <?php
}
echo "\n";
?>
    <!-- Link to the welcome page -->
    <div id="el1Parent" class="parent" style="margin-bottom: 5px">
        <nobr><a class="item" href="main.php3?<?php echo PMA_generate_common_url(); ?>"><span class="heada"><b><?php echo $strHome; ?></b></span></a></nobr>
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
        echo PMA_generate_common_hidden_inputs();
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
        $common_url_query    = PMA_generate_common_url($db);
        if ($num_tables) {
            $num_tables_disp = $num_tables;
        } else {
            $num_tables_disp = '-';
        }

        // Get additional information about tables for tooltip
        if ($cfg['ShowTooltip'] && PMA_MYSQL_INT_VERSION >= 32303
            && $num_tables
            && (!$cfg['LeftFrameLight'] || $selected_db == $j)) {
            $tooltip = array();
            $tooltip_name = array();
            $result  = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
            while ($tmp = PMA_mysql_fetch_array($result)) {
                $tooltip_name[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '');
                $tmp['Comment'] = ($cfg['ShowTooltipAliasTB'] ? $tmp['Name'] : $tmp['Comment']);

                $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                       . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
            } // end while
        } // end if

        // garvin: Get comments from PMA comments table
        $db_tooltip = '';
        if ($cfg['ShowTooltip'] && $cfgRelation['commwork']) {
            $tmp_db_tooltip = PMA_getComments($db);
            if (is_array($tmp_db_tooltip)) {
                $db_tooltip = implode(' ', $tmp_db_tooltip);
            }
        }

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
            <img name="imEx" id="el<?php echo $j; ?>Img" src="images/plus.png" border="0" width="9" height="9" alt="+" /></a>
                <?php
            } else {
                echo "\n";
                ?>
        <nobr><img name="imEx" src="images/minus.png" border="0" width="9" height="9" alt="-" />
                <?php
            }
            echo "\n";
            ?>
        <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" title="<?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db) : htmlspecialchars($db_tooltip)); ?>" onclick="if (capable) {expandBase('el<?php echo $j; ?>', false)}">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? '<i>' . htmlspecialchars($db_tooltip) . '</i>' : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>

    <div id="el<?php echo $j;?>Child" class="child" style="margin-bottom: 5px"<?php echo $on_mouse; ?>>

            <?php
            // Displays the list of tables from the current database
            for ($t = 0; $t < $num_tables; $t++) {
                $table     = PMA_mysql_tablename($tables, $t);
                $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                           ? htmlspecialchars($tooltip_name[$table])
                           : '';
                $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                           ? htmlspecialchars($tooltip[$table])
                           : '';

                echo "\n";

                $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');
                ?>
        <nobr><img src="images/spacer.gif" border="0" width="9" height="9" alt="" />
        <a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>" title="<?php echo $strBrowse . ': ' . $url_title; ?>">
            <img src="images/browse.png" width="8" height="8" border="0" alt="<?php echo $strBrowse . ': ' . $url_title; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
        <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
            <?php echo ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)); ?></a></nobr><br />
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
                    $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                               ? str_replace('"', '&quot;', $tooltip_name[$table])
                               : '';

                    $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

                    $table_list .= '    <nobr><a target="phpmain" href="sql.php3?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '">' . "\n";
                    $table_list .= '              <img src="images/browse.png" width="8" height="8" border="0" alt="' . $strBrowse . ': ' . $url_title . '" title="' . $strBrowse . ': ' . $url_title . '" /></a><bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
                    if (PMA_USR_BROWSER_AGENT == 'IE') {
                        $table_list .= '          <span class="tblItem"><a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></span></nobr><br />' . "\n";
                    } else {
                        $table_list .= '          <a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></nobr><br />' . "\n";
                    }
                } // end for $t (tables list)

                if (!$table_list) {
                    $table_list = '    <br /><br />' . "\n"
                                . '    <div>' . $strNoTablesFound . '</div>' . "\n";
                }
                $selected = ' selected="selected"';

                $table_list_header .= '    <a class="item" target="phpmain" href="' . $cfg['DefaultTabDatabase'] . '?' . $common_url_query . '">' . "\n";
                $table_list_header .= '        <span class="heada"><b>' . ($db_tooltip != '' && $cfg['ShowTooltipAliasTB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . '</b><bdo dir="' . $text_dir . '">&nbsp;&nbsp;</bdo></span></a><br />' . "\n\n";
            } else {
                $selected = '';
            } // end if... else...

            if (!empty($num_tables)) {
                echo '            <option value="' . htmlspecialchars($db) . '"' . $selected . '>' . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (' . $num_tables . ')</option>' . "\n";
            } else {
                echo '            <option value="' . htmlspecialchars($db) . '"' . $selected . '>' . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (-)</option>' . "\n";
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
    $common_url_query    = PMA_generate_common_url($db);
    if ($num_tables) {
        $num_tables_disp = $num_tables;
    } else {
        $num_tables_disp = '-';
    }

    // Get additional infomation about tables for tooltip
    if ($cfg['ShowTooltip'] && PMA_MYSQL_INT_VERSION >= 32303
        && $num_tables) {
        $tooltip = array();
        $tooltip_name = array();
        $result  = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
        while ($tmp = PMA_mysql_fetch_array($result)) {
                $tooltip_name[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '');
                $tmp['Comment'] = ($cfg['ShowTooltipAliasTB'] ? $tmp['Name'] : $tmp['Comment']);

            $tooltip[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '')
                                   . '(' . (isset($tmp['Rows']) ? $tmp['Rows'] : '0') . ' ' . $strRows . ')';
        } // end while
    } // end if

    // garvin: Get comments from PMA comments table
    $db_tooltip = '';
    if ($cfg['ShowTooltip'] && $cfgRelation['commwork']) {
        $tmp_db_tooltip = PMA_getComments($db);
        if (is_array($tmp_db_tooltip)) {
            $db_tooltip = implode(' ', $tmp_db_tooltip);
        }
    }

    // Displays the database name
    echo "\n";
    ?>
    <div id="el2Parent" class="parent">
        <nobr><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>
    <div id="el2Child" class="child" style="margin-bottom: 5px">
    <?php
    // Displays the list of tables from the current database
    for ($j = 0; $j < $num_tables; $j++) {
        $table     = PMA_mysql_tablename($tables, $j);
        $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                   ? str_replace('"', '&quot;', $tooltip_name[$table])
                   : '';
        $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                   ? str_replace('"', '&quot;', $tooltip[$table])
                   : '';
        $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

        echo "\n";
        ?>
        <nobr><a target="phpmain" href="sql.php3?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>" title="<?php echo $strBrowse . ': ' . htmlspecialchars($table); ?>">
                  <img src="images/browse.png" width="8" height="8" border="0" alt="<?php echo $strBrowse . ': ' . htmlspecialchars($table); ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
              <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
                  <?php echo ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)); ?></a></nobr><br />
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
