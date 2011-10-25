<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * finishes HTML output
 *
 * updates javascript variables in index.php for correct working with querywindow
 * and navigation frame refreshing
 *
 * send buffered data if buffered
 *
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 *
 * always use $GLOBALS, as this script is also included by functions
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * for PMA_setHistory()
 */
if (! PMA_isValid($_REQUEST['no_history']) && empty($GLOBALS['error_message'])
 && ! empty($GLOBALS['sql_query'])) {
    PMA_setHistory(PMA_ifSetOr($GLOBALS['db'], ''),
        PMA_ifSetOr($GLOBALS['table'], ''),
        $GLOBALS['cfg']['Server']['user'],
        $GLOBALS['sql_query']);
}

if ($GLOBALS['error_handler']->hasDisplayErrors()) {
    echo '<div class="clearfloat">';
    $GLOBALS['error_handler']->dispErrors();
    echo '</div>';
}

if (count($GLOBALS['footnotes'])) {
    echo '<div class="footnotes">';
    foreach ($GLOBALS['footnotes'] as $footnote) {
        echo '<span id="footnote_' . $footnote['nr'] . '"><sup>'
            . $footnote['nr'] . '</sup> ' . $footnote['note'] . '</span><br />';
    }
    echo '</div>';
}

if (! empty($_SESSION['debug'])) {
    $sum_time = 0;
    $sum_exec = 0;
    foreach ($_SESSION['debug']['queries'] as $query) {
        $sum_time += $query['count'] * $query['time'];
        $sum_exec += $query['count'];
    }

    echo '<div>';
    echo count($_SESSION['debug']['queries']) . ' queries executed '
        . $sum_exec . ' times in ' . $sum_time . ' seconds';
    echo '<pre>';
    print_r($_SESSION['debug']);
    echo '</pre>';
    echo '</div>';
    $_SESSION['debug'] = array();
}

if (!$GLOBALS['is_ajax_request']) {
?>
<script type="text/javascript">
//<![CDATA[
<?php
if (empty($GLOBALS['error_message'])) {
    ?>
$(document).ready(function() {
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
    } else if (isset($_GET['reload_left_frame']) && $_GET['reload_left_frame'] == '1') {
        // reload left frame (used by user preferences)
        ?>
        if (window.parent && window.parent.frame_navigation) {
            window.parent.frame_navigation.location.reload();
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
});

//]]>
</script>
<?php
}

// Link to itself to replicate windows including frameset
if (! isset($GLOBALS['checked_special'])) {
    $GLOBALS['checked_special'] = false;
}

if (PMA_getenv('SCRIPT_NAME') && empty($_POST) && !$GLOBALS['checked_special'] && ! $GLOBALS['is_ajax_request']) {
    echo '<div id="selflink" class="print_ignore">' . "\n";
    $url_params['target'] = basename(PMA_getenv('SCRIPT_NAME'));
    ?>
<script type="text/javascript">
//<![CDATA[

/* Store current location in hash part of URL to allow direct bookmarking */
setURLHash("<?php echo PMA_generate_common_url($url_params, 'text', ''); ?>");

//]]>
</script>
<?php

    echo '<a href="index.php' . PMA_generate_common_url($url_params) . '"'
        . ' title="' . __('Open new phpMyAdmin window') . '" target="_blank">';
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo PMA_getImage('window-new.png', __('Open new phpMyAdmin window'));
    }
    if ($GLOBALS['cfg']['NavigationBarIconic'] !== true) {
        echo __('Open new phpMyAdmin window');
    }
    echo '</a>' . "\n";
    echo '</div>' . "\n";
}

// Include possible custom footers
if (! $GLOBALS['is_ajax_request'] && file_exists(CUSTOM_FOOTER_FILE)) {
    include CUSTOM_FOOTER_FILE;
}

/**
 * If we are in an AJAX request, we do not need to generate the closing tags for
 * body and html.
 */
if (! $GLOBALS['is_ajax_request']) {
?>
</body>
</html>
<?php
}
/**
 * Stops the script execution
 */
exit;
?>
