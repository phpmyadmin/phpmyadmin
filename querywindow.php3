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
    <base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> />
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

<script type="text/javascript" language="javascript">    
<?php
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && $cfg['QueryFrameDebug']) {
    $js_db = (isset($db) ? $db : 'FALSE');
    $js_table = (isset($table) ? $table : 'FALSE');
    $js_server = (isset($server) ? $server : 'FALSE');

    $js_true_db = '\' + document.querywindow.db.value + \'';
    $js_true_table = '\' + document.querywindow.table.value + \'';
    $js_true_server = '\' + document.querywindow.server.value + \'';
    
    $js_parent = '\' + opener.location.href + \'';
    $js_frame = '\' + opener.parent.location.href + \'';
?>
function debug() {
    alert('<?php echo sprintf($strQueryFrameDebugBox, $js_db, $js_table, $js_server, $js_true_db, $js_true_table, $js_true_server, $js_parent, $js_frame); ?>');
    return false;
}
<?php
}
?>
function query_auto_commit() {
    document.sqlform.submit();
}

// js form validation stuff
var errorMsg0   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strFormEmpty']); ?>';
var errorMsg1   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotNumber']); ?>';
var errorMsg2   = '<?php echo str_replace('\'', '\\\'', $GLOBALS['strNotValidNumber']); ?>';
var noDropDbMsg = '<?php echo((!$GLOBALS['cfg']['AllowUserDropDatabase']) ? str_replace('\'', '\\\'', $GLOBALS['strNoDropDatabases']) : ''); ?>';
var confirmMsg  = '<?php echo(($GLOBALS['cfg']['Confirm']) ? str_replace('\'', '\\\'', $GLOBALS['strDoYouReally']) : ''); ?>';
//-->
</script>
<script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
</head>

<body bgcolor="<?php echo ($cfg['QueryFrameJS'] ? $cfg['LeftBgColor'] : $cfg['RightBgColor']); ?>">

<?php
// Hidden forms and query frame interaction stuff
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {

    $input_query_history = array();
    $sql_history = array();
    $dup_sql = array();

    if (isset($query_history_latest) && isset($query_history_latest_db) && $query_history_latest != '' && $query_history_latest_db != '') {
        $input_query_history[] = '<input type="hidden" name="query_history[]" value="' . $query_history_latest . '" />';
        $input_query_history[] = '<input type="hidden" name="query_history_db[]" value="' . htmlspecialchars($query_history_latest_db) . '" />';
        $input_query_history[] = '<input type="hidden" name="query_history_table[]" value="' . (isset($query_history_latest_table) ? htmlspecialchars($query_history_latest_table) : '') . '" />';

        $sql_history[] = '<li><a href="#" onClick="document.querywindow.query_history_latest.value = \'' . htmlspecialchars($query_history_latest) . '\'; document.querywindow.auto_commit.value = \'true\'; document.querywindow.db.value = \'' . htmlspecialchars($query_history_latest_db) . '\'; document.querywindow.query_history_latest_db.value = \'' . htmlspecialchars($query_history_latest_db) . '\'; document.querywindow.table.value = \'' . (isset($query_history_latest_table) ? htmlspecialchars($query_history_latest_table) : '') . '\'; document.querywindow.query_history_latest_table.value = \'' . (isset($query_history_latest_table) ? htmlspecialchars($query_history_latest_table) : '') . '\'; document.querywindow.submit(); return false;">[' . htmlspecialchars($query_history_latest_db) . '] ' . urldecode($query_history_latest) . '</a></li>' . "\n";

        $sql_query = urldecode($query_history_latest);
        $db = $query_history_latest_db;
        $table = $query_history_latest_table;
        $show_query = 1;
        $dup_sql[$query_history_latest] = true;
    }

    if (isset($query_history) && is_array($query_history)) {
        $current_index = count($query_history);
        @reset($query_history);
        while(list($query_no, $query_sql) = each($query_history)) {
            if (!isset($dup_sql[$query_sql])) {

                $input_query_history[] = '<input type="hidden" name="query_history[]" value="' . $query_sql . '" />';
                $input_query_history[] = '<input type="hidden" name="query_history_db[]" value="' . htmlspecialchars($query_history_db[$query_no]) . '" />';
                $input_query_history[] = '<input type="hidden" name="query_history_table[]" value="' . (isset($query_history_table[$query_no]) ? htmlspecialchars($query_history_table[$query_no]) : '') . '" />';

                $sql_history[] = '<li><a href="#" onClick="document.querywindow.query_history_latest.value = \'' . $query_sql . '\'; document.querywindow.auto_commit.value = \'true\'; document.querywindow.db.value = \'' . htmlspecialchars($query_history_db[$query_no]) . '\'; document.querywindow.query_history_latest_db.value = \'' . htmlspecialchars($query_history_db[$query_no]) . '\'; document.querywindow.table.value = \'' . (isset($query_history_table[$query_no]) ? htmlspecialchars($query_history_table[$query_no]) : '') . '\'; document.querywindow.query_history_latest_table.value = \'' . (isset($query_history_table[$query_no]) ? htmlspecialchars($query_history_table[$query_no]) : '') . '\'; document.querywindow.submit(); return false;">[' . htmlspecialchars($query_history_db[$query_no]) . '] ' . urldecode($query_sql) . '</a></li>' . "\n";
                $dup_sql[$query_sql] = true;
            }
        }
    }
}

$url_query = PMA_generate_common_url(isset($db) ? $db : '', isset($table) ? $table : '');
if (!isset($goto)) {
    $goto = '';
}

require './libraries/bookmark.lib.php3';
require './tbl_query_box.php3';

// Hidden forms and query frame interaction stuff
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
    if (isset($auto_commit) && $auto_commit == 'true') {
    ?>
        <script type="text/javascript" language="javascript">
        query_auto_commit();
        </script>
    <?php
    }
    
    if (isset($sql_history) && is_array($sql_history) && count($sql_history) > 0) {
    ?>
    <li>
        <div style="margin-bottom: 10px"><?php echo $strQuerySQLHistory . ' :<br><ul>' . implode('', $sql_history) . '</ul>'; ?></div>
    </li>
    <?php
    }
?>
<form action="querywindow.php3" method="post" name="querywindow">
<?php 
    echo PMA_generate_common_hidden_inputs('', '');
    if (count($input_query_history) > 0) {
        echo implode("\n", $input_query_history);
    }
?>
    <input type="hidden" name="db" value="<?php (isset($db) && $db != '' ? $db : ''); ?>" />
    <input type="hidden" name="table" value="<?php (isset($table) && $table != '' ? $table : ''); ?>" />

    <input type="hidden" name="query_history_latest" value="" />
    <input type="hidden" name="query_history_latest_db" value="" />
    <input type="hidden" name="query_history_latest_table" value="" />

    <input type="hidden" name="previous_db" value="<?php echo htmlspecialchars($db); ?>" />

    <input type="hidden" name="auto_commit" value="false" />
</form>
<?php 
} 

/* REMOVE ME */
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS'] && $cfg['QueryFrameDebug']) {
?>
<br>
<center>
    <a href='#' onClick='return debug();'><?php echo $strQueryFrameDebug; ?></a>
</center>
<?php
}
/* REMOVE ME */
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
