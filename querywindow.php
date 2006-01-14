<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/common.lib.php');

/**
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
if (isset($db) && strlen($db)) {
    $db_start = $db;
}


/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/sql_query_form.lib.php';
require_once('./libraries/ob.lib.php');
if ( $GLOBALS['cfg']['OBGzip'] ) {
    $ob_mode = PMA_outBufferModeGet();
    if ( $ob_mode ) {
        PMA_outBufferPre( $ob_mode );
    }
}

require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();


// initialize some variables
$_sql_history = array();
$_input_query_history = array();

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

// garvin: For re-usability, moved http-headers and stylesheets
// to a seperate file. It can now be included by libraries/header.inc.php,
// querywindow.php.

require_once('./libraries/header_http.inc.php');
require_once('./libraries/header_meta_style.inc.php');
?>
<script type="text/javascript" language="javascript">
//<![CDATA[
function query_auto_commit() {
    document.getElementById( 'sqlqueryform' ).target = window.opener.frames[1].name;
    document.getElementById( 'sqlqueryform' ).submit();
    return;
}

function query_tab_commit(tab) {
    document.getElementById('hiddenqueryform').querydisplay_tab.value = tab;
    document.getElementById('hiddenqueryform').submit();
    return false;
}

// js form validation stuff
/**/
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
var noDropDbMsg = '<?php echo !$GLOBALS['cfg']['AllowUserDropDatabase']
    ? str_replace('\'', '\\\'', $GLOBALS['strNoDropDatabases']) : ''; ?>';
var confirmMsg  = '<?php echo $GLOBALS['cfg']['Confirm']
    ? str_replace('\'', '\\\'', $GLOBALS['strDoYouReally']) : ''; ?>';
/**/

<?php
if ( empty( $querydisplay_tab ) ) {
    $onload = 'onload="resize();"';
?>
function resize() {

    // for Gecko
    if ( typeof( self.sizeToContent ) == 'function' ) {
        self.sizeToContent();
        //self.scrollbars.visible = false;
        // give some more space ... to prevent 'fli(pp/ck)ing'
        self.resizeBy( 10, 50 );
        return;
    }

    // for IE, Opera
    if (document.getElementById && typeof(document.getElementById('querywindowcontainer')) != 'undefined' ) {

        // get content size
        var newWidth  = document.getElementById('querywindowcontainer').offsetWidth;
        var newHeight = document.getElementById('querywindowcontainer').offsetHeight;

        // set size to contentsize
        // plus some offset for scrollbars, borders, statusbar, menus ...
        self.resizeTo( newWidth + 45, newHeight + 75 );
    }
}
<?php
} else {
    $onload = '';
}
?>
//]]>
</script>
<script src="./js/functions.js" type="text/javascript" language="javascript"></script>
</head>

<body id="bodyquerywindow" <?php echo $onload; ?> >
<div id="querywindowcontainer">
<?php
if ( !isset($no_js) ) {
    $querydisplay_tab = (isset($querydisplay_tab) ? $querydisplay_tab : $GLOBALS['cfg']['QueryWindowDefTab']);

    $tabs = array();
    $tabs['sql']['icon']   = 'b_sql.png';
    $tabs['sql']['text']   = $strSQL;
    $tabs['sql']['link']   = '#';
    $tabs['sql']['attr']   = 'onclick="javascript:query_tab_commit(\'sql\');return false;"';
    $tabs['sql']['active'] = (bool) ( $querydisplay_tab == 'sql' );
    $tabs['import']['icon']   = 'b_import.png';
    $tabs['import']['text']   = $strImportFiles;
    $tabs['import']['link']   = '#';
    $tabs['import']['attr']   = 'onclick="javascript:query_tab_commit(\'files\');return false;"';
    $tabs['import']['active'] = (bool) ( $querydisplay_tab == 'files' );
    $tabs['history']['icon']   = 'b_bookmark.png';
    $tabs['history']['text']   = $strQuerySQLHistory;
    $tabs['history']['link']   = '#';
    $tabs['history']['attr']   = 'onclick="javascript:query_tab_commit(\'history\');return false;"';
    $tabs['history']['active'] = (bool) ( $querydisplay_tab == 'history' );

    if ( $GLOBALS['cfg']['QueryWindowDefTab'] == 'full' ) {
        $tabs['all']['text']   = $strAll;
        $tabs['all']['link']   = '#';
        $tabs['all']['attr']   = 'onclick="javascript:query_tab_commit(\'full\');return false;"';
        $tabs['all']['active'] = (bool) ( $querydisplay_tab == 'full' );
    }

    echo PMA_getTabs( $tabs );
    unset( $tabs );
} else {
    $querydisplay_tab = 'full';
}

if ( true == $GLOBALS['cfg']['PropertiesIconic'] ) {
    $titles['Change'] =
         '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        . 'b_edit.png" alt="' . $strChange . '" title="' . $strChange
        . '" />';

    if ( 'both' === $GLOBALS['cfg']['PropertiesIconic'] ) {
        $titles['Change'] .= $strChange;
    }
} else {
    $titles['Change'] = $strChange;
}

// Hidden forms and query frame interaction stuff

if ( ! empty( $query_history_latest ) && ! empty( $query_history_latest_db ) ) {
    if ( $GLOBALS['cfg']['QueryHistoryDB'] && $cfgRelation['historywork'] ) {
        PMA_setHistory((isset($query_history_latest_db) ? $query_history_latest_db : ''),
            (isset($query_history_latest_table) ? $query_history_latest_table : ''),
            $GLOBALS['cfg']['Server']['user'],
            $query_history_latest );
    }

    $_input_query_history[$query_history_latest] = array(
        'db'    => $query_history_latest_db,
        'table' => isset($query_history_latest_table) ? $query_history_latest_table : '',
    );

    $_sql_history[$query_history_latest] = array(
        'db'    =>  $query_history_latest_db,
        'table' => isset($query_history_latest_table) ? $query_history_latest_table : '',
    );

    $sql_query = urldecode($query_history_latest);
    $db = $query_history_latest_db;
    $table = $query_history_latest_table;
} elseif ( ! empty( $query_history_latest ) ) {
    $sql_query = urldecode($query_history_latest);
}

if (isset($sql_query)) {
    $show_query = 1;
}

if ( $GLOBALS['cfg']['QueryHistoryDB'] && $cfgRelation['historywork'] ) {

    $temp_history = PMA_getHistory( $GLOBALS['cfg']['Server']['user'] );
    if (is_array($temp_history) && count($temp_history) > 0) {
        foreach ($temp_history AS $history_nr => $history_array) {
            if ( ! isset( $_sql_history[$history_array['sqlquery']] ) ) {
                $_sql_history[$history_array['sqlquery']] = array(
                    'db'    => $history_array['db'],
                    'table' => isset( $history_array['table'] ) ? $history_array['table'] : '',
                );
            }
        }
    }

} else {

    if (isset($query_history) && is_array($query_history)) {
        $current_index = count($query_history);
        foreach ($query_history AS $query_no => $query_sql) {
            if ( ! isset( $_input_query_history[$query_sql] ) ) {
                $_input_query_history[$query_sql] = array(
                    'db'    => $query_history_db[$query_no],
                    'table' => isset($query_history_table[$query_no]) ? $query_history_table[$query_no] : '',
                );
                $_sql_history[$query_sql] = array(
                    'db'    => $query_history_db[$query_no],
                    'table' => isset( $query_history_table[$query_no] ) ? $query_history_table[$query_no] : '',
                );
            } // end if check if this item exists
        } // end while print history
    } // end if history exists
} // end if DB-based history

$url_query = PMA_generate_common_url(isset($db) ? $db : '', isset($table) ? $table : '');
if (!isset($goto)) {
    $goto = '';
}

require_once './libraries/bookmark.lib.php';

if (isset($no_js) && $no_js) {
    // ... we redirect to appropriate query sql page
    // works only full if $db and $table is also stored/grabbed from $_COOKIE
    if ( isset( $table ) && strlen($table) ) {
        require './tbl_properties.php';
    } elseif ( isset($db) && strlen($db) ) {
        require './db_details.php';
    } else {
        require './server_sql.php';
    }
    exit;
}

/**
 * Defines the query to be displayed in the query textarea
 */
if ( ! empty( $show_query ) ) {
    $query_to_display = $sql_query;
} else {
    $query_to_display = '';
}
unset( $sql_query );

PMA_sqlQueryForm( $query_to_display, $querydisplay_tab );

// Hidden forms and query frame interaction stuff
if (isset($auto_commit) && $auto_commit == 'true') {
?>
        <script type="text/javascript" language="javascript">
        //<![CDATA[
        query_auto_commit();
        //]]>
        </script>
<?php
}

if ( count( $_sql_history ) > 0
  && ( $querydisplay_tab == 'history' || $querydisplay_tab == 'full' ) ) {
    $tab = isset($querydisplay_tab) && $querydisplay_tab != 'full' ? 'sql' : 'full';
    echo $strQuerySQLHistory . ':<br />' . "\n"
        .'<ul>';
    foreach ( $_sql_history as $sql => $query ) {
        echo '<li>' . "\n";
        // edit link
        echo '<a href="#" onclick="'
               .' document.getElementById(\'hiddenqueryform\').'
               .'querydisplay_tab.value = \'' . $tab . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest.value = \''
                . preg_replace('/(\r|\n)+/i', '\\n',
                    htmlentities( $sql, ENT_QUOTES ) ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'auto_commit.value = \'false\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'db.value = \'' . htmlspecialchars( $query['db'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest_db.value = \''
               . htmlspecialchars( $query['db'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'table.value = \'' . htmlspecialchars( $query['table'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest_table.value = \''
               . htmlspecialchars( $query['table'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').submit();'
               .' return false;">' . $titles['Change'] . '</a>';
            // execute link
        echo '<a href="#" onclick="'
               .' document.getElementById(\'hiddenqueryform\').'
               .'querydisplay_tab.value = \'' . $tab . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest.value = \''
                . preg_replace('/(\r|\n)+/i', '\\r\\n',
                    htmlentities( $sql, ENT_QUOTES ) ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'auto_commit.value = \'true\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'db.value = \'' . htmlspecialchars( $query['db'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest_db.value = \''
               . htmlspecialchars( $query['db'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'table.value = \'' . htmlspecialchars( $query['table'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').'
               .'query_history_latest_table.value = \''
               . htmlspecialchars( $query['table'] ) . '\';'
               .' document.getElementById(\'hiddenqueryform\').submit();'
               .' return false;">[' . htmlspecialchars( $query['db'] ) . '] '
               . urldecode( $sql ) . '</a>' . "\n";

        echo '</li>' . "\n";
    }
    unset( $tab, $_sql_history, $sql, $query );
    echo '</ul>' . "\n";
}
?>
<form action="querywindow.php" method="post" name="querywindow" id="hiddenqueryform">
<?php
echo PMA_generate_common_hidden_inputs('', '') . "\n";
foreach ( $_input_query_history as $sql => $history ) {
    echo '<input type="hidden" name="query_history[]" value="'
        . $sql . '" />' . "\n";
    echo '<input type="hidden" name="query_history_db[]" value="'
        . htmlspecialchars( $history['db'] ) . '" />' . "\n";
    echo '<input type="hidden" name="query_history_table[]" value="'
        . htmlspecialchars( $history['table'] ) . '" />' . "\n";
}
unset( $_input_query_history, $sql, $history );
?>
    <input type="hidden" name="db" value="<?php echo (! isset($db) ? '' : htmlspecialchars($db)); ?>" />
    <input type="hidden" name="table" value="<?php echo (! isset($table) ? '' : htmlspecialchars($table)); ?>" />

    <input type="hidden" name="query_history_latest" value="" />
    <input type="hidden" name="query_history_latest_db" value="" />
    <input type="hidden" name="query_history_latest_table" value="" />

    <input type="hidden" name="previous_db" value="<?php echo htmlspecialchars($db); ?>" />

    <input type="hidden" name="auto_commit" value="false" />
    <input type="hidden" name="querydisplay_tab" value="<?php echo $querydisplay_tab; ?>" />
</form>
    <?php
?>
</div>
</body>
</html>

<?php

/**
 * Close MySql connections
 */
if (isset($controllink) && $controllink) {
    PMA_DBI_close($controllink);
}
if (isset($userlink) && $userlink) {
    PMA_DBI_close($userlink);
}


/**
 * Sends bufferized data
 */
if ( $GLOBALS['cfg']['OBGzip'] && isset( $ob_mode ) && $ob_mode ) {
     PMA_outBufferPost($ob_mode);
}
?>
