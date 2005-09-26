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
unset( $ctype );
?>
html {
    margin: 0;
    padding: 0;
}

a img {
    border: 0;
}

caption {
    font-size: <?php echo $font_size; ?>;
}

table .value {
    text-align: right;
    white-space: nowrap;
    white-space: pre;
    font-family: "Courier New", Courier, monospace;
}

table .name {
    text-align: left;
    font-weight: normal;
}

div#tablestatistics {
    border-bottom: 0.1em solid silver;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#tablestatistics table {
    float: left;
    margin-bottom: 0.5em;
    margin-right: 0.5em;
}

div#tablestatistics table caption {
    margin-right: 0.5em;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align: center;
    border-bottom: 0.1em solid silver;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#leftframelinks a {
    margin: 0.1em;
    padding: 0.2em;
    border: 0.1em solid <?php echo $cfg['BgcolorTwo']; ?>;
}

div#leftframelinks a:hover {
    border-color: gray;
    background-color: silver;
}

div#databaseList form {
    display: inline;
}

body#bodyquerywindow {
    margin: 0;
    padding: 0;
}

div#querywindowcontainer {
    margin: 0;
    padding: 0.5em;
    float: left;
}

/* Calendar */
table.calendar      { width: 100%; }
table.calendar td   { text-align: center; }
table.calendar td a { display: block; }

table.calendar td a:hover {
    background-color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

table.calendar th {
    background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>;
}

table.calendar td.selected {
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

img.calendar { border: none; }
form.clock   { text-align: center; }
/* end Calendar */

/* Options, eg. on import page */
fieldset.options {
    background-color: <?php echo $cfg['BgcolorOne']; ?>;
}
fieldset.options legend {
    background-color: transparent;
}

.nowrap    { white-space: nowrap; }
div.nowrap { margin: 0; padding: 0; }

li      { padding-bottom: 1em; }
li form { display: inline; }

/* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
button { display: inline; }

/* Textarea */
textarea { overflow: auto; }

.nospace { margin: 0; padding: 0; }

/* topmenu */
#topmenu {
    font-weight: bold;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    margin-right: 0.1em;
    margin-left: 0.1em;
    white-space: nowrap;
}

/* disbaled tabs */
span.tab {
    color: #666666;
}

/* disabled drop/empty tabs */
span.tabcaution {
    color: #ff6666;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color: #FF0000;
}
a.tabcaution:hover {
    color: #FFFFFF;
    background-color: #FF0000;
}

<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
/* active tab */
a.tabactive {
    border-bottom: 0.1em solid black;
    color: black;
}
<?php } else { ?>
#topmenu {
    margin-top: 0.5em;
    border-bottom: 0.1em solid black;
    padding: 0.1em 0.3em 0.1em 0.3em;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    background-color: #E5E5E5;
    border: 0.1em solid silver;
    border-bottom: 0.1em solid black;
    border-radius-topleft: 0.5em;
    border-radius-topright: 0.5em;
    -moz-border-radius-topleft: 0.5em;
    -moz-border-radius-topright: 0.5em;
    padding: 0.1em 0.2em 0.1em 0.2em;
}

/* enabled hover/active tabs */
a.tab:hover, a.tabcaution:hover, .tabactive {
    margin-right: 0;
    margin-left: 0;
    padding: 0.3em 0.3em 0.1em 0.3em;
}
a.tab:hover, .tabactive {
    background-color: #CCCCCC;
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
    cursor: url(themes/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


/* odd table rows 1,3,5,7,... */
table tbody tr.odd td,
table tbody tr.odd th {
    background-color: <?php echo $cfg['BgcolorOne']; ?>;
}

/* even table rows 2,4,6,8,... */
table tbody tr.even td,
table tbody tr.even th {
    background-color: <?php echo $cfg['BgcolorTwo']; ?>;
}


.value .attention {
    color: red;
    font-weight: bold;
}

.value .allfine {
    color: green;
}


/* serverstatus */
div#serverstatus table caption a.top {
    float: right;
}

table.data caption,
table.data th,
table.data td,
div#serverstatus table caption,
div#serverstatus table th,
div#serverstatus table td {
    padding: 0.1em 0.5em 0.1em 0.5em;
    margin: 0.1em;
    vertical-align: top;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float: left;
}

#serverstatussection,
.clearfloat {
    clear: both;
}

div#serverstatus table.statuslist .name {
    width: 18em;
}
div#serverstatus table.statuslist .value {
    width: 6em;
}

div#serverstatus table tbody td.descr a,
div#serverstatus table .tblFooters a {
    white-space: nowrap;
}
div#serverstatus div#statuslinks a:before,
div#serverstatus div#sectionlinks a:before,
div#serverstatus table tbody td.descr a:before,
div#serverstatus table .tblFooters a:before {
    content: '[';
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
    content: ']';
}

div#serverstatus table.statuslist {
    width: 100%;
    margin-bottom: 1em;
}
/* end serverstatus */
<?php

$_valid_css = array( 'left', 'right', 'print' );
if ( empty( $_REQUEST['js_frame'] ) || ! in_array( $_REQUEST['js_frame'], $_valid_css ) ) {
    $js_frame = 'left';
} else {
    $js_frame = $_REQUEST['js_frame'];
}
unset( $_valid_css );

if ( $js_frame == 'right' ) {
    echo PMA_SQP_buildCssData();
}

$_css_file = $GLOBALS['cfg']['ThemePath']
           . '/' . $GLOBALS['theme']
           . '/css/theme_' . $js_frame . '.css.php';

if ( file_exists( $_css_file ) ) {
    include( $_css_file );
}
unset( $_css_file );
?>
