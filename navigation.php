<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * the navigation frame - displays server, db and table selection tree
 *
 * @version $Id$
 * @uses $GLOBALS['PMA_List_Database']
 * @uses $GLOBALS['server']
 * @uses $GLOBALS['db']
 * @uses $GLOBALS['table']
 * @uses $GLOBALS['available_languages']
 * @uses $GLOBALS['lang']
 * @uses $GLOBALS['text_dir']
 * @uses $GLOBALS['charset']
 * @uses $GLOBALS['pmaThemeImage']
 * @uses $GLOBALS['strNoDatabases']
 * @uses $GLOBALS['strDatabase']
 * @uses $GLOBALS['strGo']
 * @uses $GLOBALS['strSelectADb']
 * @uses $GLOBALS['strNoTablesFound']
 * @uses $GLOBALS['cfg']['LeftFrameLight']
 * @uses $GLOBALS['cfg']['ShowTooltip']
 * @uses $GLOBALS['cfg']['ShowTooltipAliasDB']
 * @uses $GLOBALS['cfg']['DefaultTabDatabase']
 * @uses $GLOBALS['cfgRelation']['commwork']) {
 * @uses PMA_List_Database::getSingleItem()
 * @uses PMA_List_Database::count()
 * @uses PMA_List_Database::getHtmlSelectGrouped()
 * @uses PMA_List_Database::getGroupedDetails()
 * @uses PMA_generate_common_url()
 * @uses PMA_generate_common_hidden_inputs()
 * @uses PMA_getComments();
 * @uses PMA_getTableCount()
 * @uses PMA_getTableList()
 * @uses PMA_getRelationsParam()
 * @uses PMA_outBufferPre()
 * @uses session_write_close()
 * @uses strlen()
 * @uses session_write_close()
 * @uses is_array()
 * @uses implode()
 * @uses htmlspecialchars()
 */

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.inc.php';

/**
 * finish and cleanup navigation.php script execution
 *
 * @uses $GLOBALS['controllink'] to close it
 * @uses $GLOBALS['userlink'] to close it
 * @uses PMA_DBI_close()
 * @access private only to be used in navigation.php
 */
function PMA_exitNavigationFrame()
{
    echo '</body></html>';

    /**
     * Close MySQL connections
     */
    if (isset($GLOBALS['controllink']) && $GLOBALS['controllink']) {
        @PMA_DBI_close($GLOBALS['controllink']);
    }
    if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
        @PMA_DBI_close($GLOBALS['userlink']);
    }

    exit;
}

// keep the offset of the db list in session before closing it
if (! isset($_SESSION['userconf']['navi_limit_offset'])) {
    $_SESSION['userconf']['navi_limit_offset'] = 0;
}
if (! isset($_SESSION['userconf']['table_limit_offset'])) {
	$_SESSION['userconf']['table_limit_offset'] = 0;
}
if (isset($_REQUEST['pos'])) {
	if (isset($_REQUEST['tpos'])) {
		$_SESSION['userconf']['table_limit_offset'] = (int) $_REQUEST['pos'];
	}
	else {
		$_SESSION['userconf']['navi_limit_offset'] = (int) $_REQUEST['pos'];
	}
}
$pos = $_SESSION['userconf']['navi_limit_offset'];
$tpos = $_SESSION['userconf']['table_limit_offset'];

// free the session file, for the other frames to be loaded
session_write_close();

/**
 * the output compression library
 */
require_once './libraries/ob.lib.php';

PMA_outBufferPre();

/*
 * selects the database if there is only one on current server
 */
if ($GLOBALS['server'] && ! strlen($GLOBALS['db'])) {
    $GLOBALS['db'] = $GLOBALS['PMA_List_Database']->getSingleItem();
}

$db_start = $GLOBALS['db'];

/**
 * the relation library
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * garvin: For re-usability, moved http-headers to a seperate file.
 * It can now be included by libraries/header.inc.php, querywindow.php.
 */
require_once './libraries/header_http.inc.php';

if (! isset($_SESSION['userconf']['navi_limit_offset'])) {
    $_SESSION['userconf']['navi_limit_offset'] = 0;
}
if (! isset($_SESSION['userconf']['table_limit_offset'])) {
	$_SESSION['userconf']['table_limit_offset'] = 0;
}
if (isset($_REQUEST['pos'])) {
	if (isset($_REQUEST['tpos'])) {
		$_SESSION['userconf']['table_limit_offset'] = (int) $_REQUEST['pos'];
	}
	else {
		$_SESSION['userconf']['navi_limit_offset'] = (int) $_REQUEST['pos'];
	}
}
$pos = $_SESSION['userconf']['navi_limit_offset'];
$tpos = $_SESSION['userconf']['table_limit_offset'];

/*
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
    dir="<?php echo $GLOBALS['text_dir']; ?>">

<head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type"
        content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
    <base target="frame_content" />
    <link rel="stylesheet" type="text/css"
        href="phpmyadmin.css.php?<?php echo PMA_generate_common_url('', ''); ?>&amp;js_frame=left&amp;nocache=<?php echo $_SESSION['PMA_Config']->getThemeUniqueValue(); ?>" />
    <script type="text/javascript" src="js/navigation.js"></script>
    <script type="text/javascript" src="js/functions.js"></script>
    <script type="text/javascript">
    // <![CDATA[
    var image_minus = '<?php echo $GLOBALS['pmaThemeImage']; ?>b_minus.png';
    var image_plus = '<?php echo $GLOBALS['pmaThemeImage']; ?>b_plus.png';

    // INIT PMA_setFrameSize
    var onloadCnt = 0; 
    var onLoadHandler = window.onload;  
    var resizeHandler = window.onresize;
    window.document.onresize  = resizeHandler;
    window.onload = function() {
        if (onloadCnt == 0) {
            if (typeof(onLoadHandler) == "function") { 
                onLoadHandler(); 
            }
            if (typeof(PMA_setFrameSize) != 'undefined' && typeof(PMA_setFrameSize) == 'function') { 
                PMA_setFrameSize(); 
            }
            onloadCnt++;
        }
    };
    window.onresize = function() {
        if (typeof(resizeHandler) == "function") { 
            resizeHandler(); 
        }
        if (typeof(PMA_saveFrameSize) != 'undefined' && typeof(PMA_saveFrameSize) == 'function') { 
            PMA_saveFrameSize(); 
        }
    };
    // ]]>
    </script>
    <?php
    /*
     * remove horizontal scroll bar bug in IE 6 by forcing a vertical scroll bar
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
require './libraries/navigation_header.inc.php';
if (! $GLOBALS['server']) {
    // no server selected
    PMA_exitNavigationFrame();
} elseif (! $GLOBALS['PMA_List_Database']->count()) {
    // no database available, so we break here
    echo '<p>' . $GLOBALS['strNoDatabases'] . '</p>';
    PMA_exitNavigationFrame();
} elseif ($GLOBALS['cfg']['LeftFrameLight'] && $GLOBALS['PMA_List_Database']->count() > 1) {
    $list = $cfg['DisplayDatabasesList'];
    if ($list === 'auto') {
        if (empty($GLOBALS['db'])) {
            $list = true;
        } else {
            $list = false;
        }
    }
    if (!$list) {
        // more than one database available and LeftFrameLight is true
        // display db selectbox
        //
        // Light mode -> beginning of the select combo for databases
        // Note: When javascript is active, the frameset will be changed from
        // within navigation.php. With no JS (<noscript>) the whole frameset will
        // be rebuilt with the new target frame.
    ?>

    <div id="databaseList">
    <form method="post" action="index.php" target="_parent" id="left">
    <label for="lightm_db"><?php echo $GLOBALS['strDatabase']; ?></label>
    <?php
        echo PMA_generate_common_hidden_inputs() . "\n";
        echo $GLOBALS['PMA_List_Database']->getHtmlSelectGrouped(true, $_SESSION['userconf']['navi_limit_offset'], $GLOBALS['cfg']['MaxDbList']) . "\n";
        echo '<noscript>' . "\n"
            .'<input type="submit" name="Go" value="' . $GLOBALS['strGo'] . '" />' . "\n"
            .'</noscript>' . "\n"
            .'</form>' . "\n";
    } else {
        if (! empty($db)) {
            echo '<div id="databaseList">' . "\n";
        }
        echo $GLOBALS['PMA_List_Database']->getHtmlListGrouped(true, $_SESSION['userconf']['navi_limit_offset'], $GLOBALS['cfg']['MaxDbList']) . "\n";
    }
    $_url_params = array('pos' => $pos);
    PMA_listNavigator($GLOBALS['PMA_List_Database']->count(), $pos, $_url_params, 'navigation.php', 'frame_navigation', $GLOBALS['cfg']['MaxDbList']);
    if (! empty($db)) {
        echo '</div>' . "\n";
    }
}
?>

<div id="left_tableList">
<?php
// Don't display expansible/collapsible database info if:
// 1. $GLOBALS['server'] == 0 (no server selected)
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
    .' href="navigation.php?%s" target="_self">';

$element_counter = 0;

if ($GLOBALS['cfg']['LeftFrameLight'] && strlen($GLOBALS['db'])) {
    // show selected databasename as link to DefaultTabDatabase-page
    // with table count in ()
    $common_url_query = PMA_generate_common_url($GLOBALS['db']);

    $db_tooltip = '';

    if ($GLOBALS['cfg']['ShowTooltip']
      && $GLOBALS['cfgRelation']['commwork']) {
        $_db_tooltip = PMA_getComments($GLOBALS['db']);
        if (is_array($_db_tooltip)) {
            $db_tooltip = implode(' ', $_db_tooltip);
        }
    }

    $disp_name  = $GLOBALS['db'];
    if ($db_tooltip && $GLOBALS['cfg']['ShowTooltipAliasDB']) {
        $disp_name      = $db_tooltip;
        $disp_name_cut  = $db_tooltip;
        $db_tooltip     = $GLOBALS['db'];
    }

    ?>
    <p><a class="item"
        href="<?php echo $GLOBALS['cfg']['DefaultTabDatabase'] . '?' . $common_url_query; ?>"
        title="<?php echo htmlspecialchars($db_tooltip); ?>" >
    <?php
    if ($GLOBALS['text_dir'] === 'rtl') {
        echo ' <bdo dir="ltr">(' . PMA_getTableCount($GLOBALS['db']) . ')</bdo> ';
    }
    echo '<span class="navi_dbName">' . htmlspecialchars($disp_name) . '</span>';
    if ($GLOBALS['text_dir'] === 'ltr') {
        echo ' <bdo dir="ltr">(' . PMA_getTableCount($GLOBALS['db']) . ')</bdo> ';
    }
    echo '</a></p>';

    /**
     * This helps reducing the navi panel size; in the right panel,
     * user can find a navigator to page thru all tables.
     *
     */
    $table_list = PMA_getTableList($GLOBALS['db'], null, $tpos, $cfg['MaxTableList']);
    if (! empty($table_list)) {
        $table_count = PMA_getTableCount($GLOBALS['db']);
        // upper table list paginator
        if (count($table_list) <= $GLOBALS['cfg']['MaxTableList'] && $table_count > $GLOBALS['cfg']['MaxTableList']) {
            $_url_params = array(
              'tpos' => 'true',
              'pos' => $tpos,
              'db' => $GLOBALS['db']
            );
            PMA_listNavigator($table_count, $tpos, $_url_params, 'navigation.php', 'frame_navigation', $GLOBALS['cfg']['MaxTableList']);
        } 
        PMA_displayTableList($table_list, true, '', $GLOBALS['db']);
        // lower table list paginator
        if (count($table_list) <= $GLOBALS['cfg']['MaxTableList'] && $table_count > $GLOBALS['cfg']['MaxTableList']) {
            PMA_listNavigator($table_count, $tpos, $_url_params, 'navigation.php', 'frame_navigation', $GLOBALS['cfg']['MaxTableList']);
        } 
    } else {
        echo $GLOBALS['strNoTablesFound'];
    }
    unset($table_list);
} elseif ($GLOBALS['cfg']['LeftFrameLight']) {
    echo '<p>' . $GLOBALS['strSelectADb'] . '</p>' . "\n";
} else {
    echo '<div id="databaseList">' . "\n";
    $_url_params = array('pos' => $pos);
    PMA_listNavigator($GLOBALS['PMA_List_Database']->count(), $pos, $_url_params, 'navigation.php', 'frame_navigation', $GLOBALS['cfg']['MaxDbList']);
    echo '</div>' . "\n";

    $common_url_query = PMA_generate_common_url();
    PMA_displayDbList($GLOBALS['PMA_List_Database']->getGroupedDetails($_SESSION['userconf']['navi_limit_offset'],$GLOBALS['cfg']['MaxDbList']), $_SESSION['userconf']['navi_limit_offset'],$GLOBALS['cfg']['MaxDbList']);
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
 * @global  $db_start
 * @global  $common_url_query
 * @param   array   $ext_dblist extended db list
 * @param   integer $offset
 * @param   integer $count
 */
function PMA_displayDbList($ext_dblist, $offset, $count) {
    global $element_counter, $img_minus, $img_plus, $href_left,
        $db_start, $common_url_query;

    // get table list, for all databases
    // doing this in one step takes advantage of a single query with information_schema!
    $tables_full = PMA_DBI_get_tables_full($GLOBALS['PMA_List_Database']->getLimitedItems($offset, $count));

    $url_dbgroup = '';
    echo '<ul id="leftdatabaselist">';
    $close_db_group = false;
    foreach ($ext_dblist as $group => $db_group) {
        if ($GLOBALS['PMA_List_Database']->count() > 1) {
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

            if ($GLOBALS['PMA_List_Database']->count() > 1) {
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
                            window.parent.goTo('./navigation.php?<?php echo $common_url_query; ?>');
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
                    (bool) ($GLOBALS['PMA_List_Database']->count() === 1 || $db_start == $db['name']);
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
 * @global  integer the element counter
 * @global  string  html code for '-' image
 * @global  string  html code for '+' image
 * @global  string  html code for self link
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
            if ($visible
             && ((isset($_REQUEST['tbl_group'])
               && (strpos($_REQUEST['tbl_group'], $group) === 0
                || strpos($_REQUEST['tbl_group'], $sep . $group) !== false))
              || strpos($GLOBALS['table'], $group) === 0)) {
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
                        window.parent.goTo('./navigation.php?<?php echo $common_url_query; ?>');
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
              || strpos($GLOBALS['table'], $group) === 0)) {
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
                // preserve spaces in table name
                . str_replace(' ', '&nbsp;', htmlspecialchars($table['disp_name'])) . '</a>';
            echo '</li>' . "\n";
        }
    }
    echo '</ul>';
}

echo '</div>' . "\n";

PMA_exitNavigationFrame();
?>
