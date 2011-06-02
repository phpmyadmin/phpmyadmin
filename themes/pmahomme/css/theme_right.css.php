<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Original
 *
 * @package phpMyAdmin-theme
 * @subpackage pmahomme
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}

function PMA_ieFilter($start_color, $end_color)
{
    return PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6 && PMA_USR_BROWSER_VER <= 8
        ? 'filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="' . $start_color . '", endColorstr="' . $end_color . '");'
        : '';
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
}


body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding:            0;
    margin:             0.5em;
    color:              #444;
    background:         #fff;
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
    font-size:          2em;
    font-weight:        normal;
    text-shadow:		0px 1px 0px #fff;
    padding:			10px 0 10px 3px;
    color:				#777;
}
/* Hiding icons in the page titles */
h2 img{display:none;}
h2 a img{display:inline;}


.data{
margin: 0 0 12px 0;

}

h3 {
    font-weight:        bold;
}

a, a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #235a81;
    cursor:             pointer;
    outline: none;

}

a:hover {
    text-decoration:    underline;
    color:              #235a81;
}

#initials_table {
	background:#f3f3f3;
    border:1px solid #aaa;
    margin-bottom:10px;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
}

#initials_table td{padding:8px !important}

#initials_table a {
    border:1px solid #aaa;
    background:#fff;
    padding:4px 8px;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    background-image: url(./themes/svg_gradient.php?from=ffffff&to=cccccc);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    background: -o-linear-gradient(top,  #ffffff,  #cccccc);
    <?php echo PMA_ieFilter('#ffffff', '#cccccc'); ?>
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
    background:         #f3f3f3;
    background-image: url(./themes/svg_gradient.php?from=ffffff&to=cccccc);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    background: -o-linear-gradient(top,  #ffffff,  #cccccc);
    <?php echo PMA_ieFilter('#ffffff', '#cccccc'); ?>
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

input[type=text]{
	border-radius:2px;
    -moz-border-radius:2px;
    -webkit-border-radius:2px;

    box-shadow:0 1px 2px #ddd;
    -moz-box-shadow:0 1px 2px #ddd;
    -webkit-box-shadow:0 1px 2px #ddd;

    background:url(./themes/pmahomme/img/input_bg.gif);
    border:1px solid #aaa;
    color:#555555;
    padding:4px;
    margin:6px;

}

input[type=password]{
	border-radius:2px;
    -moz-border-radius:2px;
    -webkit-border-radius:2px;

    box-shadow:0 1px 2px #ddd;
    -moz-box-shadow:0 1px 2px #ddd;
    -webkit-box-shadow:0 1px 2px #ddd;

	background:url(./themes/pmahomme/img/input_bg.gif);
    border:1px solid #aaa;
    color:#555555;
    padding:4px;
    margin:6px;

}

input[type=submit]{
    font-weight:bold;
    margin-left:14px;
	border: 1px solid #aaa;
	padding: 3px 7px;
	color: #111;
	text-decoration: none;
	background: #ddd;

    border-radius: 12px;
	-webkit-border-radius: 12px;
	-moz-border-radius: 12px;

	text-shadow: 0px 1px 0px #fff;

    background-image: url(./themes/svg_gradient.php?from=ffffff&to=cccccc);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    background: -o-linear-gradient(top,  #ffffff,  #cccccc);
    <?php echo PMA_ieFilter('#ffffff', '#cccccc'); ?>
}

input[type=submit]:hover{	position: relative;
    background-image: url(./themes/svg_gradient.php?from=cccccc&to=dddddd);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#cccccc), to(#dddddd));
    background: -moz-linear-gradient(top,  #cccccc,  #dddddd);
    background: -o-linear-gradient(top,  #cccccc,  #dddddd);
    <?php echo PMA_ieFilter('#cccccc', '#dddddd'); ?>
    cursor:pointer;
}

input[type=submit]:active{	position: relative;
	top: 1px;
	left: 1px;
}
textarea {
    overflow:           visible;
    height:             <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}

fieldset {
    margin-top:         1em;
    border-radius:4px 4px 0 0;
    -moz-border-radius:4px 4px 0 0;
    -webkit-border-radius:4px 4px 0 0;
    padding:5px;
    border:             #aaa solid 1px;
    padding:            1.5em;
    background:         #eee;
    text-shadow:0 1px 0 #fff;
	-moz-box-shadow: 1px 1px 2px #fff inset;
    -webkit-box-shadow: 1px 1px 2px #fff inset;
    box-shadow: 1px 1px 2px #fff inset;
}

fieldset fieldset {
    margin:             0.8em;
    background:#fff;
    border:1px solid #aaa;
    background:none repeat scroll 0 0 #E8E8E8;

}

fieldset legend {
    font-weight:        bold;
    color:              #444;
    padding:5px 10px;
    border-radius:2px;
    -moz-border-radius:2px;
    -webkit-border-radius:2px;
    border:1px solid #aaa;
    background-color:   #fff;
    -moz-box-shadow:3px 3px 15px #bbb;
    -webkit-box-shadow:3px 3px 15px #bbb;
    box-shadow:3px 3px 15px #bbb;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display:            inline;
}

table caption,
table th,
table td {
    padding:            0.3em;
    margin:             0.1em;
    vertical-align:     top;
    text-shadow:0 1px 0 #FFFFFF;
}

/* 3.4 */
table{border-collapse:collapse;}
th{border-right:1px solid #fff; text-align:left;}


img,
input,
select,
button {
    vertical-align:     middle;
}

select{
    -moz-border-radius:2px;
    -webkit-border-radius:2px;
    border-radius:2px;

    -moz-box-shadow:0 1px 2px #ddd;
	-webkit-box-shadow:0 1px 2px #ddd;
    box-shadow:0 1px 2px #ddd;

    border:1px solid #aaa;
    color:#333333;
    padding:3px;
    background:url(./themes/pmahomme/img/input_bg.gif)
}

select[multiple] {
    background: #fff;
    background: -webkit-gradient(linear, center top, center bottom, from(#fff), color-stop(0.8, #f1f1f1), to(#fbfbfb));
    background: -webkit-linear-gradient(#fff, #f1f1f1 80%, #fbfbfb);
    background: -moz-linear-gradient(#fff, #f1f1f1 80%, #fbfbfb);
    /* none for Opera 11.10 as <option>s always have solid white background */
    filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr="#ffffff", endColorstr="#f2f2f2");
}

/******************************************************************************/
/* classes */
div.tools {
   /* border: 1px solid #000000; */
    padding: 0.2em;
}

div.tools a{color:#3a7ead !important;}

div.tools,
fieldset.tblFooters {
    margin-top:         0;
    margin-bottom:      0.5em;
    /* avoid a thick line since this should be used under another fieldset */
    border-top:         0;
    text-align:         <?php echo $right; ?>;
    float:              none;
    clear:              both;
    -webkit-border-radius:0 0 4px 4px;
    -moz-border-radius:0 0 4px 4px;
    border-radius: 0 0 4px 5px;
}

div.null_div {
    height: 20px;
    text-align: center;
    font-style:normal;
    min-width:50px;
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
    background: #fff;
}

/* even items 2,4,6,8,... */
table tr.even th,
.even {
    background: #f3f3f3;
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
table tr.marked td,
table tr.marked th,
table tr.marked {
    background:  url(./themes/pmahomme/img/marked_bg.png) repeat-x #b6c6d7;
    color:   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['BrowsePointerEnable']) { ?>
/* hovered items */
.odd:hover,
.even:hover,
.hover,
.structure_actions_dropdown {
    background:  url(./themes/pmahomme/img/marked_bg.png) repeat-x #b6c6d7; /* 3.4 */
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th {
    background:  url(./themes/pmahomme/img/marked_bg.png) repeat-x #b6c6d7; /* 3.4 */
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

/**
 * cells with the value NULL
 */
td.null {
    font-style: italic;
    text-align: <?php echo $right; ?>;
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
	font-family: Verdan, Arial, Tahoma;
    font-size:          110%;
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
    vertical-align: -3px;
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

/* message boxes: error, confirmation */
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
    margin:             0.5em 0 1.3em 0;
    border:             1px solid;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 25px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            25px 10px 10px 10px
        <?php } ?>
    <?php } else { ?>
    padding:            0.3em;
    <?php } ?>

    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;

    -moz-box-shadow: 0 1px 1px #fff inset;
    -webkit-box-shadow: 0 1px 1px #fff inset;
    box-shadow:  0 1px 1px #fff inset;
}

.success  a{text-decoration:underline;}
.notice a{text-decoration:underline;}
.error a{text-decoration:underline;}
.footnotes a{text-decoration:underline;}

.success {
    color:              #000000;
    background-color:   #ebf8a4;
}
h1.success,
div.success {
    border-color:       #a2d246;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_success.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
        <?php } else { ?>
    background-position: 97% 50%;
        <?php } ?>
    <?php } ?>
}
.success h1 {
    border-color:       #00FF00;
}

.notice, .footnotes {
    color:              #000;
    background-color:   #e8eef1;
}
h1.notice,
div.notice,
div.footnotes {
    border-color:       #3a6c7e;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_notice.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
        <?php } else { ?>
    background-position: 97% 50%;
        <?php } ?>
    <?php } ?>
}
.notice h1 {
    border-color:       #ffb10a;
}

.error {
	border:1px solid maroon !important;
    color: #000;
    background:pink;
}

h1.error,
div.error {
    border-color:       #333;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_error.png);
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 5px 50%;
        <?php } else { ?>
    background-position: 97% 50%;
        <?php } ?>
    <?php } ?>
}
div.error h1 {
    border-color:       #ff0000;
}



.confirmation {
    color:              #000000;
    background-color:   pink;
}
fieldset.confirmation {

}
fieldset.confirmation legend {

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

div.tools,
.tblFooters {
    font-weight:        normal;
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background:         <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
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
    color:              #0000FF;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
    color:              #FF0000;
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
    background-color:   #f3f3f3;
    color:#aaa;
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
#topmenu a {text-shadow:0px 1px 0px #fff;}

#topmenu .error{background:#eee;border:0px !important;color:#aaa;}

ul#topmenu, ul#topmenu2, ul.tabs {
    font-weight:        bold;
    list-style-type:    none;
    margin:             0;
    padding:            0;

}

ul#topmenu2 {
    margin: 0.25em 0.5em 0;
    height: 2em;
    clear: both;
}

ul#topmenu li, ul#topmenu2 li {
    float:              <?php echo $left; ?>;
    margin:             0;
    vertical-align:     middle;
}

#topmenu img, #topmenu2 img {
    margin-right:0.5em;
    vertical-align:-3px;
}

#topmenucontainer{background:url(./themes/pmahomme/img/tab_bg.png) repeat-x;    border-top:1px solid #aaa;}

/* default tab styles */
.tabactive {
    background:#fff !important;
}

ul#topmenu a, ul#topmenu span {
    display:            block;
    margin:             0px;
    padding:            0px;
    white-space:        nowrap;
}

ul#topmenu ul a {
    margin:             0;

}

ul#topmenu .submenu {
    display:	        none;
    position:	        relative;
}

ul#topmenu .shown {
    display:            inline-block;
}

ul#topmenu ul {
    margin:             0;
    padding:            0;
    position:           absolute;
    right:              0;
    list-style-type:    none;
    display:            none;
    border:             1px #ddd solid;
}

ul#topmenu li:hover {
    background:url(./themes/pmahomme/img/tab_hover_bg.png) repeat-x 50% 0%!important;
}

ul#topmenu li:hover ul, ul#topmenu .submenuhover ul {
    display:            block;
    font-weight:3em;
    background:#fff;

}

ul#topmenu ul li {
    width:              100%;
}

ul#topmenu2 a {
    display:            block;
    margin:             7px 6px 7px 0px;
    padding:            4px 10px;
    white-space:        nowrap;
    border:1px solid #ddd;
    border-radius: 20px;
    -moz-border-radius: 20px;
    -webkit-border-radius: 20px;
    background:#f2f2f2;

}

/* disabled tabs */
ul#topmenu span.tab {
    color:              #666666;
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

ul#topmenu ul {
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}
<?php } else { ?>
#topmenu {
    margin-top:         0.5em;
    padding:            0.1em 0.3em 0.1em 0.3em;
}

ul#topmenu ul {
    -moz-box-shadow:    1px 1px 6px #ddd;
    -webkit-box-shadow: 2px 2px 3px #666;
    box-shadow:         2px 2px 3px #666;
}

ul#topmenu > li {
    border-right: 1px solid #fff;
    border-left: 1px solid #ccc;
}

/* default tab styles */
ul#topmenu a, ul#topmenu span {
	padding:10px;
}

ul#topmenu ul a {
    border-width:       1pt 0 0 0;
    -moz-border-radius: 0;
    -webkit-border-radius: 0;
    border-radius:      0;

}

ul#topmenu ul li:first-child a {
    border-width:       0;

}

/* enabled hover/active tabs */
ul#topmenu > li > a:hover,
ul#topmenu > li > .tabactive {
    text-decoration:    none;
}

ul#topmenu ul a:hover,
ul#topmenu ul .tabactive {
    text-decoration:    none;
}

ul#topmenu a.tab:hover,
ul#topmenu .tabactive {
    /* background-color:   <?php echo $GLOBALS['cfg']['MainBackground']; ?>;  */
}

ul#topmenu2 a.tab:hover,
ul#topmenu2 a.tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    border-radius:      0.3em;
    -moz-border-radius: 0.3em;
    -webkit-border-radius: 0.3em;
    text-decoration:    none;
}

/* to be able to cancel the bottom border, use <li class="active"> */
ul#topmenu > li.active {
    /* border-bottom:      0pt solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>; */
    border-right:0px;
}

/* disabled tabs */
ul#topmenu span.tab,
a.error {
    cursor:             url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>error.ico), default;
    color:#ccc;
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
    margin-<?php echo $right; ?>: 1.5em;
    margin-top: 0.5em;
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
border-bottom:1px solid #fff;
-moz-border-radius: 4px 4px 0 0;
-webkit-border-radius: 4px 4px 0 0;
border-radius:4px 4px 0 0;
background:#888;
padding:10px;
text-shadow:0 1px 0 #000000;
}

#serverinfo .item {
    white-space:        nowrap;
    color:#fff;
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
}
div#serverstatus div#statuslinks a:after,
div#serverstatus div#sectionlinks a:after,
div#serverstatus table tbody td.descr a:after,
div#serverstatus table .tblFooters a:after {
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

#togglequerybox{margin:0 10px}

#serverstatus p {

    margin:             1.5em 0px;
    border:             1px solid #000;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            10px 10px 10px 25px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            25px 10px 10px 10px
        <?php } ?>
    <?php } else { ?>
    padding:            0.3em;
    <?php } ?>
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    -moz-box-shadow: 0px 1px 2px #fff inset;
    -webkit-box-shadow: 0px 1px 2px #fff inset;
    box-shadow:0px 1px 2px #fff; inset;
    background:#555;
    color:#d4fb6a;
}
#serverstatus p a{color:#fff;text-decoration:underline;}
#serverstatus h3
{
	margin:35px 0px;font-weight:normal;color:#999;font-size:1.7em;
}
#sectionlinks{
	padding:16px;
    background:#f3f3f3;
    border:1px solid #aaa;
    border-radius:5px;
    -webkit-border-radius:5px;
    -moz-border-radius:5px;

	box-shadow:0px 1px 1px #fff inset;
    -webkit-box-shadow:0px 1px 1px #fff inset;
    -moz-box-shadow:0px 1px 1px #fff inset;

}
#sectionlinks a, #statuslinks a{
	font-size:0.88em;
    font-weight:bold;
    text-shadow: 0px 1px 0px #fff;
    line-height:35px;
	margin-left:7px;
	border: 1px solid #aaa;
	padding: 5px 10px;
	color: #111;
	text-decoration: none;
	background: #ddd;
	border-radius: 20px;
	-webkit-border-radius: 20px;
	-moz-border-radius: 20px;
	box-shadow: 1px 1px 2px rgba(0,0,0,.5);
	/*
    -webkit-box-shadow: 1px 1px 2px rgba(0,0,0,.5);
	-moz-box-shadow: 1px 1px 2px rgba(0,0,0,.5);
	text-shadow: #fff 0px 1px 0px;
    */
    background-image: url(./themes/svg_gradient.php?from=ffffff&to=cccccc);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    background: -o-linear-gradient(top,  #ffffff,  #cccccc);
    <?php echo PMA_ieFilter('#ffffff', '#cccccc'); ?>
}
#sectionlinks a:hover, #statuslinks a:hover{
    background-image: url(./themes/svg_gradient.php?from=cccccc&to=dddddd);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#cccccc), to(#dddddd));
    background: -moz-linear-gradient(top,  #cccccc,  #dddddd);
    background: -o-linear-gradient(top,  #cccccc,  #dddddd);
    <?php echo PMA_ieFilter('#cccccc', '#dddddd'); ?>
}

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
    background:#fff;
    /* height: 12em; */
}

textarea#sqlquery {
    width: 100%;
    /* height: 100%; */
    -moz-border-radius:4px;
    -webkit-border-radius:4px;
    border-raduis:4px
	border:1px solid #aaa;
	padding:5px;
	font-family:inherit;
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
    /* background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>logo_right.png); */
    /* background-position: <?php echo $right; ?> bottom; */
    /* background-repeat: no-repeat; */
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
    margin-bottom:  0.3em;
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
    background:#f3f3f3;
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
    display:            block;
    padding:            1em;
    margin-top:         0;
    margin-bottom:      0;
    border-top:         0;
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

    border:1px solid #999;
    background:#f3f3f3;
    -moz-border-radius:4px;
    -webkit-border-radius:4px;
    border-radius:4px;
    -moz-box-shadow:2px 2px 5px #ccc;
    -webkit-box-shadow:2px 2px 5px #ccc;
    box-shadow:3px 3px 10px #ddd;
    margin-bottom:      1em;
    padding-bottom: 1em;
}

.group h2 {
    background-color:   #bbb;
    padding:            0.1em 0.3em;
    margin-top:         0;
	color:#fff;
    font-size:1.6em;
    font-weight:normal;
    text-shadow:0 1px 0 #777;
    -moz-box-shadow: 1px 1px 15px  #999 inset;
    -webkit-box-shadow: 1px 1px 15px  #999 inset;
    box-shadow: 1px 1px 15px  #999 inset;
}

.group-cnt {
    padding: 0 0 0 0.5em;
    display: inline-block;
    width: 98%;
}

textarea#partitiondefinition {
    height:3em;
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
input[type=text].invalid_value,
.invalid_value {
    background:#F00;
}

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
    padding: 5px;   /** Keep a little space on the sides of the text */
    width: 350px;

    z-index: 1100;      /** If this is not kept at a high z-index, the jQueryUI modal dialogs (z-index:1000) might hide this */
    text-align: center;
    display: inline;
    left: 0;
    right: 0;
    background-image: url(./themes/pmahomme/img/ajax_clock_small.gif);
    background-repeat: no-repeat;
    background-position: 2%;
    border:1px solid #e2b709;
 }

/* additional styles */
.ajax_notification{
    margin-top:200px;background:#ffe57e;
    border-radius:5px;
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    box-shadow: 0px 5px 90px #888;
    -moz-box-shadow: 0px 5px 90px #888;
    -webkit-box-shadow: 0px 5px 90px #888;
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
    font-weight:bold;
    margin-left:14px;
	border: 1px solid #aaa;
	padding: 5px 12px;
	color: #111;
	text-decoration: none;
	background: #ddd;

    border-radius: 12px;
	-webkit-border-radius: 12px;
	-moz-border-radius: 12px;

	text-shadow: 0px 1px 0px #fff;

    background-image: url(./themes/svg_gradient.php?from=ffffff&to=cccccc);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#ffffff), to(#cccccc));
    background: -moz-linear-gradient(top,  #ffffff,  #cccccc);
    background: -o-linear-gradient(top,  #ffffff,  #cccccc);
    <?php echo PMA_ieFilter('#ffffff', '#cccccc'); ?>
    cursor: pointer;
}
#buttonGo:hover{
    background-image: url(./themes/svg_gradient.php?from=cccccc&to=dddddd);
    background-size: 100% 100%;
    background: -webkit-gradient(linear, left top, left bottom, from(#cccccc), to(#dddddd));
    background: -moz-linear-gradient(top,  #cccccc,  #dddddd);
    background: -o-linear-gradient(top,  #cccccc,  #dddddd);
    <?php echo PMA_ieFilter('#cccccc', '#dddddd'); ?>
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
    background:#fff;
    line-height:24px;
    border:1px solid #aaa;
    -moz-box-shadow:0px 3px 3px #ddd;
}
.structure_actions_dropdown span{display:block;}
.structure_actions_dropdown span:hover{background:#ddd;}

td.more_opts {
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
    white-space:      nowrap;
    text-decoration:  none;
    border:           1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom:    none;
}

.config-form ul.tabs li a {
    padding:7px 10px;
    -moz-border-radius:5px 5px 0 0;
    -webkit-border-radius:5px 5px 0 0;
    border-radius:5px 5px 0 0;
    background:#f2f2f2;
    color:#555;
    text-shadow: 0 1px 0 #fff;
}

.config-form ul.tabs li a:hover,
.config-form ul.tabs li a:active {
    background:#e5e5e5;
}

.config-form ul.tabs li a.active {
    background-color: #fff;
    margin-top:1px;
    color:#000;
    text-shadow: none;
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
    background: #fff;
    border-top:0px;
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
    border-right: none;
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

#table_columns input, #table_columns select {
    width:              14em;
    box-sizing:         border-box;
    -ms-box-sizing:     border-box;
    -moz-box-sizing:    border-box;
    -webkit-box-sizing: border-box;
}

#table_columns select {
    margin:             0 6px;
}

