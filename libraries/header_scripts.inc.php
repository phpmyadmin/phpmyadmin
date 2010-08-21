<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package phpMyAdmin
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
?>
<script src="./js/cross_framing_protection.js" type="text/javascript"></script>
<?php
}
// generate title
$title = PMA_expandUserString(
            !empty($GLOBALS['table']) ? $GLOBALS['cfg']['TitleTable'] :
            (!empty($GLOBALS['db']) ? $GLOBALS['cfg']['TitleDatabase'] :
            (!empty($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['TitleServer'] :
            $GLOBALS['cfg']['TitleDefault']))
            );
// here, the function does not exist with this configuration: $cfg['ServerDefault'] = 0;
$is_superuser    = function_exists('PMA_isSuperuser') && PMA_isSuperuser();

$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'jquery.qtip-1.0.0.min.js';
$params = array('lang' => $GLOBALS['lang']);
if (isset($GLOBALS['db'])) {
    $params['db'] = $GLOBALS['db'];
}
$GLOBALS['js_include'][] = 'messages.php' . PMA_generate_common_url($params);

/**
 * Here we add a timestamp when loading the file, so that users who
 * upgrade phpMyAdmin are not stuck with older .js files in their
 * browser cache. This produces an HTTP 304 request for each file.
 */

// avoid loading twice a js file
$GLOBALS['js_include'] = array_unique($GLOBALS['js_include']);
foreach ($GLOBALS['js_include'] as $js_script_file) {
    if (strpos($js_script_file, '?') === FALSE) {
        echo '<script src="./js/' . $js_script_file . '?ts=' . filemtime('./js/' . $js_script_file) . '" type="text/javascript"></script>' . "\n";
    } else {
        echo '<script src="./js/' . $js_script_file . '" type="text/javascript"></script>' . "\n";
    }
}
?>
<script type="text/javascript">
// <![CDATA[
// Updates the title of the frameset if possible (ns4 does not allow this)
if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
    && typeof(parent.document.title) == 'string') {
    parent.document.title = '<?php echo PMA_sanitize(PMA_escapeJsString(htmlspecialchars($title))); ?>';
}

<?php
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
