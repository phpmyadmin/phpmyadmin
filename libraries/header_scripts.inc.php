<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: header.inc.php 10719 2007-10-04 15:03:44Z cybot_tm $
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

if (in_array('functions.js', $GLOBALS['js_include'])) {
    $GLOBALS['js_messages']['strFormEmpty'] = $GLOBALS['strFormEmpty'];
    $GLOBALS['js_messages']['strNotNumber'] = $GLOBALS['strNotNumber'];

    if (!$is_superuser && !$GLOBALS['cfg']['AllowUserDropDatabase']) {
        $GLOBALS['js_messages']['strNoDropDatabases'] = $GLOBALS['strNoDropDatabases'];
    } else {
        $GLOBALS['js_messages']['strNoDropDatabases'] = '';
    }

    if ($GLOBALS['cfg']['Confirm']) {
        $GLOBALS['js_messages']['strDoYouReally'] = $GLOBALS['strDoYouReally'];
        $GLOBALS['js_messages']['strDropDatabaseStrongWarning'] = $GLOBALS['strDropDatabaseStrongWarning'];

        // rajk - for blobstreaming
        $GLOBALS['js_messages']['strBLOBRepositoryDisableStrongWarning'] = $GLOBALS['strBLOBRepositoryDisableStrongWarning'];
        $GLOBALS['js_messages']['strBLOBRepositoryDisableAreYouSure'] = sprintf($GLOBALS['strBLOBRepositoryDisableAreYouSure'], $GLOBALS['db']);
    } else {
        $GLOBALS['js_messages']['strDoYouReally'] = '';
        $GLOBALS['js_messages']['strDropDatabaseStrongWarning'] = '';

        // rajk - for blobstreaming
        $GLOBALS['js_messages']['strBLOBRepositoryDisableStrongWarning'] = '';
        $GLOBALS['js_messages']['strBLOBRepositoryDisableAreYouSure'] = '';
    }
} elseif (in_array('indexes.js', $GLOBALS['js_include'])) {
    $GLOBALS['js_messages']['strFormEmpty'] = $GLOBALS['strFormEmpty'];
    $GLOBALS['js_messages']['strNotNumber'] = $GLOBALS['strNotNumber'];
}

if (in_array('server_privileges.js', $GLOBALS['js_include'])) {
    $GLOBALS['js_messages']['strHostEmpty'] = $GLOBALS['strHostEmpty'];
    $GLOBALS['js_messages']['strUserEmpty'] = $GLOBALS['strUserEmpty'];
    $GLOBALS['js_messages']['strPasswordEmpty'] = $GLOBALS['strPasswordEmpty'];
    $GLOBALS['js_messages']['strPasswordNotSame'] = $GLOBALS['strPasswordNotSame'];
}

$GLOBALS['js_include'][] = 'tooltip.js';

$GLOBALS['js_events'][] = array(
    'object'    => 'window',
    'event'     => 'load',
    'function'  => 'PMA_TT_init',
);

foreach ($GLOBALS['js_include'] as $js_script_file) {
    echo '<script src="./js/' . $js_script_file . '" type="text/javascript"></script>' . "\n";
}
?>
<script type="text/javascript">
// <![CDATA[
// Updates the title of the frameset if possible (ns4 does not allow this)
if (typeof(parent.document) != 'undefined' && typeof(parent.document) != 'unknown'
    && typeof(parent.document.title) == 'string') {
    parent.document.title = '<?php echo PMA_sanitize(PMA_escapeJsString($title)); ?>';
}

var PMA_messages = new Array();
<?php
foreach ($GLOBALS['js_messages'] as $name => $js_message) {
    echo "PMA_messages['" . $name . "'] = '" . PMA_escapeJsString($js_message) . "';\n";
}

foreach ($GLOBALS['js_events'] as $js_event) {
    echo "window.parent.addEvent(" . $js_event['object'] . ", '" . $js_event['event'] . "', "
        . $js_event['function'] . ");\n";
}
?>
// ]]>
</script>
<?php
// Reloads the navigation frame via JavaScript if required
PMA_reloadNavigation();

?>
