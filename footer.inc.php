<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 */

require_once('./libraries/relation.lib.php'); // for PMA_setHistory()

/**
 * Query window
 */

// If query window is wanted and open, update with latest selected db/table.
if ( $cfg['QueryFrame'] && $cfg['QueryFrameJS'] ) {  
    ?>
<script type="text/javascript">
//<![CDATA[
    <?php
    if (!isset($no_history) && !empty($db) && (!isset($error_message) || $error_message == '')) {
        $table = isset( $table ) ? $table : '';
        ?>
        // sets selection in left frame quick db selectbox to current db
        window.parent.setAll( '<?php echo $lang; ?>', '<?php echo $collation_connection; ?>', '<?php echo $server; ?>', '<?php echo $db; ?>', '<?php echo $table; ?>' );
        <?php
    }
    ?>

    <?php
    if ( ! isset( $no_history ) && empty( $error_message ) ) {
        if ( isset( $LockFromUpdate ) && $LockFromUpdate == '1' && isset( $sql_query ) ) {
            // When the button 'LockFromUpdate' was selected in the querywindow,
            // it does not submit it's contents to
            // itself. So we create a SQL-history entry here.
            if ($cfg['QueryHistoryDB'] && $cfgRelation['historywork']) {
                PMA_setHistory( ( isset( $db ) ? $db : '' ),
                    ( isset( $table ) ? $table : '' ),
                    $cfg['Server']['user'],
                    $sql_query );
            }
        }
        ?>
        window.parent.reload_querywindow(
            "<?php echo isset( $db ) ? addslashes( $db ) : '' ?>",
            "<?php echo isset( $table ) ? addslashes( $table ) : '' ?>",
            "<?php echo isset( $sql_query ) ? urlencode( $sql_query ) : ''; ?>" );
        <?php
    }
    ?>

    <?php
    if ( ! empty( $focus_querywindow ) ) {
        ?>
        if ( parent.querywindow && !parent.querywindow.closed && parent.querywindow.location) {
            self.focus();
        }
        <?php
    }
    ?>
//]]>
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
    <script src="libraries/tooltip.js" type="text/javascript"
        language="javascript"></script>
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
