<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 */

require_once('./libraries/relation.lib.php'); // for PMA_setHistory()

?>
<script type="text/javascript">
<!--
<?php
    if (!isset($no_history) && !empty($db) && (!isset($error_message) || $error_message == '')) {
        $table = isset( $table ) ? $table : '';
        ?>
        // sets selection in left frame quick db selectbox to current db
        parent.frames[0].setTable( '<?php echo $db; ?>', '<?php echo $table; ?>' );
        <?php
    }
    ?>

    function reload_querywindow () {
        if (parent.frames[0] && parent.frames[0].querywindow && !parent.frames[0].querywindow.closed && parent.frames[0].querywindow.location) {
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
            if (!parent.frames[0].querywindow.document.sqlform.LockFromUpdate || !parent.frames[0].querywindow.document.sqlform.LockFromUpdate.checked) {
                parent.frames[0].querywindow.document.querywindow.db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
                parent.frames[0].querywindow.document.querywindow.query_history_latest_db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
                parent.frames[0].querywindow.document.querywindow.table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";
                parent.frames[0].querywindow.document.querywindow.query_history_latest_table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";

                <?php echo (isset($sql_query) ? 'parent.frames[0].querywindow.document.querywindow.query_history_latest.value = "' . urlencode($sql_query) . '";' : '// no sql query update') . "\n"; ?>

                parent.frames[0].querywindow.document.querywindow.submit();
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
        if (parent.frames[0] && parent.frames[0].querywindow && !parent.frames[0].querywindow.closed && parent.frames[0].querywindow.location) {
            if (parent.frames[0].querywindow.document.querywindow.querydisplay_tab != 'sql') {
                parent.frames[0].querywindow.document.querywindow.querydisplay_tab.value = "sql";
                parent.frames[0].querywindow.document.querywindow.query_history_latest.value = sql_query;
                parent.frames[0].querywindow.document.querywindow.submit();
                parent.frames[0].querywindow.focus();
            } else {
                parent.frames[0].querywindow.focus();
            }

            return false;
        } else if (parent.frames[0]) {
            new_win_url = 'querywindow.php?sql_query=' + sql_query + '&<?php echo PMA_generate_common_url(isset($db) ? addslashes($db) : '', isset($table) ? addslashes($table) : '', '&'); ?>';
            parent.frames[0].querywindow=window.open(new_win_url, '','toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=yes,resizable=yes,width=<?php echo $cfg['QueryWindowWidth']; ?>,height=<?php echo $cfg['QueryWindowHeight']; ?>');

            if (!parent.frames[0].querywindow.opener) {
               parent.frames[0].querywindow.opener = parent.frames[0];
            }

            // reload_querywindow();
            return false;
        }
    }

    reload_querywindow();
    <?php
    if (isset($focus_querywindow) && $focus_querywindow == "true") {
        ?>
        if (parent.frames[0] && parent.frames[0].querywindow && !parent.frames[0].querywindow.closed && parent.frames[0].querywindow.location) {
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
 * Close database connections
 */
if (isset($GLOBALS['dbh']) && $GLOBALS['dbh']) {
    @PMA_DBI_close($GLOBALS['dbh']);
}
if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
    @PMA_DBI_close($GLOBALS['userlink']);
}

include('./config.footer.inc.php');
?>
    <script src="libraries/tooltip.js" type="text/javascript" language="javascript"></script>
</body>
</html>
<?php

/**
 * Generates profiling data if requested
 */
if ( ! empty( $GLOBALS['cfg']['DBG']['enable'] )
  && ! empty( $GLOBALS['cfg']['DBG']['profile']['enable'] ) ) {
    //run the basic setup code first
    require_once('./libraries/dbg/setup.php');
    //if the setup ran fine, then do the profiling
    if ( ! empty( $GLOBALS['DBG'] ) ) {
        require_once('./libraries/dbg/profiling.php');
        dbg_dump_profiling_results();
    }
}

/**
 * Sends bufferized data
 */
if ( ! empty( $GLOBALS['cfg']['OBGzip'] )
  && ! empty( $GLOBALS['ob_mode'] ) ) {
    PMA_outBufferPost($GLOBALS['ob_mode']);
}

/**
 * Stops the script execution
 */
exit;
?>
