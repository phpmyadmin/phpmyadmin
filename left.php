<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
require_once('./libraries/grab_globals.lib.php');
if (isset($lightm_db) && !empty($lightm_db)) {
// no longer urlencoded because of html entities in the db name
//    $db = urldecode($lightm_db);
    $db = $lightm_db;
}

if (!empty($db)) {
    $db_start = $db;
}


/**
 * Gets a core script and starts output buffering work
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/ob.lib.php');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}

// This check had been put here to avoid revealing the full path
// of the phpMyAdmin directory in case this script is called
// directly. But some users report a "Missing hash" message and
// I cannot reproduce it, so let's define $hash to a dummy value
// and hope some other clue will surface, to sort this bug.
//PMA_checkParameters(array('hash'));
if (!isset($hash)) {
    $hash='';
}

require_once('./libraries/bookmark.lib.php');
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

function PMA_reduceNest($_table) {

    if ($GLOBALS['cfg']['LeftFrameTableLevel'] > 0) {
        $max = $GLOBALS['cfg']['LeftFrameTableLevel'];
        $temp_table = $_table;
        $new_table = array();
        $last_index = 0;
        for ($ti = 0; $ti < $max; $ti++) {
            if (isset($temp_table[$ti])) {
                $new_table[$ti] = $temp_table[$ti];
                unset($temp_table[$ti]);
                $last_index = $ti;
            }
        }

        $_table = $new_table;
    }

    return $_table;
}

function PMA_indent($spaces) {
    $string = '';
    for ($i = 0; $i <= $spaces; $i++) {
        $string .= ' ';
    }

    return $string;
}

function PMA_nestedSetHeaderParent($baseid, $key, $keyhistory, $indent, $indent_level, $val, $childout = true) {
    $name = $key;
    $id = preg_replace('@[^a-z0-9]*@i', '', $baseid . $keyhistory . $key) . $indent;

    $on_mouse = (($GLOBALS['cfg']['LeftPointerColor'] == '') ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $id . '\', \'' . $GLOBALS['cfg']['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $id . '\', \'' . $GLOBALS['cfg']['LeftBgColor'] . '\')}"');

    $countarray = $val;
    if (count($countarray) == 2 && isset($countarray['pma_name']) && isset($countarray['pma_list_item'])) {
        $counter = count($countarray['pma_name']);
    } else {
        unset($countarray['pma_name']);
        if (count($countarray) > 1) {
            unset($countarray['pma_list_item']);
        }
        $counter = count($countarray);
    }

    echo "\n";
    echo PMA_indent($indent * 5) . '<div id="el' . $id . 'Parent" class="parent"' . $on_mouse . '>' . "\n";
    echo PMA_indent($indent * 6) . '<nobr><img src="images/spacer.gif" border="0" width="' . (($indent - 1) * $indent_level) . '" height="9" alt="" /><a class="item" href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $GLOBALS['common_url_query'] . '" onclick="if (capable) {expandBase(\'el' . $id . '\', true); return false} else {return true}">';
    echo '<img name="imEx" id="el' . $id . 'Img" src="images/plus.png" border="0" width="9" height="9" alt="+" /></a>' . "\n";
    echo PMA_indent($indent * 6) . '<a class="item" href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $GLOBALS['common_url_query'] . '" title="' . htmlspecialchars($name) . '" onclick="if (capable) {expandBase(\'el' . $id . '\', false)}"><span class="heada">' . htmlspecialchars($name) . '<bdo dir="' . $GLOBALS['text_dir'] . '">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(' . $counter . ')</span></a></nobr>' . "\n";
    echo PMA_indent($indent * 5) . '</div><id class="PMA_nestedSetHeaderParent">' . "\n";
    echo "\n";

    if ($childout) {
        echo PMA_indent($indent * 5) . '<div id="el' . $id . 'Child" class="child" ' . $on_mouse . '>' . "\n";
    }
}

function PMA_nestedSetHeader($baseid, $tablestack, $keyhistory, $indent, $indent_level, $headerOut, $firstGroup = false, $firstGroupClose = true) {
    if ($firstGroup) {
        PMA_nestedSetHeaderParent($baseid, $firstGroup, $keyhistory, $indent, $indent_level, $tablestack);
        $indent++;
    }

    foreach($tablestack AS $key => $val) {
        if ($key != 'pma_name' && $key != 'pma_list_item') {
            if ($headerOut) {
                PMA_nestedSetHeaderParent($baseid, $key, $keyhistory, $indent, $indent_level, $val);
            }

            if (isset($val['pma_name']) && isset($val['pma_list_item']) && count($val) == 2) {
                PMA_nestedSet($baseid, $val, $key, $keyhistory . $key, false, ($indent + 1));
            } else {
                PMA_nestedSet($baseid, $val, $key, $keyhistory . $key, true, ($indent + 1));
            }

            if ($headerOut) {
                echo PMA_indent($indent * 5) . '</div><id class="PMA_nestedSetHeader">' . "\n";
            }
        }
    }

    if ($firstGroup && $firstGroupClose) {
        echo PMA_indent($indent * 4) . '</div><id class="PMA_nestedSetHeader2">' . "\n";
    } elseif ($firstGroup) {
        echo PMA_indent($indent * 4) . '<id spacer="div omitted" class="PMA_nestedSetHeader2">' . "\n";
    }
}

function PMA_nestedSet($baseid, $tablestack, $key = '__protected__', $keyhistory = '', $headerOut = false, $indent = 1) {

    if ($keyhistory == '' && $key != '__protected__') {
        $keyhistory = $key;
    }

    $indent_level = 9;

    if (isset($tablestack)
        && isset($tablestack['pma_name'])
        && isset($tablestack['pma_list_item'])) {

        if (count($tablestack) > 1 && !empty($key) && isset($tablestack['pma_name']) && isset($tablestack['pma_list_item']) && $indent == 1) {
            PMA_nestedSetHeader($baseid, $tablestack, $keyhistory, ($indent+1), $indent_level, $headerOut, $key, false);
            $divClose = true;
            $extra_indent = 1;
        } else {
            PMA_nestedSetHeader($baseid, $tablestack, $keyhistory, $indent, $indent_level, $headerOut);
            $divClose = false;
            $extra_indent = 0;
        }

        $on_mouse = (($GLOBALS['cfg']['LeftPointerColor'] == '') ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $keyhistory . $key . '\', \'' . $GLOBALS['cfg']['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $keyhistory . $key . '\', \'' . $GLOBALS['cfg']['LeftBgColor'] . '\')}"');

        $loops = 0;
        foreach($tablestack['pma_name'] AS $tkey => $tval) {

            echo PMA_indent($indent * 5) . '<nobr><img src="images/spacer.gif" border="0" width="' . (($indent+$extra_indent) * $indent_level) . '" height="9" alt="" />';
            $items = explode("\n", $tablestack['pma_list_item'][$tkey]);
            foreach($items AS $ikey => $ival) {
                echo "\n";
                echo PMA_indent(($indent * 5)) . $ival;
            }
            echo "\n";

            $loops++;
        }

        if ($divClose) {
            echo PMA_indent($indent * 5) . '</div><id space="putting omitted div" class="PMA_nestedSet2">';
        }

    } elseif (is_array($tablestack)) {
        PMA_nestedSetHeader($baseid, $tablestack, $keyhistory, (($key == '__protected__' && $indent == 1 )? ($indent-1) : ($indent + 1)), $indent_level, $headerOut,  (($key == '__protected__' && $indent == 1) || ($indent > 1) ? false : $key));
    }

    return true;
}
/**
 * Get the list and number of available databases.
 * Skipped if no server selected: in this case no database should be displayed
 * before the user choose among available ones at the welcome screen.
 */
if ($server > 0) {
    PMA_availableDatabases(); // this function is defined in "common.lib.php"
} else {
    $num_dbs = 0;
}


// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by header.inc.php,
// queryframe.php, querywindow.php.

require_once('./libraries/header_http.inc.php');

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
    <base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> target="phpmain<?php echo $hash; ?>" />

    <script type="text/javascript" language="javascript">
    <!--
<?php
if (isset($lightm_db) && !empty($lightm_db)) {
?>
    window.parent.frames['phpmain<?php echo $hash; ?>'].location.replace('./<?php echo $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db, '', '&');?>');
<?php
} elseif (isset($lightm_db)) {
?>
    window.parent.frames['phpmain<?php echo $hash; ?>'].location.replace('./main.php?<?php echo PMA_generate_common_url('', '', '&');?>');
<?php
}
?>
    //-->
    </script>

<?php
// Expandable/collapsible databases list is only used if there is more than one
// database to display
if (($num_dbs > 1 || !empty($cfg['LeftFrameTableSeparator'])) && !$cfg['LeftFrameLight']) {
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
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror 7') == 0)) {
                capable = 0;
            }
        }
        else if (typeof(navigator.userAgent) != 'undefined') {
            var browserName = ' ' + navigator.userAgent.toLowerCase();
            if ((browserName.indexOf('konqueror') > 0) && (browserName.indexOf('konqueror/3') == 0)) {
                capable = 0;
            }
        } // end if... else if...
    } // end if

    var isServer    = <?php echo ($server > 0) ? 'true' : 'false'; ?>;

    document.writeln('<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?lang=<?php echo $lang; ?>&amp;js_frame=left&amp;js_capable=' + capable + '&amp;js_isDOM=' + isDOM + '&amp;js_isIE4=' + isIE4 + '" />');
    //-->
    </script>
    <noscript>
        <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?lang=<?php echo $lang; ?>&amp;js_frame=left&amp;js_capable=0&amp;js_isDOM=0&amp;js_isIE4=0" />
    </noscript>

    <script src="libraries/left.js" type="text/javascript" language="javascript1.2"></script>
    <?php
} // end if ($num_dbs > 1)

else if ($num_dbs == 1) {
    echo "\n";
    ?>
    <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?js_frame=left&amp;js_capable=0&amp;js_isDOM=0&amp;js_isIE4=0" />
    <?php
} // end if ($num_dbs == 1)

else {
    echo "\n";
    ?>
    <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?js_frame=left&amp;num_dbs=0" />
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
if ($cfg['LeftDisplayServers']) {
?>
        <form method="post" action="index.php" target="_parent">
            <select name="server" onchange="this.form.submit();">
    <?php
    echo "\n";
    foreach($cfg['Servers'] AS $key => $val) {
        if (!empty($val['host'])) {
            echo '                <option value="' . $key . '"';
            if (!empty($server) && ($server == $key)) {
                echo ' selected="selected"';
            }
            echo '>';
            if (!empty($val['verbose'])) {
                echo $val['verbose'];
            } else {
                echo $val['host'];
                if (!empty($val['port'])) {
                    echo ':' . $val['port'];
                }
                // loic1: skip this because it's not a so good idea to display
                //        sockets used to everybody
                // if (!empty($val['socket']) && PMA_PHP_INT_VERSION >= 30010) {
                //     echo ':' . $val['socket'];
                // }
            }
            // loic1: if 'only_db' is an array and there is more than one
            //        value, displaying such informations may not be a so good
            //        idea
            if (!empty($val['only_db'])) {
                echo ' - ' . (is_array($val['only_db']) ? implode(', ', $val['only_db']) : $val['only_db']);
            }
            if (!empty($val['user']) && ($val['auth_type'] == 'config')) {
                echo '  (' . $val['user'] . ')';
            }
            echo '&nbsp;</option>' . "\n";
        } // end if (!empty($val['host']))
    } // end while
    ?>
            </select>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
            <noscript><input type="submit" value="<?php echo $strGo; ?>" /></noscript>
        </form>
<?php
}
echo "\n";
?>
    <!-- Link to the welcome page -->
    <div id="el1Parent" class="parent" style="margin-bottom: 5px">
        <nobr><a class="item" href="main.php?<?php echo PMA_generate_common_url(); ?>"><span class="heada"><b><?php echo $strHome; ?></b></span></a></nobr>
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
    // Note: When javascript is active, the frameset will be changed from
    // within left.php. With no JS (<noscript>) the whole frameset will
    // be rebuilt with the new target frame.
    if ($cfg['LeftFrameLight']) {
        ?>
        <script type="text/javascript" language="javascript">
            document.writeln('<form method="post" action="left.php" name="left" target="nav">');
        </script>
        <noscript>
            <form method="post" action="index.php" name="left" target="_parent">
        </noscript>
        <?php
        echo PMA_generate_common_hidden_inputs();
        echo '        <input type="hidden" name="hash" value="' . $hash . '" />' . "\n";
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
            $tablestack = array();
            for ($t = 0; $t < $num_tables; $t++) {
                $table     = PMA_mysql_tablename($tables, $t);
                $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                           ? htmlspecialchars($tooltip_name[$table])
                           : '';
                $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                           ? htmlspecialchars($tooltip[$table])
                           : '';

                $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

                $list_item = '<a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '" title="' . $strBrowse . ': ' . $url_title . '">';
                $list_item .= '<img src="images/button_smallbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" /></a>';
                $list_item .= '<bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
                $list_item .= '<a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">';
                $list_item .= ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></nobr><br />' . "\n";

                // garvin: Check whether to display nested sets
                if (!empty($cfg['LeftFrameTableSeparator'])) {
                    $_table = explode($cfg['LeftFrameTableSeparator'],  str_replace('\'', '\\\'',$table));
                    if (is_array($_table)) {
                        foreach($_table AS $key => $val) {
                            if ($val == '') {
                                $_table[$key] = '__protected__';
                            }
                        }

                        unset($_table[count($_table)-1]);
                        $_table = PMA_reduceNest($_table);

                        $eval_string = '$tablestack[\'' . implode('\'][\'', $_table) . '\'][\'pma_name\'][] = \'' . str_replace('\'', '\\\'', $table) . '\';';
                        $eval_string .= '$tablestack[\'' . implode('\'][\'', $_table) . '\'][\'pma_list_item\'][] = \'' . str_replace('\'', '\\\'', $list_item) . '\';';
                        eval($eval_string);
                    } else {
                        $tablestack['']['pma_name'][] = $table;
                        $tablestack['']['pma_list_item'][] = $list_item;
                    }
                } else {
                    $tablestack['']['pma_name'][] = $table;
                    $tablestack['']['pma_list_item'][] = $list_item;
                }
            } // end for $t (tables list)

            PMA_nestedSet($j, $tablestack);
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
                                ? htmlentities($tooltip[$table])
                                : '';
                    $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                               ? htmlentities($tooltip_name[$table])
                               : '';

                    $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

                    $table_list .= '    <nobr><a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '">' . "\n";
                    $table_list .= '              <img src="images/button_smallbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" title="' . $strBrowse . ': ' . $url_title . '" /></a><bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
                    if (PMA_USR_BROWSER_AGENT == 'IE') {
                        $table_list .= '          <span class="tblItem"><a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></span></nobr><br />' . "\n";
                    } else {
                        $table_list .= '          <a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></nobr><br />' . "\n";
                    }
                } // end for $t (tables list)

                if (!$table_list) {
                    $table_list = '    <br /><br />' . "\n"
                                . '    <div>' . $strNoTablesFound . '</div>' . "\n";
                }
                $selected = ' selected="selected"';

                $table_list_header .= '    <a class="item" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabDatabase'] . '?' . $common_url_query . '">' . "\n";
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
    if (!$cfg['LeftFrameLight']) {
            $on_mouse = (($cfg['LeftPointerColor'] == '') ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el2\', \'' . $cfg['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el2\', \'' . $cfg['LeftBgColor'] . '\')}"');

            echo "\n";
            echo '    <div id="el2Parent" class="parent"' . $on_mouse . '>';

            if (!empty($num_tables)) {
                echo "\n";
                ?>
        <nobr><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" onclick="if (capable) {expandBase('el2', true); return false} else {return true}">
            <img name="imEx" id="el2Img" src="images/plus.png" border="0" width="9" height="9" alt="+" /></a>
                <?php
            } else {
                echo "\n";
                ?>
        <nobr><img name="imEx" src="images/minus.png" border="0" width="9" height="9" alt="-" />
                <?php
            }
            echo "\n";
            ?>
        <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" title="<?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db) : htmlspecialchars($db_tooltip)); ?>" onclick="if (capable) {expandBase('el2', false)}">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? '<i>' . htmlspecialchars($db_tooltip) . '</i>' : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>

    <div id="el2Child" class="child" style="margin-bottom: 5px"<?php echo $on_mouse; ?>>
    <?php
    } else {
        echo "\n";
        ?>
    <div id="el2Parent" class="parent">
        <nobr><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></nobr>
    </div>
    <div id="el2Child" class="child" style="margin-bottom: 5px">
        <?php
    }

    // Displays the list of tables from the current database
    $tablestack = array();
    for ($j = 0; $j < $num_tables; $j++) {
        $table     = PMA_mysql_tablename($tables, $j);
        $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                   ? htmlentities($tooltip_name[$table])
                   : '';
        $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                   ? htmlentities($tooltip[$table])
                   : '';
        $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

        if ($cfg['LeftFrameLight']) {
        echo "\n";
        ?>
        <nobr><a target="phpmain<?php echo $hash; ?>" href="sql.php?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>" title="<?php echo $strBrowse . ': ' . $url_title; ?>">
                  <img src="images/button_smallbrowse.png" width="10" height="10" border="0" alt="<?php echo $strBrowse . ': ' . $url_title; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
              <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain<?php echo $hash; ?>" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
                  <?php echo ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)); ?></a></nobr><br />
        <?php
        } else {
            $list_item = '<a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '" title="' . $strBrowse . ': ' . $url_title . '">';
            $list_item .= '<img src="images/button_smallbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" /></a>';
            $list_item .= '<bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
            $list_item .= '<a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">';
            $list_item .= ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></nobr><br />';

            // garvin: Check whether to display nested sets
            if (!empty($cfg['LeftFrameTableSeparator'])) {
                $_table = explode($cfg['LeftFrameTableSeparator'],  str_replace('\'', '\\\'',$table));
                if (is_array($_table)) {
                    foreach($_table AS $key => $val) {
                        if ($val == '') {
                            $_table[$key] = '__protected__';
                        }
                    }

                    unset($_table[count($_table)-1]);
                    $_table = PMA_reduceNest($_table);

                    $eval_string = '$tablestack[\'' . implode('\'][\'', $_table) . '\'][\'pma_name\'][] = \'' . str_replace('\'', '\\\'', $table) . '\';';
                    $eval_string .= '$tablestack[\'' . implode('\'][\'', $_table) . '\'][\'pma_list_item\'][] = \'' . str_replace('\'', '\\\'', $list_item) . '\';';
                    eval($eval_string);
                } else {
                    $tablestack['']['pma_name'][] = $table;
                    $tablestack['']['pma_list_item'][] = $list_item;
                }
            } else {
                $tablestack['']['pma_name'][] = $table;
                $tablestack['']['pma_list_item'][] = $list_item;
            }
        }
    } // end for $j (tables list)

    if (!$cfg['LeftFrameLight']) {
        PMA_nestedSet('1', $tablestack);
        ?>
    </div>
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
    } else {
        echo '    </div>';
    }

    echo "\n";
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
