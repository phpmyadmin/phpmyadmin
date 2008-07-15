<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * finishes HTML output
 *
 * updates javascript variables in index.php for coorect working with querywindow
 * and navigation frame refreshing
 *
 * send buffered data if buffered
 *
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 *
 * always use $GLOBALS, as this script is also included by functions
 *
 * @uses    $_REQUEST['no_history']
 * @uses    $GLOBALS['lang']
 * @uses    $GLOBALS['collation_connection']
 * @uses    $GLOBALS['server']
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['table']
 * @uses    $GLOBALS['error_message']
 * @uses    $GLOBALS['reload']
 * @uses    $GLOBALS['sql_query']
 * @uses    $GLOBALS['focus_querywindow']
 * @uses    $GLOBALS['checked_special']
 * @uses    $GLOBALS['pmaThemeImage']
 * @uses    $GLOBALS['controllink'] to close it
 * @uses    $GLOBALS['userlink'] to close it
 * @uses    $cfg['Server']['user']
 * @uses    $cfg['NavigationBarIconic']
 * @uses    $cfg['DBG']['enable']
 * @uses    $cfg['DBG']['profile']['enable']
 * @uses    $GLOBALS['strOpenNewWindow']
 * @uses    $cfg['MaxCharactersInDisplayedSQL'] 
 * @uses    PMA_isValid()
 * @uses    PMA_setHistory()
 * @uses    PMA_ifSetOr()
 * @uses    PMA_escapeJsString()
 * @uses    PMA_getenv()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_DBI_close()
 * @uses    basename()
 * @uses    file_exists()
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * for PMA_setHistory()
 */
require_once './libraries/relation.lib.php';

if (! PMA_isValid($_REQUEST['no_history']) && empty($GLOBALS['error_message'])
 && ! empty($GLOBALS['sql_query'])) {
    PMA_setHistory(PMA_ifSetOr($GLOBALS['db'], ''),
        PMA_ifSetOr($GLOBALS['table'], ''),
        $GLOBALS['cfg']['Server']['user'],
        $GLOBALS['sql_query']);
}

?>
<script type="text/javascript">
//<![CDATA[
<?php
if (empty($GLOBALS['error_message'])) {
    ?>
// updates current settings
if (window.parent.setAll) {
    window.parent.setAll('<?php
        echo PMA_escapeJsString($GLOBALS['lang']) . "', '";
        echo PMA_escapeJsString($GLOBALS['collation_connection']) . "', '";
        echo PMA_escapeJsString($GLOBALS['server']) . "', '";
        echo PMA_escapeJsString(PMA_ifSetOr($GLOBALS['db'], '')) . "', '";
        echo PMA_escapeJsString(PMA_ifSetOr($GLOBALS['table'], '')) . "', '";
        echo PMA_escapeJsString($_SESSION[' PMA_token ']);?>');
}
    <?php
    if (! empty($GLOBALS['reload'])) {
        ?>
// refresh navigation frame content
if (window.parent.refreshNavigation) {
    window.parent.refreshNavigation();
}
        <?php
    }
    ?>
// set current db, table and sql query in the querywindow
if (window.parent.reload_querywindow) {
    window.parent.reload_querywindow(
        '<?php echo PMA_escapeJsString(PMA_ifSetOr($GLOBALS['db'], '')) ?>',
        '<?php echo PMA_escapeJsString(PMA_ifSetOr($GLOBALS['table'], '')) ?>',
        '<?php echo strlen($GLOBALS['sql_query']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] ? PMA_escapeJsString($GLOBALS['sql_query']) : ''; ?>');
}
    <?php
}

if (! empty($GLOBALS['focus_querywindow'])) {
    ?>
// set focus to the querywindow
if (parent.querywindow && !parent.querywindow.closed && parent.querywindow.location) {
    self.focus();
}
    <?php
}
?>

if (window.parent.frame_content) {
    // reset content frame name, as querywindow needs to set a unique name
    // before submitting form data, and navigation frame needs the original name
    if (typeof(window.parent.frame_content.name) != 'undefined'
     && window.parent.frame_content.name != 'frame_content') {
        window.parent.frame_content.name = 'frame_content';
    }
    if (typeof(window.parent.frame_content.id) != 'undefined'
     && window.parent.frame_content.id != 'frame_content') {
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
    $GLOBALS['checked_special'] = false;
}

if (PMA_getenv('SCRIPT_NAME') && empty($_POST) && !$GLOBALS['checked_special']) {
    echo '<div id="selflink" class="print_ignore">' . "\n";
    $url_params['target'] = basename(PMA_getenv('SCRIPT_NAME'));
    echo '<a href="index.php' . PMA_generate_common_url($url_params) . '"'
        . ' title="' . $GLOBALS['strOpenNewWindow'] . '" target="_blank">';
    /*
    echo '<a href="index.php?target=' . basename(PMA_getenv('SCRIPT_NAME'));
    $url = PMA_generate_common_url($GLOBALS['db'], $GLOBALS['table']);
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
if (! empty($GLOBALS['controllink'])) {
    @PMA_DBI_close($GLOBALS['controllink']);
}
if (! empty($GLOBALS['userlink'])) {
    @PMA_DBI_close($GLOBALS['userlink']);
}

// Include possible custom footers
if (file_exists('./config.footer.inc.php')) {
    require './config.footer.inc.php';
}


/**
 * Generates profiling data if requested
 */

// profiling deactivated due to licensing issues
if (! empty($GLOBALS['cfg']['DBG']['enable'])
  && ! empty($GLOBALS['cfg']['DBG']['profile']['enable'])) {
    //run the basic setup code first
    require_once './libraries/dbg/setup.php';
    //if the setup ran fine, then do the profiling
    /*
    if (! empty($GLOBALS['DBG'])) {
        require_once './libraries/dbg/profiling.php';
        dbg_dump_profiling_results();
    }
    */
}

?>
</body>
</html>
<?php
/**
 * Stops the script execution
 */
exit;
?>
