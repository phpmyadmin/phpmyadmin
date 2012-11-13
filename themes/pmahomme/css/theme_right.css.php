<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Original
 *
 * @package PhpMyAdmin-theme
 * @subpackage pmahomme
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
}


body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family:        <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding:            0;
    margin:             0 0.5em 0 0;
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
    text-shadow:        0 1px 0 #fff;
    padding:            10px 0 10px 3px;
    color:              #777;
}

/* Hiding icons in the page titles */
h2 img{display:none;}
h2 a img{display:inline;}

.data,
.data_full_width {
    margin: 0 0 12px 0;
}

.data_full_width {
    width: 100%;
}

#table_results td.data {
border-right: 1px solid #BBBBBB;
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
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
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
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
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

    background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>input_bg.gif);
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

    background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>input_bg.gif);
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

    text-shadow: 0 1px 0 #fff;

    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
}

input[type=submit]:hover{position: relative;
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
    cursor:pointer;
}

input[type=submit]:active{position: relative;
    top: 1px;
    left: 1px;
}
textarea {
    overflow:           visible;
    height:             <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
}
textarea.char {
    height:             <?php echo ceil($GLOBALS['cfg']['CharTextareaRows'] * 1.2); ?>em;
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


img, button {
    vertical-align:     middle;
}

input[type="checkbox"],input[type="radio"] {
    vertical-align: -11%;
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
    background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>input_bg.gif)
}

select[multiple] {
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'f2f2f2'); ?>
}

/******************************************************************************/
/* classes */
.clearfloat {
    clear: both;
}

.floatleft {
    float: <?php echo $left; ?>;
    margin-<?php echo $right; ?>: 1em;
}

.paddingtop {
    padding-top: 1em;
}

.separator {
    color: white;
    text-shadow: 0 1px 0 black;
}

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
    <?php echo $_SESSION['PMA_Theme']->getCssIEClearFilter(); ?>
}

/* even items 2,4,6,8,... */
/* (tested on CRTs and ACLs) */
table tr.even th,
.even {
    background: #DFDFDF;
    <?php echo $_SESSION['PMA_Theme']->getCssIEClearFilter(); ?>
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
    background:  url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>marked_bg.png) repeat-x #b6c6d7;
    color:   <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}
<?php } ?>

<?php if ($GLOBALS['cfg']['BrowsePointerEnable']) { ?>
/* hovered items */
.odd:hover,
.even:hover,
.hover,
.structure_actions_dropdown {
    background:  url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>marked_bg.png) repeat-x #b6c6d7; /* 3.4 */
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

/* hovered table rows */
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th {
    background:  url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>marked_bg.png) repeat-x #b6c6d7; /* 3.4 */
    color:   <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}
<?php } ?>

/**
 * marks table rows/cells if the db field is in a where condition
 */
.condition {
    border-color: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?> !important;
}

th.condition {
    border-width: 1px 1px 0 1px;
    border-style: solid;
}

td.condition {
    border-width: 0 1px 0 1px;
    border-style: solid;
}

tr:last-child td.condition {
    border-width: 0 1px 1px 1px;
}

<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
/* for first th which must have right border set (ltr only) */
.before-condition {
    border-right: 1px solid <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}
<?php } ?>

/**
 * cells with the value NULL
 */
td.null {
    font-style: italic;
    text-align: <?php echo $right; ?>;
}

table .valueHeader {
    text-align:         <?php echo $right; ?>;
    white-space:        normal;
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
.attention {
    color:              red;
    font-weight:        bold;
}
.allfine {
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

h1.success, div.success {
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
 * zoom search
 */
div#resizer {
    width:              600px;
    height:             400px;
}
div#querychart {
    float:              left;
    width:              600px;
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
#topmenu a {
    text-shadow: 0 1px 0 #fff;
}

#topmenu .error {
    background:#eee;border: 0 !important;color:#aaa;
}

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

.menucontainer{
    background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tab_bg.png) repeat-x;
    border-top:1px solid #aaa;
}

/* default tab styles */
.tabactive {
    background:#fff !important;
}

ul#topmenu a, ul#topmenu span {
    display:            block;
    margin:             0;
    padding:            0;
    white-space:        nowrap;
}

ul#topmenu ul a {
    margin:             0;

}

ul#topmenu .submenu {
    display:           none;
    position:          relative;
}

ul#topmenu .shown {
    display:            inline-block;
}

ul#topmenu ul {
    margin:             0;
    padding:            0;
    position:           absolute;
    list-style-type:    none;
    display:            none;
    border:             1px #ddd solid;
    z-index:            2;
}

ul#topmenu ul.only {
    left: 0;
}

ul#topmenu ul.notonly {
    right: 0;
}

ul#topmenu li:hover {
    background:url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>tab_hover_bg.png) repeat-x 50% 0%!important;
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
    margin:             7px 6px 7px 0;
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
    padding: 0.6em;
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
    border-right: 0;
}

/* disabled tabs */
ul#topmenu span.tab,
a.error {
    cursor:             url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>error.ico), default;
    color:#ccc;
}
<?php } ?>
/* end topmenu */

/* zoom search */
div#dataDisplay input, div#dataDisplay select {
    margin: 0;
    margin-<?php echo $right; ?>: 0.5em;
}
div#dataDisplay th {
    line-height: 2em;
}

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
div#tablestatistics table {
    float: <?php echo $left; ?>;
    margin-bottom: 0.5em;
    margin-<?php echo $right; ?>: 1.5em;
    margin-top: 0.5em;
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
#topmenucontainer {
    padding-right: 1em;
    width: 100%;
    background-color: #dcdcdc;
}

#serverinfo {
    border-bottom:1px solid #fff;
    background:#888;
    padding: 0.3em 0.9em;
    text-shadow:0 1px 0 #000000;
    width: 10000px;
    overflow: hidden;
}

#serverinfo .item {
    white-space:        nowrap;
    color:#fff;
    float: <?php echo $left; ?>
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

.linkElem:hover {
    text-decoration:    underline;
    color:              #235a81;
    cursor: pointer;
}

h3#serverstatusqueries span {
    font-size:60%;
    display:inline;
}

img.sortableIcon {
    float:right;
    background-repeat:no-repeat;
    margin:0;
}

.buttonlinks {
    float: <?php echo $right; ?>;
    white-space: nowrap;
}

.jsfeature {
    display: none; /* Made visible with js */
}

/* Also used for the variables page */
fieldset#tableFilter {
    margin-bottom:1em;
}

div#serverStatusTabs {
    margin-top:1em;
}

caption a.top {
    float: <?php echo $right; ?>;
}

div#serverstatusquerieschart {
    float:<?php echo $left; ?>;
    width:500px;
    height:350px;
    padding-<?php echo $left; ?>: 30px;
}

table#serverstatusqueriesdetails, table#serverstatustraffic {
    float: <?php echo $left; ?>;
}

table#serverstatusqueriesdetails th {
    min-width: 35px;
}

table#serverstatusvariables {
    width: 100%;
    margin-bottom: 1em;
}
table#serverstatusvariables .name {
    width: 18em;
    white-space:nowrap;
}
table#serverstatusvariables .value {
    width: 6em;
}
table#serverstatusconnections {
    float: <?php echo $left; ?>;
    margin-<?php echo $left; ?>: 30px;
}

div#serverstatus table tbody td.descr a,
div#serverstatus table .tblFooters a {
    white-space: nowrap;
}

div.liveChart {
    clear:both;
    min-width:500px;
    height:400px;
    padding-bottom:80px;
}

#addChartDialog input[type="text"] {
    margin: 0;
    padding: 3px;
}

div#chartVariableSettings {
    border:1px solid #ddd;
    background-color:#E6E6E6;
    margin-left:10px;
}

table#chartGrid div.monitorChart {
    background: #EBEBEB;
}

div#serverstatus div.tabLinks {
    float:<?php echo $left; ?>;
    padding-bottom: 10px;
}

.popupContent {
    display: none;
    position: absolute;
    border: 1px solid #CCC;
    margin:0;
    padding:3px;
    -moz-box-shadow:    1px 1px 6px #ddd;
    -webkit-box-shadow: 2px 2px 3px #666;
    box-shadow:         2px 2px 3px #666;
    background-color:white;
    z-index: 2;
}

div#logTable {
    padding-top: 10px;
    clear: both;
}

div#logTable table {
    width:100%;
}

div#queryAnalyzerDialog {
    min-width: 700px;
}

div#queryAnalyzerDialog div.CodeMirror-scroll {
    height:auto;
}

div#queryAnalyzerDialog div#queryProfiling {
    height: 250px;
}

div#queryAnalyzerDialog td.explain {
    width: 250px;
}

div#queryAnalyzerDialog table.queryNums {
    display: none;
    border:0;
    text-align:left;
}

.smallIndent {
    padding-left: 7px;
}


/* end serverstatus */

/* server variables */

a.editLink {
    float: <?php echo $left; ?>;
    font-family:sans-serif;
}

table.serverVariableEditTable {
    border:0;
    margin:0;
    padding:0;
    width:100%;
}
table.serverVariableEditTable td {
    border:0;
    margin:0;
    padding:0;
}
table.serverVariableEditTable td:first-child {
    white-space:nowrap;
    vertical-align:middle;
}

table.serverVariableEditTable input {
    width:95%;
}

table#serverVariables td {
    height:18px;
}

/* end server variables */


p.notice {
    margin:             1.5em 0;
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
    -moz-box-shadow: 0 1px 2px #fff inset;
    -webkit-box-shadow: 0 1px 2px #fff inset;
    box-shadow: 0 1px 2px #fff; inset;
    background:#555;
    color:#d4fb6a;
}

p.notice a {
    color:#fff;
    text-decoration:underline;
}

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

/* profiling */

div#profilingchart {
    width:550px;
    height:370px;
    float:left;
}

/* END profiling */

/* table charting */

#resizer {
    border: 1px solid silver;
}
#inner-resizer { /* make room for the resize handle */
    padding: 10px;
}

/* END table charting */

/* querybox */

#togglequerybox{margin:0 10px}

#serverstatus h3
{
    margin: 15px 0;
    font-weight:normal;
    color:#999;
    font-size:1.7em;
}
#sectionlinks{
    padding:16px;
    background:#f3f3f3;
    border:1px solid #aaa;
    border-radius:5px;
    -webkit-border-radius:5px;
    -moz-border-radius:5px;
    box-shadow: 0 1px 1px #fff inset;
    -webkit-box-shadow: 0 1px 1px #fff inset;
    -moz-box-shadow: 0 1px 1px #fff inset;
}
#sectionlinks a, .buttonlinks a, a.button {
    font-size:0.88em;
    font-weight:bold;
    text-shadow: 0 1px 0 #fff;
    line-height:35px;
    margin-left:7px;
    border: 1px solid #aaa;
    padding: 5px 10px;
    color: #111;
    text-decoration: none;
    background: #ddd;
    white-space: nowrap;
    border-radius: 20px;
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
    box-shadow: 1px 1px 2px rgba(0,0,0,.5);
    /*
    -webkit-box-shadow: 1px 1px 2px rgba(0,0,0,.5);
    -moz-box-shadow: 1px 1px 2px rgba(0,0,0,.5);
    text-shadow: #fff 0 1px 0;
    */
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
}
#sectionlinks a:hover, .buttonlinks a:hover, a.button:hover {
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
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
    width: 0;
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
    background:#FFCCCC;
}

/**
  *  Ajax notification styling
  */
 .ajax_notification {
    top: 0;           /** The notification needs to be shown on the top of the page */
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
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif);
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
    box-shadow: 0 5px 90px #888;
    -moz-box-shadow: 0 5px 90px #888;
    -webkit-box-shadow: 0 5px 90px #888;
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

    text-shadow: 0 1px 0 #fff;

    <?php echo $_SESSION['PMA_Theme']->getCssGradient('ffffff', 'cccccc'); ?>
    cursor: pointer;
}
#buttonGo:hover{
    <?php echo $_SESSION['PMA_Theme']->getCssGradient('cccccc', 'dddddd'); ?>
}

.format_specific_options h3 {
    margin: 10px 0 0 10px;
    border: 0;
}

.format_specific_options {
    border: 1px solid #999999;
    margin: 7px 0;
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
    margin: 20px 0 0 30px;
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
    margin: 10px 0;
    font-style: italic;
}

input#input_import_file {
    margin: 5px;
}

.formelementrow {
    margin: 5px 0 5px 0;
}

/**
 * GIS data editor styles
 */
a.close_gis_editor {
    float: right;
}

#gis_editor {
    display: none;
    position: fixed;
    _position: absolute; /* hack for IE */
    z-index: 1001;
    overflow-y: auto;
    overflow-x: hidden;
}

#gis_data {
    min-height: 230px;
}

#gis_data_textarea {
    height: 6em;
}

#gis_data_editor {
    background: #D0DCE0;
    padding: 15px;
    min-height: 500px;
}

#gis_data_editor .choice {
    display: none;
}

#gis_data_editor input[type="text"] {
    width: 75px;
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
    z-index: 1000;
    overflow: hidden;
}

/**
 * ENUM/SET editor styles
 */
p.enum_notice {
    margin: 5px 2px;
    font-size: 80%;
}

.enum_editor_no_js fieldset {
    width: 40em;
}

hr.enum_editor_no_js {
    background-color: #aaa;
}

#enum_editor p {
    margin-top: 0;
    font-style:italic;
}

#enum_editor .values, #enum_editor .add, .enum_editor_no_js #values {
    width: 100%;
}

#enum_editor .add td {
    vertical-align: middle;
    width: 50%;
    padding: 0 0 0 1em;
}

#enum_editor .values td.drop {
    width: 1.8em;
    cursor: pointer;
    vertical-align: middle;
}

#enum_editor .values input {
    margin: 0.1em 0;
    padding-right: 2em;
    width: 100%;
}

#enum_editor .values img {
    width: 1.8em;
    vertical-align: middle;
}

#enum_editor input.add_value {
    margin: 0 0.4em 0 0;
}

#enum_editor_output textarea,
.enum_editor_no_js input {
    width: 100%;
    float: right;
    margin: 1em 0 0 0;
}

.enum_editor_no_js {
    width: 40em;
}

.enum_editor_no_js input.submit {
    float: left;
    margin: 1em 0;
}

/**
 * ENUM/SET editor integration for the routines editor
 */
.enum_hint {
    position: relative;
}

.enum_hint a {
    position: absolute;
    left: 81%;
    bottom: 0.35em;
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
    -moz-box-shadow: 0 3px 3px #ddd;
}
.structure_actions_dropdown span{display:block;}
.structure_actions_dropdown span:hover{background:#ddd;}

td.more_opts {
    white-space: nowrap;
}

/**
 * Indexes
 */
#index_frm .index_info input,
#index_frm .index_info select {
    width: 14em;
    box-sizing:         border-box;
    -ms-box-sizing:     border-box;
    -moz-box-sizing:    border-box;
    -webkit-box-sizing: border-box;
}

#index_frm .index_info div {
    padding: 0.2em 0;
}

#index_frm .index_info .label {
    float: left;
    min-width: 12em;
}

#index_frm .slider {
    width: 10em;
    margin: 0.6em;
    float: <?php echo $left; ?>;
}

#index_frm .add_fields {
    float: <?php echo $left; ?>;
}

#index_frm .add_fields input {
    margin-<?php echo $left; ?>: 1em;
}

#index_frm input {
    margin: 0;
}

#index_frm td {
    vertical-align: middle;
}

table#index_columns {
    width: 100%;
}

table#index_columns select {
    width: 100%;
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
    float:         <?php echo $left; ?>;
    margin-bottom: -1px;
}

.config-form ul.tabs li a {
    display:          block;
    margin:           0.1em 0.2em 0;
    white-space:      nowrap;
    text-decoration:  none;
    border:           1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom:    1px solid #aaa;
}

.config-form ul.tabs li a {
    padding:               7px 10px;
    -webkit-border-radius: 5px 5px 0 0;
    -moz-border-radius:    5px 5px 0 0;
    border-radius:         5px 5px 0 0;
    background:            #f2f2f2;
    color:                 #555;
    text-shadow:           0 1px 0 #fff;
}

.config-form ul.tabs li a:hover,
.config-form ul.tabs li a:active {
    background: #e5e5e5;
}

.config-form ul.tabs li.active a {
    background-color: #fff;
    margin-top:       1px;
    color:            #000;
    text-shadow:      none;
    border-color:     #aaa;
    border-bottom:    1px solid #fff;
}

.config-form fieldset {
    margin-top:            0;
    padding:               0;
    clear:                 both;
    -webkit-border-radius: 0;
    -moz-border-radius:    0;
    border-radius:         0;
}

.config-form legend {
    display: none;
}

.config-form fieldset p {
    margin:    0;
    padding:   0.5em;
    background: #fff;
    border-top: 0;
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
    filter:         none;
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

.rte_table {
    table-layout: fixed;
}

.rte_table td {
    vertical-align:     middle;
}

.rte_table tr td:nth-child(1) {
    font-weight:        bold;
}

.rte_table input, .rte_table select, .rte_table textarea {
    width:              100%;
    margin:             0;
    box-sizing:         border-box;
    -ms-box-sizing:     border-box;
    -moz-box-sizing:    border-box;
    -webkit-box-sizing: border-box;
}

.rte_table .routine_params_table {
    width:              100%;
}

#placeholder .button {
    position: absolute;
    cursor: pointer;
}

#placeholder div.button {
    font-size: smaller;
    color: #999;
    background-color: #eee;
    padding: 2px;
}

.wrapper {
    float: <?php echo $left; ?>;
    margin-bottom: 1.5em;
}
.toggleButton {
    position: relative;
    cursor: pointer;
    font-size: 0.8em;
    text-align: center;
    line-height: 1.55em;
    height: 1.55em;
    overflow: hidden;
    border-right: 0.1em solid #888;
    border-left: 0.1em solid #888;
    -webkit-border-radius: 0.3em;
    -moz-border-radius: 0.3em;
    border-radius: 0.3em;
}
.toggleButton table,
.toggleButton td,
.toggleButton img {
    padding: 0;
    position: relative;
}
.toggleButton .container {
    position: absolute;
}
.toggleButton .toggleOn {
    color: white;
    padding: 0 1em;
    text-shadow: 0 0 0.2em #000;
}
.toggleButton .toggleOff {
    padding: 0 1em;
}

.doubleFieldset fieldset {
    width: 48%;
    float: <?php echo $left; ?>;
    padding: 0;
}
.doubleFieldset fieldset.left {
    margin-<?php echo $right; ?>: 1%;
}
.doubleFieldset fieldset.right {
    margin-<?php echo $left; ?>: 1%;
}
.doubleFieldset legend {
    margin-<?php echo $left; ?>: 1.5em;
}
.doubleFieldset div.wrap {
    padding: 1.5em;
}

#table_columns input[type="text"], #table_columns select {
    width:              10em;
    box-sizing:         border-box;
    -ms-box-sizing:     border-box;
    -moz-box-sizing:    border-box;
    -webkit-box-sizing: border-box;
}

#table_columns select {
    margin:             0 6px;
}

#placeholder {
    position: relative;
    border: 1px solid #aaa;
    float: right;
    overflow: hidden;
}

.placeholderDrag {
    cursor: move;
}

#placeholder .button {
    position: absolute;
}

#left_arrow {
    left:8px;
    top:26px;
}

#right_arrow {
    left:26px;
    top:26px;
}

#up_arrow {
    left:17px;
    top:8px;
}

#down_arrow {
    left:17px;
    top:44px;
}

#zoom_in {
    left:17px;
    top:67px;
}

#zoom_world {
    left:17px;
    top:85px;
}

#zoom_out {
    left:17px;
    top:103px;
}

.gis_table td {
    vertical-align: middle;
}

.gis_table select {
    min-width: 160px;
    margin: 6px;
}

.gis_table .save {
    color: #111;
    font-weight: bold;
    vertical-align: bottom;
    height: 100px;
}

.gis_table .button {
   text-align: <?php echo $right; ?>;
}

.gis_table .choice {
    display: none;
}

.CodeMirror {
  font-size: 100%;
  font-family: monospace;
  background: white;
  border: 1px solid black;
}

.CodeMirror-scroll {
  overflow: auto;
  height:             <?php echo ceil($GLOBALS['cfg']['TextareaRows'] * 1.2); ?>em;
  /* This is needed to prevent an IE[67] bug where the scrolled content
     is visible outside of the scrolling box. */
  position: relative;
}

.CodeMirror-gutter {
  position: absolute; left: 0; top: 0;
  z-index: 10;
  background-color: #f7f7f7;
  border-right: 1px solid #eee;
  min-width: 2em;
  height: 100%;
}
.CodeMirror-gutter-text {
  color: #aaa;
  text-align: right;
  padding: .4em .2em .4em .4em;
  white-space: pre !important;
}
.CodeMirror-lines {
  padding: .4em;
}

.CodeMirror pre {
  -moz-border-radius: 0;
  -webkit-border-radius: 0;
  -o-border-radius: 0;
  border-radius: 0;
  border-width: 0; margin: 0; padding: 0; background: transparent;
  font-family: inherit;
  font-size: inherit;
  padding: 0; margin: 0;
  white-space: pre;
  word-wrap: normal;
}

.CodeMirror-wrap pre {
  word-wrap: break-word;
  white-space: pre-wrap;
}
.CodeMirror-wrap .CodeMirror-scroll {
  overflow-x: hidden;
}

.CodeMirror textarea {
  font-family: inherit !important;
  font-size: inherit !important;
}

.CodeMirror-cursor {
  z-index: 10;
  position: absolute;
  visibility: hidden;
  border-left: 1px solid black !important;
}
.CodeMirror-focused .CodeMirror-cursor {
  visibility: visible;
}

span.CodeMirror-selected {
  background: #ccc !important;
  color: HighlightText !important;
}
.CodeMirror-focused span.CodeMirror-selected {
  background: Highlight !important;
}

.CodeMirror-matchingbracket {color: #0f0 !important;}
.CodeMirror-nonmatchingbracket {color: #f22 !important;}

<?php echo $_SESSION['PMA_Theme']->getCssCodeMirror(); ?>

.colborder {
    cursor: col-resize;
    height: 100%;
    margin-left: -6px;
    position: absolute;
    width: 5px;
}

.colborder_active {
    border-right: 2px solid #a44;
}

.pma_table td {
    position: static;
}

.pma_table th.draggable span, .pma_table tbody td span {
    display: block;
    overflow: hidden;
}

.cRsz {
    position: absolute;
}

.cCpy {
    background: #333;
    color: #FFF;
    font-weight: bold;
    margin: 0.1em;
    padding: 0.3em;
    position: absolute;
    text-shadow: -1px -1px #000;

    -moz-box-shadow: 0 0 0.7em #000;
    -webkit-box-shadow: 0 0 0.7em #000;
    box-shadow: 0 0 0.7em #000;
    -moz-border-radius: 0.3em;
    -webkit-border-radius: 0.3em;
    border-radius: 0.3em;
}

.cPointer {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>col_pointer.png);
    height: 20px;
    margin-left: -5px;  /* must be minus half of its width */
    margin-top: -10px;
    position: absolute;
    width: 10px;
}

.normalqTip {
    background: #333 !important;
    opacity: 0.8 !important;
    border:1px solid #000 !important;
    -moz-border-radius: 0.3em !important;
    -webkit-border-radius: 0.3em !important;
    border-radius: 0.3em !important;
    text-shadow: -1px -1px #000 !important;
    font-size: 0.8em !important;
    font-weight: bold !important;
}

.normalqTip * {
    background: none !important;
    color: #FFF !important;
}

.normalqTipContent {
    padding: 1px 3px !important;
}

.cDrop {
    left: 0;
    position: absolute;
    top: 0;
}

.coldrop {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>col_drop.png);
    cursor: pointer;
    height: 16px;
    margin-left: 0.3em;
    margin-top: 0.3em;
    position: absolute;
    width: 16px;
}

.coldrop:hover, .coldrop-hover {
    background-color: #999;
}

.cList {
    background: #EEE;
    border: solid 1px #999;
    position: absolute;
    -moz-box-shadow: 0 0.2em 0.5em #333;
    -webkit-box-shadow: 0 0.2em 0.5em #333;
    box-shadow: 0 0.2em 0.5em #333;
}

.cList .lDiv div {
    padding: 0.2em 0.5em 0.2em 0.2em;
}

.cList .lDiv div:hover {
    background: #DDD;
    cursor: pointer;
}

.cList .lDiv div input {
    cursor: pointer;
}

.showAllColBtn {
    border-bottom: solid 1px #999;
    border-top: solid 1px #999;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: bold;
    padding: 0.35em 1em;
    text-align: center;
}

.showAllColBtn:hover {
    background: #DDD;
}

.navigation {
    margin: 0.8em 0;

    border-radius: 5px;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;

    <?php echo $_SESSION['PMA_Theme']->getCssGradient('eeeeee', 'cccccc'); ?>
}

.navigation td {
    margin: 0;
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
}

.navigation_separator {
    color: #999;
    display: inline-block;
    font-size: 1.5em;
    text-align: center;
    height: 1.4em;
    width: 1.2em;
    text-shadow: 1px 0 #FFF;
}

.navigation input[type=submit] {
    background: none;
    border: 0;
    filter: none;
    margin: 0;
    padding: 0.8em 0.5em;

    border-radius: 0;
    -webkit-border-radius: 0;
    -moz-border-radius: 0;
}

.navigation input[type=submit]:hover, .navigation input.edit_mode_active {
    color: white;
    cursor: pointer;
    text-shadow: none;

    <?php echo $_SESSION['PMA_Theme']->getCssGradient('333333', '555555'); ?>
}

.navigation select {
    margin: 0 0.8em;
}

.cEdit {
    margin: 0;
    padding: 0;
    position: absolute;
}

.cEdit input[type=text] {
    background: #FFF;
    height: 100%;
    margin: 0;
    padding: 0;
}

.cEdit .edit_area {
    background: #FFF;
    border: 1px solid #999;
    min-width: 10em;
    padding: 0.3em 0.5em;
}

.cEdit .edit_area select, .cEdit .edit_area textarea {
    width: 97%;
}

.cEdit .cell_edit_hint {
    color: #555;
    font-size: 0.8em;
    margin: 0.3em 0.2em;
}

.cEdit .edit_box {
    overflow: hidden;
    padding: 0;
}

.cEdit .edit_box_posting {
    background: #FFF url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat right center;
    padding-right: 1.5em;
}

.cEdit .edit_area_loading {
    background: #FFF url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat center;
    height: 10em;
}

.cEdit .goto_link {
    background: #EEE;
    color: #555;
    padding: 0.2em 0.3em;
}

.saving_edited_data {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif) no-repeat left;
    padding-left: 20px;
}

/* css for timepicker */
.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }
.ui-timepicker-div dl{ text-align: left; }
.ui-timepicker-div dl dt{ height: 25px; }
.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }
.ui-timepicker-div td { font-size: 90%; }

/* Designer */
.input_tab {
    background-color: #A6C7E1;
    color: #000000;
}

#canvas {
    background-color: #FFFFFF;
    color: #000000;
}

canvas.pmd {
    display: inline-block;
    overflow: hidden;
    text-align: left;
}

canvas.pmd * {
    behavior: url(#default#VML);
}

.pmd_tab {
    background-color: #FFFFFF;
    color: #000000;
    border-collapse: collapse;
    border: 1px solid #AAAAAA;
    z-index: 1;
    -moz-user-select: none;
}

.tab_zag {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header.png);
    background-repeat: repeat-x;
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
}

.tab_zag_2 {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header_Linked.png);
    background-repeat: repeat-x;
    text-align: center;
    cursor: move;
    padding: 1px;
    font-weight: bold;
}

.tab_field {
    background: #FFFFFF;
    color: #000000;
    cursor: default;
}

.tab_field_2 {
    background-color: #CCFFCC;
    color: #000000;
    background-repeat: repeat-x;
    cursor: default;
}

.tab_field_3 {
    background-color: #FFE6E6; /*#DDEEFF*/
    color: #000000;
    cursor: default;
}

#pmd_hint {
    white-space: nowrap;
    position: absolute;
    background-color: #99FF99;
    color: #000000;
    left: 200px;
    top: 50px;
    z-index: 3;
    border: #00CC66 solid 1px;
    display: none;
}

.scroll_tab {
    overflow: auto;
    width: 100%;
    height: 500px;
}

.pmd_Tabs {
    cursor: default;
    color: #0055bb;
    white-space: nowrap;
    text-decoration: none;
    text-indent: 3px;
    font-weight: bold;
    margin-left: 2px;
    text-align: left;
    background-color: #FFFFFF;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/left_panel_butt.png);
    border: #CCCCCC solid 1px;
}

.pmd_Tabs2 {
    cursor: default;
    color: #0055bb;
    background: #FFEE99;
    text-indent: 3px;
    font-weight: bold;
    white-space: nowrap;
    text-decoration: none;
    border: #9999FF solid 1px;
    text-align: left;
}

.owner {
    font-weight: normal;
    color: #888888;
}

.option_tab {
    padding-left: 2px;
    padding-right: 2px;
    width: 5px;
}

.select_all {
    vertical-align: top;
    padding-left: 2px;
    padding-right: 2px;
    cursor: default;
    width: 1px;
    color: #000000;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header.png);
    background-repeat: repeat-x;
}

.small_tab {
    vertical-align: top;
    background-color: #0064ea;
    color: #FFFFFF;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/small_tab.png);
    cursor: default;
    text-align: center;
    font-weight: bold;
    padding-left: 2px;
    padding-right: 2px;
    width: 1px;
    text-decoration: none;
}

.small_tab2 {
    vertical-align: top;
    color: #FFFFFF;
    background-color: #FF9966;
    cursor: default;
    padding-left: 2px;
    padding-right: 2px;
    text-align: center;
    font-weight: bold;
    width: 1px;
    text-decoration: none;
}

.small_tab_pref {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header.png);
    background-repeat: repeat-x;
    text-align: center;
    width: 1px;
}

.small_tab_pref2 {
    vertical-align: top;
    color: #FFFFFF;
    background-color: #FF9966;
    cursor: default;
    text-align: center;
    font-weight: bold;
    width: 1px;
    text-decoration: none;
}

.butt {
    border: #4477aa solid 1px;
    font-weight: bold;
    height: 19px;
    width: 70px;
    background-color: #FFFFFF;
    color: #000000;
    vertical-align: baseline;
}

.L_butt2_1 {
    padding: 1px;
    text-decoration: none;
    background-color: #ffffff;
    color: #000000;
    vertical-align: middle;
    cursor: default;
}

.L_butt2_2 {
    padding: 0;
    border: #0099CC solid 1px;
    background: #FFEE99;
    text-decoration: none;
    color: #000000;
    cursor: default;
}

/* ---------------------------------------------------------------------------*/
.bor {
    width: 10px;
    height: 10px;
}

.frams1 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/1.png) no-repeat right bottom;
}

.frams2 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/2.png) no-repeat left bottom;
}

.frams3 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/3.png) no-repeat left top;
}

.frams4 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/4.png) no-repeat right top;
}

.frams5 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/5.png) repeat-x center bottom;
}

.frams6 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/6.png) repeat-y left;
}

.frams7 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/7.png) repeat-x top;
}

.frams8 {
    background: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/8.png) repeat-y right;
}

#osn_tab {
    background-color: #FFFFFF;
    color: #000000;
    border: #A9A9A9 solid 1px;
}

.pmd_header {
    background-color: #EAEEF0;
    color: #000000;
    text-align: center;
    font-weight: bold;
    margin: 0;
    padding: 0;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/top_panel.png);
    background-position: top;
    background-repeat: repeat-x;
    border-right: #999999 solid 1px;
    border-left: #999999 solid 1px;
    height: 28px;
}

.pmd_header a {
    display: block;
    float: left;
    margin: 3px 1px 4px 1px;
    height: 20px;
    border: 1px dotted #ffffff;
}

.pmd_header .M_bord {
    display: block;
    float: left;
    margin: 4px;
    height: 20px;
    width: 2px;
}

.pmd_header a.first {
    margin-right: 1em;
}

.pmd_header a.last {
    margin-left: 1em;
}

a.M_butt_Selected_down_IE,
a.M_butt_Selected_down {
    border: 1px solid #C0C0BB;
    background-color: #99FF99;
    color: #000000;
}

a.M_butt_Selected_down_IE:hover,
a.M_butt_Selected_down:hover,
a.M_butt:hover {
    border: 1px solid #0099CC;
    background-color: #FFEE99;
    color: #000000;
}

#layer_menu {
    z-index: 1000;
    position: absolute;
    left: 0;
    background-color: #EAEEF0;
    border: #999999 solid 1px;
}

#layer_action {
    position: absolute;
    left: 638px;
    top: 52px;
    z-index: 1000;
    background-color: #CCFF99;
    padding: 3px;
    border: #009933 solid 1px;
    white-space: nowrap;
    font-weight: bold;
}

#layer_upd_relation {
    position: absolute;
    left: 637px;
    top: 224px;
    z-index: 1000;
}

#layer_new_relation {
    position: absolute;
    left: 636px;
    top: 85px;
    z-index: 1000;
    width: 153px;
}

#pmd_optionse {
    position: absolute;
    left: 636px;
    top: 85px;
    z-index: 1000;
    width: 153px;
}

#layer_menu_sizer {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/resize.png);
    cursor: nw-resize;
    width: 16px;
    height: 16px;
}

.panel {
    position: fixed;
    top: 50px;
    right: 0;
    display: none;
    background: #FFF;
    border:1px solid #F5F5F5;
    width: 350 px;
    height: auto;
    padding: 30px 170px 30px 30px;
    color:#FFF;
    z-index:99;
}

a.trigger{
    position: fixed;
    text-decoration: none;
    top: 60px; right: 0;
    color:#fff;
    padding: 10px 40px 10px 15px;
    background:#333333 url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/plus.png) 85% 55% no-repeat;
    border:1px solid #444444;
    display: block;
}

a.trigger:hover{
    position: fixed;
    text-decoration: none;
    top: 60px; right: 0;
    color:#080808;
    padding: 10px 40px 10px 15px;
    background:#fff696 url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/plus.png) 85% 55% no-repeat;
    border:1px solid #999;
    display: block;
}

a.active.trigger {
    background:#222222 url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/minus.png) 85% 55% no-repeat;
    z-index:999;
}

a.active.trigger:hover {
    background:#fff696 url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/minus.png) 85% 55% no-repeat;
    z-index:999;
}

h2.tiger{
    background-repeat: repeat-x;
    padding: 1px;
    font-weight: bold;
    padding: 50 20 50 20px;
    margin: 0 0 5px 0;
    width: 250px;
    float: left;
    color : #333;
    text-align: center;
}

h2.tiger a {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header.png);
    text-align: center;
    text-decoration: none;
    color : #333;
    display: block;
}

h2.tiger a:hover {
    color: #000;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header_Linked.png);
}

h2.active {
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>pmd/Header.png);
    background-repeat: repeat-x;
    padding: 1px;
    background-position: left bottom;
}

.toggle_container {
    margin: 0 0 5px;
    padding: 0;
    border-top: 1px solid #d6d6d6;
    background: #FFF ;
    width: 250px;
    overflow: hidden;
    font-size: 1.2em;
    clear: both;
}

.toggle_container .block {
    background-color: #DBE4E8;
    padding:40 15 40 15px; /*--Padding of Container--*/
    border:1px solid #999;
    color:#000;
}

.history_table {
    text-align: center;
    background-color: #9999CC;
}

.history_table2 {
    text-align: center;
    background-color: #DBE4E8;
}

#filter {
    display: none;
    position: absolute;
    top: 0%;
    left: 0%;
    width: 100%;
    height: 100%;
    background-color: #CCA;
    z-index:10;
    opacity:0.5;
    filter: alpha(opacity=50);
}

#box {
    display: none;
    position: absolute;
    top: 20%;
    left: 30%;
    width: 500px;
    height: 220px;
    padding: 48px;
    margin:0;
    border: 1px solid black;
    background-color: white;
    z-index:101;
    overflow: visible;
}

#boxtitle {
    position:absolute;
    float:center;
    top:0;
    left:0;
    width:593px;
    height:20px;
    padding:0;
    padding-top:4px;
    left-padding:8px;
    margin:0;
    border-bottom:4px solid #3CF;
    background-color: #D0DCE0;
    color:black;
    font-weight:bold;
    padding-left: 2px;
    text-align:left;
}

#tblfooter {
    background-color: #D3DCE3;
    float: right;
    padding-top:10px;
    color: black;
    font-weight: normal;
}

input.btn {
    color:#333;
    background-color: #D0DCE0;
}

body .ui-widget {
    font-size: 1em;
}

.ui-dialog fieldset legend a {
    color: #235A81;
}

/* jqPlot */

/*rules for the plot target div.  These will be cascaded down to all plot elements according to css rules*/
.jqplot-target {
    position: relative;
    color: #222222;
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    font-size: 1em;
/*    height: 300px;
    width: 590px;*/
}

/*rules applied to all axes*/
.jqplot-axis {
    font-size: 0.75em;
}

.jqplot-xaxis {
    margin-top: 10px;
}

.jqplot-x2axis {
    margin-bottom: 10px;
}

.jqplot-yaxis {
    margin-right: 10px;
}

.jqplot-y2axis, .jqplot-y3axis, .jqplot-y4axis, .jqplot-y5axis, .jqplot-y6axis, .jqplot-y7axis, .jqplot-y8axis, .jqplot-y9axis, .jqplot-yMidAxis {
    margin-left: 10px;
    margin-right: 10px;
}

/*rules applied to all axis tick divs*/
.jqplot-axis-tick, .jqplot-xaxis-tick, .jqplot-yaxis-tick, .jqplot-x2axis-tick, .jqplot-y2axis-tick, .jqplot-y3axis-tick, .jqplot-y4axis-tick, .jqplot-y5axis-tick, .jqplot-y6axis-tick, .jqplot-y7axis-tick, .jqplot-y8axis-tick, .jqplot-y9axis-tick, .jqplot-yMidAxis-tick {
    position: absolute;
    white-space: pre;
}


.jqplot-xaxis-tick {
    top: 0px;
    /* initial position untill tick is drawn in proper place */
    left: 15px;
/*    padding-top: 10px;*/
    vertical-align: top;
}

.jqplot-x2axis-tick {
    bottom: 0px;
    /* initial position untill tick is drawn in proper place */
    left: 15px;
/*    padding-bottom: 10px;*/
    vertical-align: bottom;
}

.jqplot-yaxis-tick {
    right: 0px;
    /* initial position untill tick is drawn in proper place */
    top: 15px;
/*    padding-right: 10px;*/
    text-align: right;
}

.jqplot-yaxis-tick.jqplot-breakTick {
	right: -20px;
	margin-right: 0px;
	padding:1px 5px 1px 5px;
/*	background-color: white;*/
	z-index: 2;
	font-size: 1.5em;
}

.jqplot-y2axis-tick, .jqplot-y3axis-tick, .jqplot-y4axis-tick, .jqplot-y5axis-tick, .jqplot-y6axis-tick, .jqplot-y7axis-tick, .jqplot-y8axis-tick, .jqplot-y9axis-tick {
    left: 0px;
    /* initial position untill tick is drawn in proper place */
    top: 15px;
/*    padding-left: 10px;*/
/*    padding-right: 15px;*/
    text-align: left;
}

.jqplot-yMidAxis-tick {
    text-align: center;
    white-space: nowrap;
}

.jqplot-xaxis-label {
    margin-top: 10px;
    font-size: 11pt;
    position: absolute;
}

.jqplot-x2axis-label {
    margin-bottom: 10px;
    font-size: 11pt;
    position: absolute;
}

.jqplot-yaxis-label {
    margin-right: 10px;
/*    text-align: center;*/
    font-size: 11pt;
    position: absolute;
}

.jqplot-yMidAxis-label {
    font-size: 11pt;
    position: absolute;
}

.jqplot-y2axis-label, .jqplot-y3axis-label, .jqplot-y4axis-label, .jqplot-y5axis-label, .jqplot-y6axis-label, .jqplot-y7axis-label, .jqplot-y8axis-label, .jqplot-y9axis-label {
/*    text-align: center;*/
    font-size: 11pt;
    margin-left: 10px;
    position: absolute;
}

.jqplot-meterGauge-tick {
    font-size: 0.75em;
    color: #999999;
}

.jqplot-meterGauge-label {
    font-size: 1em;
    color: #999999;
}

table.jqplot-table-legend {
    margin-top: 12px;
    margin-bottom: 12px;
    margin-left: 12px;
    margin-right: 12px;
}

table.jqplot-table-legend, table.jqplot-cursor-legend {
    background-color: rgba(255,255,255,0.6);
    border: 1px solid #cccccc;
    position: absolute;
    font-size: 0.75em;
}

td.jqplot-table-legend {
    vertical-align:middle;
}

/*
These rules could be used instead of assigning
element styles and relying on js object properties.
*/

/*
td.jqplot-table-legend-swatch {
    padding-top: 0.5em;
    text-align: center;
}

tr.jqplot-table-legend:first td.jqplot-table-legend-swatch {
    padding-top: 0px;
}
*/

td.jqplot-seriesToggle:hover, td.jqplot-seriesToggle:active {
    cursor: pointer;
}

.jqplot-table-legend .jqplot-series-hidden {
    text-decoration: line-through;
}

div.jqplot-table-legend-swatch-outline {
    border: 1px solid #cccccc;
    padding:1px;
}

div.jqplot-table-legend-swatch {
    width:0px;
    height:0px;
    border-top-width: 5px;
    border-bottom-width: 5px;
    border-left-width: 6px;
    border-right-width: 6px;
    border-top-style: solid;
    border-bottom-style: solid;
    border-left-style: solid;
    border-right-style: solid;
}

.jqplot-title {
    top: 0px;
    left: 0px;
    padding-bottom: 0.5em;
    font-size: 1.2em;
}

table.jqplot-cursor-tooltip {
    border: 1px solid #cccccc;
    font-size: 0.75em;
}


.jqplot-cursor-tooltip {
    border: 1px solid #cccccc;
    font-size: 0.75em;
    white-space: nowrap;
    background: rgba(208,208,208,0.5);
    padding: 1px;
}

.jqplot-highlighter-tooltip, .jqplot-canvasOverlay-tooltip {
    border: 1px solid #cccccc;
    font-size: 0.75em;
    white-space: nowrap;
    background: rgba(208,208,208,0.5);
    padding: 1px;
}

.jqplot-point-label {
    font-size: 0.75em;
    z-index: 2;
}
      
td.jqplot-cursor-legend-swatch {
    vertical-align: middle;
    text-align: center;
}

div.jqplot-cursor-legend-swatch {
    width: 1.2em;
    height: 0.7em;
}

.jqplot-error {
/*   Styles added to the plot target container when there is an error go here.*/
    text-align: center;
}

.jqplot-error-message {
/*    Styling of the custom error message div goes here.*/
    position: relative;
    top: 46%;
    display: inline-block;
}

div.jqplot-bubble-label {
    font-size: 0.8em;
/*    background: rgba(90%, 90%, 90%, 0.15);*/
    padding-left: 2px;
    padding-right: 2px;
    color: rgb(20%, 20%, 20%);
}

div.jqplot-bubble-label.jqplot-bubble-label-highlight {
    background: rgba(90%, 90%, 90%, 0.7);
}

div.jqplot-noData-container {
	text-align: center;
	background-color: rgba(96%, 96%, 96%, 0.3);
}
