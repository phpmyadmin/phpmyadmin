<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * forms frameset
 * 
 * @uses    libraries/grab_globals.lib.php  import variables
 * @uses    libraries/common.lib.php        global fnctions
 * @uses    libraries/select_theme.lib.php  theme manager
 * @uses    libraries/relation.lib.php      table relations
 * @uses    $GLOBALS['strNoFrames']
 * @uses    $GLOBALS['cfg']['QueryHistoryDB']
 * @uses    $GLOBALS['cfg']['Server']['user']
 * @uses    $GLOBALS['cfg']['DefaultTabServer']     as src for the mainframe
 * @uses    $GLOBALS['cfg']['DefaultTabDatabase']   as src for the mainframe
 * @uses    $GLOBALS['cfg']['LeftWidth']            for left frame width
 * @uses    $GLOBALS['collation_connection']    from $_REQUEST (grab_globals.lib.php)
 *                                              or common.lib.php
 * @uses    $GLOBALS['available_languages'] from common.lib.php (select_lang.lib.php)
 * @uses    $GLOBALS['cookie_path']         from common.lib.php
 * @uses    $GLOBALS['is_https']            from common.lib.php
 * @uses    $GLOBALS['db']
 * @uses    $GLOBALS['charset']
 * @uses    $GLOBALS['lang']
 * @uses    $GLOBALS['text_dir']
 * @uses    $_ENV['HTTP_HOST']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_purgeHistory()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_VERSION
 * @uses    setcookie()
 * @uses    session_write_close()
 * @uses    time()
 * @uses    getenv()
 * @uses    header()                to send charset
 */

/**
 * Gets core libraries and defines some variables
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
/**
 * Includes the ThemeManager if it hasn't been included yet
 */
require_once('./libraries/select_theme.lib.php');
require_once('./libraries/relation.lib.php');

// hash for the window names, against window hijacking
$_SESSION['window_name_hash'] = time();

// free the session file, for the other frames to be loaded
session_write_close();

/**
 * Saves collation_connection (coming from main.php) in a cookie
 */
// (from grab_globals)
// TODO: move into common.lib.php line #360
if ( isset( $collation_connection ) ) {
    setcookie( 'pma_collation_connection', $collation_connection,
        time() + 60*60*24*30, $cookie_path, '', $is_https );
}


// Gets the host name
// loic1 - 2001/25/11: use the new globals arrays defined with php 4.1+
if (empty($HTTP_HOST)) {
    if (!empty($_ENV) && isset($_ENV['HTTP_HOST'])) {
        $HTTP_HOST = $_ENV['HTTP_HOST'];
    }
    else if (@getenv('HTTP_HOST')) {
        $HTTP_HOST = getenv('HTTP_HOST');
    }
    else {
        $HTTP_HOST = '';
    }
}


// purge querywindow history
$cfgRelation = PMA_getRelationsParam();
if ( $GLOBALS['cfg']['QueryHistoryDB'] && $cfgRelation['historywork'] ) {
    PMA_purgeHistory( $GLOBALS['cfg']['Server']['user'] );
}

$drops = array( 'lang', 'server', 'convcharset', 'collation_connection',
    'db', 'table' );

$url_querys = array();
// pass variables to child pages
foreach( $_GET as $key => $val ) {
    if ( ! in_array( $key, $drops ) ) {
        $url_querys[] = urlencode( $key ) . '=' . urlencode( $val );
    }
}
unset( $drops );

if ( count( $url_querys ) ) {
    $url_query = implode( '&amp;', $url_querys ) . '&amp;';
} else {
    $url_query = '';
}
unset( $url_querys );

if ( empty( $GLOBALS['db'] ) ) {
    $url_query .= PMA_generate_common_url();
    $main_target = $GLOBALS['cfg']['DefaultTabServer'];
} elseif ( empty( $GLOBALS['table'] ) ) {
    $url_query .= PMA_generate_common_url( $GLOBALS['db'] );
    $main_target = $GLOBALS['cfg']['DefaultTabDatabase'];
} else {
    $url_query .= PMA_generate_common_url( $GLOBALS['db'], $GLOBALS['table'] );
    $main_target = $GLOBALS['cfg']['DefaultTabTable'];
}

$main_target .= '?' . $url_query;

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
<title>phpMyAdmin <?php echo PMA_VERSION; ?> - <?php echo $HTTP_HOST; ?></title>
<meta http-equiv="Content-Type"
    content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />

<script type="text/javascript" language="javascript">
//<![CDATA[
    // definitions used in querywindow.js
    var common_query = '<?php echo PMA_generate_common_url('','','&');?>';
    var opendb_url = '<?php echo $GLOBALS['cfg']['DefaultTabDatabase']; ?>';
    var safari_browser = <?php echo PMA_USR_BROWSER_AGENT != 'SAFARI' ? 'true' : 'false' ?>;    
    var querywindow_height = <?php echo $GLOBALS['cfg']['QueryWindowHeight']; ?>;
    var querywindow_width = <?php echo $GLOBALS['cfg']['QueryWindowWidth']; ?>;
    var table = '<?php echo $GLOBALS['table']; ?>';
    var db    = '<?php echo $GLOBALS['db']; ?>';
    var pma_absolute_uri = '<?php echo $GLOBALS['cfg']['PmaAbsoluteUri']; ?>';
//]]>
</script>
<script src="libraries/querywindow.js" type="text/javascript"
    language="javascript"></script>
</head>
<frameset cols="<?php echo $GLOBALS['cfg']['LeftWidth']; ?>,*" rows="*" id="mainFrameset">
    <frame frameborder="0" id="leftFrame"
        src="left.php?<?php echo $url_query; ?>" 
        name="nav<?php echo $_SESSION['window_name_hash']; ?>"
        id="framenavigation" />
    <frame frameborder="0" id="rightFrame"
        src="<?php echo $main_target; ?>" 
        name="phpmain<?php echo $_SESSION['window_name_hash']; ?>" />
    <noframes>
        <body>
            <p><?php echo $GLOBALS['strNoFrames']; ?></p>
        </body>
    </noframes>
</frameset>
</html>