<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * In this file you may add PHP or HTML statements that will be used to define
 * the footer for phpMyAdmin pages.
 *
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 */

require_once('./libraries/relation.lib.php'); // for PMA_setHistory()

/**
 * Query window
 */

// If query window is wanted and open, update with latest selected db/table.
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
?>

<script type="text/javascript">
<!--
<?php
    if ($cfg['QueryFrameDebug']) {
    ?>
        document.writeln("Updating query window. DB: <?php echo (isset($db) ? addslashes($db) : 'FALSE'); ?>, Table: <?php echo (isset($table) ? addslashes($table) : 'FALSE'); ?><br>");
        document.writeln("Window: " + parent.frames.queryframe.querywindow.location + "<br>");
    <?php
    }
    ?>

    <?php
    if (!isset($no_history) && (!isset($error_message) || $error_message == '')) {
    ?>
    if (parent.frames.queryframe && parent.frames.queryframe.document && parent.frames.queryframe.document.queryframeform) {
        parent.frames.queryframe.document.queryframeform.db.value = "<?php echo (isset($db) ? addslashes($db) : ''); ?>";
        parent.frames.queryframe.document.queryframeform.table.value = "<?php echo (isset($table) ? addslashes($table) : ''); ?>";
    }
    <?php
    }
    ?>

    function reload_querywindow () {
        if (parent.frames.queryframe && parent.frames.queryframe.querywindow && !parent.frames.queryframe.querywindow.closed && parent.frames.queryframe.querywindow.location) {
            <?php echo ($cfg['QueryFrameDebug'] ? 'document.writeln("<a href=\'#\' onClick=\'parent.frames.queryframe.querywindow.focus(); return false;\'>Query Window</a> can be updated.<br>");' : ''); ?>

            <?php
            if (!isset($no_history) && (!isset($error_message) || $error_message == '')) {
                if (isset($LockFromUpdate) && $LockFromUpdate == '1' && isset($sql_query)) {
                    // When the button 'LockFromUpdate' was selected in the querywindow, it does not submit it's contents to
                    // itself. So we create a SQL-history entry here.
                    if ($cfg['QueryHistoryDB'] && $cfgRelation['historywork']) {
                        PMA_setHistory((isset($db) ? $db : ''), (isset($table) ? $table : ''), $cfg['Server']['user'], $sql_query);
                    }
                }
            ?>
            if (!parent.frames.queryframe.querywindow.document.sqlform.LockFromUpdate || !parent.frames.queryframe.querywindow.document.sqlform.LockFromUpdate.checked) {
                parent.frames.queryframe.querywindow.document.querywindow.db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
                parent.frames.queryframe.querywindow.document.querywindow.query_history_latest_db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
                parent.frames.queryframe.querywindow.document.querywindow.table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";
                parent.frames.queryframe.querywindow.document.querywindow.query_history_latest_table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";

                <?php echo (isset($sql_query) ? 'parent.frames.queryframe.querywindow.document.querywindow.query_history_latest.value = "' . urlencode($sql_query) . '";' : '// no sql query update') . "\n"; ?>

                <?php echo ($cfg['QueryFrameDebug'] ? 'alert(\'Querywindow submits. Last chance to check variables.\');' : '') . "\n"; ?>
                parent.frames.queryframe.querywindow.document.querywindow.submit();
            }
            <?php
            } else {
            ?>
            // no submit, query was invalid
            <?php
            }
            ?>
        }
    }

    function focus_querywindow(sql_query) {
        if (parent.frames.queryframe && parent.frames.queryframe.querywindow && !parent.frames.queryframe.querywindow.closed && parent.frames.queryframe.querywindow.location) {
            if (parent.frames.queryframe.querywindow.document.querywindow.querydisplay_tab != 'sql') {
                parent.frames.queryframe.querywindow.document.querywindow.querydisplay_tab.value = "sql";
                parent.frames.queryframe.querywindow.document.querywindow.query_history_latest.value = sql_query;
                parent.frames.queryframe.querywindow.document.querywindow.submit();
                parent.frames.queryframe.querywindow.focus();
            } else {
                parent.frames.queryframe.querywindow.focus();
            }

            return false;
        } else if (parent.frames.queryframe) {
            new_win_url = 'querywindow.php?sql_query=' + sql_query + '&<?php echo PMA_generate_common_url(isset($db) ? addslashes($db) : '', isset($table) ? addslashes($table) : '', '&'); ?>';
            parent.frames.queryframe.querywindow=window.open(new_win_url, '','toolbar=0,location=1,directories=0,status=1,menubar=0,scrollbars=yes,resizable=yes,width=<?php echo $cfg['QueryWindowWidth']; ?>,height=<?php echo $cfg['QueryWindowHeight']; ?>');

            if (!parent.frames.queryframe.querywindow.opener) {
               parent.frames.queryframe.querywindow.opener = parent.frames.queryframe;
            }

            // reload_querywindow();
            return false;
        }
    }

    reload_querywindow();
<?php
if (isset($focus_querywindow) && $focus_querywindow == "true") {
?>
    if (parent.frames.queryframe && parent.frames.queryframe.querywindow && !parent.frames.queryframe.querywindow.closed && parent.frames.queryframe.querywindow.location) {
        self.focus();
    }
<?php
}
?>

//-->
</script>
<?php
}


/**
 * Close MySql non-persistent connections
 */
if (isset($GLOBALS['dbh']) && $GLOBALS['dbh']) {
    @mysql_close($GLOBALS['dbh']);
}
if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
    @mysql_close($GLOBALS['userlink']);
}
?>

</body>

</html>
<?php

/**
 * Generates profiling data if requested
 */
if (isset($GLOBALS['cfg']['DBG']['enable'])
        && $GLOBALS['cfg']['DBG']['enable']
        && isset($GLOBALS['cfg']['DBG']['profile']['enable'])
        && $GLOBALS['cfg']['DBG']['profile']['enable']) {
    //run the basic setup code first
    require_once('./libraries/dbg/setup.php');
    //if the setup ran fine, then do the profiling
    if (isset($GLOBALS['DBG']) && $GLOBALS['DBG']) {
        require_once('./libraries/dbg/profiling.php');
        dbg_dump_profiling_results();
    }
}

/**
 * Sends bufferized data
 */
if (isset($GLOBALS['cfg']['OBGzip']) && $GLOBALS['cfg']['OBGzip']
        && isset($GLOBALS['ob_mode']) && $GLOBALS['ob_mode']) {
    PMA_outBufferPost($GLOBALS['ob_mode']);
}

/**
 * Stops the script execution
 */
exit;

?>
