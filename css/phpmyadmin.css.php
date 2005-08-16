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