<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

chdir('..');
$is_minimum_common = TRUE;
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/sqlparser.lib.php');

// Gets the default font sizes
// garvin: TODO: Should be optimized to not include the whole common.lib.php bunch
// but only functions used to determine browser heritage.
PMA_setFontSizes();

$ctype = 'css';
require_once('./libraries/header_http.inc.php');

if (!isset($js_frame)) {
    $js_frame = 'left';
}

if ($js_frame == 'left') {
/************************************************************************************
 * LEFT FRAME
 ************************************************************************************/
    // 2004-05-30: Michael Keck (mail@michaelkeck.de)
    //             Check, if theme_left.css.php exists and include
    $tmp_file = $GLOBALS['cfg']['ThemePath'] . '/' . $theme . '/css/theme_left.css.php';
    if (@file_exists($tmp_file)) {
        include($tmp_file);
    } // end of include theme_left.css.php
} elseif ($js_frame == 'print') {
/************************************************************************************
 * PRINT VIEW
 ************************************************************************************/
    // 2004-05-30: Michael Keck (mail@michaelkeck.de)
    //             Check, if theme_print.css.php exists and include
    $tmp_file = $GLOBALS['cfg']['ThemePath'] . '/' . $theme . '/css/theme_print.css.php';
    if (@file_exists($tmp_file)) {
        include($tmp_file);
    } // end of include theme_print.css.php
} else {
/************************************************************************************
 * RIGHT FRAME
 ************************************************************************************/
    // 2004-05-30: Michael Keck (mail@michaelkeck.de)
    //             Check, if theme_right.css.php exists and include
    $tmp_file = $GLOBALS['cfg']['ThemePath'] . '/' . $theme . '/css/theme_right.css.php';
    if (@file_exists($tmp_file)) {
        include($tmp_file);
    } // end of include theme_right.css.php
    echo PMA_SQP_buildCssData();
}

?>

/* Calendar */
table.calendar {
    width: 100%;
}

table.calendar td {
    text-align: center;
}

table.calendar td a {
    display: block;
}

table.calendar td a:hover {
    background-color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

table.calendar th {
    background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>;
}

table.calendar td.selected {
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

img.calendar {
    border: none;
}

form.clock {
    text-align: center;
}

.nowrap {
    white-space: nowrap;
}

div.nowrap {
    margin: 0px;
    padding: 0px;
}

li {
    padding-bottom: 1em;
}

li form {
    display: inline;
}

ul.main {
    margin: 0px;
    padding-left:2em;
    padding-right:2em;
}

/* no longer needed
ul.main li {
    list-style-image: url(../images/dot_violet.png);
    padding-bottom: 0.1em;
}
*/

button {
    /* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
    display: inline;
}

/* Tabs */

/* For both light and non light */
.tab {
    white-space: nowrap;
    font-weight: bolder;
}

/* For non light */
td.tab {
    width: 64px;
    text-align: center;
    background-color: #dfdfdf;
}

td.tab a {
    display: block;
}

/* For light */
div.tab { }

/* Highlight active tab */
td.activetab {
    background-color: silver;
}

/* Textarea */

textarea {
    overflow: auto;
}

.nospace {
    margin: 0px;
    padding: 0px;
}
