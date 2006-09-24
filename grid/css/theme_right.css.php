<?php /*jw <-- diff from original/theme_right.css.php 20060922 */
	// 2do: replace ../ with $oneUp
	// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
	exit();
}
/*jw*/
if ('2.8' == substr(PMA_VERSION,0,3)) {
	echo "/* 2.8 (experimental) */
html {
	font-size:	", $GLOBALS['cfg']['FontSize'],'
}

table {
	font-size:	', $GLOBALS['cfg']['FontSize'],'
}
';//echo

} //<2.9
?>
/** Grid right general tags v. 2.9 20060922 windkiel **/
body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
	font-family:		<?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
	padding:		0;
	margin:		.4em;/*.5*/
	color:		<?php echo $GLOBALS['cfg']['MainColor']; ?>;
	background:		<?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea, tt, pre, code {
	font-family:	<?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
input { /*jw*/
	padding:		0 .3em;
	font-size:		100%
}

h1 {
	font-size:		140%;
	font-weight:	bold;
	margin:		0 .8em 0 .8em;/*jw*/
}

h2 {
	font-size:		120%;
	font-weight:	bold;
}

h3 {
	font-weight:	bold;
}

a {
	padding:		0	.1em	0	.1em
}
a:link,
a:visited,
a:active {
	text-decoration:	none;
	color:	<?php echo $GLOBALS['cfg']['MainLinkColor']; /*jw,was 00f*/ ?>;
}

a:hover {
	text-decoration:	underline;
	color:	<?php echo $GLOBALS['cfg']['MainLinkColor']; /*jw*/ ?>;
	background:	<?php echo $GLOBALS['cfg']['MainLinkBackground']; /*jw*/ ?>;
}

dfn {
	font-style:	normal;
}

dfn:hover {
	font-style:	normal;
	cursor:	help;
}

th {
	font-weight:bold;
	color:	<?php echo $GLOBALS['cfg']['ThColor']; ?>;
	background:	<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

a img {
	border:	0;
}

hr {
	color:	<?php echo $GLOBALS['cfg']['MainColor']; ?>;
	background:	<?php echo $GLOBALS['cfg']['MainColor']; ?>;
	border:	0;
	height:	1px;
}

form {
	padding:	0;
	margin:	0;
	display:	inline;
}

textarea {
	overflow:	visible;
	height:	8em;
}

fieldset {
	margin:	1em 0 1px;
	border:	<?php echo $GLOBALS['cfg']['MainColor']; ?> solid 1px;
	padding:	.5em;
	background:	<?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

fieldset fieldset {
	margin:	.8em;
}

fieldset legend {
	padding:	0 .2em 0 .2em;
	background:	<?php echo $GLOBALS['cfg']['BgTwo']; #ffffdd transparent ?>
}
/* buttons in some browsers (eg. Konqueror) are block elements,
 this breaks design */
button {
	display:	inline;
}
table {
	margin:	1px;
	border-collapse:	collapse
}

table caption,
table th,
table td {
	padding:		0 2px 0 2px;
	vertical-align:	top;
<?php if ($GLOBALS['cfg']['Border']) {
	echo 'border:	', $GLOBALS['cfg']['Border'], ' solid ', $GLOBALS['cfg']['MainGridColor'];
	//effects also page navigation table :(
}
?>

}

img,
input,
select,
button {
	vertical-align:	middle;
}


<?php /* * * * * * * * * * * * * * * * * * * * * * * * * */ ?>
/* classes */

fieldset.tblFooters {
	margin-top:		0;
	margin-bottom:	.5em;
	text-align:		<?php echo $right; ?>;
	float:		none;
	clear:		both;
}

fieldset .formelement {
	float:		<?php echo $left; ?>;
	margin-<?php echo $right; ?>:	.5em;
	/* IE */
	white-space:		nowrap;
}

/* revert for Gecko */
fieldset div[class=formelement] {
	white-space:	normal;
}

button.mult_submit {
	border:		none;
	background:	transparent;
}

/* odd items 1,3,5,7,... */
table tr.odd th,
.odd {
	background:	<?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

/* even items 2,4,6,8,... */
table tr.even th,
.even {
	background:	<?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd,
table tr.even th,
table tr.even {
	text-align:	<?php echo $left; ?>;
}

/* marked tbale rows */
table tr.marked th,
table tr.marked {
	background:	<?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

/* hovered items */
.odd:hover,
.even:hover,
.hover {
	background:	<?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th {
	background:	<?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
	color:	<?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/**
 * marks table rows/cells if the db field is in a where condition
 */
tr.condition th,
tr.condition td,
td.condition,
th.condition {
	border:	1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}

table .value {
	text-align:	<?php echo $right; ?>;
	white-space:	normal;
}
<?php /* IE? doesnt handles 'pre' right */ ?>
table [class=value] {
	white-space:	normal;
}


<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
.value {
	font-family:	<?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
.value .attention {
	color:		red;
	font-weight:	bold;
}
.value .allfine {
	color:		green;
}


img.lightbulb {
	cursor:		pointer;
}

.pdflayout {
	overflow:		hidden;
	clip:		inherit;
	background:		#fff;
	display:		none;
	border:		1px solid #000;
	position:		relative;
}

.pdflayout_table {
	background:		#D3DCE3;
	color:		#000;
	overflow:		hidden;
	clip:		inherit;
	z-index:		2;
	display:		inline;
	visibility:		inherit;
	cursor:		move;
	position:		absolute;
	font-size:/*jw??*/80%;
	border:		1px dashed #000;
}

/* MySQL Parser */
.syntax {
	font-size:/*jw 80*/	90%;
}

.syntax_comment {
	padding-left:	4pt;
	padding-right:	4pt;
}

.syntax_alpha_columnType {
	text-transform:	uppercase;
}

.syntax_alpha_columnAttrib {
	text-transform:	uppercase;
}

.syntax_alpha_reservedWord {
	text-transform:	uppercase;
	font-weight:	bold;
}

.syntax_alpha_functionName {
	text-transform:	uppercase;
}

.syntax_quote {
	white-space:	pre;
}

.syntax_quote_backtick {
}

/* leave some space between icons and text */
.icon {
	vertical-align:	middle;
	margin-right:	.3em;
	margin-left:	.3em;
}
/* no extra space in table cells */
td .icon {
	margin:	0;
}

.selectallarrow {
	margin-<?php echo $right; ?>:	.3em;
	margin-<?php echo $left; ?>:	.6em;
}

/* message boxes:	warning, error, confirmation */
.notice {
	color:	#000;
	background:	#FFFFDD;
}
h1.notice,
div.notice {
	margin:	.5em 0 .5em 0;
	border:	1px solid #FFD700;
	<?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
	background-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png");
	background-repeat:no-repeat;
		<?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
	background-position:	10px 50%;
	padding:	10px 10px 10px 36px;
		<?php } else { ?>
	background-position:	99% 50%;
	padding:	10px 5% 10px 10px;
		<?php } ?>
	<?php } else { ?>
	padding:	.5em;
	<?php } ?>
}
.notice h1 {
	border-bottom:	1px solid #FFD700;
	font-weight:	bold;
	text-align:		<?php echo $left; ?>;
	margin:		0 0 .2em 0;
}

.warning {
	color:	#c00;
	background:	#ffc;
}
p.warning,
h1.warning,
div.warning {
	margin:	.5em 0 .5em 0;
	border:	1px solid #c00;
	<?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
	background-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_warn.png");
	background-repeat:no-repeat;
		<?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
	background-position:	10px 50%;
	padding:	10px 10px 10px 36px;
		<?php } else { ?>
	background-position:	99% 50%;
	padding:	10px 5% 10px 10px;
		<?php } ?>
	<?php } else { ?>
	padding:	.5em;
	<?php } ?>
}
.warning h1 {
	border-bottom:	1px solid #c00;
	font-weight:	bold;
	text-align:		<?php echo $left; ?>;
	margin:		0 0 .2em 0;
}

.error {
	background:	#ffc;
	color:	#f00;
}

h1.error,
div.error {
	margin:	.5em 0 .5em 0;
	border:	1px solid #f00;
	<?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
	background-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png");
	background-repeat:no-repeat;
		<?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
	background-position:	10px 50%;
	padding:	10px 10px 10px 36px;
		<?php } else { ?>
	background-position:	99% 50%;
	padding:	10px 5% 10px 10px;
		<?php } ?>
	<?php } else { ?>
	padding:	.5em;
	<?php } ?>
}
div.error h1 {
	border-bottom:	1px solid #f00;
	font-weight:	bold;
	text-align:		<?php echo $left; ?>;
	margin:		0 0 .2em 0;
}

.confirmation {
	background:	#ffc;
}
fieldset.confirmation {
	border:	1px solid #f00;
}
fieldset.confirmation legend {
	border-left:	1px solid #f00;
	border-right:	1px solid #f00;
	font-weight:	bold;
	<?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
	background-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_really.png");
	background-repeat:no-repeat;
		<?php if ( $GLOBALS['text_dir'] === 'ltr' ) { ?>
	background-position:	5px 50%;
	padding:		.2em .2em .2em 25px;
		<?php } else { ?>
	background-position:	97% 50%;
	padding:		.2em 25px .2em .2em;
		<?php } ?>
	<?php } ?>
}
/* end messageboxes */


.tblcomment {
	font-size:/*jw70*/ 90%;
	font-weight:	normal;
	color:		#009;
}

.tblHeaders {
	font-weight:	bold;
	color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
	background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

.tblFooters {
	font-weight:	normal;
	color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
	background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;

}

.tblHeaders a:link,
.tblHeaders a:active,
.tblHeaders a:visited,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
	color:	#00f;
}

.tblHeaders a:hover,
.tblFooters a:hover {
	color:	#f00;
}

/* forbidden, no privilegs */
.noPrivileges {
	color:	#f00;
	font-weight:bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
	color:	#666;
}

.disabled a:hover {
	color:	#666;
	text-decoration:	none;
}

tr.disabled td,
td.disabled {
	background:	#cccccc;
}

/**
 * login form
 */
body.loginform h1,
body.loginform a.logo {
	display:	block;
	text-align:	center;
}

body.loginform {
	text-align:	center;
}

body.loginform div.container {
	text-align:	<?php echo $left; ?>;
	width:	30em;
	margin:	0	auto;
}

form.login label {
	float:	<?php echo $left; ?>;
	width:	10em;
	font-weight:bolder;
}


<?php /* * * * * * * * * * * * * * * * * * * * * * * * * */ ?>
/* specific elements */

/* topmenu */
ul#topmenu {
	font-weight:	bold;
	list-style-type:	none;
	margin:		0;
	padding:		0;
}

ul#topmenu li {
	float:		<?php echo $left;
/* "dot problem"
lem9: xp, ff1507
C-Dev:
Mozilla/5.0 (X11; U; FreeBSD i386; en-US; rv:1.7.12) Gecko/20051218 Firefox/1.0.7
thx to Ci-Dev on irc , workaround : (gecko flaw? should be inherited, ok in original)  */?>;
	list-style-type: none;
	margin:		0;
	padding:		0;
	vertical-align:	middle;
}

#topmenu img {
	vertical-align:	middle;
	margin-<?php echo $right; ?>:	1px;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
	display:		block;
	margin:		.2em .2em 0 .2em;
	padding:		.2em .2em 0 .2em;
	white-space:	nowrap;
}

/* disabled tabs */
span.tab {
	color:		#666;
}

/* disabled drop/empty tabs */
span.tabcaution {
	color:		#f66;
}

/* enabled drop/empty tabs */
a.tabcaution {
	color:		#f00;
}
a.tabcaution:hover {
	color:		#fff;
	background:		#f00;
}

<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
/* active tab */
a.tabactive {
	color:		black;
}
<?php } else { ?>
#topmenu {
	margin-top:		.5em;
	padding:		1px .3em 1px .3em;
}

ul#topmenu li {
	border-bottom:	1px/*pt*/ solid black;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
	background:	<?php echo $GLOBALS['cfg']['BgOne']; ?>;
	border:		1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
	border-bottom:	0;
	<?php
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') > 0) { /*FF..*/
		echo '-moz-border-radius-topleft:.6em;
	-moz-border-radius-topright:.6em';
	}
/**
MSIE:
Sebastian removed the wrong lines from "original" :(
neither border-radius-topleft nor border-top-left-radius is css compliant
http://blogs.msdn.com/ie/archive/2005/06/23/431980.aspx
**/
?>

}

/* enabled hover/active tabs */
a.tab:hover,
a.tabcaution:hover,
.tabactive,
.tabactive:hover {
	margin:		0;
	padding:		.2em .4em .2em .4em;
	text-decoration:	none;
}

a.tab:hover,
.tabactive {
	background:	<?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

/* disabled drop/empty tabs */
span.tab,
span.tabcaution {
	<?php
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') < 0) {
		echo '	cursor:	url("../', $_SESSION['PMA_Theme']->getImgPath(), 'error.ico"), auto;';/*jw*/
	}
	?>
}
<?php } ?>
/* end topmenu */


/* Calendar */
table.calendar {
	width:		100%;
}
table.calendar td {
	text-align:		center;
}
table.calendar td a {
	display:		block;
}

table.calendar td a:hover {
	background:	#cfc;
}

table.calendar th {
	background:	#D3DCE3;
}

table.calendar td.selected {
	background:	#fc9;
}

img.calendar {
	border:		none;
}
form.clock {
	text-align:		center;
}
/* end Calendar */


/* table stats */
div#tablestatistics {
	border-bottom:	1px solid #699;
	margin-bottom:	.5em;
	padding-bottom:	.5em;
}

div#tablestatistics table {
	float:	<?php echo $left; ?>;
	margin-bottom:	.5em;
	margin-<?php echo $right; ?>:	.5em;
}

div#tablestatistics table caption {
	margin-<?php echo $right; ?>:	.5em;
}
/* END table stats */


/* server privileges */
#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
	vertical-align:	middle;
}
/* END server privileges */



/* Heading */
#serverinfo {
	font-weight:	bold;
	margin-bottom:	.5em;
}

#serverinfo .item {
	white-space:	nowrap;
}

#span_table_comment {
	font-weight:	normal;
	font-style:		italic;
	white-space:	nowrap;
}

#serverinfo img {
	margin:		0 1px 0 .2em;
}


#textSQLDUMP {
	width:		95%;
	height:		95%;
	font-family:	"Courier New", Courier, mono;
	font-size:		110%;
}

#TooltipContainer {
	position:		absolute;
	z-index:		99;
	width:		20em;
	height:		auto;
	overflow:		visible;
	visibility:		hidden;
	background:	#ffc;
	color:		#060;
	border:		1px solid #000;
	padding:		.5em;
}

/* user privileges */
#fieldset_add_user_login div.item {
	border-bottom:	1px solid silver;
	padding-bottom:	.3em;
	margin-bottom:	.3em;
}

#fieldset_add_user_login label {
	float:		<?php echo $left; ?>;
	display:		block;
	width:		10em;
	max-width:		100%;
	text-align:		<?php echo $right; ?>;
	padding-<?php echo $right; ?>:	.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
	width:		100%;
	max-width:		100%;
}

#fieldset_add_user_login span.options {
	float:	<?php echo $left; ?>;
	display:	block;
	width:	12em;
	max-width:	100%;
	padding-<?php echo $right; ?>:	.5em;
}

#fieldset_add_user_login input {
	width:	12em;
	clear:	<?php echo $right; ?>;
	max-width:	100%;
}

#fieldset_add_user_login span.options input {
	width:	auto;
}

#fieldset_user_priv div.item {
	float:	<?php echo $left; ?>;
	width:	9em;
	max-width:	100%;
}

#fieldset_user_priv div.item div.item {
	float:	none;
}

#fieldset_user_priv div.item label {
	white-space:	nowrap;
}

#fieldset_user_priv div.item select {
	width:	100%;
}

#fieldset_user_global_rights fieldset {
	float:	<?php echo $left; ?>;
}
/* END user privileges */


/* serverstatus */
div#serverstatus table caption a.top {
	float:	<?php echo $right; ?>;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
	float:	<?php echo $left; ?>;
}

#serverstatussection,
.clearfloat {
	clear:	both;
}
div#serverstatussection table {
	width:	100%;
	margin-bottom:	1em;
}
div#serverstatussection table .name {
	width:	18em;
}
div#serverstatussection table .value {
	width:	6em;
}

div#serverstatus table tbody td.descr a,
div#serverstatus table .tblFooters a {
	white-space:	nowrap;
}
div#serverstatus div#statuslinks a:before,
div#serverstatus div#sectionlinks a:before,
div#serverstatus table tbody td.descr a:before,
div#serverstatus table .tblFooters a:before {
	content:	'[';
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
	content:	']';
}
/* end serverstatus */

/* querywindow */
body#bodyquerywindow {
	margin:	0;
	padding:	0;
	background-image:	none;
	background:	#F5F5F5;
}

div#querywindowcontainer {
	margin:	0;
	padding:	0;
	width:	100%;
}

div#querywindowcontainer fieldset {
	margin-top:	0;
}
/* END querywindow */


/* querybox */

div#sqlquerycontainer {
	float:	<?php echo $left; ?>;
	width:	69%;
<?php	/* height:	15em; */?>
}

div#tablefieldscontainer {
	float:	<?php echo $right; ?>;
	width:	29%;
	/* height:	15em; */
}

div#tablefieldscontainer select {
	width:	100%;
	/* height:	12em; */
}

textarea#sqlquery {
	width:	100%;
	/* height:	100%; */
}

div#queryboxcontainer div#bookmarkoptions {
	margin-top:	.5em;
}
/* end querybox */

/* main page */
#maincontainer {
	background-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png");
	background-position:	<?php echo $right; ?> bottom;
	background-repeat:	no-repeat;
	border-bottom:		1px solid silver;
}

#mysqlmaininformation,
#pmamaininformation {
	float:		<?php echo $left; ?>;
	width:		49%;
}

#maincontainer ul {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_<?php echo $GLOBALS['text_dir']; ?>.png");
	vertical-align:	middle;
}

#maincontainer li {
	margin-bottom:	.3em;
	padding:		0 .3em 0 .3em;
}
/* END main page */


<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?>
/* iconic view for ul items */
li#li_create_database {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_newdb.png");
}

li#li_select_lang {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_lang.png");
}

li#li_select_mysql_collation,
li#li_select_mysql_charset {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png");
}

li#li_select_theme{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png");
}

li#li_server_info{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png");
}
<?php /*
li#li_user_info{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png");
}
*/ ?>
li#li_mysql_status{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_status.png");
}

li#li_mysql_variables{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_vars.png");
}

li#li_mysql_processes{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_process.png");
}

li#li_mysql_collations{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png");
}

li#li_mysql_engines{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_engine.png");
}

li#li_mysql_binlogs {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_tbl.png");
}

li#li_mysql_databases {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png");
}

li#li_export {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_export.png");
}

li#li_import {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_import.png");
}

li#li_change_password {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_passwd.png");
}

li#li_log_out {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_loggoff.png");
}

li#li_pma_docs {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_docs.png");
}

li#li_phpinfo {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>php_sym.png");
}

li#li_pma_homepage {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_home.png");
}

li#li_mysql_privilegs{
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png");
}

li#li_switch_dbstats {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_dbstatistics.png");
}

li#li_flush_privileges {
	list-style-image:	url("../<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_reload.png");
}
/* END iconic view for ul items */
<?php } /* end if $GLOBALS['cfg']['MainPageIconic'] */ ?>


#body_browse_foreigners {
	background:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
	margin:	.5em .5em 0 .5em;
}

#bodyquerywindow {
	background:	<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

#bodythemes {
	width:	500px;
	margin:	auto;
	text-align:	center;
}

#bodythemes img {
	border:	1px solid black;
}

#bodythemes a:hover img {
	border:	1px solid red;
}

#fieldset_select_fields {
	float:	<?php echo $left; ?>;
}

#selflink {
	clear:	both;
	display:	block;
	margin-top:	1em;
	margin-bottom:	1em;
	width:	100%;
	border-top:	1px solid silver;
	text-align:	<?php echo $right; ?>;
}

#table_innodb_bufferpool_usage,
#table_innodb_bufferpool_activity {
	float:	<?php echo $left; ?>;
}

#div_mysql_charset_collations table {
	float:	<?php echo $left; ?>;
}

#div_table_order {
	min-width:	48%;
	float:	<?php echo $left; ?>;
}

#div_table_rename {
	min-width:	48%;
	float:	<?php echo $left; ?>;
}

#div_table_copy {
	min-width:	48%;
	float:	<?php echo $left; ?>;
}

#div_table_options {
	clear:	both;
	min-width:	48%;
	float:	<?php echo $left; ?>;
}

#qbe_div_table_list {
	float:	<?php echo $left; ?>;
}

#qbe_div_sql_query {
	float:	<?php echo $left; ?>;
}

label.desc {
	width:	30em;
	float:	<?php echo $left; ?>;
}
