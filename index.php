<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * forms frameset
 *
 * @version $Id$
 * @uses    $GLOBALS['strNoFrames']
 * @uses    $GLOBALS['cfg']['QueryHistoryDB']
 * @uses    $GLOBALS['cfg']['Server']['user']
 * @uses    $GLOBALS['cfg']['DefaultTabServer']     as src for the mainframe
 * @uses    $GLOBALS['cfg']['DefaultTabDatabase']   as src for the mainframe
 * @uses    $GLOBALS['cfg']['NaviWidth']            for navi frame width
 * @uses    $GLOBALS['collation_connection']    from $_REQUEST (grab_globals.lib.php)
 *                                              or common.inc.php
 * @uses    $GLOBALS['available_languages'] from common.inc.php (select_lang.lib.php)
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['charset']
 * @uses    $GLOBALS['lang']
 * @uses    $GLOBALS['text_dir']
 * @uses    $_ENV['HTTP_HOST']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_purgeHistory()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_VERSION
 * @uses    session_write_close()
 * @uses    time()
 * @uses    PMA_getenv()
 * @uses    header()                to send charset
 * @package phpMyAdmin
 */

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';

/**
 * Includes the ThemeManager if it hasn't been included yet
 */
require_once './libraries/relation.lib.php';

// free the session file, for the other frames to be loaded
session_write_close();

// Gets the host name
if (empty($HTTP_HOST)) {
    if (PMA_getenv('HTTP_HOST')) {
        $HTTP_HOST = PMA_getenv('HTTP_HOST');
    } else {
        $HTTP_HOST = '';
    }
}


// purge querywindow history
$cfgRelation = PMA_getRelationsParam();
if ($GLOBALS['cfg']['QueryHistoryDB'] && $cfgRelation['historywork']) {
    PMA_purgeHistory($GLOBALS['cfg']['Server']['user']);
}
unset($cfgRelation);


/**
 * pass variables to child pages
 */
$drops = array('lang', 'server', 'convcharset', 'collation_connection',
    'db', 'table');

foreach ($drops as $each_drop) {
    if (array_key_exists($each_drop, $_GET)) {
        unset($_GET[$each_drop]);
    }
}
unset($drops, $each_drop);

if (! strlen($GLOBALS['db'])) {
    $main_target = $GLOBALS['cfg']['DefaultTabServer'];
} elseif (! strlen($GLOBALS['table'])) {
    $_GET['db'] = $GLOBALS['db'];
    $main_target = $GLOBALS['cfg']['DefaultTabDatabase'];
} else {
    $_GET['db'] = $GLOBALS['db'];
    $_GET['table'] = $GLOBALS['table'];
    $main_target = $GLOBALS['cfg']['DefaultTabTable'];
}

$url_query = PMA_generate_common_url($_GET);

if (isset($GLOBALS['target']) && is_string($GLOBALS['target']) && !empty($GLOBALS['target']) && in_array($GLOBALS['target'], $goto_whitelist)) {
    $main_target = $GLOBALS['target'];
}

$main_target .= $url_query;

$lang_iso_code = $GLOBALS['available_languages'][$GLOBALS['lang']][2];


// start output
header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $lang_iso_code; ?>"
    lang="<?php echo $lang_iso_code; ?>"
    dir="<?php echo $GLOBALS['text_dir']; ?>">
<head>
<link rel="icon" href="./favicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
<title>phpMyAdmin <?php echo PMA_VERSION; ?> -
    <?php echo htmlspecialchars($HTTP_HOST); ?></title>
<meta http-equiv="Content-Type"
    content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
<meta name="robots" content="noindex,nofollow" />
<script type="text/javascript">
// <![CDATA[
    // definitions used in common.js
    var common_query = '<?php echo PMA_escapeJsString(PMA_generate_common_url('', '', '&'));?>';
    var opendb_url = '<?php echo PMA_escapeJsString($GLOBALS['cfg']['DefaultTabDatabase']); ?>';
    var safari_browser = <?php echo PMA_USR_BROWSER_AGENT == 'SAFARI' ? 'true' : 'false' ?>;
    var querywindow_height = <?php echo PMA_escapeJsString($GLOBALS['cfg']['QueryWindowHeight']); ?>;
    var querywindow_width = <?php echo PMA_escapeJsString($GLOBALS['cfg']['QueryWindowWidth']); ?>;
    var collation_connection = '<?php echo PMA_escapeJsString($GLOBALS['collation_connection']); ?>';
    var lang = '<?php echo PMA_escapeJsString($GLOBALS['lang']); ?>';
    var server = '<?php echo PMA_escapeJsString($GLOBALS['server']); ?>';
    var table = '<?php echo PMA_escapeJsString($GLOBALS['table']); ?>';
    var db    = '<?php echo PMA_escapeJsString($GLOBALS['db']); ?>';
    var token = '<?php echo PMA_escapeJsString($_SESSION[' PMA_token ']); ?>';
    var text_dir = '<?php echo PMA_escapeJsString($GLOBALS['text_dir']); ?>';
    var pma_absolute_uri = '<?php echo PMA_escapeJsString($GLOBALS['cfg']['PmaAbsoluteUri']); ?>';
    var pma_text_default_tab = '<?php echo PMA_escapeJsString(PMA_getTitleForTarget($GLOBALS['cfg']['DefaultTabTable'])); ?>';
    var pma_text_left_default_tab = '<?php echo PMA_escapeJsString(PMA_getTitleForTarget($GLOBALS['cfg']['LeftDefaultTabTable'])); ?>';

    // for content and navigation frames

    var frame_content = 0;
    var frame_navigation = 0;
    function getFrames() {
<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
        frame_content = window.frames[1];
        frame_navigation = window.frames[0];
<?php } else { ?>
        frame_content = window.frames[0];
        frame_navigation = window.frames[1];
<?php } ?>
    }
    var onloadCnt = 0;
    var onLoadHandler = window.onload;
    window.onload = function() {
        if (onloadCnt == 0) {
            if (typeof(onLoadHandler) == "function") {
                onLoadHandler();
            }
            if (typeof(getFrames) != 'undefined' && typeof(getFrames) == 'function') {
                getFrames();
            }
            onloadCnt++;
        }
    };
// ]]>
</script>
<script src="./js/common.js" type="text/javascript"></script>
</head>
<frameset cols="<?php
if ($GLOBALS['text_dir'] === 'rtl') {
    echo '*,';
}
echo $GLOBALS['cfg']['NaviWidth'];
if ($GLOBALS['text_dir'] === 'ltr') {
    echo ',*';
}
?>" rows="*" id="mainFrameset">
    <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    <frame frameborder="0" id="frame_navigation"
        src="navigation.php<?php echo $url_query; ?>"
        name="frame_navigation" />
    <?php } ?>
    <frame frameborder="0" id="frame_content"
        src="<?php echo $main_target; ?>"
        name="frame_content" />
    <?php if ($GLOBALS['text_dir'] === 'rtl') { ?>
    <frame frameborder="0" id="frame_navigation"
        src="navigation.php<?php echo $url_query; ?>"
        name="frame_navigation" />
    <?php } ?>
    <noframes>
        <body>
            <p><?php echo $GLOBALS['strNoFrames']; ?></p>
        </body>
    </noframes>
</frameset>
</html>
