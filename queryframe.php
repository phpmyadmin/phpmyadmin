<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
require_once('./libraries/grab_globals.lib.php');
if (!empty($db)) {
    $db_start = $db;
}


/**
 * Gets a core script and starts output buffering work
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');
require_once('./libraries/ob.lib.php');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
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

/**
 * Relations
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();
echo "<?xml version=\"1.0\" encoding=\"" . $GLOBALS['charset'] . "\"?".">"; // remove vertical scroll bar bug in ie
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<title>phpMyAdmin</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> />
<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?lang=<?php echo $lang; ?>&amp;js_frame=left&amp;num_dbs=0" />
<?php
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
?>
<script type="text/javascript" language="javascript">
<!--
var querywindow = '';

function open_querywindow(url) {

    if (!querywindow.closed && querywindow.location) {
        querywindow.focus();
    } else {
        querywindow=window.open(url + '&db=' + document.queryframeform.db.value + '&table=' + document.queryframeform.table.value, '','toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=yes,resizable=yes,width=<?php echo $cfg['QueryWindowWidth']; ?>,height=<?php echo $cfg['QueryWindowHeight']; ?>');
    }

    if (!querywindow.opener) {
        querywindow.opener = self;
    }

    if (window.focus) {
        querywindow.focus();
    }

    return false;
}

/**
  * function resizeRowsLeft()
  * added 2004-07-20 by Michael Keck <mail@michaelkeck.de>
  *                  - this function checks the complete frameset of
  *                    index.php (parent.frames)
  *                  - gets the offsetHeight of qfcontainer
  *                  - sets a new frameset.rows - definition for the
  *                    frameset 'leftFrameset' in 'index.php' dynamic.
  * this script was tested on
  *   IE 6, Opera 7.53, Netsacpe 7.1 and Firefox 0.9
  *   and should work on all other DOM-Browsers and old IE-Browsers.
  *   It will never work on Netscape smaller Version 6 and IE smaller Version 4.
  * Please give me feedback if any browser doesn't work with this script
  *   mailto:mail@michaelkeck.de?subject=resizeFrames - Browser: [the browser]
**/

function resizeRowsLeft() {
    if (document.getElementById('qfcontainer')) { // dom browsers
        // get the height of the div-element 'qfcontainer'
        // we must add 10 (px) for framespacing
        newHeight = document.getElementById('qfcontainer').offsetHeight+10;
        // check if the frameset exists
        // please see index.php and check the frameset-definitions
        if (parent.document.getElementById('mainFrameset') && parent.document.getElementById('leftFrameset')) {
            parent.document.getElementById('leftFrameset').rows=newHeight+',*';
        }
    } else {
        if (document.all) { // older ie-browsers
            // get the height of the div-element 'qfcontainer'
            // we must add 10 (px) for framespacing
            newHeight=document.all('qfcontainer').offsetHeight+10;
            // check if the frameset exists
            // please see index.php and check the frameset-definitions
            if (parent.leftFrameset) {
                parent.leftFrameset.rows=newHeight+',*';
            }
        }
    }
}

//-->
</script>
<?php
    // setup the onload handler for resizing frames
    $js_frame_onload=' onload="resizeRowsLeft();"';
}
if ($cfg['QueryFrame']) {
?>
<script type="text/javascript" language="javascript">
<!--
// added 2004-09-16 by Michael Keck (mkkeck)
//                  bug: #1027321
//                       drop-down databases list keep focus on database change
// modified 2004-11-06: bug #1046434 (Light mode does not work)
var focus_removed = false;
function remove_focus_select() {
    focus_removed = false;
    set_focus_to_nav();
}
function set_focus_to_nav() {
    if (typeof(parent.frames.nav)!='undefined' && focus_removed!=true) {
        parent.frames.nav.focus();
        focus_removed=true;
    } else {
        focus_removed=false;
        setTimeout("set_focus_to_nav();",500);
    }
}
//-->
</script>
<?php
}
?>
</head>

<body id="body_queryFrame" bgcolor="<?php echo $cfg['LeftBgColor']; ?>"<?php echo ((isset($js_frame_onload) && $js_frame_onload!='') ? $js_frame_onload : ''); ?>>
    <div id="qfcontainer">
<?php
if ($cfg['LeftDisplayLogo']) {
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
        echo '    '
           . '<div align="center"><a href="http://www.phpmyadmin.net" target="_blank">'
           . '<img src="' . $GLOBALS['pmaThemeImage'] . 'pma_logo2.png'  .'" alt="phpMyAdmin" border="0" />'
           . '</a></div>' . "\n";
    }
    echo '<hr id="hr_first" />';
} // end of display logo

if ($cfg['MainPageIconic']) {
    $str_spacer_links='';
} else{
    $str_spacer_links=' - ';
}
    ?>
<!-- Link to the welcome page -->
    <div align="center">
<?php
    echo '<a class="item" href="main.php?' . PMA_generate_common_url() . '" target="phpmain' . $hash . '">'
       . ($cfg['MainPageIconic']
            ? '<img src="' . $pmaThemeImage . 'b_home.png" width="16" height="16" border="0" hspace="2" alt="' . $strHome . '" title="' . $strHome . '"'
                    .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
            : '<b>' . $strHome . '</b>')
        . '</a>';
    // if we have chosen server
    if ($server != 0) {
        // Logout for advanced authentication
        if ($cfg['Server']['auth_type'] != 'config') {
            echo $str_spacer_links;
            echo '<a class="item" href="index.php?' . PMA_generate_common_url() . '&amp;old_usr=' . urlencode($PHP_AUTH_USER) . '" target="_parent">'
               . ($cfg['MainPageIconic']
                    ? '<img src="' . $pmaThemeImage . 's_loggoff.png" width="16" height="16" border="0" hspace="2" alt="' . $strLogout . '" title="' . $strLogout . '"'
                            .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
                    : '<b>' . $strLogout . '</b>')
               . '</a>';
        } // end if

        $anchor = 'querywindow.php?' . PMA_generate_common_url('', '');
        if ($cfg['QueryFrameJS']) {
            $href = $anchor;
            $target = '';
            $onclick = 'onclick="javascript:open_querywindow(this.href); return false;"';
        } else {
            $href = $anchor;
            $target = 'target="phpmain' . $hash . '"';
            $onclick = '';
        }
        if ($cfg['MainPageIconic']) {
            $query_frame_link_text = '<img src="' . $pmaThemeImage . 'b_selboard.png" border="0" hspace="1" width="16" height="16" alt="' . $strQueryFrame . '" title="' . $strQueryFrame . '"'
                                   .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />';
        } else {
            echo ($str_spacer_links != '' ? '<br />' : '');
            $query_frame_link_text = '<b>' . $strQueryFrame . '</b>';
        }
    ?>
    <script type="text/javascript">
    <!--
    document.writeln('<a href="<?php echo $href; ?>" <?php echo $target . ' ' . $onclick; ?> class="item"><?php echo addslashes($query_frame_link_text); ?></a>');
    //-->
    </script>
    <noscript>
        <a href="<?php echo $href; ?>&amp;no_js=true" <?php echo $target . ' ' . $onclick; ?> target="phpmain<?php echo $hash; ?>" class="item"><?php echo $query_frame_link_text; ?></a>
    </noscript>
    <?php
    }

if ($cfg['MainPageIconic']) {
    echo '<img src="' .$GLOBALS['pmaThemeImage'] . 'spacer.png'  .'" width="2" height="1" border="0" alt="" />'
       . '<a href="Documentation.html" target="documentation" class="item">'
       . '<img src="' . $pmaThemeImage . 'b_docs.png" border="0" hspace="1" width="16" height="16" alt="' . $strPmaDocumentation . '" title="' . $strPmaDocumentation . '"'
       . ' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
       . '</a>';
    echo ''
       . '<a href="' . $cfg['MySQLManualBase'] . '" target="documentation" class="item">'
       . '<img src="' . $pmaThemeImage . 'b_sqlhelp.png" border="0" hspace="1" width="16" height="16" alt="MySQL - ' . $strDocu . '" title="MySQL - ' . $strDocu . '"'
       .' onmouseover="this.style.backgroundColor=\'#ffffff\';" onmouseout="this.style.backgroundColor=\'\';" align="middle" />'
       . '</a>';
}

    ?>
    </div>
    <hr id="hr_second" />

    <?php
if ($cfg['LeftDisplayServers']){
    $show_server_left = TRUE;
    include('./libraries/select_server.lib.php');
} // end if LeftDisplayServers
    ?>
<!-- Databases list -->
    <?php
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
    <table border="0" cellpadding="1" cellspacing="0">
        <tr>
            <td align="left" class="heada"><?php echo $strDatabase; ?>:</td>
        </tr>
        <tr>
            <td nowrap="nowrap">
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
        ?>
        <select name="lightm_db" onchange="remove_focus_select();this.form.submit();">
        <?php
        echo '            <option value="">(' . $strDatabases . ') ...</option>' . "\n";
        $table_list = '';
        $table_list_header = '';
        $db_name    = '';
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

            // Builds the databases' names list
            if (!empty($db_start) && $db == $db_start) {
                $table_title = array();
                $table_array = array();
                // Gets the list of tables from the current database
                while (list($table) = PMA_DBI_fetch_row($tables)) {
                    $table_array[$table] = '';
                    $url_title  = (!empty($tooltip) && isset($tooltip[$table]))
                                ? htmlspecialchars($tooltip[$table])
                                : '';
                    $alias = (!empty($tooltip_name) && isset($tooltip_name[$table]))
                                ? htmlspecialchars($tooltip_name[$table])
                                : '';
                    $book_sql_query = PMA_queryBookmarks($db, $cfg['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');
                } // end while (tables list)
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            } // end if... else...

            if (!empty($num_tables)) {
                echo '            '
                   . '<option value="' . htmlspecialchars($db) . '"' . $selected . '>'
                   . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (' . $num_tables . ')</option>' . "\n";
            } else {
                echo '            '
                   . '<option value="' . htmlspecialchars($db) . '"' . $selected . '>'
                   . ($db_tooltip != '' && $cfg['ShowTooltipAliasDB'] ? htmlspecialchars($db_tooltip) : htmlspecialchars($db)) . ' (-)</option>' . "\n";
            } // end if... else...

        } // end for $i (db list)
        ?>
        </select>
        <noscript><input type="submit" name="Go" value="<?php echo $strGo; ?>" /></noscript>
    </form>
            </td>
        </tr>
    </table>
    <hr id="hr_third" />
    <?php
    } // end if LeftFrameLight
} // end if num_db > 1
    ?>
    <form name="queryframeform" action="queryframe.php" method="get" style="margin:0px; padding:0px; display:none;">
        <input type="hidden" name="db" value="" />
        <input type="hidden" name="table" value="" />
        <input type="hidden" name="framename" value="queryframe" />
    </form>
    <form name="hashform" action="queryframe.php" style="margin:0px; padding:0px; display:none;">
        <input type="hidden" name="hash" value="<?php echo $hash; ?>" />
    </form>
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
