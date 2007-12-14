<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
// sometimes, we lose $_REQUEST['js_frame']
define('PMA_FRAME',empty($_REQUEST['js_frame']) ? 'right' : $_REQUEST['js_frame']);

define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
require_once './libraries/sqlparser.lib.php';

// MSIE 6 (at least some unpatched versions) has problems loading CSS
// when zlib_compression is on
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER == '6'
 && (ini_get('zlib.output_compression'))) {
    ini_set('zlib.output_compression', 'Off');
}

if ($GLOBALS['text_dir'] === 'ltr') {
    $right = 'right';
    $left = 'left';
} else {
    $right = 'left';
    $left = 'right';
}

// Send correct type:
header('Content-Type: text/css; charset=ISO-8859-1');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

?>
html {
    font-size: <?php echo $_SESSION['PMA_Config']->get('fontsize'); ?>;
}

input, select, textarea {
    font-size: 1em;
}

div.item label {
    white-space: nowrap;
}

/* @deprecated */
.nowrap {
    white-space: nowrap;
}
div.nowrap {
    margin: 0;
    padding: 0;
}

<?php
if ($_SESSION['PMA_Theme']->checkVersion('2.7.0')) {
    ?>

form {
    margin: 0;
    padding: 0;
    display: inline;
}

a img {
    border: 0;
}


/* server privileges */
#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align: middle;
}
/* END server privileges */


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
    float: <?php echo $left; ?>;
    margin-bottom: 0.5em;
    margin-right: 0.5em;
}

div#tablestatistics table caption {
    margin-right: 0.5em;
}


/* left frame content */
body#body_leftFrame {
    background-color: <?php echo $GLOBALS['cfg']['LeftBgColor']; ?>;
}

div#pmalogo,
div#leftframelinks,
div#databaseList {
    text-align: center;
    border-bottom: 0.1em solid #669999;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#leftframelinks .icon {
    vertical-align: middle;
    padding: 0;
    margin: 0;
}

div#leftframelinks a:hover {
    background-color: #669999;
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

<?php if ($GLOBALS['cfg']['LeftPointerEnable']) { ?>
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
/* END left frame content */


/* querywindow */
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
/* END querywindow */


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

/* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
button { display: inline; }

/* Textarea */
textarea {
    overflow: auto;
    height: <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}


/* topmenu */

/* Gecko 1.7 bug (FF 1.0) */
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

<?php if ($GLOBALS['cfg']['LightTabs']) { ?>
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
    border-top-left-radius: 0.4em;
    border-top-right-radius: 0.4em;
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
    cursor: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */



/* data tables */
table caption,
table th,
table td {
    padding: 0.1em 0.5em 0.1em 0.5em;
    margin: 0;
    margin: 0.1em;
    vertical-align: top;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorOne']; ?>;
}

/* even table rows 2,4,6,8,... */
table tr.even th,
table tr.even {
    background-color: <?php echo $GLOBALS['cfg']['BgcolorTwo']; ?>;
}

/* marked table rows */
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
}
/* IE doesnt handles 'pre' right */
table [class=value] {
    white-space: pre;
}

.value {
    font-family: "Courier New", Courier, monospace;
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
} // end styles 2.7.0

if ($_SESSION['PMA_Theme']->checkVersion('2.7.1')) {
    ?>

/********************/
/* NEW in PMA 2.7.1 */
/********************/

body.loginform h1,
body.loginform a.logo {
    display: block;
    text-align: center;
}

form.login label {
    float: left;
    width: 10em;
    font-weight: bolder;
}


/* main page */
#maincontainer {
    background-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/logo_right.png);
    background-position: <?php echo $right; ?> bottom;
    background-repeat: no-repeat;
}

#mysqlmaininformation,
#pmamaininformation {
    float: <?php echo $left; ?>;
    width: 49%;
}

#maincontainer ul {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/item_<?php echo $GLOBALS['text_dir']; ?>.png);
    vertical-align: middle;
}

#maincontainer li {
    margin-bottom: 0.3em;
}
/* END main page */


<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?>
/* iconic view for ul items */
li#li_create_database {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_newdb.png);
}

li#li_select_lang {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_lang.png);
}

li#li_select_mysql_collation,
li#li_select_mysql_charset {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_asci.png);
}

li#li_select_theme{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_theme.png);
}

li#li_server_info,
li#li_server_version{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_host.png);
}

li#li_user_info{
    /* list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_rights.png); */
}

li#li_mysql_status{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_status.png);
}

li#li_mysql_variables{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_vars.png);
}

li#li_mysql_processes{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_process.png);
}

li#li_mysql_collations{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_asci.png);
}

li#li_mysql_engines{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_engine.png);
}

li#li_mysql_binlogs {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_tbl.png);
}

li#li_mysql_databases {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_db.png);
}

li#li_export {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_export.png);
}

li#li_import {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_import.png);
}

li#li_change_password {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_passwd.png);
}

li#li_log_out {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_loggoff.png);
}

li#li_pma_docs,
li#li_pma_wiki {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_docs.png);
}

li#li_phpinfo {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/php_sym.png);
}

li#li_pma_homepage {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_home.png);
}

li#li_mysql_privilegs{
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_rights.png);
}

li#li_switch_dbstats {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/b_dbstatistics.png);
}

li#li_flush_privileges {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_reload.png);
}
/* END iconic view for ul items */
<?php } /* end if $GLOBALS['cfg']['MainPageIconic'] */ ?>


#body_browse_foreigners {
    background-color:   <?php echo $cfg['LeftBgColor']; ?>;
    margin:             5px 5px 0 5px;
}

#bodyquerywindow {
    background-color:   <?php echo $cfg['LeftBgColor']; ?>;
}

#bodythemes {
    width: 500px;
    margin: auto;
    text-align: center;
}

#bodythemes img {
    border: 0.1em solid black;
}

#bodythemes a:hover img {
    border: 0.1em solid red;
}

#fieldset_select_fields {
    float: left;
}

#selflink {
    clear: both;
    display: block;
    margin-top: 1em;
    margin-bottom: 1em;
    width: 100%;
    border-top: 0.1em solid silver;
    text-align: <?php echo $right; ?>;
}

#table_innodb_bufferpool_usage,
#table_innodb_bufferpool_activity {
    float: <?php echo $left; ?>;
}

#div_mysql_charset_collations table {
    float: <?php echo $left; ?>;
}

#div_table_order {
    min-width: 48%;
    float: <?php echo $left; ?>;
}

#div_table_rename {
    min-width: 48%;
    float: <?php echo $left; ?>;
}

#div_table_copy {
    min-width: 48%;
    float: <?php echo $left; ?>;
}

#div_table_options {
    clear: both;
    min-width: 48%;
    float: <?php echo $left; ?>;
}

#qbe_div_table_list {
    float: <?php echo $left; ?>;
}

#qbe_div_sql_query {
    float: <?php echo $left; ?>;
}
    <?php
    $GLOBALS['cfg']['BgOne'] = $GLOBALS['cfg']['BgcolorOne'];
    $GLOBALS['cfg']['BgTwo'] = $GLOBALS['cfg']['BgcolorTwo'];
} // end styles 2.7.1

if ($_SESSION['PMA_Theme']->checkVersion('2.9')) {
    ?>

/********************/
/* NEW in PMA 2.9   */
/********************/

<?php if ($GLOBALS['cfg']['BrowseMarkerColor']) { ?>
/* marked items */
.marked a,
.marked {
    background: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

/* odd items 1,3,5,7,... */
.odd {
    background: <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

/* even items 2,4,6,8,... */
.even {
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

/* hovered items */
.odd:hover,
.even:hover,
.hover {
    background: <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/**
 * marks table rows/cells if the db field is in a where condition
 */
tr.condition th,
tr.condition td,
td.condition,
th.condition {
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}

label.desc {
    width: 30em;
    float: <?php echo $left; ?>;
}

body.loginform {
    text-align: center;
}

body.loginform div.container {
    text-align: <?php echo $left; ?>;
    width: 30em;
    margin: 0 auto;
}

#body_leftFrame #list_server {
    list-style-image: url(<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_host.png);
    list-style-position: inside;
    list-style-type: none;
    margin: 0;
    padding: 0;
}

#body_leftFrame #list_server li {
    margin: 0;
    padding: 0;
    font-size:          80%;
}
    <?php
} // end styles 2.9

$_SESSION['PMA_Theme_Manager']->printCss(PMA_FRAME);
?>
