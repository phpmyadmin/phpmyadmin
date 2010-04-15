<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
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
<script type="text/javascript">
//<![CDATA[
try {
    // can't access this if on a different domain
    var topdomain = top.document.domain;
    // double-check just for sure
    if (topdomain != self.document.domain) {
        alert("Redirecting...");
        top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
    }
}
catch(e) {
    alert("Redirecting... (error: " + e);
    top.location.replace(self.document.URL.substring(0, self.document.URL.lastIndexOf("/")+1));
}
//]]>
</script>
<?php
}
// generate title
$title = str_replace(
            array(
                '@HTTP_HOST@',
                '@SERVER@',
                '@VERBOSE@',
                '@VSERVER@',
                '@DATABASE@',
                '@TABLE@',
                '@PHPMYADMIN@',
                ),
            array(
                PMA_getenv('HTTP_HOST') ? PMA_getenv('HTTP_HOST') : '',
                isset($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['Server']['host'] : '',
                isset($GLOBALS['cfg']['Server']['verbose']) ? $GLOBALS['cfg']['Server']['verbose'] : '',
                !empty($GLOBALS['cfg']['Server']['verbose']) ? $GLOBALS['cfg']['Server']['verbose'] : (isset($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['Server']['host'] : ''),
                $GLOBALS['db'],
                $GLOBALS['table'],
                'phpMyAdmin ' . PMA_VERSION,
                ),
            !empty($GLOBALS['table']) ? $GLOBALS['cfg']['TitleTable'] :
            (!empty($GLOBALS['db']) ? $GLOBALS['cfg']['TitleDatabase'] :
            (!empty($GLOBALS['cfg']['Server']['host']) ? $GLOBALS['cfg']['TitleServer'] :
            $GLOBALS['cfg']['TitleDefault']))
            );
// here, the function does not exist with this configuration: $cfg['ServerDefault'] = 0;
$is_superuser    = function_exists('PMA_isSuperuser') && PMA_isSuperuser();

$GLOBALS['js_include'][] = 'tooltip.js';
$params = array('lang' => $GLOBALS['lang']);
if (isset($GLOBALS['db'])) {
    $params['db'] = $GLOBALS['db'];
}
$GLOBALS['js_include'][] = 'messages.php' . PMA_generate_common_url($params);

$GLOBALS['js_events'][] = array(
    'event'     => 'load',
    'function'  => 'PMA_TT_init',
);

/**
 * Here we add a timestamp when loading the file, so that users who
 * upgrade phpMyAdmin are not stuck with older .js files in their
 * browser cache. This produces an HTTP 304 request for each file.
 */
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
    parent.document.title = '<?php echo PMA_sanitize(PMA_escapeJsString($title)); ?>';
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
