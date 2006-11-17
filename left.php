<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * the navigation frame - displays server, db and table selection tree
 */

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.lib.php';

// free the session file, for the other frames to be loaded
session_write_close();

require_once './libraries/ob.lib.php';
if ($GLOBALS['cfg']['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}

require_once './libraries/bookmark.lib.php';
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * Get the list and number of available databases.
 * Skipped if no server selected: in this case no database should be displayed
 * before the user choose among available ones at the welcome screen.
 */
if ($server > 0) {
    // this function is defined in "common.lib.php"
    // it defines $num_dbs and $dblist
    PMA_availableDatabases();

    if ((! isset($db) || ! strlen($db)) && count($dblist) === 1) {
        reset($dblist);
        $db = current($dblist);
    }
} else {
    $num_dbs = 0;
}

$db       = isset($db)    ? $db    : '';
$table    = isset($table) ? $table : '';
$db_start = $db;


// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by libraries/header.inc.php,
// querywindow.php.

require_once './libraries/header_http.inc.php';

/**
 * Displays the frame
 */
// xml declaration moves IE into quirks mode, making much trouble with CSS
/* echo '<?xml version="1.0" encoding="' . $GLOBALS['charset'] . '"?>'; */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $available_languages[$lang][2]; ?>"
    lang="<?php echo $available_languages[$lang][2]; ?>"
    dir="<?php echo $text_dir; ?>">

<head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type"
        content="text/html; charset=<?php echo $charset; ?>" />
    <base target="frame_content" />
    <link rel="stylesheet" type="text/css"
        href="./css/phpmyadmin.css.php?<?php echo PMA_generate_common_url('', ''); ?>&amp;js_frame=left" />
    <script type="text/javascript" language="javascript">
    //<![CDATA[
    function toggle(id, only_open) {
        var el = document.getElementById('subel' + id);
        if (! el) {
            return false;
        }

        var img = document.getElementById('el' + id + 'Img');

        if (el.style.display == 'none' || only_open) {
            el.style.display = '';
            if (img) {
                img.src = '<?php echo $GLOBALS['pmaThemeImage']; ?>b_minus.png';
                img.alt = '-';
            }
        } else {
            el.style.display = 'none';
            if (img) {
                img.src = '<?php echo $GLOBALS['pmaThemeImage']; ?>b_plus.png';
                img.alt = '+';
            }
        }
        return true;
    }
    //]]>
    </script>
    <?php
    /**
     * remove horizontal scroll bar bug in IE by forcing a vertical scroll bar
     */
    ?>
    <!--[if IE 6]>
    <style type="text/css">
    /* <![CDATA[ */
    html {
        overflow-y: scroll;
    }
    /* ]]> */
    </style>
    <![endif]-->
</head>

<body id="body_leftFrame">
<?php
require './libraries/left_header.inc.php';

if ($num_dbs === 0) {
    // no database available, so we break here
    echo '<p>' . $strNoDatabases . '</p></body></html>';

    /**
     * Close MySql connections
     */
    if (isset($controllink) && $controllink) {
        @PMA_DBI_close($controllink);
    }
    if (isset($userlink) && $userlink) {
        @PMA_DBI_close($userlink);
    }

    /**
     * Sends bufferized data
     */
    if ($GLOBALS['cfg']['OBGzip'] && isset($ob_mode) && $ob_mode) {
         PMA_outBufferPost($ob_mode);
    }
    exit();
} elseif ($GLOBALS['cfg']['LeftFrameLight'] && $num_dbs > 1) {
    // more than one database available and LeftFrameLight is true
    // display db selectbox
    //
    // Light mode -> beginning of the select combo for databases
    // Note: When javascript is active, the frameset will be changed from
    // within left.php. With no JS (<noscript>) the whole frameset will
    // be rebuilt with the new target frame.
    ?>

    <div id="databaseList">
    <form method="post" action="index.php" target="_parent" id="left">
    <label for="lightm_db"><?php echo $strDatabase; ?></label>
    <?php
    echo PMA_generate_common_hidden_inputs() . "\n";
    echo PMA_getHtmlSelectDb($db) . "\n";
    echo '<noscript>' . "\n"
        .'<input type="submit" name="Go" value="' . $strGo . '" />' . "\n"
        .'</noscript>' . "\n"
        .'</form>' . "\n"
        .'</div>' . "\n";
}
?>

<div id="left_tableList">
<?php
// Don't display expansible/collapsible database info if:
// 1. $server == 0 (no server selected)
//    This is the case when there are multiple servers and
//    '$GLOBALS['cfg']['ServerDefault'] = 0' is set. In that case, we want the welcome
//    screen to appear with no database info displayed.
// 2. there is only one database available (ie either only one database exists
//    or $GLOBALS['cfg']['Servers']['only_db'] is defined and is not an array)
//    In this case, the database should not be collapsible/expandable

$img_plus = '<img class="icon" id="el%dImg" src="' . $pmaThemeImage . 'b_plus.png"'
    .' width="9" height="9" alt="+" />';
$img_minus = '<img class="icon" id="el%dImg" src="' . $pmaThemeImage . 'b_minus.png"'
    .' width="9" height="9" alt="-" />';

$href_left = '<a onclick="if (toggle(\'%d\')) return false;"'
    .' href="left.php?%s" target="_self">';

$element_counter = 0;

if ($GLOBALS['cfg']['LeftFrameLight'] && isset($db) && strlen($db)) {
    // show selected databasename as link to DefaultTabDatabase-page
    // with table count in ()
    $common_url_query = PMA_generate_common_url($db);

    $db_tooltip = '';
    if ($GLOBALS['cfg']['ShowTooltip']
      && $GLOBALS['cfgRelation']['commwork']) {
        $_db_tooltip = PMA_getComments($db);
        if (is_array($_db_tooltip)) {
            $db_tooltip = implode(' ', $_db_tooltip);
        }
    }

    $disp_name  = $db;
    if ($db_tooltip && $GLOBALS['cfg']['ShowTooltipAliasDB']) {
        $disp_name      = $db_tooltip;
        $disp_name_cut  = $db_tooltip;
        $db_tooltip     = $db;
    }

    ?>
    <p><a class="item"
        href="<?php echo $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $common_url_query; ?>"
        title="<?php echo htmlspecialchars($db_tooltip); ?>" >
    <?php
    if ($GLOBALS['text_dir'] === 'rtl') {
        echo ' <bdo dir="ltr">(' . PMA_getTableCount($db) . ')</bdo> ';
    }
    echo htmlspecialchars($disp_name);
    if ($GLOBALS['text_dir'] === 'ltr') {
        echo ' <bdo dir="ltr">(' . PMA_getTableCount($db) . ')</bdo> ';
    }
    echo '</a></p>';

    $table_list = PMA_getTableList($db);
    if (count($table_list) > 0) {
        PMA_displayTableList($table_list, true, '', $db);
    } else {
        echo $strNoTablesFound;
    }
    unset($table_list);
} elseif ($GLOBALS['cfg']['LeftFrameLight']) {
    echo '<p>' . $GLOBALS['strSelectADb'] . '</p>' . "\n";
} else {
    $common_url_query = PMA_generate_common_url();
    PMA_displayDbList(PMA_getDbList());
}

/**
 * displays collapsable db list
 *
 * @uses    $_REQUEST['dbgroup']
 * @uses    $GLOBALS['cfg']['DefaultTabDatabase']
 * @uses    $GLOBALS['strSelectADb']
 * @uses    strpos()
 * @uses    urlencode()
 * @uses    printf()
 * @uses    htmlspecialchars()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_getTableList()
 * @uses    PMA_displayTableList()
 * @global  $element_counter
 * @global  $img_minus
 * @global  $img_plus
 * @global  $href_left
 * @global  $num_dbs
 * @global  $db_start
 * @global  $common_url_query
 * @param   array   $ext_dblist extended db list
 */
function PMA_displayDbList($ext_dblist) {
    global $element_counter, $img_minus, $img_plus, $href_left, $num_dbs,
        $db_start, $common_url_query;

    // get table list, for all databases
    // doing this in one step takes advantage of a single query with information_schema!
    $tables_full = PMA_DBI_get_tables_full($GLOBALS['dblist']);

    $url_dbgroup = '';
    echo '<ul id="leftdatabaselist">';
    $close_db_group = false;
    foreach ($ext_dblist as $group => $db_group) {
        if ($num_dbs > 1) {
            if ($close_db_group) {
                $url_dbgroup = '';
                echo '</ul>';
                echo '</li>';
                $close_db_group = false;
            }
            if (count($db_group) > 1) {
                $close_db_group = true;
                $url_dbgroup = '&amp;dbgroup=' . urlencode($group);
                $common_url_query = PMA_generate_common_url() . $url_dbgroup;
                $element_counter++;
                echo '<li class="dbgroup">';
                if ((! empty($_REQUEST['dbgroup']) && $_REQUEST['dbgroup'] == $group)
                  || $db_start == $group || strpos($db_start, $group) === 0) {
                    // display + only if this db(group) is not preselected
                    printf($href_left, $element_counter, PMA_generate_common_url());
                    printf($img_minus, $element_counter);
                } else {
                    printf($href_left, $element_counter, $common_url_query);
                    printf($img_plus, $element_counter);
                }
                echo '</a> ' . $group . "\n";
                if ((! empty($_REQUEST['dbgroup']) && $_REQUEST['dbgroup'] == $group)
                  || $db_start == $group || strpos($db_start, $group) === 0) {
                    echo '<ul id="subel' . $element_counter . '">' . "\n";
                } else {
                    echo '<ul id="subel' . $element_counter . '"'
                        .' style="display: none">' . "\n";
                }
            }
        }
        foreach ($db_group as $db) {
            $common_url_query = PMA_generate_common_url($db['name']) . $url_dbgroup;

            $element_counter++;
            // Displays the database name
            echo '<li>' . "\n";

            if ($num_dbs > 1) {
                // only with more than one db we need collapse ...
                if ($db_start != $db['name'] || $db['num_tables'] < 1) {
                    // display + only if this db is not preselected
                    // or table count is 0
                    printf($href_left, $element_counter, $common_url_query);
                    printf($img_plus, $element_counter);
                } else {
                    printf($href_left, $element_counter,
                        PMA_generate_common_url() . $url_dbgroup);
                    printf($img_minus, $element_counter);
                }
                echo '</a>';

                // ... and we need to refresh both frames on db selection
                ?>
                <a class="item"
                    id="<?php echo htmlspecialchars($db['name']); ?>"
                    href="index.php?<?php echo $common_url_query; ?>"
                    target="_parent"
                    title="<?php echo htmlspecialchars($db['comment']); ?>"
                    onclick="
                        if (! toggle('<?php echo $element_counter; ?>', true))
                            window.parent.goTo('./left.php?<?php echo $common_url_query; ?>');
                        window.parent.goTo('./<?php echo $GLOBALS['cfg']['DefaultTabDatabase']
                            . '?' . $common_url_query; ?>', 'main');
                        return false;">
                    <?php
                    if ($GLOBALS['text_dir'] === 'rtl') {
                        echo ' <bdo dir="ltr">(' . $db['num_tables'] . ')</bdo> ';
                    }
                    echo htmlspecialchars($db['disp_name']);
                    if ($GLOBALS['text_dir'] === 'ltr') {
                        echo ' <bdo dir="ltr">(' . $db['num_tables'] . ')</bdo> ';
                    }
                    ?>
                </a>
                <?php
            } else {
                // with only 1 db available we dont need to refresh left frame
                // on db selection, only phpmain
                ?>
                <a href="<?php echo $GLOBALS['cfg']['DefaultTabDatabase']
                    . '?' . $common_url_query; ?>"
                    id="<?php echo htmlspecialchars($db['name']); ?>"
                    title="<?php echo htmlspecialchars($db['comment']); ?>">
                    <?php
                    if ($GLOBALS['text_dir'] === 'rtl') {
                        echo ' <bdo dir="ltr">(' . $db['num_tables'] . ')</bdo> ';
                    }
                    echo htmlspecialchars($db['disp_name']);
                    if ($GLOBALS['text_dir'] === 'ltr') {
                        echo ' <bdo dir="ltr">(' . $db['num_tables'] . ')</bdo> ';
                    }
                    ?>
                </a>
                <?php
            }
            if ($db['num_tables']) {
                if (isset($tables_full[$db['name']])) {
                    $tables = PMA_getTableList($db['name'], $tables_full[$db['name']]);
                } elseif (isset($tables_full[strtolower($db['name'])])) {
                    // on windows with lower_case_table_names = 1
                    // MySQL returns
                    // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
                    // but information_schema.TABLES gives `test`
                    // bug #1436171
                    // sf.net/tracker/?func=detail&aid=1436171&group_id=23067&atid=377408
                    $tables = PMA_getTableList($db['name'], $tables_full[strtolower($db['name'])]);
                } else {
                    $tables = PMA_getTableList($db['name']);
                }
                $child_visible =
                    (bool) ($num_dbs === 1 || $db_start == $db['name']);
                PMA_displayTableList($tables, $child_visible, '', $db['name']);
            } elseif ($GLOBALS['cfg']['LeftFrameLight']) {
                // no tables and LeftFrameLight:
                // display message no tables in selected db
                echo '<p>' . $GLOBALS['strSelectADb'] . '</p>' . "\n";
            }
            echo '</li>' . "\n";
        } // end foreach db
    } // end foreach group

    if ($close_db_group) {
        $url_dbgroup = '';
        echo '</ul>';
        echo '</li>';
        $close_db_group = false;
    }

    echo '</ul>' . "\n";
}

/**
 * display unordered list of tables
 * calls itself recursively if table in given list
 * is a list itself
 *
 * @uses    is_array()
 * @uses    count()
 * @uses    urlencode()
 * @uses    strpos()
 * @uses    printf()
 * @uses    htmlspecialchars()
 * @uses    strlen()
 * @uses    is_array()
 * @uses    PMA_displayTableList()
 * @uses    $_REQUEST['tbl_group']
 * @uses    $GLOBALS['common_url_query']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['cfg']['LeftFrameTableSeparator']
 * @uses    $GLOBALS['cfg']['DefaultTabDatabase']
 * @uses    $GLOBALS['cfg']['DefaultTabTable']
 * @uses    $GLOBALS['strRows']
 * @uses    $GLOBALS['strBrowse']
 * @global  $element_counter
 * @global  $img_minus
 * @global  $img_plus
 * @global  $href_left
 * @param   array   $tables         array of tables/tablegroups
 * @param   boolean $visible        wether the list is visible or not
 * @param   string  $tab_group_full full tab group name
 * @param   string  $table_db       db of this table
 */
function PMA_displayTableList($tables, $visible = false,
    $tab_group_full = '', $table_db = '')
{
    if (! is_array($tables) || count($tables) === 0) {
        return;
    }

    global $element_counter, $img_minus, $img_plus, $href_left;
    $sep = $GLOBALS['cfg']['LeftFrameTableSeparator'];

    if ($visible) {
        echo '<ul id="subel' . $element_counter . '">';
    } else {
        echo '<ul id="subel' . $element_counter . '" style="display: none">';
    }
    foreach ($tables as $group => $table) {
        if (isset($table['is' . $sep . 'group'])) {
            $common_url_query = $GLOBALS['common_url_query']
                . '&amp;tbl_group=' . urlencode($tab_group_full . $group);

            $element_counter++;
            echo '<li>' . "\n";
            if ($visible &&
              ((isset($_REQUEST['tbl_group'])
                && (strpos($_REQUEST['tbl_group'], $group) === 0
                || strpos($_REQUEST['tbl_group'], $sep . $group) !== false))
              ||
              (isset($GLOBALS['table'])
                && strpos($GLOBALS['table'], $group) === 0))) {
                printf($href_left, $element_counter,
                    $GLOBALS['common_url_query'] . '&amp;tbl_group=' . $tab_group_full);
                printf($img_minus, $element_counter);
            } else {
                printf($href_left, $element_counter, $common_url_query);
                printf($img_plus, $element_counter);
            }
            echo '</a>';
            ?>
            <a href="index.php?<?php echo $common_url_query; ?>"
                target="_parent"
                onclick="
                    if (! toggle('<?php echo $element_counter; ?>', true))
                        window.parent.goTo('./left.php?<?php echo $common_url_query; ?>');
                    window.parent.goTo('./<?php echo $GLOBALS['cfg']['DefaultTabDatabase']
                        . '?' . $common_url_query; ?>', 'main');
                    return false;">
                <?php
                if ($GLOBALS['text_dir'] === 'rtl') {
                    echo ' <bdo dir="ltr">(' . $table['tab' . $sep . 'count'] . ')</bdo> ';
                }
                echo htmlspecialchars(substr($group, 0, strlen($group) - strlen($sep)));
                if ($GLOBALS['text_dir'] === 'ltr') {
                    echo ' <bdo dir="ltr">(' . $table['tab' . $sep . 'count'] . ')</bdo> ';
                }
                ?>
            </a>
            <?php

            unset($table['is' . $sep . 'group']);
            unset($table['tab' . $sep . 'group']);
            unset($table['tab' . $sep . 'count']);

            if ($visible &&
              ((isset($_REQUEST['tbl_group'])
                && (strpos($_REQUEST['tbl_group'], $group) === 0
                || strpos($_REQUEST['tbl_group'], $sep . $group) !== false))
              ||
              (isset($GLOBALS['table'])
                && strpos($GLOBALS['table'], $group) === 0))) {
                PMA_displayTableList($table, true,
                    $tab_group_full . $group, $table_db);
            } else {
                PMA_displayTableList($table, false, '', $table_db);
            }
            echo '</li>' . "\n";
        } elseif (is_array($table)) {
            $href = $GLOBALS['cfg']['DefaultTabTable'] . '?'
                .$GLOBALS['common_url_query'] . '&amp;table='
                .urlencode($table['Name']);
            echo '<li>' . "\n";
            echo '<a title="' . $GLOBALS['strBrowse'] . ': '
                . htmlspecialchars($table['Comment'])
                .' (' . PMA_formatNumber($table['Rows'], 0) . ' ' . $GLOBALS['strRows'] . ')"'
                .' id="browse_' . htmlspecialchars($table_db . '.' . $table['Name']) . '"'
                .' href="sql.php?' . $GLOBALS['common_url_query']
                .'&amp;table=' . urlencode($table['Name'])
                .'&amp;goto=' . $GLOBALS['cfg']['DefaultTabTable']
                . '" >'
                .'<img class="icon"';
            if ('VIEW' === strtoupper($table['Comment'])) {
                echo ' src="' . $GLOBALS['pmaThemeImage'] . 's_views.png"';
            } else {
                echo ' src="' . $GLOBALS['pmaThemeImage'] . 'b_sbrowse.png"';
            }
            echo ' id="icon_' . htmlspecialchars($table_db . '.' . $table['Name']) . '"'
                .' width="10" height="10" alt="' . $GLOBALS['strBrowse'] . '" /></a>' . "\n"
                .'<a href="' . $href . '" title="' . htmlspecialchars($table['Comment']
                .' (' . PMA_formatNumber($table['Rows'], 0) . ' ' . $GLOBALS['strRows']) . ')"'
                .' id="' . htmlspecialchars($table_db . '.' . $table['Name']) . '">'
                . htmlspecialchars($table['disp_name']) . '</a>';
            echo '</li>' . "\n";
        }
    }
    echo '</ul>';
}
?>
</div>
</body>
</html>
<?php
/**
 * Close MySql connections
 */
if (isset($controllink) && $controllink) {
    @PMA_DBI_close($controllink);
}
if (isset($userlink) && $userlink) {
    @PMA_DBI_close($userlink);
}

/**
 * Sends bufferized data
 */
if ($GLOBALS['cfg']['OBGzip'] && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
