<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Emphasis
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Emphasis
 */
 
// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/*****************************************************************************/
/* general tags */
html {
    font-size:	<?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : (
        isset($_COOKIE['pma_fontsize']) ? $_COOKIE['pma_fontsize'] : '82%'));?>;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:	<?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    background:		<?php echo $GLOBALS['cfg']['MainBackground']; ?> url('<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>background.png') 0% 0% repeat-x;
    color:		<?php echo $GLOBALS['cfg']['MainColor']; ?>;
    margin:		0.5em;
    padding:		0;
}

<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea, tt, pre, code {
    font-family:	<?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>

textarea {
    overflow:		visible;
    height:		<?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}

select,
option,
input,
textarea {
    font-size:		1em;
    border:		1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border-radius:	0.3em;
    -moz-border-radius: 0.3em;
    color:		#082E58;
    background-color:	#FFF;
}

img,
input,
select,
button {
    vertical-align:	middle;
}

label {
    color:		#FFF;
}

h1 {
    font-size:		140%;
    font-weight:	bold;
}

h2 {
    font-size:		120%;
    font-weight:	bold;
}

h3 {
    font-weight:	bold;
}

a:link,
a:visited,
a:active {
    text-decoration:	none;
    color:		#909090;
    cursor:		pointer;
}

a:hover {
    text-decoration:	none;
    color:		#FFFFFF;
}

a img {
    border:		0;
}

dfn {
    font-style:		normal;
}

dfn:hover {
    font-style:		normal;
    cursor:		help;
}

hr {
    color:	<?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background-color:	<?php echo $GLOBALS['cfg']['MainColor']; ?>;
    border:		0;
    height:		1px;
}

form {
    padding:		0;
    margin:		0;
    display:		inline;
}

fieldset {
    margin-top:		1em;
    border:		1px solid <?php echo $GLOBALS['cfg']['ThBackground'] ?>;
    padding:		0.5em;
    background:		<?php /*echo $GLOBALS['cfg']['BgOne']; */?>#183E68;
}

fieldset fieldset {
    margin:		0.8em;
}

fieldset legend {
    font-weight:	bold;
    color:		#FFFFFF;
}

/* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
button {
    display:		inline;
}

table {
    border-collapse:	collapse;
    margin:		2px;
}

th {
    font-weight:	bold;
    color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

table.data a {
    color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
}

table caption,
table th,
table td {
    padding:		0.1em 0.5em 0.1em 0.5em;
    margin:		0.1em 0;
    vertical-align:	top;
    border:		1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

table.data thead a:hover {
    color:		#FFF;
}

table.data tbody a:hover {
    color:		#000;
}

table#tableprocesslist,
table#tablestructure,
table#tablespecificuserrights {
    margin-top:		10px;
}

table.formlayout {
    color:		#FFF;
}

/******************************************************************************/
/* classes */
div.tools {
    border:		1px solid #000000;
    padding:		0.2em;
}

div.tools,
fieldset.tblFooters {
    margin-top:		0;
    margin-bottom:	0.5em;
    /* avoid a thick line since this should be used under another fieldset */
    border:		1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    text-align:		<?php echo $right; ?>;
    float:		none;
    clear:		both;
    background-color:	<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    color:		#082E58;
}

fieldset.tblFooters input,
th.tblFooters input {
    border:		1px solid #082E58;
}

fieldset .formelement {
    float:		<?php echo $left; ?>;
    margin-right:	0.5em;
    /* IE */
    white-space:	nowrap;
}

/* revert for Gecko */
fieldset div[class=formelement] {
    white-space:	normal;
}

button.mult_submit {
    border:		none;
    background-color:	transparent;
    color:		#909090;
    cursor:		pointer;
}

button.mult_submit:hover {
    color:		#FFF;
}

/* odd items 1,3,5,7,... */
table tr.odd th,
.odd {
    background:		<?php echo $GLOBALS['cfg']['BgOne']; ?>;
    color:		#082e58;
}

/* even items 2,4,6,8,... */
table tr.even th,
.even {
    background:		<?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    color:		#082e58;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd,
table tr.even th,
table tr.even {
    text-align:		<?php echo $left; ?>;
}

.odd label,
.odd a,
.even label,
.even a {
    color:		#082E58;
}

.odd a:hover,
.even a:hover {
    color:		#486E98;
}


<?php if ($GLOBALS['cfg']['BrowseMarkerEnable']) { ?>
/* marked table rows */
td.marked,
table tr.marked th,
table tr.marked {
    background:		<?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['BrowsePointerEnable']) { ?>
/* hovered items */
.odd:hover,
.even:hover,
.hover,
.structure_actions_dropdown {
    background:		<?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th {
    background:		<?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color:		<?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
<?php } ?>

/**
 * marks table rows/cells if the db field is in a where condition
 */
tr.condition th,
tr.condition td,
td.condition,
th.condition {
    border:		1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}

table .value {
    text-align:		<?php echo $right; ?>;
    white-space:	normal;
}
/* IE doesnt handles 'pre' right */
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
    background-color:	#FFFFFF;
    display:		none;
    border:		1px solid #000000;
    position:		relative;
}

.pdflayout_table {
    background:		#D3DCE3;
    color:		#000000;
    overflow:		hidden;
    clip:		inherit;
    z-index:		2;
    display:		inline;
    visibility:		inherit;
    cursor:		move;
    position:		absolute;
    font-size:          80%;
    border:             1px dashed #000000;
}

/* MySQL Parser */
.syntax {
    font-size:		95%;
}

.syntax a {
    text-decoration:	none;
    border-bottom:	px dotted black;
}

.syntax_comment {
    padding-left:	4pt;
    padding-right:	4pt;
}

.syntax_alpha_columnType,
.syntax_alpha_columnAttrib,
.syntax_alpha_functionName {
    text-transform:	uppercase;
}

.syntax_alpha_reservedWord {
    text-transform:	uppercase;
    font-weight:	bold;
}

.syntax_quote {
    white-space:	pre;
}

/* leave some space between icons and text */
.icon, img.footnotemarker {
    vertical-align:	middle;
    margin-right:	0.3em;
    margin-left:	0.3em;
}

img.footnotemarker {
    display:		none;
}

/* no extra space in table cells */
td .icon {
    margin:		0;
}

.selectallarrow {
    margin-<?php echo $right; ?>:	0.3em;
    margin-<?php echo $left; ?>:	0.6em;
}

/* message boxes: warning, error, confirmation */
.success h1,
.notice h1,
.warning h1,
div.error h1 {
    border-bottom:	2px solid;
    font-weight:	bold;
    text-align:		<?php echo $left; ?>;
    margin:		0 0 0.2em 0;
}

div.success,
div.notice,
div.warning,
div.error,
div.footnotes {
    margin:		0.3em 0 0 0;
    border:		2px solid;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-repeat:	no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position:	10px 50%;
    padding:		0.1em 0.1em 0.1em 36px;
        <?php } else { ?>
    background-position:	99% 50%;
    padding:		10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:		0.3em;
    <?php } ?>
}

div.notice a:hover {
    color:		#082E58;
}

.success {
    color:		#000000;
    background-color:	#f0fff0;
}
h1.success,
div.success {
    border-color:	#409064;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_success.png);
    background-repeat:  no-repeat;
	<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:		0.2em 0.2em 0.2em 25px;
	<?php } else { ?>
    background-position: 97% 50%;
    padding:		0.2em 25px 0.2em 0.2em;
	<?php } ?>
    <?php } ?>
}
.success h1 {
    border-color:	#00FF00;
}

.notice, .footnotes {
    color:		#000000;
    background-color:	#FFFFDD;
}
h1.notice,
div.notice,
div.footnotes {
    border-color:	#FFD700;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png);
    background-repeat:	no-repeat;
	<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:		0.2em 0.2em 0.2em 25px;
	<?php } else { ?>
    background-position: 97% 50%;
    padding:		0.2em 25px 0.2em 0.2em;
	<?php } ?>
    <?php } ?>
}
.notice h1 {
    border-color:	#FFD700;
}

.warning {
    color:		#903050;
    background-color:	#FFE0F0;
}
p.warning,
h1.warning,
div.warning {
    border-color:	#903050;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_warn.png);
    background-repeat:	no-repeat;
	<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:		0.2em 0.2em 0.2em 25px;
	<?php } else { ?>
    background-position: 97% 50%;
    padding:		0.2em 25px 0.2em 0.2em;
	<?php } ?>
    <?php } ?>
}
.warning h1 {
    border-color:	#cc0000;
}

.error {
    background-color:	/#903050;
    color:		#FFE0F0;
}

h1.error,
div.error {
    border-color:	#904064;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png);
    background-repeat:  no-repeat;
	<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:		0.2em 0.2em 0.2em 25px;
	<?php } else { ?>
    background-position: 97% 50%;
    padding:		0.2em 25px 0.2em 0.2em;
	<?php } ?>
    <?php } ?>
}
div.error h1 {
    border-color:	#904064;
}

.confirmation {
    background-color:	#FFFFCC;
}
fieldset.confirmation {
    border:		0.1em solid #904064;
}
fieldset.confirmation legend {
    border-left:	0.1em solid #904064;
    border-right:	0.1em solid #904064;
    font-weight:	bold;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_really.png);
    background-repeat:	no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
    padding:		0.2em 0.2em 0.2em 25px;
        <?php } else { ?>
    background-position: 97% 50%;
    padding:		0.2em 25px 0.2em 0.2em;
        <?php } ?>
    <?php } ?>
}
/* end messageboxes */

.tblcomment {
    font-size:		70%;
    font-weight:	normal;
    color:		#000099;
}

.tblHeaders {
    font-weight:	bold;
    color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

div.tools,
.tblFooters {
    font-weight:	normal;
    color:		<?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

.tblHeaders a:link,
.tblHeaders a:active,
.tblHeaders a:visited,
div.tools a:link,
div.tools a:visited,
div.tools a:active,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
    color:		#082E58;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
    color:		#903050;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:		#903050;
    font-weight:	bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
    color:		#666666;
}

.disabled a:hover {
    color:		#666666;
    text-decoration:	none;
}

tr.disabled td,
td.disabled {
    background-color:	#cccccc;
}

.nowrap {
    white-space:	nowrap;
}

/**
 * login form
 */
body.loginform h1,
body.loginform a.logo {
    display:		block;
    text-align:		center;
}

body.loginform {
    text-align:		center;
}

body.loginform div.container {
    text-align:		<?php echo $left; ?>;
    width:		30em;
    margin:		0 auto;
}

form.login label {
    float:		<?php echo $left; ?>;
    width:		10em;
    font-weight:	bolder;
}

.commented_column {
    border-bottom:	1px dashed black;
}

.column_attribute {
    font-size:		70%;
}

/******************************************************************************/
/* specific elements */

/* topmenu */
ul#topmenu,
ul.tabs {
    font-weight:	bold;
    list-style-type:	none;
    margin:		0;
    padding:		0;
}

ul#topmenu2 {
    font-weight:	bold;
    list-style-type:	none;
    padding:		0;
    margin:		0.25em 0 0;
    height:		2em;
    clear:		both;
}

ul#topmenu li,
ul#topmenu2 li {
    float:		<?php echo $left; ?>;
    margin:		0;
    padding:		0;
    vertical-align:	middle;
}

#topmenu img,
#topmenu2 img {
    vertical-align:	middle;
    margin-right:	0.1em;
}

/* default tab styles */
ul#topmenu a,
ul#topmenu span {
    display:		block; 
    margin:		2px 2px 0;
    padding:		2px;
    white-space:	nowrap;
    color:		#204670;
}
 
ul#topmenu ul a {
    margin:		2px;
    padding-bottom:	2px;
}
 
ul#topmenu .submenu {
    position:		relative;
    display:		none;
}
ul#topmenu .shown {
    display:		block;
}
 
ul#topmenu ul {
    margin:		0;
    padding:		0;
    position:		absolute;
    right:		0;
    list-style-type:	none;
    display:		none;
}
 
ul#topmenu li:hover ul,
ul#topmenu .submenuhover ul {
    display:		block;
}
 
ul#topmenu ul li {
    width:		100%;
}
 
ul#topmenu2 a {
    display:		block;
    margin:		0.1em;
    padding:		0.2em;
    white-space:	nowrap;
    border-width:	1pt 1pt 0 1pt;
    -moz-border-radius:	0.4em;
    border-radius:	0.4em;*/
}

/* disabled tabs */
ul#topmenu span.tab {
    color:		#666666;
}

/* disabled drop/empty tabs */
ul#topmenu span.tabcaution {
    color:		#ff6666;
}

/* enabled drop/empty tabs */
ul#topmenu a.tabcaution {
    color:		#903050;
}
ul#topmenu a.tabcaution:hover {
    color:		#FFFFFF;
    background-color:	#904064;
}

fieldset.caution li {
    list-style-type:	none;
}

fieldset.caution a {
    color:		#903050;
}
fieldset.caution a:hover {
    color:		#ffffff;
    background-color:	#903050;
}

<?php if ($GLOBALS['cfg']['LightTabs']) { ?>
/* active tab */
ul#topmenu a.tabactive,
ul#topmenu2 a.tabactive {
    color:		#905070;
}

ul#topmenu a {
    color:		#909090;
}

ul#topmenu a:hover,
ul#topmenu2 a:hover {
    color:		#FFF;
}

ul#topmenu ul {
    background:		#183e68;
}
<?php } else { ?>
#topmenu {
    margin-top:		0.5em;
    padding:		0.1em 0.3em 0.1em 0.3em;
}

/* default tab styles */
ul#topmenu a,
ul#topmenu span {
    background-color:	<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border-width:	1pt 1pt 0 1pt;
    -moz-border-radius:	0.4em 0.4em 0 0;
    border-radius:	0.4em 0.4em 0 0;
}
 
ul#topmenu ul a {
    border:		1px solid #082E58;
    -moz-border-radius:	0.4em;
    border-radius:	0.4em;
}
 
/* enabled hover/active tabs */
ul#topmenu a.tab:hover {
    color: #FFFFFF;
}

ul#topmenu .tabactive {
    color: #903050;
}

/*vkk*/
ul#topmenu2 a {
    background-color:	<?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border-width:       1pt 1pt 0 1pt;
    -moz-border-radius: 0.4em;
    border-radius:      0.4em;
}

ul#topmenu2 a.tabactive {
    color:		#905070;
    -moz-border-radius:	0.3em;
    border-radius:	0.3em;
    text-decoration:	none; 
}

ul#topmenu2 a.tab:hover,
ul#topmenu2 a.tabactive:hover {
    color:		#FFF;
}

/* disabled drop/empty tabs */
ul#topmenu span.tab,
a.warning,
ul#topmenu span.tabcaution {
    cursor:		url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>error.ico), default;
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
    background-color:	#CCFFCC;
}

table.calendar th {
    background-color:	#D3DCE3;
}

table.calendar td.selected {
    background-color:	#FFCC99;
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
    border-bottom:	0.1em solid #669999;
    margin-bottom:	0.5em;
    padding-bottom:	0.5em;
}

div#tablestatistics table {
    float:		<?php echo $left; ?>;
    margin-bottom:	0.5em;
    margin-left:	0.5em;
}

div#tablestatistics table caption {
    margin-left:	0.5em;
}
/* END table stats */

/* server privileges */
table#tableuserrights {
    margin-top:		10px;
}

#tableuserrights td,
#tablespecificuserrights td,
#tabledatabases td {
    vertical-align:	middle;
}

#tabledatabases thead tr th a {
    color:		#204670;
}

#tabledatabases thead a:hover {
    color:		#FFFFFF;
}

#tabledatabases tbody a {
    color:		#000000;
}

/* END server privileges */

/* Heading */
#serverinfo {
    font-weight:	bold;
    margin-bottom:	0.5em;
}

#serverinfo .item {
    white-space:	nowrap;
    color:		#909090;
}

#serverinfo .item:hover {
    color:		#FFFFFF;
}

#span_table_comment {
    font-weight:	normal;
    font-style:		italic;
    white-space:	nowrap;
}

#serverinfo img {
    margin:		0 0.1em 0 0.2em;
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
    background-color:	#ffffcc;
    color:		#006600;
    border:		0.1em solid #000000;
    padding:		0.5em;
}

/* user privileges */
#fieldset_add_user_login div.item {
    padding-bottom:	0.3em;
    margin-bottom:	0.3em;
}

#fieldset_add_user_login label {
    float:		<?php echo $left; ?>;
    display:		block;
    width:		10em;
    max-width:		100%;
    text-align:		<?php echo $right; ?>;
    padding-right:	0.5em;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width:		100%;
    max-width:		100%;
}

#fieldset_add_user_login span.options {
    float:		<?php echo $left; ?>;
    display:		block;
    width:		12em;
    max-width:		100%;
    padding-right:	0.5em;
}

#fieldset_add_user_login input {
    width:		12em;
    clear:		<?php echo $right; ?>;
    max-width:		100%;
}

#fieldset_add_user_login span.options input {
    width:		auto;
}

#fieldset_user_priv div.item {
    float:		<?php echo $left; ?>;
    width:		9em;
    max-width:		100%;
}

#fieldset_user_priv div.item div.item {
    float:		none;
}

#fieldset_user_priv div.item label {
    white-space:	nowrap;
}

#fieldset_user_priv div.item select {
    width:		100%;
}

#fieldset_user_global_rights fieldset {
    float:		<?php echo $left; ?>;
}

/* END user privileges */

/* serverstatus */
div#serverstatus table caption a.top {
    float:		<?php echo $right; ?>;
}

div#serverstatus div#serverstatusqueriesdetails table,
div#serverstatus table#serverstatustraffic,
div#serverstatus table#serverstatusconnections {
    float:		<?php echo $left; ?>;
    margin-bottom:	5px;
}

#serverstatussection,
.clearfloat {
    clear:		both;
}

div#serverstatussection table {
    width:		100%;
    margin-bottom:	1em;
}
div#serverstatussection table .name {
    width:		18em;
}
div#serverstatussection table .value {
    width:		6em;
}

div#serverstatus table tbody td.descr a,
div#serverstatus table .tblFooters a {
    white-space:	nowrap;
}
div#serverstatus div#statuslinks a:before,
div#serverstatus div#sectionlinks a:before,
div#serverstatus table tbody td.descr a:before,
div#serverstatus table .tblFooters a:before {
    content:		'[';
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
    content:		']';
}
/* end serverstatus */

/* querywindow */
body#bodyquerywindow {
    margin:		0;
    padding:		0;
    background-image:	none;
    background-color:	<?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

div#querywindowcontainer {
    margin:		0;
    padding:		0;
    width:		100%;
}

div#querywindowcontainer fieldset {
    margin-top:		0;
}
/* END querywindow */

/* querybox */

div#sqlquerycontainer {
    float:		<?php echo $left; ?>;
}

div#tablefieldscontainer {
    float:		<?php echo $right; ?>;
    width:		29%;
}

div#tablefieldscontainer select {
    width:		100%;
}

textarea#sqlquery {
    width:		100%;
}
textarea#sql_query_edit{
    height:		7em;
    width:		95%;
    display:		block;
}

div#queryboxcontainer div#bookmarkoptions {
    margin-top:		0.5em;
}

/* end querybox */

/* main page */
#maincontainer {
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png);
    background-position: <?php echo $right; ?> bottom;
    background-repeat:	no-repeat;
    padding: 8px 2px;
}

#maincontainer h2 {
    color:		#204670;
}

#maincontainer a {
    color:		#909090;
}

#maincontainer a:hover {
    color:		#FFFFFF;
}

#mysqlmaininformation,
#pmamaininformation {
    float:		<?php echo $left; ?>;
    width:		49%;
}

#maincontainer ul {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_<?php echo $GLOBALS['text_dir']; ?>.png);
    vertical-align:	middle;
}

#maincontainer li {
    margin-bottom:	0.3em;
}

/* END main page */

<?php if ($GLOBALS['cfg']['MainPageIconic']) { ?>
/* iconic view for ul items */
li#li_create_database {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_newdb.png);
}

li#li_select_lang {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_lang.png);
}

li#li_select_mysql_collation,
li#li_select_mysql_charset {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
}

li#li_select_theme{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png);
}

li#li_server_info,
li#li_server_version{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);
}

li#li_mysql_privilegs,
li#li_user_info{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_rights.png);
}

li#li_mysql_status{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_status.png);
}

li#li_mysql_variables{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_vars.png);
}

li#li_mysql_processes{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_process.png);
}

li#li_mysql_collations{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
}

li#li_mysql_engines{
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_engine.png);
}

li#li_mysql_binlogs {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_tbl.png);
}

li#li_mysql_databases {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_db.png);
}

li#li_export {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_export.png);
}

li#li_import {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_import.png);
}

li#li_change_password {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_passwd.png);
}

li#li_log_out {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_loggoff.png);
}

li#li_pma_docs,
li#li_pma_wiki {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_docs.png);
}

li#li_phpinfo {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>php_sym.png);
}

li#li_pma_homepage {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_home.png);
}

li#li_switch_dbstats {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_dbstatistics.png);
}

li#li_flush_privileges {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_reload.png);
}

li#li_user_preferences {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_tblops.png);
}

/* END iconic view for ul items */
<?php } /* end if $GLOBALS['cfg']['MainPageIconic'] */ ?>


#body_browse_foreigners {
    background:		<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    margin:		0.5em 0.5em 0 0.5em;
}

#bodyquerywindow {
    background:		<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

#bodythemes {
    width:		500px;
    margin:		auto;
    text-align:		center;
}

#bodythemes img {
    border:		0.1em solid black;
}

#bodythemes a:hover img {
    border:		0.1em solid red;
}

#fieldset_select_fields {
    float:		<?php echo $left; ?>;
}

#selflink {
    clear:		both;
    display:		block;
    margin-top:		1em;
    margin-bottom:	1em;
    padding-top:	5px;
    width:		100%;
    border-top:		0.1em solid silver;
    text-align:		<?php echo $right; ?>;
}

#selflink a{
    color:		#909090;
}

#selflink a:hover {
    color:		#FFFFFF;
}

#table_innodb_bufferpool_usage,
#table_innodb_bufferpool_activity,
#div_mysql_charset_collations table,
#qbe_div_table_list,
#qbe_div_sql_query {
    float:		<?php echo $left; ?>;
}

#div_table_order,
#div_table_rename,
#div_table_copy,
#div_partition_maintenance,
#div_referential_integrity,
#div_table_removal,
#div_table_maintenance {
    min-width:		48%;
    float:		<?php echo $left; ?>;
}

#div_table_options {
    clear:		both;
    min-width:		48%;
    float:		<?php echo $left; ?>;
}

label.desc {
    width:		30em;
    float:		<?php echo $left; ?>;
}

label.desc sup {
    position:		absolute;
}

code.sql,
div.sqlvalidate {
    display:		block;
    padding:		0.3em;
    margin-top:		0;
    margin-bottom:	0;
    border-width:	0;
    max-height:		10em;
    overflow:		auto;
    background:		<?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

#main_pane_left {
    width:		50%;
    float:		<?php echo $left; ?>;
}

#main_pane_right {
    margin-left:	50%;
    margin-right:	1px;
    margin-bottom:	4px;
    padding:		0;
    padding-left:	1em;
}

.group {
    border-left:	1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border-top:		1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border-top-left-radius: 0.4em;
    -moz-border-radius-topleft: 0.4em;
    margin:		0 0 10px 0;
}

.group h2 {
    background-color:	<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    padding:		0.1em 0.3em;
    margin-top:		0;
}

.group-cnt {
    padding:		0 0 0 0.5em; 
    display:		inline-block;
    width:		98%;
}

textarea#partitiondefinition {
    height:		3em;
}

/* for elements that should be revealed only via js */
.hide {
    display:		none;
}

#li_select_server {
    list-style-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);
    padding-bottom:	0.3em;
    border-bottom:	0.3em solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    margin-bottom:	0.3em;
}

#list_server {
    list-style-image:	none;
}

/**
  *  Progress bar styles
  */
div.upload_progress_bar_outer {
    border:		1px solid black; 
    width:		202px;
}

div.upload_progress_bar_inner {
    background-color:	<?php echo (isset($_SESSION['userconf']['custom_color']) ? $_SESSION['userconf']['custom_color'] : $GLOBALS['cfg']['NaviBackground']); ?>;
    width:		0px;
    height:		12px;
    margin:		1px;
}

table#serverconnection_src_remote,
table#serverconnection_trg_remote,
table#serverconnection_src_local, 
table#serverconnection_trg_local {
    float:		left;
}
/**
  * Validation error message styles
  */
.invalid_value {
    background:		#F00;
}

/**
  *  Ajax notification styling
  */
.ajax_notification {
    top:		0px; /** The notification needs to be shown on the top of the page */
    position:		fixed;
    margin-top:		0;
    margin-right:	auto;
    margin-bottom:	0;
    margin-left:	auto;
    padding:		3px 5px; /** Keep a little space on the sides of the text */
    min-width:		70px;
    max-width:		350px; /** This value might have to be changed */
    background-color:	#FFFFDD;
    border:		1px solid #FFD700;
    color:		#000;
    z-index:		1100; /** If this is not kept at a high z-index, the jQueryUI modal dialogs (z-index:1000) might hide this */
    text-align:		center;
    display:		block;
    left:		0;
    right:		0;
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif);
    background-repeat:	no-repeat;
    background-position: 2%;
}

#loading_parent {
/** Need this parent to properly center the notification division */
    position:		relative;
    width:		100%;
}

/**
* Export and Import styles
*/

.exportoptions h3,
.importoptions h3 {
    border-bottom:	1px #999999 solid; 
    font-size:		110%;
}

.exportoptions ul,
.importoptions ul,
.format_specific_options ul {
    list-style-type:	none;
    margin-bottom:	15px;
}

.exportoptions li,
.importoptions li {
    margin:		7px;
}

.exportoptions label,
.importoptions label,
.exportoptions p,
.importoptions p {
    margin:		5px;
    float:		none;
}

#csv_options label.desc,
#ldi_options label.desc,
#latex_options label.desc,
#output label.desc{
    float:		left;
    width:		15em;
}

.exportoptions,
.importoptions {
    margin:		20px 30px 30px 10px 
}

.exportoptions #buttonGo,
.importoptions #buttonGo {
    padding:		5px 30px;
    -moz-border-radius:	11px;
    -webkit-border-radius: 11px;
    border-radius:	11px;
    background:		-webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background:		-moz-linear-gradient(top,  #ffffff,  #cccccc);
    filter:		progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#cccccc');
    border:		1px solid #444444;
    cursor:		pointer;
}

.format_specific_options h3 {
    margin:		10px 0px 0px 10px;
    border:		0px;
}

.format_specific_options {
    border:		1px solid #999999;
    margin:		7px 0px;
    padding:		3px;
}

p.desc {
    margin:		5px;
}

/**
 * Export styles only
 */

select#db_select,
select#table_select {
    width:		400px;
}

.export_sub_options {
    margin:		20px 0px 0px 30px;
}

.export_sub_options h4 {
    border-bottom:	1px #999999 solid;
}

.export_sub_options li.subgroup {
    display:		inline-block;   
    margin-top:		0;
}

.export_sub_options li {
    margin-bottom:	0;
}

#quick_or_custom,
#output_quick_export {
    display:		none;
}

/**
 * Import styles only
 */

.importoptions #import_notification {
    margin:		10px 0px;
    font-style:		italic;
}

input#input_import_file {
    margin:		5px;
}

.formelementrow {
    margin:		5px 0px 5px 0px;
}

/**
 * ENUM/SET editor styles
 */
p.enum_notice {
    margin:		5px 2px;
    font-size:		80%; 
}

#enum_editor {
    display:		none;
    position:		fixed;
    _position:		absolute; /* hack for IE */
    z-index:		101;
    overflow-y:		auto;
    overflow-x:		hidden;
}

#enum_editor_no_js {
    margin:		auto auto;
}

#enum_editor,
#enum_editor_no_js {
    background:		#D0DCE0;
    padding:		15px;
}

#popup_background {
    display:		none; 
    position:		fixed;
    _position:		absolute; /* hack for IE6 */
    width:		100%;
    height:		100%;
    top:		0;
    left:		0;
    background:		#000;
    z-index:		100;
    verflow:		hidden;
}

a.close_enum_editor {
    float:		right;
}

#enum_editor #values,
#enum_editor_no_js #values {
    margin:		15px 0px;
    width:		100%;
}

#enum_editor #values input,
#enum_editor_no_js #values input {
    margin:		5px 0px;
    float:		top;
    width:		100%;
}

#enum_editor_output {
    margin-top:		50px;
}

/**
 * Table structure styles
 */
.structure_actions_dropdown {
    position:		absolute;
    padding:		3px;
    display:		none;
    z-index:		100; 
}

.structure_actions_dropdown a {
    display:		block;
}

td.more_opts {
    display:		none;
    white-space:	nowrap;
}

iframe.IE_hack {
    z-index:		1;
    position:		absolute;
    display:		none;
    border:		0;
    filter:		alpha(opacity=0);
}

/* config forms */
.config-form ul.tabs {
    margin:		1.1em 0.2em 0;
    padding:		0 0 0.3em 0;  
    list-style:		none;
    font-weight:	bold;
}

.config-form ul.tabs li {
    float:		<?php echo $left; ?>;
}

.config-form ul.tabs li a {
    display:		block;
    margin:		0.1em 0.2em 0;
    padding:		0.1em 0.4em;  
    white-space:	nowrap;
    text-decoration:	none;
    border:		1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom:	none;
    background-color:	<?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    -moz-border-radius:	0.4em 0.4em 0 0;
    border-radius:	0.4em 0.4em 0 0;
}

.config-form ul.tabs li a:hover,
.config-form ul.tabs li a.active {
    color:		#905070;
}

.config-form ul.tabs li a:hover {
    color:		#FFF;
}

.config-form fieldset {
    margin-top:		0;
    padding:		0;
    clear:		both;
}

.config-form legend {
    display:		none;
}

.config-form fieldset p {
    margin:		0;
    padding:		0.5em;
}

.config-form fieldset .errors {
/* form error list */
    margin:		0 -2px 1em -2px;
    padding:		0.5em 1.5em;
    background:		#FBEAD9;
    border:		0 #C83838 solid;
    border-width:	1px 0;
    list-style:		none; 
    font-family:	sans-serif;
    font-size:		small;
}

.config-form fieldset .inline_errors {
/* field error list */
    margin:		0.3em 0.3em 0.3em 0;
    padding:		0;
    list-style:		none;
    color:		#9A0000;
    font-size:		small;
}

.config-form fieldset th {
    padding:		0.3em 0.3em 0.3em 0.5em;
    text-align:		left;
    vertical-align:	top;
    width:		40%;
    background:		transparent;
}

.config-form fieldset .doc,
.config-form fieldset .disabled-notice {
    margin-left:	1em;
}

.config-form fieldset .disabled-notice {
    font-size:		80%;
    text-transform:	uppercase;
    color:		#E00;
    cursor:		help;
}

.config-form fieldset td {
    padding-top:	0.3em;
    padding-bottom:	0.3em;
    vertical-align:	top;
}

.config-form fieldset th small {
    display:		block;
    font-weight:	normal;
    font-family:	sans-serif;
    font-size:		x-small;
    color:		#909090;
}

.config-form fieldset th,
.config-form fieldset td {
    border-top:		1px <?php echo $GLOBALS['cfg']['BgTwo']; ?> solid;
}

fieldset .group-header th {
    background:		<?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

fieldset .group-header + tr th {
    padding-top:	0.6em;
}

fieldset .group-field-1 th,
fieldset .group-header-2 th {
    padding-left:	1.5em;
}

fieldset .group-field-2 th,
fieldset .group-header-3 th {
    padding-left:	3em;
}
 
fieldset .group-field-3 th {
    padding-left: 4.5em;
}

fieldset .disabled-field th,
fieldset .disabled-field th small,
fieldset .disabled-field td {
    color:		#666;
    background-color:	#ddd;
}

.config-form .lastrow {
    border-top:		1px #000 solid;
}

.config-form .lastrow {
    background:		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;;
    padding:		0.5em;
    text-align:		center;
}

.config-form .lastrow input {
    font-weight:	bold;
}

/* form elements */

.config-form span.checkbox {
    padding:		2px;
    display:		inline-block;
}

.config-form span.checkbox.custom {
    padding:		1px;
}

.config-form .field-error {
     border-color:	#A11 !important;
}

.config-form input[type="text"],
.config-form select,
.config-form textarea {
    border:		1px #A7A6AA solid;
    height:		auto;
}

.config-form input[type="text"]:focus,
.config-form select:focus,
.config-form textarea:focus {
    border:		1px #6676FF solid;
    background:		#F7FBFF;
}

.config-form .field-comment-mark {
    font-family:	serif;
    color:		#007;
    cursor:		help;
    padding:		0 0.2em;
    font-weight:	bold;
    font-style:		italic;
}

.config-form .field-comment-warning {
    color:		#A00;
}

/* error list */
.config-form dd {
    margin-left:	0.5em;
}

.config-form dd:before {
    content:		"\25B8  ";
}

.click-hide-message {
    cursor:		pointer; 
}

.prefsmanage_opts {
    margin-left:	2em;
}

#prefs_autoload {
    margin-bottom:	0.5em;
}

#togglequerybox {
    font-weight:	bold;
    color:		#909090;
    cursor:		pointer;
}

#togglequerybox:hover,
div.formelement
 {
    color:		#FFF;
}

#initials_table {
    color:		#082E58;
}
