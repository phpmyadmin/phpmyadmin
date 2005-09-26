<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets the variables sent to this script, retains the db name that may have
 * been defined as startup option and include a core library
 */
require_once('./libraries/grab_globals.lib.php');


/**
 * Gets a core script and starts output buffering work
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');
require_once('./libraries/ob.lib.php');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}

// garvin: For re-usability, moved http-headers
// to a seperate file. It can now be included by header.inc.php,
// queryframe.php, querywindow.php.

require_once('./libraries/header_http.inc.php');

/**
 * Displays the frame
 */
// Gets the font sizes to use
PMA_setFontSizes();

/**
 * Relations
 */
require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();
echo "<?xml version=\"1.0\" encoding=\"" . $GLOBALS['charset'] . "\"?".">"; // remove vertical scroll bar bug in ie
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
<title>phpMyAdmin</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
<base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> />
<link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php?<?php echo PMA_generate_common_url(); ?>&amp;js_frame=left&amp;num_dbs=0" />
<?php
if ($cfg['QueryFrame'] && $cfg['QueryFrameJS']) {
?>
<script type="text/javascript" language="javascript">
<!--
var querywindow = '';

function open_querywindow(url) {

    if (!querywindow.closed && querywindow.location) {
        querywindow.focus();
    } else {
        querywindow=window.open(url + '&db=' + document.queryframeform.db.value + '&table=' + document.queryframeform.table.value, '','toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=yes,resizable=yes,width=<?php echo $cfg['QueryWindowWidth']; ?>,height=<?php echo $cfg['QueryWindowHeight']; ?>');
    }

    if (!querywindow.opener) {
        querywindow.opener = blank;
    }

    if (window.focus) {
        querywindow.focus();
    }

    return false;
}

/**
  * function resizeRowsLeft()
  * added 2004-07-20 by Michael Keck <mail@michaelkeck.de>
  *                  - this function checks the complete frameset of
  *                    index.php (parent.frames)
  *                  - gets the offsetHeight of qfcontainer
  *                  - sets a new frameset.rows - definition for the
  *                    frameset 'leftFrameset' in 'index.php' dynamic.
  * this script was tested on
  *   IE 6, Opera 7.53, Netsacpe 7.1 and Firefox 0.9
  *   and should work on all other DOM-Browsers and old IE-Browsers.
  *   It will never work on Netscape smaller Version 6 and IE smaller Version 4.
  * Please give me feedback if any browser doesn't work with this script
  *   mailto:mail@michaelkeck.de?subject=resizeFrames - Browser: [the browser]
**/

function resizeRowsLeft() {
    if (document.getElementById('qfcontainer')) { // dom browsers
        // get the height of the div-element 'qfcontainer'
        // we must add 10 (px) for framespacing
        newHeight = document.getElementById('qfcontainer').offsetHeight+10;
        // check if the frameset exists
        // please see index.php and check the frameset-definitions
        if (parent.document.getElementById('mainFrameset') && parent.document.getElementById('leftFrameset')) {
            parent.document.getElementById('leftFrameset').rows=newHeight+',*';
        }
    } else {
        if (document.all) { // older ie-browsers
            // get the height of the div-element 'qfcontainer'
            // we must add 10 (px) for framespacing
            newHeight=document.all('qfcontainer').offsetHeight+10;
            // check if the frameset exists
            // please see index.php and check the frameset-definitions
            if (parent.leftFrameset) {
                parent.leftFrameset.rows=newHeight+',*';
            }
        }
    }
}

//-->
</script>
<?php
    // setup the onload handler for resizing frames
    $js_frame_onload=' onload="resizeRowsLeft();"';
}
if ($cfg['QueryFrame']) {
?>
<script type="text/javascript" language="javascript">
<!--
// added 2004-09-16 by Michael Keck (mkkeck)
//                  bug: #1027321
//                       drop-down databases list keep focus on database change
// modified 2004-11-06: bug #1046434 (Light mode does not work)
var focus_removed = false;
function remove_focus_select() {
    focus_removed = false;
    set_focus_to_nav();
}
function set_focus_to_nav() {
    if (typeof(parent.frames.nav)!='undefined' && focus_removed!=true) {
        parent.frames.nav.focus();
        focus_removed=true;
    } else {
        focus_removed=false;
        setTimeout("set_focus_to_nav();",500);
    }
}
//-->
</script>
<?php
}
?>
</head>

<body id="body_queryFrame" bgcolor="<?php echo $cfg['LeftBgColor']; ?>"<?php echo ((isset($js_frame_onload) && $js_frame_onload!='') ? $js_frame_onload : ''); ?>>
<div id="qfcontainer">
<?php
/**
 * Get the list and number of available databases.
 * Skipped if no server selected: in this case no database should be displayed
 * before the user choose among available ones at the welcome screen.
 */
if ($server > 0) {
    PMA_availableDatabases(); // this function is defined in "common.lib.php"
} else {
    $num_dbs = 0;
}

require 'libraries/left_header.inc.php';

?>
<form name="queryframeform" action="queryframe.php" method="get">
    <input type="hidden" name="db" value="" />
    <input type="hidden" name="table" value="" />
    <input type="hidden" name="framename" value="queryframe" />
</form>
<form name="hashform" action="queryframe.php">
    <input type="hidden" name="hash" value="<?php echo $hash; ?>" />
</form>
</body>
</html>

<?php
/**
 * Close MySql connections
 */
if (isset($dbh) && $dbh) {
    @PMA_DBI_close($dbh);
}
if (isset($userlink) && $userlink) {
    @PMA_DBI_close($userlink);
}


/**
 * Sends bufferized data
 */
if (isset($cfg['OBGzip']) && $cfg['OBGzip']
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
