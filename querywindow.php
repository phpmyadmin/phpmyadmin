<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * this file is register_globals save
 *
 * @todo    move JavaScript out of here into .js files
 * @uses    $cfg['QueryWindowDefTab']
 * @uses    $cfg['PropertiesIconic']
 * @uses    $cfg['QueryHistoryDB']
 * @uses    $cfg['Server']['user']
 * @uses    $cfg['AllowUserDropDatabase']
 * @uses    $cfg['Confirm']
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['strSQL']
 * @uses    $GLOBALS['strImportFiles']
 * @uses    $GLOBALS['strQuerySQLHistory']
 * @uses    $GLOBALS['strAll']
 * @uses    $GLOBALS['strChange']
 * @uses    $GLOBALS['strFormEmpty']
 * @uses    $GLOBALS['strNotNumber']
 * @uses    $GLOBALS['strNoDropDatabases']
 * @uses    $GLOBALS['strDoYouReally']
 * @uses    $GLOBALS['strQuerySQLHistory']
 * @uses    PMA_isSuperuser()
 * @uses    PMA_outBufferPre()
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_isValid()
 * @uses    PMA_ifSetOr()
 * @uses    PMA_setHistory()
 * @uses    PMA_getHistory()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    PMA_escapeJsString()
 * @uses    PMA_getTabs()
 * @uses    PMA_sqlQueryForm()
 * @uses    PMA_jsFormat()
 * @uses    in_array()
 * @uses    strlen()
 * @uses    preg_replace()
 * @uses    htmlspecialchars()
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/common.inc.php';

$is_superuser = PMA_isSuperuser();

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/sql_query_form.lib.php';

/**
 * starts output buffering if requested and supported
 */
require_once './libraries/ob.lib.php';
PMA_outBufferPre();

/**
 * load relations
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

/**
 * load bookmark support
 */
require_once './libraries/bookmark.lib.php';

$querydisplay_tabs = array(
    'sql',
    'files',
    'history',
    'full',
);

if (isset($_REQUEST['querydisplay_tab'])
 && in_array($_REQUEST['querydisplay_tab'], $querydisplay_tabs)) {
    $querydisplay_tab = $_REQUEST['querydisplay_tab'];
} else {
    $querydisplay_tab = $GLOBALS['cfg']['QueryWindowDefTab'];
}

/**
 * $_REQUEST['no_js'] is set if open new window by JavaScript failed
 * so this page is loaded in main frame
 */
$no_js = PMA_ifSetOr($_REQUEST['no_js'], false);

if ($no_js) {
    $querydisplay_tab = 'full';
    $tabs = false;
} else {
    $tabs = array();
    $tabs['sql']['icon']   = 'b_sql.png';
    $tabs['sql']['text']   = $GLOBALS['strSQL'];
    $tabs['sql']['fragment']   = '#';
    $tabs['sql']['attr']   = 'onclick="javascript:PMA_querywindowCommit(\'sql\');return false;"';
    $tabs['sql']['active'] = (bool) ($querydisplay_tab == 'sql');
    $tabs['import']['icon']   = 'b_import.png';
    $tabs['import']['text']   = $GLOBALS['strImportFiles'];
    $tabs['import']['fragment']   = '#';
    $tabs['import']['attr']   = 'onclick="javascript:PMA_querywindowCommit(\'files\');return false;"';
    $tabs['import']['active'] = (bool) ($querydisplay_tab == 'files');
    $tabs['history']['icon']   = 'b_bookmark.png';
    $tabs['history']['text']   = $GLOBALS['strQuerySQLHistory'];
    $tabs['history']['fragment']   = '#';
    $tabs['history']['attr']   = 'onclick="javascript:PMA_querywindowCommit(\'history\');return false;"';
    $tabs['history']['active'] = (bool) ($querydisplay_tab == 'history');

    if ($GLOBALS['cfg']['QueryWindowDefTab'] == 'full') {
        $tabs['all']['text']   = $GLOBALS['strAll'];
        $tabs['all']['fragment']   = '#';
        $tabs['all']['attr']   = 'onclick="javascript:PMA_querywindowCommit(\'full\');return false;"';
        $tabs['all']['active'] = (bool) ($querydisplay_tab == 'full');
    }
}

if ($GLOBALS['cfg']['PropertiesIconic']) {
    $titles['Change'] =
         '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_edit.png" alt="' . $GLOBALS['strChange'] . '" title="' . $GLOBALS['strChange']
        . '" />';

    if ('both' === $GLOBALS['cfg']['PropertiesIconic']) {
        $titles['Change'] .= $GLOBALS['strChange'];
    }
} else {
    $titles['Change'] = $GLOBALS['strChange'];
}

$url_query = PMA_generate_common_url($db, $table);

if (! empty($sql_query)) {
    $show_query = 1;
}

if ($no_js) {
    // ... we redirect to appropriate query sql page
    // works only full if $db and $table is also stored/grabbed from $_COOKIE
    if (strlen($table)) {
        require './tbl_sql.php';
    } elseif (strlen($db)) {
        require './db_sql.php';
    } else {
        require './server_sql.php';
    }
    exit;
}

/**
 * Defines the query to be displayed in the query textarea
 */
if (! empty($show_query)) {
    $query_to_display = $sql_query;
} else {
    $query_to_display = '';
}
$sql_query = '';

/**
 * prepare JavaScript functionality
 */
$js_include[] = 'common.js';
$js_include[] = 'functions.js';
$js_include[] = 'querywindow.js';

if (PMA_isValid($_REQUEST['auto_commit'], 'identical', 'true')) {
    $js_events[] = array(
        'object'    => 'window',
        'event'     => 'load',
        'function'  => 'PMA_queryAutoCommit',
    );
}
if (PMA_isValid($_REQUEST['init'])) {
    $js_events[] = array(
        'object'    => 'window',
        'event'     => 'load',
        'function'  => 'PMA_querywindowResize',
    );
}
// always set focus to the textarea
if ($querydisplay_tab == 'sql' || $querydisplay_tab == 'full') {
    $js_events[] = array(
        'object'    => 'window',
        'event'     => 'load',
        'function'  => 'PMA_querywindowSetFocus',
    );
}

/**
 * start HTTP/HTML output
 */
require_once './libraries/header_http.inc.php';
require_once './libraries/header_meta_style.inc.php';
require_once './libraries/header_scripts.inc.php';
?>
</head>

<body id="bodyquerywindow">
<div id="querywindowcontainer">
<?php

if ($tabs) {
    echo PMA_getTabs($tabs);
    unset($tabs);
}

PMA_sqlQueryForm($query_to_display, $querydisplay_tab);

// Hidden forms and query frame interaction stuff

$_sql_history = PMA_getHistory($GLOBALS['cfg']['Server']['user']);
if (! empty($_sql_history)
 && ($querydisplay_tab == 'history' || $querydisplay_tab == 'full')) {
    $tab = $querydisplay_tab != 'full' ? 'sql' : 'full';
    echo $GLOBALS['strQuerySQLHistory'] . ':<br />' . "\n"
        .'<ul>';
    foreach ($_sql_history as $query) {
        echo '<li>' . "\n";

        // edit link
        $url_params = array(
            'querydisplay_tab' => $tab,
            'sql_query' => $query['sqlquery'],
            'db' => $query['db'],
            'table' => $query['table'],
        );
        echo '<a href="querywindow.php' . PMA_generate_common_url($url_params)
            . '">' . $titles['Change'] . '</a>';

        // execute link
        $url_params['auto_commit'] = 'true';
        echo '<a href="import.php' . PMA_generate_common_url($url_params) . '"'
            . ' target="frame_content">';

        if (! empty($query['db'])) {
            echo '[';
            echo htmlspecialchars(PMA_backquote($query['db']));
            if (! empty($query['table'])) {
                echo '.' . htmlspecialchars(PMA_backquote($query['table']));
            }
            echo  '] ';
        }
        if (strlen($query['sqlquery']) > 120) {
            echo '<span title="' . htmlspecialchars($query['sqlquery']) . '">';
            echo htmlspecialchars(substr($query['sqlquery'], 0, 50)) . ' [...] ';
            echo htmlspecialchars(substr($query['sqlquery'], -50));
            echo '</span>';
        } else {
            echo htmlspecialchars($query['sqlquery']);
        }
        echo '</a>' . "\n";
        echo '</li>' . "\n";
    }
    unset($tab, $_sql_history, $query);
    echo '</ul>' . "\n";
}
?>
<form action="querywindow.php" method="post" name="hiddenqueryform"
    id="hiddenqueryform">
    <?php echo PMA_generate_common_hidden_inputs('', ''); ?>
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>" />
    <input type="hidden" name="sql_query" value="" />
    <input type="hidden" name="querydisplay_tab" value="<?php echo $querydisplay_tab; ?>" />
</form>
</div>
</body>
</html>
