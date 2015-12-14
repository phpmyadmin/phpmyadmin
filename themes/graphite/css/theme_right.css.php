<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Original
 *
 * @package phpMyAdmin-theme
 * @subpackage Original
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */
html {
    font-size: <?php echo $GLOBALS['PMA_Config']->get('fontsize'); ?>;
}

input, select, textarea {
    font-size: 1em;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding:            0;
    margin:             0.5em;
    color:              <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea, tt, pre, code {
    font-family:        <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
h1 {
    font-size:          140%;
    font-weight:        bold;
}

h2 {
    font-size:          120%;
    font-weight:        bold;
}

h3 {
    font-weight:        bold;
}

a, a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #008;
    cursor:             pointer;
}

a:hover {
    text-decoration:    underline;
    color:              #008;
}

dfn {
    font-style:         normal;
}

dfn:hover {
    font-style:         normal;
    cursor:             help;
}

th {
    font-weight:        bold;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}
thead th a {
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?> !important;
}

a img {
    border:             0;
}

hr {
    color:              <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background-color:   <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    border:             0;
    height:             1px;
}

form {
    padding:            0;
    margin:             0;
    display:            inline;
}

textarea {
    overflow:           visible;
    height:             <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}

fieldset {
    clear: both;
    margin-top:         1em;
    border:             <?php echo $GLOBALS['cfg']['MainColor']; ?> solid 2px;
    padding:            0.5em;
    background:         <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    -moz-border-radius: 0.5em;
    border-radius:      0.5em;
}

fieldset fieldset {
    margin:             0.8em;
}

fieldset legend {
    font-weight:        bold;
    color:              #444444;
    background-color:   <?php echo 'OPERA' != PMA_USR_BROWSER_AGENT ? 'transparent' : $GLOBALS['cfg']['BgOne']; ?>;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}

table {
    border-collapse:collapse;
    margin: 0.2em;
}

table caption {
    padding:            0.1em 0.5em 0.1em 0.5em;
}

th,
td {
    padding:            0.1em 0.5em 0.1em 0.5em;
    margin:             0.1em;
    vertical-align:     top;
    border: 1px solid black;
}
table.navigation {
    clear: both;
}

table.navigation td, #actions_panel table td {
    border: none;
}

img,
input,
select,
button {
    vertical-align:     middle;
}


/******************************************************************************/
/* classes */
div.tools,
fieldset.tblFooters {
    margin:             0 1em 1em 1em;
    border:             2px solid #000;
    border-top: none;
    width: auto;
    padding: 0.2em;
    float: <?php echo $left; ?>;
    clear:              both;
    -moz-border-radius: 0 0 0.5em 0.5em;
    border-radius:      0 0 0.5em 0.5em;
}

div.null_div {
    height: 20px;
    text-align: center;
    font-style:normal;
    min-width:60px;
}

fieldset .formelement {
    float:              <?php echo $left; ?>;
    margin-<?php echo $right; ?>:       0.5em;
    /* IE */
    white-space:        nowrap;
}

/* revert for Gecko */
fieldset div[class=formelement] {
    white-space:        normal;
}

button.mult_submit {
    border:             none;
    background-color:   transparent;
}

/* odd items 1,3,5,7,... */
tr.odd th,
tr.odd {
    background: <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

/* even items 2,4,6,8,... */
tr.even th,
tr.even {
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}
tr.even th, tr.odd th, fieldset th  {
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

/* odd table rows 1,3,5,7,... */
tr.odd,
tr.even {
    text-align:         <?php echo $left; ?>;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerEnable']) { ?>
/* marked table rows */
tr.marked th,
tr.marked {
    background:   <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['BrowsePointerEnable']) { ?>
/* hovered items */
.structure_actions_dropdown {
    background: <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
tr.even:hover th,
tr.odd:hover th,
tr:hover {
    background:   <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
<?php } ?>

/**
 * marks table rows/cells if the db field is in a where condition
 */
tr.condition th,
tr.condition td,
td.condition,
th.condition {
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}

table .value {
    text-align:         <?php echo $right; ?>;
    white-space:        normal;
}
/* IE doesnt handles 'pre' right */
table [class=value] {
    white-space:        normal;
}
table .tbl_size {
    white-space:        nowrap;
}


<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
.value {
    font-family:        <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
.value .attention {
    color:              red;
    font-weight:        bold;
}
.value .allfine {
    color:              green;
}


img.lightbulb {
    cursor:             pointer;
}

.pdflayout {
    overflow:           hidden;
    clip:               inherit;
    background-color:   #FFFFFF;
    display:            none;
    border:             1px solid #000000;
    position:           relative;
}

.pdflayout_table {
    background:         #D3DCE3;
    color:              #000000;
    overflow:           hidden;
    clip:               inherit;
    z-index:            2;
    display:            inline;
    visibility:         inherit;
    cursor:             move;
    position:           absolute;
    font-size:          80%;
    border:             1px dashed #000000;
}

/* MySQL Parser */
.syntax {
    font-size:          80%;
}

.syntax a {
    text-decoration: none;
    border-bottom:1px dotted black;
}

.syntax_comment {
    padding-left:       4pt;
    padding-right:      4pt;
}

.syntax_digit {
}

.syntax_digit_hex {
}

.syntax_digit_integer {
}

.syntax_digit_float {
}

.syntax_punct {
}

.syntax_alpha {
}

.syntax_alpha_columnType {
    text-transform:     uppercase;
}

.syntax_alpha_columnAttrib {
    text-transform:     uppercase;
}

.syntax_alpha_reservedWord {
    text-transform:     uppercase;
    font-weight:        bold;
}

.syntax_alpha_functionName {
    text-transform:     uppercase;
}

.syntax_alpha_identifier {
}

.syntax_alpha_charset {
}

.syntax_alpha_variable {
}

.syntax_quote {
    white-space:        pre;
}

.syntax_quote_backtick {
}

/* leave some space between icons and text */
.icon, img.footnotemarker {
    vertical-align:     middle;
    margin-right:       0.3em;
    margin-left:        0.3em;
}

img.footnotemarker {
    display: none;
}

/* no extra space in table cells */
td .icon {
    margin: 0;
}

.selectallarrow {
    margin-<?php echo $right; ?>: 0.3em;
    margin-<?php echo $left; ?>: 0.6em;
}

/* message boxes: warning, error, confirmation */
.success h1,
.notice h1,
div.error h1 {
    border-bottom:      2px solid;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

div.success,
div.notice,
div.error,
div.footnotes {
    clear: both;
    margin:             0.3em 0 0 0;
    border:             2px solid;
    padding:            0.5em;
    -moz-border-radius: 0.5em;
    border-radius:      0.5em;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding-left: 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding-right: 36px;
        <?php } ?>
    <?php } ?>
}

.success {
    color:              #000000;
    background-color:   #f0fff0;
}
h1.success,
div.success {
    border-color:       #00FF00;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_success.png);
    <?php } ?>
}
.success h1 {
    border-color:       #00FF00;
}

.notice, .footnotes {
    color:              #000000;
    background-color:   #FFFFDD;
}
h1.notice,
div.notice,
div.footnotes {
    border-color:       #FFD700;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png);
    <?php } ?>
}
.notice h1 {
    border-color:       #FFD700;
}

.error {
    background-color:   #FFFFCC;
    color:              #ff0000;
}

h1.error,
div.error {
    border-color:       #ff0000;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png);
    <?php } ?>
}
div.error h1 {
    border-color:       #ff0000;
}

.confirmation {
    background-color:   #FFFFCC;
}
fieldset.confirmation {
    border:             0.1em solid #FF0000;
}
fieldset.confirmation legend {
    border-left:        0.1em solid #FF0000;
    border-right:       0.1em solid #FF0000;
    font-weight:        bold;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_really.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:            0.2em 0.2em 0.2em 25px;
        <?php } else { ?>
    background-position: 97% 50%;
    padding:            0.2em 25px 0.2em 0.2em;
        <?php } ?>
    <?php } ?>
}
/* end messageboxes */


.tblcomment {
    font-size:          70%;
    font-weight:        normal;
    color:              #000099;
}

.tblHeaders {
    font-weight:        bold;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}
.tblHeaders a {
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?> !important;
}

div.tools,
.tblFooters {
    font-weight:        normal;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}
.tblFooters a {
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
}
div.tools a {
    display: inline-block;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:              #FF0000;
    font-weight:        bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
    color:              #666666;
}

.disabled a:hover {
    color:              #666666;
    text-decoration:    none;
}

tr.disabled td,
td.disabled {
    background-color:   #cccccc;
}

.nowrap {
    white-space:        nowrap;
}

/**
 * login form
 */
body.loginform h1,
body.loginform a.logo {
    display: block;
    text-align: center;
}

body.loginform {
    text-align: center;
}

body.loginform div.container {
    text-align: <?php echo $left; ?>;
    width: 30em;
    margin: 0 auto;
}

form.login label {
    float: <?php echo $left; ?>;
    width: 10em;
    font-weight: bolder;
}

.commented_column {
    border-bottom: 1px dashed black;
}

.column_attribute {
    font-size: 70%;
}

/******************************************************************************/
/* specific elements */

/* topmenu */
#topmenu, #topmenu2, #topmenu .submenu ul {
    list-style: none;
    padding: 0;
    margin: 0.5em 0 0.5em 0;
    border-top: 1px solid #000;
    border-bottom: 1px solid #000;
    background: #ccc;
    white-space: nowrap;
}
#topmenu li, #topmenu2 li {
    display: inline-block;
    margin: 0;
    padding: 0;
}
#topmenu li a, #topmenu2 li a {
    display: inline-block;
    margin: 0;
    padding: 0.2em;
    min-height: 16px; /* To match icons */
}
#topmenu li a:hover, #topmenu2 li a:hover {
    background: #ddd;
    text-decoration: none;
}
a.tabactive {
    font-weight: bolder;
    background: #aaa;
}
#topmenu .submenu {
    position:           relative;
    display: none;
}
#topmenu .shown {
    display: inline-block;
}
#topmenu .submenu ul {
    margin:             0;
    padding:            0;
    position:           absolute;
    <?php echo $right;?>:              0;
    list-style-type:    none;
    display:            none;
    border:             1px #666 solid;
}

#topmenu li:hover ul, #topmenu .submenuhover ul {
    display:            block;
}

/* end topmenu */

/* table stats */
div#tablestatistics {
    border-bottom: 0.1em solid #669999;
    margin-bottom: 0.5em;
    padding-bottom: 0.5em;
}

div#tablestatistics table {
    float: <?php echo $left; ?>;
    margin-bottom: 0.5em;
    margin-<?php echo $right; ?>: 0.5em;
}

/* END table stats */


/* server privileges */
#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align: middle;
}
/* END server privileges */



/* Heading */
#serverinfo {
    font-weight:        bold;
    margin-bottom:      0.5em;
}

#serverinfo .item {
    white-space:        nowrap;
}

#span_table_comment {
    font-weight:        normal;
    font-style:         italic;
    white-space:        nowrap;
}

#serverinfo img {
    margin:             0 0.1em 0 0.2em;
}


#textSQLDUMP {
    width:              95%;
    height:             95%;
    font-family:        "Courier New", Courier, mono;
    font-size:          110%;
}

#TooltipContainer {
    position:           absolute;
    z-index:            99;
    width:              20em;
    height:             auto;
    overflow:           visible;
    visibility:         hidden;
    background-color:   #ffffcc;
    color:              #006600;
    border:             0.1em solid #000000;
    padding:            0.5em;
}

/* user privileges */
#fieldset_add_user_login div.item {
    border-bottom:      1px solid silver;
    padding-bottom:     0.3em;
    margin-bottom:      0.3em;
}

#fieldset_add_user_login label {
    float:              <?php echo $left; ?>;
    display:            block;
    width:              10em;
    max-width:          100%;
    text-align:         <?php echo $right; ?>;
    padding-<?php echo $right; ?>:      0.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width:              100%;
    max-width:          100%;
}

#fieldset_add_user_login span.options {
    float: <?php echo $left; ?>;
    display: block;
    width: 12em;
    max-width: 100%;
    padding-<?php echo $right; ?>: 0.5em;
}

#fieldset_add_user_login input {
    width: 12em;
    clear: <?php echo $right; ?>;
    max-width: 100%;
}

#fieldset_add_user_login span.options input {
    width: auto;
}

#fieldset_user_priv div.item {
    float: <?php echo $left; ?>;
    width: 9em;
    max-width: 100%;
}

#fieldset_user_priv div.item div.item {
    float: none;
}

#fieldset_user_priv div.item label {
    white-space: nowrap;
}

#fieldset_user_priv div.item select {
    width: 100%;
}

#fieldset_user_global_rights fieldset {
    float: <?php echo $left; ?>;
}
/* END user privileges */


/* serverstatus */
div#serverstatus table caption a.top {
    float: <?php echo $right; ?>;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float: <?php echo $left; ?>;
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

/* querywindow */
body#bodyquerywindow {
    margin: 0;
    padding: 0;
    background-image: none;
    background-color: #F5F5F5;
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

div#sqlquerycontainer {
    float: <?php echo $left; ?>;
    width: 69%;
    /* height: 15em; */
}

div#tablefieldscontainer {
    float: <?php echo $right; ?>;
    width: 29%;
    /* height: 15em; */
}

div#tablefieldscontainer select {
    width: 100%;
    /* height: 12em; */
}

textarea#sqlquery {
    width: 100%;
    /* height: 100%; */
}
textarea#sql_query_edit{
    height:7em;
    width: 95%;
    display:block;
}
div#queryboxcontainer div#bookmarkoptions {
    margin-top: 0.5em;
}
/* end querybox */

/* main page */
.pmagroup {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png);
    background-position: <?php echo $right; ?> bottom;
    background-repeat: no-repeat;
}

#mysqlmaininformation,
#pmamaininformation {
    float: <?php echo $left; ?>;
    width: 49%;
}

#maincontainer ul {
    list-style-type: disc;
    vertical-align: middle;
}

#maincontainer li {
    margin:  0.2em 0em;
}
/* END main page */


<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?>
/* iconic view for ul items */
li#li_create_database {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_newdb.png);
}

li#li_select_lang {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_lang.png);
}

li#li_select_mysql_collation {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
}

li#li_select_theme{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png);
}

li#li_user_info{
    /* list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png); */
}

li#li_mysql_status{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_status.png);
}

li#li_mysql_variables{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_vars.png);
}

li#li_mysql_processes{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_process.png);
}

li#li_mysql_collations{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
}

li#li_mysql_engines{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_engine.png);
}

li#li_mysql_binlogs {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_tbl.png);
}

li#li_mysql_databases {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png);
}

li#li_export {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_export.png);
}

li#li_import {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_import.png);
}

li#li_change_password {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_passwd.png);
}

li#li_log_out {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_loggoff.png);
}

li#li_mysql_privilegs{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png);
}

li#li_switch_dbstats {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_dbstatistics.png);
}

li#li_flush_privileges {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_reload.png);
}

li#li_user_preferences {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_tblops.png);
}

li#li_select_fontsize {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_font.png);
}
/* END iconic view for ul items */
<?php } /* end if $GLOBALS['cfg']['MainPageIconic'] */ ?>


#body_browse_foreigners {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    margin:             0.5em 0.5em 0 0.5em;
}

#bodyquerywindow {
    background:         <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
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
    float: <?php echo $left; ?>;
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

.operations_half_width {
    width: 48%;
    float: <?php echo $left; ?>;
}

.operations_full_width {
    width: 100%;
    clear: both;
}

#qbe_div_table_list {
    float: <?php echo $left; ?>;
}

#qbe_div_sql_query {
    float: <?php echo $left; ?>;
}

label.desc {
    width: 30em;
    float: <?php echo $left; ?>;
}

label.desc sup {
    position: absolute;
}

code.sql, div.sqlvalidate {
    margin:             0.3em 0 0 0;
    border:             2px solid;
    padding:            0.5em;
    -moz-border-radius: 0.5em;
    border-radius:      0.5em;
    display:            block;
    margin-top:         0.2em;
    margin-bottom:      0;
    max-height:         10em;
    overflow:           auto;
    background:         <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

#main_pane_left {
    width:              50%;
    float:              <?php echo $left; ?>;
    padding-top:        1em;
}

#main_pane_right {
    margin-<?php echo $left; ?>: 50%;
    padding-top: 1em;
    padding-<?php echo $left; ?>: 1em;
}

.group {
    border: 2px solid #000;
    margin-bottom: 1em;
    padding: 0 0 0.5em 0;
    -moz-border-radius: 0.5em;
    border-radius:      0.5em;
}

.group h2 {
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    color:         <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    padding:            0.1em 0.3em;
    margin-top:         0;
    border-bottom: 1px solid #000;
}

.group-cnt {
    padding: 0 0 0 0.5em;
    display: inline-block;
    width: 98%;
}

/* for elements that should be revealed only via js */
.hide {
    display:            none;
}

#li_select_server {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);
}

#list_server {
    list-style-image: none;
}

/**
  *  Progress bar styles
  */
div.upload_progress_bar_outer
{
    border: 1px solid black;
    width: 202px;
}

div.upload_progress_bar_inner
{
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    width: 0px;
    height: 12px;
    margin: 1px;
}

table#serverconnection_src_remote,
table#serverconnection_trg_remote,
table#serverconnection_src_local,
table#serverconnection_trg_local  {
  float:left;
}
/**
  *  Validation error message styles
  */
.invalid_value
{background:#F00;}

/**
  *  Ajax notification styling
  */
 .ajax_notification {
    top: 0px;           /** The notification needs to be shown on the top of the page */
    position: fixed;
    margin-top: 0;
    margin-right: auto;
    margin-bottom: 0;
    margin-left: auto;
    padding: 3px 5px;   /** Keep a little space on the sides of the text */
    width: 350px;
    background-color: #FFD700;
    z-index: 1100;      /** If this is not kept at a high z-index, the jQueryUI modal dialogs (z-index:1000) might hide this */
    text-align: center;
    display: block;
    left: 0;
    right: 0;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif);
    background-repeat: no-repeat;
    background-position: 2%;
 }

 #loading_parent {
    /** Need this parent to properly center the notification division */
    position: relative;
    width: 100%;
 }
/**
  * Export and Import styles
  */

.exportoptions h3, .importoptions h3 {
    border-bottom: 1px #999999 solid;
    font-size: 110%;
}

.exportoptions ul, .importoptions ul, .format_specific_options ul {
    list-style-type: none;
    margin-bottom: 15px;
}

.exportoptions li, .importoptions li {
    margin: 7px;
}
.exportoptions label, .importoptions label, .exportoptions p, .importoptions p {
    margin: 5px;
    float: none;
}

#csv_options label.desc, #ldi_options label.desc, #latex_options label.desc, #output label.desc{
    float: left;
    width: 15em;
}

.exportoptions, .importoptions {
    margin: 20px 30px 30px 10px
}

.exportoptions #buttonGo, .importoptions #buttonGo {
    padding: 5px 30px;
    -moz-border-radius: 11px;
    -webkit-border-radius: 11px;
    border-radius: 11px;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#cccccc');
    border: 1px solid #444444;
    cursor: pointer;
}

.format_specific_options h3 {
    margin: 10px 0px 0px 10px;
    border: 0px;
}

.format_specific_options {
    border: 1px solid #999999;
    margin: 7px 0px;
    padding: 3px;
}

p.desc {
    margin: 5px;
}

/**
  * Export styles only
  */
select#db_select, select#table_select {
    width: 400px;
}

.export_sub_options {
    margin: 20px 0px 0px 30px;
}

.export_sub_options h4 {
    border-bottom: 1px #999999 solid;
}

.export_sub_options li.subgroup {
	display: inline-block;
	margin-top: 0;
}

.export_sub_options li {
	margin-bottom: 0;
}

#quick_or_custom, #output_quick_export {
    display: none;
}
/**
 * Import styles only
 */

.importoptions #import_notification {
    margin: 10px 0px;
    font-style: italic;
}

input#input_import_file {
    margin: 5px;
}

.formelementrow {
    margin: 5px 0px 5px 0px;
}

/**
 * ENUM/SET editor styles
 */
p.enum_notice {
    margin: 5px 2px;
    font-size: 80%;
}

#enum_editor {
    display: none;
    position: fixed;
    _position: absolute; /* hack for IE */
    z-index: 101;
    overflow-y: auto;
    overflow-x: hidden;
}

#enum_editor_no_js {
   margin: auto auto;
}

#enum_editor, #enum_editor_no_js {
    background: #D0DCE0;
    padding: 15px;
}

#popup_background {
    display: none;
    position: fixed;
    _position: absolute; /* hack for IE6 */
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: #000;
    z-index: 100;
    overflow: hidden;
}

a.close_enum_editor {
    float: right;
}

#enum_editor #values, #enum_editor_no_js #values {
    margin: 15px 0px;
    width: 100%;
}

#enum_editor #values input, #enum_editor_no_js #values input {
    margin: 5px 0px;
    float: top;
    width: 100%;
}

#enum_editor_output {
    margin-top: 50px;
}

/**
 * Table structure styles
 */
.structure_actions_dropdown {
    position: absolute;
    padding: 0;
    display: none;
    z-index: 100;
    border-collapse:collapse;
    border: 1px solid #000;
    padding: 0.2em;
}

.structure_actions_dropdown a {
    display: block;
}

.structure_actions_dropdown div {
    margin: 0;
    padding: 0;
}

td.more_opts {
    display: none;
    white-space: nowrap;
}

iframe.IE_hack {
    z-index: 1;
    position: absolute;
    display: none;
    border: 0;
    filter: alpha(opacity=0);
}

/* config forms */
ul.tabs {
    margin:      1.1em 0.2em 0;
    padding:     0 0 0.3em 0;
    list-style:  none;
    font-weight: bold;
}

ul.tabs li {
    float: <?php echo $left; ?>;
}

ul.tabs a {
    display:          block;
    margin:           0.1em 0.2em 0;
    padding:          0.1em 0.4em;
    white-space:      nowrap;
    text-decoration:  none;
    border:           1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom:    none;
}

ul.tabs a:hover,
ul.tabs a:active,
ul.tabs a.active {
    margin:           0;
    padding:          0.1em 0.6em 0.2em;
}

ul.tabs a.active {
    background-color: <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

.config-form fieldset {
    margin-top:   0;
    padding:      0;
    clear:        both;
    /*border-color: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;*/
}

.config-form legend {
    display: none;
}

.config-form fieldset p {
    margin:    0;
    padding:   0.5em;
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

.config-form fieldset .errors { /* form error list */
    margin:       0 -2px 1em -2px;
    padding:      0.5em 1.5em;
    background:   #FBEAD9;
    border:       0 #C83838 solid;
    border-width: 1px 0;
    list-style:   none;
    font-family:  sans-serif;
    font-size:    small;
}

.config-form fieldset .inline_errors { /* field error list */
    margin:     0.3em 0.3em 0.3em 0;
    padding:    0;
    list-style: none;
    color:      #9A0000;
	font-size:  small;
}

.config-form fieldset th {
    padding:        0.3em 0.3em 0.3em 0.5em;
    text-align:     left;
    vertical-align: top;
    width:          40%;
    background:     transparent;
}

.config-form fieldset .doc, .config-form fieldset .disabled-notice {
    margin-left: 1em;
}

.config-form fieldset .disabled-notice {
    font-size: 80%;
    text-transform: uppercase;
    color: #E00;
    cursor: help;
}

.config-form fieldset td {
    padding-top:    0.3em;
    padding-bottom: 0.3em;
    vertical-align: top;
}

.config-form fieldset th small {
    display:     block;
    font-weight: normal;
    font-family: sans-serif;
    font-size:   x-small;
    color:       #444;
}

.config-form fieldset th, .config-form fieldset td {
    border-top: 1px <?php echo $GLOBALS['cfg']['BgTwo']; ?> solid;
}

fieldset .group-header th {
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

fieldset .group-header + tr th {
    padding-top: 0.6em;
}

fieldset .group-field-1 th, fieldset .group-header-2 th {
    padding-left: 1.5em;
}

fieldset .group-field-2 th, fieldset .group-header-3 th {
    padding-left: 3em;
}

fieldset .group-field-3 th {
    padding-left: 4.5em;
}

fieldset .disabled-field th,
fieldset .disabled-field th small,
fieldset .disabled-field td {
    color: #666;
    background-color: #ddd;
}

.config-form .lastrow {
    border-top: 1px #000 solid;
}

.config-form .lastrow {
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;;
    padding:    0.5em;
    text-align: center;
}

.config-form .lastrow input {
    font-weight: bold;
}

/* form elements */

.config-form span.checkbox {
    padding: 2px;
    display: inline-block;
}

.config-form .custom { /* customized field */
    background: #FFC;
}

.config-form span.checkbox.custom {
    padding:    1px;
    border:     1px #EDEC90 solid;
    background: #FFC;
}

.config-form .field-error {
    border-color: #A11 !important;
}

.config-form input[type="text"],
.config-form select,
.config-form textarea {
    border: 1px #A7A6AA solid;
    height: auto;
}

.config-form input[type="text"]:focus,
.config-form select:focus,
.config-form textarea:focus {
    border:     1px #6676FF solid;
    background: #F7FBFF;
}

.config-form .field-comment-mark {
    font-family: serif;
    color: #007;
    cursor: help;
    padding: 0 0.2em;
    font-weight: bold;
    font-style: italic;
}

.config-form .field-comment-warning {
    color: #A00;
}

/* error list */
.config-form dd {
    margin-left: 0.5em;
}

.config-form dd:before {
    content: "\25B8  ";
}

.click-hide-message {
    cursor: pointer;
}

.prefsmanage_opts {
    margin-<?php echo $left; ?>: 2em;
}

#prefs_autoload {
    margin-bottom: 0.5em;
}

#togglequerybox {
    clear: both;
    display: block;
    margin:             0 1em 1em 1em;
    border:             2px solid #000;
    width: auto;
    padding: 0.2em;
    clear:              both;
    -moz-border-radius: 0.5em;
    border-radius:      0.5em;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    <?php if ($GLOBALS['cfg']['PropertiesIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_sql.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:            0.2em 0.2em 0.2em 25px;
        <?php } else { ?>
    background-position: 97% 50%;
    padding:            0.2em 25px 0.2em 0.2em;
        <?php } ?>
    <?php } ?>
}
