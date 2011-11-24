<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/common.inc.php';


// Cross-framing protection
if ( false === $GLOBALS['cfg']['AllowThirdPartyFraming']) {
    echo PMA_includeJS('cross_framing_protection.js');
}
// generate title (unless we already have $page_title, from cookie auth)
if (! isset($page_title)) {
    if ($GLOBALS['server'] > 0) {
        $title = PMA_expandUserString(
            ! empty($GLOBALS['table']) ? $GLOBALS['cfg']['TitleTable'] :
            (! empty($GLOBALS['db']) ? $GLOBALS['cfg']['TitleDatabase'] :
            (! empty($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['TitleServer'] :
            $GLOBALS['cfg']['TitleDefault']))
        );
    }
} else {
    $title = $page_title;
}
// here, the function does not exist with this configuration: $cfg['ServerDefault'] = 0;
$is_superuser    = function_exists('PMA_isSuperuser') && PMA_isSuperuser();

$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'jquery/jquery.qtip-1.0.0-rc3.js';
$params = array('lang' => $GLOBALS['lang']);
if (isset($GLOBALS['db'])) {
    $params['db'] = $GLOBALS['db'];
}
$GLOBALS['js_include'][] = 'messages.php' . PMA_generate_common_url($params);
// Append the theme id to this url to invalidate the cache on a theme change
$GLOBALS['js_include'][] = 'get_image.js.php?theme=' . urlencode($_SESSION['PMA_Theme']->getId());

/**
 * Here we add a timestamp when loading the file, so that users who
 * upgrade phpMyAdmin are not stuck with older .js files in their
 * browser cache. This produces an HTTP 304 request for each file.
 */

// avoid loading twice a js file
$GLOBALS['js_include'] = array_unique($GLOBALS['js_include']);
foreach ($GLOBALS['js_include'] as $js_script_file) {
    echo PMA_includeJS($js_script_file);
}
// Below javascript Updates the title of the frameset if possible
?>
<script type="text/javascript">
// <![CDATA[
if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
    && typeof(parent.document.title) == 'string') {
    parent.document.title = '<?php echo (isset($title) ? PMA_sanitize(PMA_escapeJsString(htmlspecialchars($title))) : ''); ?>';
}
<?php
if (count($GLOBALS['js_script']) > 0) {
    echo implode("\n", $GLOBALS['js_script'])."\n";
}

foreach ($GLOBALS['js_events'] as $js_event) {
    echo "$(window.parent).bind('" . $js_event['event'] . "', "
        . $js_event['function'] . ");\n";
}
?>
// ]]>
</script>
<?php
// Reloads the navigation frame via JavaScript if required
PMA_reloadNavigation();

?>
