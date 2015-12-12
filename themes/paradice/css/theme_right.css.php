<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Paradice
 *
 * @version $Id: theme_right.css.php 38 2011-01-14 18:12:31Z andyscherzinger $
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
    font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : (
        isset($_COOKIE['pma_fontsize']) ? $_COOKIE['pma_fontsize'] : '82%'));?>;
}

input, select, textarea {
    font-size: 1em;
    padding: 2px;
}

input, textarea {
    -moz-border-radius: 12px;
    -webkit-border-radius: 12px;
    border-radius: 12px;
}

select {
    padding: 2px 2px 2px 4px;
    -moz-border-radius: 12px 0 0 12px;
    -webkit-border-radius: 12px 0 0 12px;
    border-radius: 12px 0 0 12px;
}

:focus {
    outline: none;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding:            0;
    margin:             0.5em;
    color:              <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background:         <?php echo (isset($_SESSION['tmp_user_values']['custom_color']) ? $_SESSION['tmp_user_values']['custom_color'] : $GLOBALS['cfg']['MainBackground']); ?>;
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

a, a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #1F457E;
    cursor:             pointer;
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
	border: 			 1px solid #79A2DF;
}

input [type="checkbox"], input [type="radio"], input [type="file"] {
	border: 			 0;
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
    height:             <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
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
    font-weight:        bold;
    color:              <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    background-color:   <?php echo 'OPERA' != PMA_USR_BROWSER_AGENT ? 'transparent' : $GLOBALS['cfg']['BgTwo']; ?>;
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
div.tools {
    border: 1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    padding: 0.2em;
}

div.tools,
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

.login fieldset.tblFooters input[type=submit], input[type=submit] {
    padding: 2px 10px;    
    -moz-border-radius: 12px;
    -webkit-border-radius: 12px;
    border-radius: 12px2;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#cccccc');
    border: 1px solid #ACACAC;
    cursor: pointer;
    color: #606060;  
    -moz-box-shadow: inset 0 3px 8px #ffffff;
    -webkit-box-shadow: inset 0 3px 8px #ffffff;
    box-shadow: inset 0 3px 8px #ffffff;
}

input[type=submit] {
    margin-right: 2px;
    margin-bottom: 2px;
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
td.marked,
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
.hover,
.structure_actions_dropdown {
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
.warning h1,
div.error h1 {
    border-bottom:      2px solid;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.notice {
    color:              #000000;
    background-color:   #FFFFDD;
}

h1.notice,
div.success,
div.notice,
div.warning,
div.error,
div.footnotes {
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

.success {
    color:              #005E20;
    background-color:   #E5F7E3;
}
h1.success,
div.success {
    border-color:       #C5E1C8;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_success.png);
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
div.success h1 {
	border-bottom:       1px solid #C5E1C8;
}
.success h1 {
    border-color:       #C5E1C8;
}

.notice, .footnotes {
    color:              #004A80;
    background-color:   #E8F8FE;
}
h1.notice,
div.notice,
div.footnotes {
    border-color:       #CFDFE5;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_info.png);
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

.notice h1 {
    border-bottom:      1px solid #CFDFE5;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}

.warning {
    color:              #555555;
    background-color:   #FEFFD5;
}
p.warning,
h1.warning,
div.warning {
    margin:             0.5em 0 0.5em 0;
    border:             1px solid #EEEB5B;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_warn.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } ?>
}
.warning h1 {
    border-bottom:      1px solid #cc0000;
    font-weight:        bold;
    text-align:         <?php echo $left; ?>;
    margin:             0 0 0.2em 0;
}
div.warning h1 {
	border-bottom:       1px solid #EEEB5B;
}

.error {
    background-color:   #FFEBEB;
    color:              #9E0B0F;
}

h1.error,
div.error {
    margin:             0.5em 0 0.5em 0;
    border:             1px solid #F5C1C2;
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
    <?php } ?>
}

div.error h1 {
    border-bottom:      1px solid #F5C1C2;
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

div.tools,
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
div.tools a:link,
div.tools a:visited,
div.tools a:active,
.tblFooters a:link,
.tblFooters a:active,
.tblFooters a:visited {
    color:              #FFFFCC;
	text-decoration:    underline;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
    color:              #FFFFFF;
    text-decoration:    none;
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
    width: 10em;
    font-weight: bolder;
}

form.login fieldset div.item input {
    margin-bottom:		3px;
    width:				14em;
}

form.login fieldset div.item select {
	width:				14em;
	border:				1px solid <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

.loginform .container fieldset select[name=lang] {
    width:				24em;
}

.commented_column {
    border-bottom: 1px dashed black;
}

.column_attribute {
    font-size: 100%;
}

/******************************************************************************/
/* specific elements */

/* topmenu */
div#topmenucontainer {
    padding-bottom:     18px;
}

ul#topmenu, ul#topmenu2, ul.tabs {
    font-weight:        bold;
    list-style-type:    none;
    margin:             0;
    padding:            0;
    border:				0;
}

ul#topmenu2 {
    margin: 0;
    height: 2em;
    clear: both;
}

ul#topmenu li, ul#topmenu2 li {
    float:              <?php echo $left; ?>;
    margin:             0;
    padding:            0;
    vertical-align:     middle;
	border-bottom: 		0;
}

#topmenu img, #topmenu2 img {
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

ul#topmenu .submenu {
    position:           relative;
}

ul#topmenu ul {
    margin:             0;
    padding:            0;
    position:           absolute;
    right:              0;
    list-style-type:    none;
    display:            none;
}

ul#topmenu li:hover ul, ul#topmenu .submenuhover ul {
    display:            block;
}

ul#topmenu ul li {
    width:              100%;
}

ul#topmenu2 a {
    display:            block;
    margin:             0.1em;
    padding:            0.2em;
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
	padding-top: 		 4px;
	padding-bottom:		 4px;
	padding-left:		 4px;
	padding-right:		 10px;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color:              #FF0000;
}
a.tabcaution:hover {
    color: #FFFFFF;
    background-color:   #FF0000;
}
fieldset.caution a {
    color:              #FF0000;
}
fieldset.caution a:hover {
    color:              #ffffff;
    background-color:   #FF0000;
}

<?php if ($GLOBALS['cfg']['LightTabs']) { ?>
/* active tab */
ul#topmenu a.tabactive, ul#topmenu2 a.tabactive {
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
ul#topmenu .tab, ul#topmenu .tabcaution, ul#topmenu .tabactive {
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

ul#topmenu2 .tab, ul#topmenu2 .tabcaution, ul#topmenu2 .tabactive {
    -moz-border-radius: 12px;
    -webkit-border-radius: 12px;
    border-radius: 12px;
    background-color:   <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    padding: 4px 12px 4px 12px;
	margin-right: 4px;
}

ul#topmenu2 .tab:hover, ul#topmenu2 .tabcaution:hover, ul#topmenu2 .tabactive:hover {
    -moz-border-radius: 12px;
    -webkit-border-radius: 12px;
    border-radius: 12px;
    background-color:   #DDDDDD;
    padding: 4px 12px 4px 12px;
	margin-right: 4px;
}

ul#topmenu2 li a.tab:hover, ul#topmenu2 li a.tabcaution:hover, ul#topmenu2 li a.tabactive:hover {
    text-decoration:	 none;
}

ul#topmenu li a.tab {
	color:               #FFFFFF;
}

ul#topmenu li a.tab:hover {
	color:               #FFFFFF;
	text-decoration:     none;
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
ul#topmenu > li > a.tabactive {
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

ul#topmenu a.tabactive:hover {
	background-image:    url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
	padding: 		 	 4px 8px 4px 8px;
	color:				 #FFFFFF;
	text-decoration:		none;
}

ul#topmenu a.tab:link, ul#topmenua.tab:active, ul#topmenu a.tab:hover, ul#topmenu a.tab:visited {
    padding: 			 4px 8px 4px 8px;
	border: 			 0;
    border-right: 		 1px solid #FFFFFF;
    color:               #FFFFFF;
    text-decoration:		none;
}

ul#topmenu a.tab:hover,
ul#topmenu .tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    background-image:	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
    text-decoration:		none;
}

/* disabled drop/empty tabs */
ul#topmenu span.tab,
a.warning,
ul#topmenu span.tabcaution {
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
    width: 99%;
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
#maincontainer {
    
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
li#li_select_mysql_charset,
li#li_used_php_extension,
li#li_web_server_software,
li#li_mysql_client_version,
li#li_server_info,
li#li_server_version {
	color: #888888;
}

#form_fontsize_selection label {
    color: #142F56;
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

li#li_select_mysql_collation {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_asci.png);
    color:			  #1F457E;
}

li#li_select_theme{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_theme.png);
}

li#li_custom_color{
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_color.png);
}

#myRainbow {
	padding-right: 4px;
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
}

li#li_select_mysql_charset {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
	font-size:			80%;
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

li#li_user_preferences {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>b_tblops.png);
}

li#li_used_php_extension {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
	font-size: 80%;
	margin-bottom: 2em;
}

li#li_pma_version {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
}

li#li_web_server_software {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
    font-size: 80%;
}

li#li_mysql_client_version {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>item_ltr.png);
    font-size: 80%;
}

li#li_select_server {
    list-style-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_host.png);
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

#div_table_copy,
#div_partition_maintenance,
#div_referential_integrity,
#div_table_removal,
#div_table_maintenance {
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

label.desc sup {
    position: absolute;
}

code.sql, div.sqlvalidate {
    display:            block;
    padding:            0.3em;
    margin-top:         0;
    margin-bottom:      0;
    border:             #79A2DF solid 1px;
    border-bottom:      0;
    max-height:         10em;
    overflow:           auto;
    background:         <?php echo $GLOBALS['cfg']['BgOne']; ?>;
}

#main_pane_left {
    width:              60%;
    float:              <?php echo $left; ?>;
    padding-top:        1em;
}

#main_pane_right {
    margin-<?php echo $left; ?>: 60%;
    padding-top: 1em;
    padding-<?php echo $left; ?>: 1em;
}

.group {
    border: 1px solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    margin-bottom:      1em;
}

.group h2 {
    font-size:          1em;
    font-weight:        bold;
    background-image: 	url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tbl_header.png);
	background-position:left top;
	background-repeat: 	repeat-x;
	color: 				#FFFFFF;
	padding: 			2px;
	margin: 			0;
	display:            block;
	border:     		1px solid #FBFBFF;
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
    background-color: <?php echo (isset($_SESSION['userconf']['custom_color']) ? $_SESSION['userconf']['custom_color'] : $GLOBALS['cfg']['NaviBackground']); ?>;
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
    min-width: 70px;
    max-width: 350px;   /** This value might have to be changed */
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
    padding: 2px 10px;
    -moz-border-radius: 12px;
    -webkit-border-radius: 12px;
    border-radius: 12px2;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#cccccc');
    border: 1px solid #ACACAC;
    cursor: pointer;
    color: #606060;
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

}

#enum_editor_output {
    margin-top: 50px;
}

/**
 * Table structure styles
 */
.structure_actions_dropdown {
    position: absolute;
    padding: 3px;
    display: none;
    z-index: 100;
}

.structure_actions_dropdown a {
    display: block;
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
.config-form ul.tabs {
    margin:      1.1em 0.2em 0;
    padding:     0 0 0.3em 0;
    list-style:  none;
    font-weight: bold;
}

.config-form ul.tabs li {
    float: <?php echo $left; ?>;
}

.config-form ul.tabs li a {
    display:          block;
    margin:           0.1em 0.2em 0;
    padding:          0.1em 0.4em;
    white-space:      nowrap;
    text-decoration:  none;
    border:           1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom:    none;
}

.config-form ul.tabs li a:hover,
.config-form ul.tabs li a:active,
.config-form ul.tabs li a.active {
    margin:           0;
    padding:          0.1em 0.6em 0.2em;
}

.config-form ul.tabs li a.active {
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
