<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 *
 * always use $GLOBALS, as this script is also included by functions
 *
 */

require_once './libraries/relation.lib.php'; // for PMA_setHistory()

/**
 * updates javascript variables in index.php for coorect working with querywindow
 * and navigation frame refreshing
 */
?>
<script type="text/javascript" language="javascript">
//<![CDATA[
<?php
if (! isset($GLOBALS['no_history']) && isset($GLOBALS['db'])
  && strlen($GLOBALS['db']) && empty($GLOBALS['error_message'])) {
    $table = isset($GLOBALS['table']) ? $GLOBALS['table'] : ''; ?>
// updates current settings
if (window.parent.setAll) {
    window.parent.setAll('<?php echo $GLOBALS['lang']; ?>', '<?php echo htmlspecialchars($GLOBALS['collation_connection']); ?>', '<?php echo $GLOBALS['server']; ?>', '<?php echo htmlspecialchars($GLOBALS['db']); ?>', '<?php echo htmlspecialchars($table); ?>');
}
<?php } ?>

<?php if (! empty($GLOBALS['reload'])) { ?>
// refresh navigation frame content
if (window.parent.refreshLeft) {
    window.parent.refreshLeft();
}
<?php } ?>

<?php
if (! isset($GLOBALS['no_history']) && empty($GLOBALS['error_message'])) {
    if (isset($GLOBALS['LockFromUpdate']) && $GLOBALS['LockFromUpdate'] == '1'
      && isset($GLOBALS['sql_query'])) {
        // When the button 'LockFromUpdate' was selected in the querywindow,
        // it does not submit it's contents to
        // itself. So we create a SQL-history entry here.
        if ($GLOBALS['cfg']['QueryHistoryDB'] && $GLOBALS['cfgRelation']['historywork']) {
            PMA_setHistory((isset($GLOBALS['db']) ? $GLOBALS['db'] : ''),
                (isset($GLOBALS['table']) ? $GLOBALS['table'] : ''),
                $GLOBALS['cfg']['Server']['user'],
                $GLOBALS['sql_query']);
        }
    }
    ?>
// set current db, table and sql query in the querywindow
if (window.parent.refreshLeft) {
    window.parent.reload_querywindow(
        "<?php echo isset($GLOBALS['db']) ? htmlspecialchars(addslashes($GLOBALS['db'])) : '' ?>",
        "<?php echo isset($GLOBALS['table']) ? htmlspecialchars(addslashes($GLOBALS['table'])) : '' ?>",
        "<?php echo isset($GLOBALS['sql_query']) ? htmlspecialchars(urlencode($GLOBALS['sql_query'])) : ''; ?>");
}
<?php } ?>

<?php if (! empty($GLOBALS['focus_querywindow'])) { ?>
// set focus to the querywindow
if (parent.querywindow && !parent.querywindow.closed && parent.querywindow.location) {
    self.focus();
}
<?php } ?>

if (window.parent.frame_content) {
    // reset content frame name, as querywindow needs to set a unique name
    // before submitting form data, and navigation frame needs the original name
    if (window.parent.frame_content.name != 'frame_content') {
        window.parent.frame_content.name = 'frame_content';
    }
    if (window.parent.frame_content.id != 'frame_content') {
        window.parent.frame_content.id = 'frame_content';
    }
    //window.parent.frame_content.setAttribute('name', 'frame_content');
    //window.parent.frame_content.setAttribute('id', 'frame_content');
}
//]]>
</script>
<?php

// Link to itself to replicate windows including frameset
if (!isset($GLOBALS['checked_special'])) {
    $GLOBALS['checked_special'] = FALSE;
}

if (PMA_getenv('SCRIPT_NAME') && empty($_POST) && !$GLOBALS['checked_special']) {
    echo '<div id="selflink" class="print_ignore">' . "\n";
    $url_params['target'] = basename(PMA_getenv('SCRIPT_NAME'));
    echo '<a href="index.php' . PMA_generate_common_url($url_params) . '"'
        . ' title="' . $GLOBALS['strOpenNewWindow'] . '" target="_blank">';
    /*
    echo '<a href="index.php?target=' . basename(PMA_getenv('SCRIPT_NAME'));
    $url = PMA_generate_common_url(isset($GLOBALS['db']) ? $GLOBALS['db'] : '', isset($GLOBALS['table']) ? $GLOBALS['table'] : '');
    if (!empty($url)) {
        echo '&amp;' . $url;
    }
    echo '" target="_blank">';
    */
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo '<img class="icon" src="'. $GLOBALS['pmaThemeImage'] . 'window-new.png"'
            . ' alt="' . $GLOBALS['strOpenNewWindow'] . '" />';
    }
    if ($GLOBALS['cfg']['NavigationBarIconic'] !== true) {
        echo $GLOBALS['strOpenNewWindow'];
    }
    echo '</a>' . "\n";
    echo '</div>' . "\n";
}

/**
 * Close database connections
 */
if (isset($GLOBALS['controllink']) && $GLOBALS['controllink']) {
    @PMA_DBI_close($GLOBALS['controllink']);
}
if (isset($GLOBALS['userlink']) && $GLOBALS['userlink']) {
    @PMA_DBI_close($GLOBALS['userlink']);
}

// Include possible custom footers
if (file_exists('./config.footer.inc.php')) {
    require('./config.footer.inc.php');
}


/**
 * Generates profiling data if requested
 */
if (! empty($GLOBALS['cfg']['DBG']['enable'])
  && ! empty($GLOBALS['cfg']['DBG']['profile']['enable'])) {
    //run the basic setup code first
    require_once './libraries/dbg/setup.php';
    //if the setup ran fine, then do the profiling
    if (! empty($GLOBALS['DBG'])) {
        require_once './libraries/dbg/profiling.php';
        dbg_dump_profiling_results();
    }
}

?>
</body>
</html>
<?php
/**
 * Sends bufferized data
 */
if (! empty($GLOBALS['cfg']['OBGzip'])
  && ! empty($GLOBALS['ob_mode'])) {
    PMA_outBufferPost($GLOBALS['ob_mode']);
}

/**
 * Stops the script execution
 */
exit;
?>
