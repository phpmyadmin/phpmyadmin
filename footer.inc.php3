<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// In this file you may add PHP or HTML statements that will be used to define
// the footer for phpMyAdmin pages.

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
        document.writeln("Window: " + top.frames.queryframe.querywindow.location + "<br>");
    <?php
    }
    ?>
    
    <?php
    if (!isset($error_message) || $error_message == '') {
    ?>
    if (top.frames.queryframe && top.frames.queryframe.document && top.frames.queryframe.document.queryframeform) {
        top.frames.queryframe.document.queryframeform.db.value = "<?php echo (isset($db) ? addslashes($db) : ''); ?>";
        top.frames.queryframe.document.queryframeform.table.value = "<?php echo (isset($table) ? addslashes($table) : ''); ?>";
    }
    <?php
    }
    ?>
    
    function reload_querywindow () {
        if (top.frames.queryframe && top.frames.queryframe.querywindow && !top.frames.queryframe.querywindow.closed && top.frames.queryframe.querywindow.location) {
            <?php echo ($cfg['QueryFrameDebug'] ? 'document.writeln("<a href=\'#\' onClick=\'top.frames.queryframe.querywindow.focus(); return false;\'>Query Window</a> can be updated.<br>");' : ''); ?>
    
            <?php
            if (!isset($error_message) || $error_message == '') {
            ?>
            top.frames.queryframe.querywindow.document.querywindow.db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
            top.frames.queryframe.querywindow.document.querywindow.query_history_latest_db.value = "<?php echo (isset($db) ? addslashes($db) : '') ?>";
            top.frames.queryframe.querywindow.document.querywindow.table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";
            top.frames.queryframe.querywindow.document.querywindow.query_history_latest_table.value = "<?php echo (isset($table) ? addslashes($table) : '') ?>";
    
            <?php echo (isset($sql_query) ? 'top.frames.queryframe.querywindow.document.querywindow.query_history_latest.value = "' . urlencode($sql_query) . '";' : '// no sql query update') . "\n"; ?>
    
            <?php echo ($cfg['QueryFrameDebug'] ? 'alert(\'Querywindow submits. Last chance to check variables.\');' : '') . "\n"; ?>
            top.frames.queryframe.querywindow.document.querywindow.submit();
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
        if (top.frames.queryframe && top.frames.queryframe.querywindow && !top.frames.queryframe.querywindow.closed && top.frames.queryframe.querywindow.location) {
            if (top.frames.queryframe.querywindow.document.querywindow.querydisplay_tab != 'sql') {
                top.frames.queryframe.querywindow.document.querywindow.querydisplay_tab.value = "sql";
                top.frames.queryframe.querywindow.document.querywindow.query_history_latest.value = sql_query;
                top.frames.queryframe.querywindow.document.querywindow.submit();
                top.frames.queryframe.querywindow.focus();
            } else {
                top.frames.queryframe.querywindow.focus();
            }

            return false;
        } else if (top.frames.queryframe) {
            new_win_url = 'querywindow.php3?sql_query=' + sql_query + '&<?php echo PMA_generate_common_url(isset($db) ? addslashes($db) : '', isset($table) ? addslashes($table) : '', '&'); ?>';
            top.frames.queryframe.querywindow=window.open(new_win_url, '','toolbar=0,location=1,directories=0,status=1,menubar=0,scrollbars=yes,resizable=yes,width=<?php echo $cfg['QueryWindowWidth']; ?>,height=<?php echo $cfg['QueryWindowHeight']; ?>');
    
            if (!top.frames.queryframe.querywindow.opener) {
               top.frames.queryframe.querywindow.opener = top.frames.queryframe;
            }

            // reload_querywindow();
            return false;
        }
    }

    reload_querywindow();
<?php
if (isset($focus_querywindow) && $focus_querywindow == "true") {
?>
    if (top.frames.queryframe && top.frames.queryframe.querywindow && !top.frames.queryframe.querywindow.closed && top.frames.queryframe.querywindow.location) {
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
    include('./libraries/dbg/setup.php3');
    //if the setup ran fine, then do the profiling
    if (isset($GLOBALS['DBG']) && $GLOBALS['DBG']) {
        include('./libraries/dbg/profiling.php3');
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

?>
