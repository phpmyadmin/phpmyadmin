<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

chdir('..');
$is_minimum_common = TRUE;
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/sqlparser.lib.php');

if ( $GLOBALS['text_dir'] === 'ltr' ) {
    $right = 'right';
    $left = 'left';
} else {
    $right = 'left';
    $left = 'right';
}

// Gets the default font sizes
// garvin: TODO: Should be optimized to not include the whole common.lib.php bunch
// but only functions used to determine browser heritage.
PMA_setFontSizes();

// Send correct type:
header('Content-Type: text/css; charset=ISO-8859-1');

?>
html {
    margin: 0;
    padding: 0;
}

form {
    margin: 0;
    padding: 0;
}

a img {
    border: 0;
}

#mainheader {
    border: 0.1px solid transparent;
    border-bottom: 0.1em solid gray;
    margin-bottom: 1em;
}

#pmalogoright {
    float: <?php echo $right; ?>;
}

#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align: middle;
}

/* leave some space between icons and text */
.icon {
    vertical-align: middle;
    margin-right: 0.3em;
    margin-left: 0.3em;
}
/* no extra space in table cells */
td .icon {
    margin: 0;
}

.selectallarrow {
    margin-<?php echo $right; ?>: 0.3em;
    margin-<?php echo $left; ?>: 0.6em;
}

div#tablestatistics {
    border-bottom: 0.1em solid #669999;
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
    border-bottom: 0.1em solid #669999;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#leftframelinks img {
    vertical-align: middle;
}

div#leftframelinks a {
    padding: 0.1em;
}

div#leftframelinks a:hover {
    background-color: #669999;
}

body#bodyquerywindow {
    margin: 0;
    padding: 0;
}

div#querywindowcontainer {
    margin: 0;
    padding: 0;
    width: 100%;
}

div#querywindowcontainer fieldset {
    margin-top: 0;
}


body#body_leftFrame {
    background-color: <?php echo $GLOBALS['cfg']['LeftBgColor']; ?>;
}

/* leftdatabaselist */
div#left_tableList ul {
    list-style-type: none;
    list-style-position: outside;
    margin: 0;
    padding: 0;
    background-color: <?php echo $GLOBALS['cfg']['LeftBgColor']; ?>;
}

div#left_tableList li {
    margin: 0;
    padding: 0;
    white-space: nowrap;
}

<?php if ( $GLOBALS['cfg']['LeftPointerEnable'] ) { ?>
div#left_tableList li:hover {
    background-color: <?php echo $GLOBALS['cfg']['LeftPointerColor']; ?>;
}
<?php } ?>

div#left_tableList img {
    padding: 0;
    vertical-align: middle;
}

div#left_tableList ul ul {
    margin-left: 0em;
    padding-left: 0.1em;
    border-left: 0.1em solid #669999;
    padding-bottom: 0.1em;
    border-bottom: 0.1em solid #669999;
}

/* querybox */

/* Gecko bug */
div[class=formelementrow],
div[id=queryfieldscontainer] {
    border: 1px solid transparent;
}

div#sqlquerycontainer {
    float: left;
    width: 69%;
    /* height: 15em; */
}

div#tablefieldscontainer  {
    float: right;
    width: 29%;
    /* height: 15em; */
}

div#tablefieldscontainer select  {
    width: 100%;
    /* height: 12em; */
}

textarea#sqlquery {
    width: 100%;
    /* height: 100%; */
}

div#queryboxcontainer div#bookmarkoptions {
    margin-top: 0.5em;
}
/* end querybox */


fieldset .formelement {
    line-height: 2.4em;
    float: left;
    margin-right: 0.5em;
    /* IE */
    white-space: nowrap;
}
/* revert for Gecko */
fieldset div[class=formelement] {
    white-space: normal;
}

/* IE */
fieldset .formelement input,
fieldset .formelement select {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
/* revert for Gecko */
fieldset div[class=formelement] input,
fieldset div[class=formelement] select {
    margin-top: auto;
    margin-bottom: auto;
    height: auto;
}

#fieldsetexport #exportoptions {
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
fieldset {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorOne']; ?>;
}
fieldset legend {
    background-color: transparent;
}

.nowrap    { white-space: nowrap; }
div.nowrap { margin: 0; padding: 0; }

/* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
button { display: inline; }

/* Textarea */
textarea { overflow: auto; }

.nospace { margin: 0; padding: 0; }


/* topmenu */

/* Gecko bug */
#topmenucontainer {
    border: 1px solid <?php echo $GLOBALS['cfg']['RightBgColor']; ?>;
}

ul#topmenu {
    font-weight: bold;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

ul#topmenu li {
    float: left;
    margin: 0;
    padding: 0;
    vertical-align: middle;
}

#topmenu img {
    vertical-align: middle;
    margin-right: 0.1em;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    display: block;
    margin: 0.2em 0.2em 0 0.2em;
    padding: 0.2em 0.2em 0 0.2em;
    white-space: nowrap;
}

/* disabled tabs */
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
    color: black;
}
<?php } else { ?>
#topmenu {
    margin-top: 0.5em;
    padding: 0.1em 0.3em 0.1em 0.3em;
}

ul#topmenu li {
    border-bottom: 1pt solid black;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorOne']; ?>;
    border: 1pt solid <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
    border-bottom: 0;
    border-radius-topleft: 0.4em;
    border-radius-topright: 0.4em;
    -moz-border-radius-topleft: 0.4em;
    -moz-border-radius-topright: 0.4em;
}

/* enabled hover/active tabs */
a.tab:hover, a.tabcaution:hover, .tabactive, .tabactive:hover {
    margin: 0;
    padding: 0.2em 0.4em 0.2em 0.4em;
    text-decoration: none;
}

a.tab:hover, .tabactive {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
    cursor: url(themes/original/img/error.ico), url(../themes/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


/* data tables */
table.data caption,
table.data th,
table.data td {
    padding: 0.1em 0.5em 0.1em 0.5em;
    margin: 0;
    margin: 0.1em;
    vertical-align: top;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorOne']; ?>;
    text-align: left;
}

/* even table rows 2,4,6,8,... */
table tr.even th,
table tr.even {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
    text-align: left;
}

/* marked tbale rows */
table tr.marked th,
table tr.marked {
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover,
table tr.even:hover,
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th,
table tr.hover {
    background-color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

table .value {
    text-align: right;
    white-space: nowrap;
    font-family: "Courier New", Courier, monospace;
}
/* IE doesnt handles 'pre' right */
table [class=value] {
    white-space: pre;
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

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float: left;
}

#serverstatussection,
.clearfloat {
    clear: both;
}
div#serverstatussection table {
    width: 100%;
    margin-bottom: 1em;
}
div#serverstatussection table .name {
    width: 18em;
}
div#serverstatussection table .value {
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
/* end serverstatus */


img.lightbulb {
    cursor: pointer;
}
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
