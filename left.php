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

function PMA_multimerge(&$stack, &$table) {
global $list_item, $table_item;

    $key = array_shift($table);

    if (count($table) > 0) {
        if (!isset($stack[$key])) {
            $stack[$key] = '';
        }
        PMA_multimerge($stack[$key], $table);
    } else {
        $stack['pma_name'][]      = $table_item;
        $stack['pma_list_item'][] = $list_item;
    }
}

/* This will take a 1-dimensional array, and shift as many elemnts off
 * the end, until the allowed maximum level is reached */
function PMA_reduceNest(&$_table) {
    if ($GLOBALS['cfg']['LeftFrameTableLevel'] > 0) {
        $elements = count($_table);
        for ($ti = $elements; $ti > $GLOBALS['cfg']['LeftFrameTableLevel']; $ti--) {
            unset($_table[$ti]);
        }
    }
}

function PMA_nestedSetHeaderParent($baseid, $key, $keyhistory, $indent, $indent_level, $val, $childout = true) {
    $name = $key;
    //$id = preg_replace('@[^a-z0-9]*@i', '', $baseid . $keyhistory . $key) . $indent;
    $id = base64_encode($baseid . $keyhistory . $key) . $indent;

    $groupkey = $keyhistory . ($key != $keyhistory ? $GLOBALS['cfg']['LeftFrameTableSeparator'] . $key : '');

    $on_mouse = (($GLOBALS['cfg']['LeftPointerEnable'] == FALSE) ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $id . '\', \'' . $GLOBALS['cfg']['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $id . '\', \'' . $GLOBALS['cfg']['LeftBgColor'] . '\')}"');

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
    echo str_repeat(' ', $indent * 5) . '<div id="el' . $id . 'Parent" class="parent"' . $on_mouse . '>' . "\n";
    echo str_repeat(' ', $indent * 6) . '<div class="nowrap"><img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" border="0" width="' . (($indent - 1) * $indent_level) . '" height="9" alt="" /><a class="item" href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $GLOBALS['common_url_query'] . '&amp;tbl_group=' . htmlspecialchars($groupkey) . '" onclick="if (capable) {expandBase(\'el' . $id . '\', true); return false} else {return true}">';
    echo '<img name="imEx" id="el' . $id . 'Img" src="' . $GLOBALS['pmaThemeImage'] . 'b_plus.png" border="0" width="9" height="9" alt="+" /></a>' . "\n";
    echo str_repeat(' ', $indent * 6) . '<a class="item" href="' . $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $GLOBALS['common_url_query'] . '&amp;tbl_group=' . htmlspecialchars($groupkey) . '" title="' . htmlspecialchars($name) . '" onclick="if (capable) {expandBase(\'el' . $id . '\', false)}"><span class="heada">' . htmlspecialchars($name) . '<bdo dir="' . $GLOBALS['text_dir'] . '">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(' . $counter . ')</span></a></div>' . "\n";
    echo str_repeat(' ', $indent * 5) . '</div><!-- class="PMA_nestedSetHeaderParent" -->' . "\n";
    echo "\n";

    if ($childout) {
        echo str_repeat(' ', $indent * 5) . '<div id="el' . $id . 'Child" class="child nowrap" ' . $on_mouse . '>' . "\n";
    }
}

function PMA_nestedSetHeader($baseid, $tablestack, $keyhistory, $indent, $indent_level, $headerOut, $firstGroup = false, $firstGroupClose = true) {
    if ($firstGroup) {
        PMA_nestedSetHeaderParent($baseid, $firstGroup, $keyhistory, $indent, $indent_level, $tablestack);
        $indent++;
    }

    foreach ($tablestack AS $key => $val) {
        if ($key != 'pma_name' && $key != 'pma_list_item') {
            if ($headerOut) {
                PMA_nestedSetHeaderParent($baseid, $key, $keyhistory, $indent, $indent_level, $val);
            }

            if (isset($val['pma_name']) && isset($val['pma_list_item']) && count($val) == 2) {
                PMA_nestedSet($baseid, $val, $key, $keyhistory . ($keyhistory != '' ? $GLOBALS['cfg']['LeftFrameTableSeparator'] : '') . $key, false, ($indent + 1));
            } else {
                PMA_nestedSet($baseid, $val, $key, $keyhistory . ($keyhistory != '' ? $GLOBALS['cfg']['LeftFrameTableSeparator'] : '') . $key, true, ($indent + 1));
            }

            if ($headerOut) {
                echo str_repeat(' ', $indent * 5) . '</div><!-- class="PMA_nestedSetHeader" -->' . "\n";
            }
        }
    }

    if ($firstGroup && $firstGroupClose) {
        echo str_repeat(' ', $indent * 4) . '</div><!-- class="PMA_nestedSetHeader2" -->' . "\n";
    } elseif ($firstGroup) {
        echo str_repeat(' ', $indent * 4) . '<!-- spacer="div omitted" class="PMA_nestedSetHeader2" -->' . "\n";
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

        $on_mouse = (($GLOBALS['cfg']['LeftPointerEnable'] == FALSE) ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $keyhistory . $key . '\', \'' . $GLOBALS['cfg']['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $keyhistory . $key . '\', \'' . $GLOBALS['cfg']['LeftBgColor'] . '\')}"');

        $loops = 0;
        foreach ($tablestack['pma_name'] AS $tkey => $tval) {

            echo str_repeat(' ', $indent * 5) . '<img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' .'" border="0" width="' . (($indent+$extra_indent) * $indent_level) . '" height="9" alt="" />';
            $items = explode("\n", $tablestack['pma_list_item'][$tkey]);
            foreach ($items AS $ikey => $ival) {
                echo "\n";
                echo str_repeat(' ', ($indent * 5)) . $ival;
            }
            echo "\n";

            $loops++;
        }

        if ($divClose) {
            echo str_repeat(' ', $indent * 5) . '</div><!-- space="putting omitted div" class="PMA_nestedSet2" -->';
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
echo "<?xml version=\"1.0\" encoding=\"" . $GLOBALS['charset'] . "\"?".">"; // remove vertical scroll bar bug in ie
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
    function goTo(targeturl, targetframe) {
        if (!targetframe) {
            targetframe = self;
        }

        if (targetframe) {
<?php if (PMA_USR_BROWSER_AGENT != 'SAFARI') { ?>
            targetframe.location.replace(targeturl);
<?php } else { ?>
            targetframe.location.href = targeturl;
<?php } ?>
        }

        return true;
    }

<?php
if (isset($lightm_db) && !empty($lightm_db)) {
?>
    goTo('./<?php echo $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db, '', '&');?>', window.parent.frames['phpmain<?php echo $hash; ?>']);
<?php
} elseif (isset($lightm_db)) {
?>
    goTo('./main.php?<?php echo PMA_generate_common_url('', '', '&');?>', window.parent.frames['phpmain<?php echo $hash; ?>']);
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
    var imgUrlPlus  = "<?php echo $GLOBALS['pmaThemeImage'] . 'b_plus.png'; ?>";
    var imgUrlMinus = "<?php echo $GLOBALS['pmaThemeImage'] . 'b_minus.png'; ?>";
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

<body bgcolor="<?php echo $cfg['LeftBgColor']; ?>" id="body_leftFrame">

<?php
if ($cfg['LeftDisplayLogo'] && !$cfg['QueryFrame']) {
?>
<!-- phpMyAdmin logo -->
<?php
    if (@file_exists($pmaThemeImage . 'logo_left.png')) {
?>
    <div align="center">
        <a href="http://www.phpmyadmin.net" target="_blank"><img src="<?php echo '' . $pmaThemeImage . 'logo_left.png'; ?>" alt="phpMyAdmin" vspace="3" border="0" /></a>
    </div>
<?php
    } else {
        echo '<div align="center"><a href="http://www.phpmyadmin.net" target="_blank">';
        echo '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png' . '" alt="phpMyAdmin" border="0" />';
        echo '</a></div>' . "\n";
    }
    echo '<hr />';
} // end of display logo
echo "\n";

if (!$cfg['QueryFrame']) {
    echo "\n";
?>
<!-- Link to the welcome page -->
    <div id="el1Parent" class="parent nowrap" align="center">
    <?php
    if ($cfg['MainPageIconic']) {
        $str_spacer_links='';
    } else{
        $str_spacer_links=' - ';
    }
    echo '<a class="item" href="main.php?' . PMA_generate_common_url() . '" target="phpmain' . $hash . '">'
       . ($cfg['MainPageIconic']
            ? '<img src="' . $pmaThemeImage . 'b_home.png" width="16" height="16" border="0" hspace="2" alt="' . $strHome . '" title="' . $strHome . '"'
                .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
            : '<b>' . $strHome . '</b>')
       . '</a>';
    // if we have chosen server show logout for advanced authentication
    if ($server != 0 && $cfg['Server']['auth_type'] != 'config') {
        echo $str_spacer_links;
        echo '<a class="item" href="index.php?' . PMA_generate_common_url() . '&amp;old_usr=' . urlencode($PHP_AUTH_USER) . '" target="_parent">'
           . ($cfg['MainPageIconic']
                ? '<img src="' . $pmaThemeImage . 's_loggoff.png" width="16" height="16" border="0" hspace="2" alt="' . $strLogout . '" title="' . $strLogout . '"'
                    .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
                : '<b>' . $strLogout . '</b>')
           . '</a>';
    } // end if
    if ($cfg['MainPageIconic']) {
        echo '<img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png' . '" width="2" height="1" border="0" />'
           . '<a href="Documentation.html" target="documentation" class="item">'
           . '<img src="' . $pmaThemeImage . 'b_docs.png" border="0" hspace="1" width="16" height="16" alt="' . $strPmaDocumentation . '" title="' . $strPmaDocumentation . '"'
           .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
           . '</a>';
       echo ''
           . '<a href="' . $cfg['MySQLManualBase'] . '" target="documentation" class="item">'
           . '<img src="' . $pmaThemeImage . 'b_sqlhelp.png" border="0" hspace="1" width="16" height="16" alt="MySQL - ' . $strDocu . '" title="MySQL - ' . $strDocu . '"'
           .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
           . '</a>';
    }
?>
    </div>
    <hr />
<?php
    if ($cfg['LeftDisplayServers']) {
        $show_server_left = TRUE;
        include('./libraries/select_server.lib.php');
    }
} // end !$cfg['QueryFrame']

?>
<!-- Databases and tables list -->
<div id="left_tableList">
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
        if (!$cfg['QueryFrame']) {
        ?>
    <script type="text/javascript" language="javascript">
    <!--
        document.writeln('<form method="post" action="left.php" name="left" target="nav" style="margin: 0px; padding: 0px;">');
    //-->
    </script>
    <noscript>
        <form method="post" action="index.php" name="left" target="_parent" style="margin: 0px; padding: 0px;">
    </noscript>
    <?php
            echo PMA_generate_common_hidden_inputs();
            echo '        <input type="hidden" name="hash" value="' . $hash . '" />' . "\n";
            echo '        <span class="heada"><b>' . $strDatabase . ':</b></span><br />';
            echo '        <select name="lightm_db" onchange="this.form.submit()">' . "\n";
            echo '            <option value="">(' . $strDatabases . ') ...</option>' . "\n";
        } // end !$cfg['QueryFrame']

        $table_list = '';
        $table_list_header = '';
        $db_name    = '';
    } // end FrameLight

    $selected_db = 0;

    // natural order for db list
    if ($cfg['NaturalOrder'] && $num_dbs > 0) {
        $dblist_temp = $dblist;
        natsort($dblist_temp);
        $i = 0;
        foreach ($dblist_temp as $each) {
            $dblist[$i] = $each;
            $i++;
        }
    }

    // Gets the tables list per database
    for ($i = 0; $i < $num_dbs; $i++) {
        $db = $dblist[$i];
        $j  = $i + 2;
        if (!empty($db_start) && $db == $db_start) {
            $selected_db = $j;
        }
        $tables              = PMA_DBI_try_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);
        $num_tables          = ($tables) ? @PMA_DBI_num_rows($tables) : 0;
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
            $result  = PMA_DBI_try_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db));
            while ($tmp = PMA_DBI_fetch_assoc($result)) {
                $tooltip_name[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '');
                $tmp['Comment'] = ($cfg['ShowTooltipAliasTB'] && $cfg['ShowTooltipAliasTB'] !== 'nested' ? $tmp['Name'] : $tmp['Comment']);

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
            $on_mouse = (($cfg['LeftPointerEnable'] == FALSE) ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el' . $j . '\', \'' . $cfg['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el' . $j . '\', \'' . $cfg['LeftBgColor'] . '\')}"');

            echo "\n";
            echo '    <div id="el' . $j . 'Parent" class="parent nowrap"' . $on_mouse . '>';

            if (!empty($num_tables)) {
                echo "\n";
            ?>
            <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" onclick="if (capable) {expandBase('el<?php echo $j; ?>', true); return false} else {return true}">
            <img name="imEx" id="el<?php echo $j; ?>Img" src="<?php echo $pmaThemeImage; ?>b_plus.png" border="0" width="9" height="9" alt="+" /></a>
            <?php
            } else {
                echo "\n";
            ?>
            <img name="imEx" src="<?php echo $pmaThemeImage; ?>b_minus.png" border="0" width="9" height="9" alt="-" />
            <?php
            }
            echo "\n";
            ?>
            <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" title="<?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db) : htmlspecialchars($db_tooltip)); ?>" onclick="if (capable) {expandBase('el<?php echo $j; ?>', false)}">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? '<i>' . htmlspecialchars($db_tooltip) . '</i>' : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a>
        </div>

        <div id="el<?php echo $j;?>Child" class="child nowrap" style="margin-bottom: 5px"<?php echo $on_mouse; ?>>
        <?php
            // Displays the list of tables from the current database
            $tablestack  = array();
            $table_array = array();
            while (list($table) = PMA_DBI_fetch_row($tables)) {
                $table_item = (!empty($tooltip_name) && isset($tooltip_name[$table]) && !empty($tooltip_name[$table]) && $cfg['ShowTooltipAliasTB'] && strtolower($cfg['ShowTooltipAliasTB']) !== 'nested'
                            ? htmlspecialchars($tooltip_name[$table])
                            : htmlspecialchars($table));
                $table_array[$table] = $table_item;
            }

            if ($cfg['NaturalOrder']) {
                natsort($table_array);
            }

            $book_sql_cache = PMA_queryDBBookmarks($db, $cfg['Bookmark'], $table_array);
            foreach ($table_array as $table => $table_sortkey) {
                $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                           ? htmlspecialchars($tooltip_name[$table])
                           : '';
                $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                           ? htmlspecialchars($tooltip[$table])
                           : '';
                $table_item = ($alias != '' && $cfg['ShowTooltipAliasTB'] && strtolower($cfg['ShowTooltipAliasTB']) !== 'nested'
                            ? $alias
                            : htmlspecialchars($table));
                $tablename = ($alias != '' && $cfg['ShowTooltipAliasTB']
                           ? $alias
                           : htmlspecialchars($table));

                $book_sql_query = (isset($book_sql_cache[$table]) ? $book_sql_cache[$table] : FALSE);

                $list_item = '<a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '" title="' . $strBrowse . ': ' . $url_title . '">';
                $list_item .= '<img src="' . $pmaThemeImage . 'b_sbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" /></a>';
                $list_item .= '<bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
                $list_item .= '<a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">';
                $list_item .= $table_item . '</a><br />' . "\n";

                // garvin: Check whether to display nested sets
                if (!empty($cfg['LeftFrameTableSeparator'])) {
                    $_table = explode($cfg['LeftFrameTableSeparator'],  str_replace('\'', '\\\'', $tablename));
                    if (is_array($_table)) {
                        foreach ($_table AS $key => $val) {
                            if ($val == '') {
                                $_table[$key] = '__protected__';
                            }
                        }
                        PMA_reduceNest($_table);

                        if (count($_table) == 1) {
                            array_unshift($_table, '');
                        }
                        PMA_multimerge($tablestack, $_table);
                    } else {
                        $tablestack['']['pma_name'][] = $table_item;
                        $tablestack['']['pma_list_item'][] = $list_item;
                    }
                } else {
                    $tablestack['']['pma_name'][] = $table_item;
                    $tablestack['']['pma_list_item'][] = $list_item;
                }
            } // end while (tables list)

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
                $table_title = array();
                $table_array = array();
                // Gets the list of tables from the current database
                $book_sql_cache = PMA_queryDBBookmarks($db, $cfg['Bookmark'], $table_array);
                while (list($table) = PMA_DBI_fetch_row($tables)) {
                    $table_array[$table] = '';
                    $url_title  = (!empty($tooltip) && isset($tooltip[$table]))
                                ? htmlspecialchars($tooltip[$table])
                                : '';
                    $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                               ? htmlspecialchars($tooltip_name[$table])
                               : '';

                    $book_sql_query = (isset($book_sql_cache[$table]) ? $book_sql_cache[$table] : FALSE);

                    // natural order or not, use an array for the table list

                    $table_array[$table] .= '    <div class="nowrap"><a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '">' . "\n";
                    $table_array[$table] .= '              <img src="' . $pmaThemeImage . 'b_sbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" title="' . $strBrowse . ': ' . $url_title . '" /></a><bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";

                    if (PMA_USR_BROWSER_AGENT == 'IE') {
                        $table_array[$table] .= '          <span class="tblItem"><a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></span></div>' . "\n";
                    } else {
                        $table_array[$table] .= '          <a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">' . ($alias != '' && $cfg['ShowTooltipAliasTB'] ? $alias : htmlspecialchars($table)) . '</a></div>' . "\n";
                    }

                    $table_title[$table] = htmlspecialchars($table);

                } // end while (tables list)

                if (count($table_title) > 0) {
                    if ($cfg['NaturalOrder'] && $num_tables > 0) {
                        natsort($table_title);
                    }

                    foreach ($table_title as $each_key => $each_val) {
                        $table_list .= ' ' . $table_array[$each_key];
                    }
                } else {
                    $table_list = '    <br /><br />' . "\n"
                                . '    <div>' . $strNoTablesFound . '</div>' . "\n";
                }
                $selected = ' selected="selected"';

                $table_list_header .= '    <a class="item" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabDatabase'] . '?' . $common_url_query . '">' . "\n";
                $table_list_header .= '        <span class="heada"><b>' . ($db_tooltip != '' && $cfg['ShowTooltipAliasTB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . '</b><bdo dir="' . $text_dir . '">&nbsp;&nbsp;</bdo></span></a><br />' . "\n\n";
            } else {
                $selected = '';
            } // end if... else...
            if (!$cfg['QueryFrame']) {
                if (!empty($num_tables)) {
                    echo '            <option value="' . htmlspecialchars($db) . '"' . $selected . '>'
                       . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (' . $num_tables . ')</option>' . "\n";
                } else {
                    echo '            <option value="' . htmlspecialchars($db) . '"' . $selected . '>'
                       . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (-)</option>' . "\n";
                }
            } // end !$cfg['QueryFrame']

        } // end if (light mode)

    } // end for $i (db list)

    // Light mode -> end of the select combo for databases and table list for
    // the current database
    if ($cfg['LeftFrameLight']) {
        if (!$cfg['QueryFrame']) {
            echo '        </select>' . "\n";
            echo '        <noscript><input type="submit" name="Go" value="' . $strGo . '" /></noscript>' . "\n";
            echo '    </form>' . "\n";
        }
        if (!$table_list) {
            $table_list = '    <div align="center"><b>' . $strSelectADb . '</b></div>' . "\n";
        }

        // Displays the current database name and the list of tables it
        // contains
        if (!$cfg['QueryFrame']) {
           echo '<hr />';
        }
        echo $table_list_header;
        echo $table_list;
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
    $tables              = PMA_DBI_try_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', NULL, PMA_DBI_QUERY_STORE);
    $num_tables          = ($tables) ? @PMA_DBI_num_rows($tables) : 0;
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
        $result  = PMA_DBI_try_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db), NULL, PMA_DBI_QUERY_STORE);
        while ($tmp = PMA_DBI_fetch_assoc($result)) {
            $tooltip_name[$tmp['Name']] = (!empty($tmp['Comment']) ? $tmp['Comment'] . ' ' : '');
            $tmp['Comment'] = ($cfg['ShowTooltipAliasTB'] && $cfg['ShowTooltipAliasTB'] !== 'nested' ? $tmp['Name'] : $tmp['Comment']);

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
            $on_mouse = (($cfg['LeftPointerEnable'] == FALSE) ? '' : ' onmouseover="if (isDOM || isIE4) {hilightBase(\'el2\', \'' . $cfg['LeftPointerColor'] . '\')}" onmouseout="if (isDOM || isIE4) {hilightBase(\'el2\', \'' . $cfg['LeftBgColor'] . '\')}"');

            echo "\n";
            echo '    <div id="el2Parent" class="parent"' . $on_mouse . '>';

            if (!empty($num_tables)) {
                echo "\n";
                ?>
        <div class="nowrap"><a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" onclick="if (capable) {expandBase('el2', true); return false} else {return true}">
            <img name="imEx" id="el2Img" src="<?php echo $pmaThemeImage; ?>b_plus.png" border="0" width="9" height="9" alt="+" /></a>
                <?php
            } else {
                echo "\n";
                ?>
        <div class="nowrap"><img name="imEx" src="<?php echo $pmaThemeImage; ?>b_minus.png" border="0" width="9" height="9" alt="-" />
                <?php
            }
            echo "\n";
            ?>
        <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>" title="<?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db) : htmlspecialchars($db_tooltip)); ?>" onclick="if (capable) {expandBase('el2', false)}">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? '<i>' . htmlspecialchars($db_tooltip) . '</i>' : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a></div>
    </div>

    <div id="el2Child" class="child" style="margin-bottom: 5px"<?php echo $on_mouse; ?>>
    <?php
    } else {
        echo "\n";
        ?>
    <div id="el2Parent" class="parent nowrap">
        <a class="item" href="<?php echo $cfg['DefaultTabDatabase']; ?>?<?php echo $common_url_query; ?>">
            <span class="heada"><?php echo ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)); ?><bdo dir="<?php echo($text_dir); ?>">&nbsp;&nbsp;</bdo></span><span class="headaCnt">(<?php echo $num_tables_disp; ?>)</span></a>
    </div>
    <div id="el2Child" class="child nowrap" style="margin-bottom: 5px">
        <?php
    }

    // Displays the list of tables from the current database
    $tablestack = array();
    $table_array = array();
    while (list($table) = PMA_DBI_fetch_row($tables)) {
        $table_item = (!empty($tooltip_name) && isset($tooltip_name[$table]) && !empty($tooltip_name[$table]) && $cfg['ShowTooltipAliasTB'] && strtolower($cfg['ShowTooltipAliasTB']) !== 'nested'
                    ? htmlspecialchars($tooltip_name[$table])
                    : htmlspecialchars($table));
        $table_array[$table] = $table_item;
    }

    if ($cfg['NaturalOrder']) {
        natcasesort($table_array);
    }

    $book_sql_cache = PMA_queryDBBookmarks($db, $cfg['Bookmark'], $table_array);
    foreach ($table_array as $table => $table_sortkey) {
        $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                   ? htmlspecialchars($tooltip_name[$table])
                   : '';
        $url_title = (!empty($tooltip) && isset($tooltip[$table]))
                   ? htmlspecialchars($tooltip[$table])
                   : '';
        $table_item = ($alias != '' && $cfg['ShowTooltipAliasTB'] && strtolower($cfg['ShowTooltipAliasTB']) !== 'nested'
                    ? $alias
                    : htmlspecialchars($table));
        $tablename = ($alias != '' && $cfg['ShowTooltipAliasTB']
                   ? $alias
                   : htmlspecialchars($table));

        $book_sql_query = (isset($book_sql_cache[$table]) ? $book_sql_cache[$table] : FALSE);

        if ($cfg['LeftFrameLight']) {
        echo "\n";
        ?>
            <a target="phpmain<?php echo $hash; ?>" href="sql.php?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>&amp;sql_query=<?php echo (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))); ?>&amp;pos=0&amp;goto=<?php echo $cfg['DefaultTabTable']; ?>" title="<?php echo $strBrowse . ': ' . $url_title; ?>">
                  <img src="<?php echo $pmaThemeImage . 'b_sbrowse.png'; ?>" width="10" height="10" border="0" alt="<?php echo $strBrowse . ': ' . $url_title; ?>" /></a><bdo dir="<?php echo $text_dir; ?>">&nbsp;</bdo>
              <a class="tblItem" id="tbl_<?php echo md5($table); ?>" title="<?php echo $url_title; ?>" target="phpmain<?php echo $hash; ?>" href="<?php echo $cfg['DefaultTabTable']; ?>?<?php echo $common_url_query; ?>&amp;table=<?php echo urlencode($table); ?>">
                  <?php echo $table_item; ?></a><br />
        <?php
        } else {
            $list_item = '<a target="phpmain' . $hash . '" href="sql.php?' . $common_url_query . '&amp;table=' . urlencode($table) . '&amp;sql_query=' . (isset($book_sql_query) && $book_sql_query != FALSE ? urlencode($book_sql_query) : urlencode('SELECT * FROM ' . PMA_backquote($table))) . '&amp;pos=0&amp;goto=' . $cfg['DefaultTabTable'] . '" title="' . $strBrowse . ': ' . $url_title . '">';
            $list_item .= '<img src="' . $pmaThemeImage . 'b_sbrowse.png" width="10" height="10" border="0" alt="' . $strBrowse . ': ' . $url_title . '" /></a>';
            $list_item .= '<bdo dir="' . $text_dir . '">&nbsp;</bdo>' . "\n";
            $list_item .= '<a class="tblItem" id="tbl_' . md5($table) . '" title="' . $url_title . '" target="phpmain' . $hash . '" href="' . $cfg['DefaultTabTable'] . '?' . $common_url_query . '&amp;table=' . urlencode($table) . '">';
            $list_item .= $table_item . '</a><br />';

            // garvin: Check whether to display nested sets
            if (!empty($cfg['LeftFrameTableSeparator'])) {
                $_table = explode($cfg['LeftFrameTableSeparator'],  str_replace('\'', '\\\'', $tablename));
                if (is_array($_table)) {
                    foreach ($_table AS $key => $val) {
                        if ($val == '') {
                            $_table[$key] = '__protected__';
                        }
                    }
                    PMA_reduceNest($_table);

                    if (count($_table) == 1) {
                        array_unshift($_table, '');
                    }
                    PMA_multimerge($tablestack, $_table);
                } else {
                    $tablestack['']['pma_name'][] = $table_item;
                    $tablestack['']['pma_list_item'][] = $list_item;
                }
            } else {
                $tablestack['']['pma_name'][] = $table_item;
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

// 2004-08-05 added by Michael Keck
//            reload queryframe if it exists and we delete a database
//            or select a database from the db_list.
$my_lightm_db = '';
if (isset($lightm_db) && $lightm_db!='') {
    $my_lightm_db = $lightm_db;
}

if ($cfg['LeftFrameLight'] && $cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
    if (!isset($table_array) || count($table_array)==0) {
        $my_url_query = PMA_generate_common_url('', '', '&');
?>
<script language="JavaScript" type="text/javascript">
<!--
function check_queryframe_reload() {
    if (typeof(window.parent.frames['queryframe'])!='undefined' && typeof(window.parent.frames['queryframe'].document.forms['left'])!='undefined') {
        if (window.parent.frames['queryframe'].document.forms['left'].elements['lightm_db'].value!='<?php echo $my_lightm_db; ?>') {
            window.parent.frames['queryframe'].location.replace('<?php echo 'queryframe.php?' . $my_url_query . '&hash=' . $hash; ?>');
        }
    }
}
// This is a workaround for the problem in Safari 1.2.3 where the
// top left frame does not load.
// If we call this right away it usually prevents the top-left frame from
// loading in Safari, so call it delayed. -Ryan Schmidt 2004-08-15
setTimeout('check_queryframe_reload()', 1000);

//-->
</script>
<?php
    }
}
?>
</div>
</body>
</html>

<?php
/**
 * Close MySql connections
 */
if (isset($dbh) && $dbh) {
    @PMA_DBI_close($dbh);
}
if (isset($userlink) && $userlink) {
    @PMA_DBI_close($userlink);
}


/**
 * Sends bufferized data
 */
if (isset($cfg['OBGzip']) && $cfg['OBGzip']
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
