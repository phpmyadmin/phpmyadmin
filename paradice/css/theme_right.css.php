<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Paradice
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Paradice
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */
html {
	margin: 0;
	padding: 0;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding:            0;
    margin:             0.5em;
    color:              <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    font-size:          1em;
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

a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #1F457E;
}

a:hover {
    text-decoration:    underline;
    color:              #8897AE;
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
    background-color:   #ff9900;
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_th.png);
    background-repeat:  repeat-x;
    background-position: top;
}

th a:link,
th a:active,
th a:visited {
    color:              #000000;
    text-decoration:    underline;
}

th a:hover {
    color:              #666666;
    text-decoration:    none;
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

th.td{
    font-weight: 		 normal;
    color: 				 #000000;
    background-color: 	 transparent;
    background-image: 	 url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_th.png);
}

select, textarea, input {
	border: 			 1px solid #3674CF;
}

select, textarea{
    color:            	 #000000;
    background-color: 	 #FFFFFF;
}

input.textfield{
    color:            	 #000000;
    background-color: 	 #FFFFFF;
}

a.h1:link, a.h1:active, a.h1:visited{
    font-weight:      	 bold;
    color:            	 #000000;
}

a.h1:hover{
    font-weight:      	 bold;
    color:            	 #666666;
}

a.h2:link, a.h2:active, a.h2:visited{
    font-weight:      	 bold;
    color:            	 #000000;
}

a.h2:hover{
    font-weight:      	 bold;
    color:            	 #666666;
}

a.drop:link, a.drop:visited, a.drop:active{
    color:            	 #666666;
}

a.drop:hover{
    color:            	 #ffffff;
    background-color: 	 #666666;
    text-decoration:  	 none;
}

textarea {
    overflow:           visible;
}

fieldset {
    margin-top:         1em;
    border:             <?php echo $GLOBALS['cfg']['ThBackground']; ?> solid 1px;
    padding:            0.5em;
    background:         <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

fieldset fieldset {
    margin:             0.8em;
}

fieldset legend {
    color:       		<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    font-weight: 		bold;
    background-color: 	<?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    padding:            2px;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}

table caption,
table th,
table td {
    padding:            0.1em 0.5em 0.1em 0.5em;
    margin:             0.1em;
    vertical-align:     top;
}

img,
input,
select,
button {
    vertical-align:     middle;
}


/******************************************************************************/
/* classes */

fieldset.tblFooters {
    margin-top:         0;
    margin-bottom:      0.5em;
    /* avoid a thick line since this should be used under another fieldset */
    border-top:         0;
    text-align:         <?php echo $right; ?>;
    float:              none;
    clear:              both;
}

.login fieldset.tblFooters {
    padding:			3px;
}

.login fieldset.tblFooters input[type=submit] {
    background-color:	#FFFFFF;
    color:				<?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    font-weight:		bold;
    padding-left:		20px;
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_go.png);
    background-repeat:  no-repeat;
    background-position:center left;
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
table tr.odd th,
.odd {
    background: <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

/* even items 2,4,6,8,... */
table tr.even th,
.even {
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

/* odd table rows 1,3,5,7,... */
table tr.odd th,
table tr.odd,
table tr.even th,
table tr.even {
    text-align:         <?php echo $left; ?>;
}

<?php if ($GLOBALS['cfg']['BrowseMarkerEnable']) { ?>
/* marked table rows */
table tr.marked th,
table tr.marked {
    background:   <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color:   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['BrowsePointerEnable']) { ?>
/* hovered items */
.odd:hover,
.even:hover,
.odd a:hover,
.even a:hover,
.hover {
    background: <?php echo $GLOBALS['cfg']['BrowsePointerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th {
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
    font-size:          100%;
	font-family:        <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
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
.icon {
    vertical-align:     middle;
    margin-right:       0.3em;
    margin-left:        0.3em;
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
.notice {
    color:              #000000;
    background-color:   #FFFFDD;
}
h1.notice,
div.notice {
    margin:             0.5em 0 0.5em 0;
    border:             1px solid #FFD700;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
.notice h1 {
    border-bottom:      1px solid #FFD700;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.warning {
    color:              #CC0000;
    background-color:   #FFFFCC;
}
p.warning,
h1.warning,
div.warning {
    margin:             0.5em 0 0.5em 0;
    border:             1px solid #CC0000;
    <?php if ( $GLOBALS['cfg']['ErrorIconic'] ) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_warn.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
.warning h1 {
    border-bottom:      1px solid #cc0000;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.error {
    background-color:   #FFFFCC;
    color:              #ff0000;
}

h1.error,
div.error {
    margin:             0.5em 0 0.5em 0;
    border:             1px solid #ff0000;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.5em;
    <?php } ?>
}
div.error h1 {
    border-bottom:      1px solid #ff0000;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.confirmation {
    background-color:   #FFFFCC;
}
fieldset.confirmation {
    border:             1px solid #FF0000;
}
fieldset.confirmation legend {
    border-left:        1px solid #FF0000;
    border-right:       1px solid #FF0000;
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

.data caption {
	color:				#FFFFFF;
}

.tblcomment {
	font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    font-size:          70%;
    font-weight:        normal;
    color:              #000099;
}

.tblHeaders {
	background-color:	<?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    font-weight:        bold;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
    background-repeat:  repeat-x;
    background-position:top;
}

.tblFooters {
    font-weight:        normal;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
	background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
    background-repeat:  repeat-x;
    background-position:top;
}

.tblHeaders a:link,
.tblHeaders a:active,
.tblHeaders a:visited,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
    color:              #FFFFCC;
	text-decoration:    underline;
}

.tblHeaders a:hover,
.tblFooters a:hover {
    text-decoration:    none;
    color:              #FFFFFF;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:              #CC0000;
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
    background-color:   #FFFFFF;
	background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>login_bgnd.png);
	background-repeat:  repeat-x;
    background-position: top;
}

body.loginform div.container {
    text-align: <?php echo $left; ?>;
    width: 30em;
    margin: 0 auto;
}

form.login label {
    float: <?php echo $left; ?>;
    width: 11em;
    font-weight: bolder;
}

form.login fieldset div.item input {
	background-color:   #FFFFFF;
	background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>loginfield_bgnd.png);
    background-repeat:  repeat-x;
    background-position: bottom;
    margin-bottom:		3px;
    width:				14em;
}

form.login fieldset div.item select {
	width:				14em;
	border:				1px solid <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

.loginform .container fieldset select[name=lang] {
    width:				25em;
}

/******************************************************************************/
/* specific elements */

/* topmenu */
ul#topmenu {
    font-weight:        bold;
    list-style-type:    none;
    margin:             0;
    padding:            0;
    border:				0;
}

ul#topmenu li {
    float:              <?php echo $left; ?>;
    margin:             0;
    padding:            0;
    vertical-align:     middle;
	border-bottom: 		0;
}

#topmenu img {
    vertical-align:     middle;
    margin-<?php echo $right; ?>:       0.1em;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    display:            block;
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

/* disabled tabs */
span.tab {
    color:               #FFFFFF;
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header_disabled.png);
	background-repeat:   repeat-x;
    background-position: top;
}

/* disabled drop/empty tabs */
span.tabcaution {
    color:               #FFFFFF;
    background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header_disabled.png);
	background-repeat:   repeat-x;
    background-position: top;
	text-decoration:	 none;
    padding-top:         4px;
    padding-bottom:      4px;
    padding-left:        4px;
    padding-right:       10px;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color:              #FF0000;
}
a.tabcaution:hover {
    color: 				#FFFFFF;
    background-color:   #FF0000;
}

<?php if ($GLOBALS['cfg']['LightTabs']) { ?>
/* active tab */
a.tabactive {
    color:              black;
}
<?php } else { ?>
#topmenu {
    margin-top:         0.5em;
    padding:            0.1em 0.3em 0.1em 0.3em;
}

ul#topmenu li {
    border-bottom:      0;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-top:			 0;
    border-right:		 1px;
    border-bottom:		 0;
    border-left:		 0;
	border-style:		 solid;
    border-color: 		 #FFFFFF;
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header2.png);
    background-repeat:   repeat-x;
    background-position: top;
    /* overwrite default button look */
    height:				21px;
}

ul#topmenu li a.tab {
	color:               #FFFFFF;
}

ul#topmenu li a.tab:hover {
	color:               #FFFFFF;
	text-decoration:		none;
}

ul#topmenu li:hover {
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header2.png);
	text-decoration:	 none;
	color:               #FFFFFF;
}

/* enabled drop/empty tabs */
ul#topmenu li a.tabcaution {
    color:               #FFFFFF;
    background-color:    #cc0000;
    background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    padding: 			 4px 8px 4px 8px;
    border: 			 0;
    border-right: 		 1px solid #FFFFFF;
}
ul#topmenu li a.tabcaution:hover {
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_error2.png);
	text-decoration:		none;
}

/* enabled hover/active tabs */
a.tabactive {
    color:               #000000;
    background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_headerActive.png);
    background-repeat:   repeat-x;
    background-position: top;
    background-color:    #ffffff;
    padding: 		 	 4px 8px 4px 8px;
	border: 			 0;
    border-right: 		 1px solid #FFFFFF;
	border-left: 		 1px solid #FFFFFF;
	color:               #000000;
}

a.tabactive:hover {
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
	padding: 		 	 4px 8px 4px 8px;
	color:				 #FFFFFF;
	text-decoration:		none;
}

a.tab:link, a.tab:active,a.tab:hover, a.tab:visited {
    padding: 			 4px 8px 4px 8px;
	border: 			 0;
    border-right: 		 1px solid #FFFFFF;
    color:               #FFFFFF;
    text-decoration:		none;
}

a.tab:hover,
.tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
    text-decoration:		none;
}

/* disabled drop/empty tabs */
span.tab,
a.warning,
span.tabcaution {
    cursor:             url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>error.ico), default;
}
<?php } ?>
/* end topmenu */


/* Calendar */
table.calendar {
    width:              100%;
}
table.calendar td {
    text-align:         center;
}
table.calendar td a {
    display:            block;
}

table.calendar td a:hover {
    background-color:   #CCFFCC;
}

table.calendar th {
    background-color:   #D3DCE3;
}

table.calendar td.selected {
    background-color:   #FFCC99;
}

img.calendar {
    border:             none;
}
form.clock {
    text-align:         center;
}
/* end Calendar */


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

div#tablestatistics table caption {
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
    border-bottom:      1px solid <?php echo $GLOBALS['cfg']['ThBackground']?>;
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

div#queryboxcontainer div#bookmarkoptions {
    margin-top: 0.5em;
}
/* end querybox */

/* main page */
#maincontainer {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png);
    background-position: <?php echo $right; ?> bottom;
    background-repeat: no-repeat;
    border-bottom: 1px solid <?php echo $GLOBALS['cfg']['ThBackground']?>;
}
#selflink {
    margin-top: 1em;
    margin-bottom: 1em;
	padding-top: 2px;
    width: 100%;
    border-top: 0;
    text-align: right;
	vertical-align: bottom;
}
div#tablestatistics {
    border-bottom: 0;
}
#queryfieldscontainer {
	border: 0;
}
#mysqlmaininformation h1, #pmamaininformation h1 {
	background-image: 	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
	background-position:left top;
	background-repeat: 	repeat-x;
	color: 				#FFFFFF;
	text-align: 		center;
	padding: 			1px;
	margin: 			0;
}

#mysqlmaininformation,
#pmamaininformation {
    float: <?php echo $left; ?>;
    width: 49%;
    border:     		1px solid <?php echo $GLOBALS['cfg']['ThBackground']?>;
	background-color:	#FBFBFF;
	margin-top:			5px;
	margin-left:		5px;
	margin-bottom: 		1em;
}

#maincontainer ul {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_<?php echo $GLOBALS['text_dir']; ?>.png);
    vertical-align: middle;
}

#maincontainer li {
    margin-bottom:  0.3em;
}


#li_select_lang form select {
	width: 180px;
}

li#li_server_info,
li#li_server_info2,
li#li_mysql_proto,
li#li_user_info,
li#li_used_php_extension,
li#li_mysql_client_version,
li#li_select_server,
li#li_server_info,
li#li_server_version {
	color: #888888;
}

#form_fontsize_selection label {
    color: #1F457E;
}

#mysqlmaininformation {
	background-image: 	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>mysql_info.jpg);
	background-position:right bottom;
	background-repeat: 	no-repeat;
}
#pmamaininformation {
	background-image: 	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pma_info.jpg);
	background-position:right bottom;
	background-repeat: 	no-repeat;
}
div.formelementrow {
	border: 0;
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

li#li_select_mysql_collation,
li#li_select_mysql_charset {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
    color:            #1F457E;
}

li#li_select_theme{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png);
}

li#li_server_info,
li#li_server_info2,
li#li_server_version {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
    font-size:			80%;
}

li#li_user_info{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
	font-size:			80%;
	margin-bottom: 2em;
}

li#li_mysql_proto{
	list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
	font-size:			80%;
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

li#li_pma_docs,
li#li_pma_wiki {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_docs.png);
}

li#li_phpinfo {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>php_sym.png);
}

li#li_pma_homepage {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_home.png);
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

li#li_used_php_extension {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
	font-size: 80%;
	margin-bottom: 2em;
}

li#li_mysql_client_version {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
    font-size: 80%;
}

li#li_select_server {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
    font-size: 80%;
}

li#li_select_fontsize {
	list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
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

label.desc {
    width: 30em;
    float: <?php echo $left; ?>;
}
