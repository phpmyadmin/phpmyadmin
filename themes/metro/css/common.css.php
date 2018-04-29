<?php

// unplanned execution path
if (! defined('PMA_MINIMUM_COMMON') && ! defined('TESTSUITE')) {
    exit();
}
?>
/******************************************************************************/

/* CSS Reset */

html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, hgroup, menu, nav, output, ruby, section, summary, time, mark, audio, video
{
    margin: 0;
    padding: 0;
    border: 0;
    font-family: 'Open Sans';
}

/* HTML5 display-role reset for older browsers */

article, aside, details, figcaption, figure, footer, header, hgroup, menu, nav, section
{
    display: block;
}

ol, ul
{
    list-style: none;
}

blockquote, q
{
    quotes: none;
}

blockquote:before, blockquote:after, q:before, q:after
{
    content: '';
    content: none;
}

table
{
    border-collapse: collapse;
    border-spacing: 0;
}

/* fonts */

@font-face {
    font-family: 'IcoMoon';
    src: local('☺');
    src: url('./themes/metro/fonts/IcoMoon.eot');
    src: url('./themes/metro/fonts/IcoMoon.eot?#iefix') format('embedded-opentype'),
        url('./themes/metro/fonts/IcoMoon.svg#IcoMoon') format('svg'),
        url('./themes/metro/fonts/IcoMoon.woff') format('woff'),
        url('./themes/metro/fonts/IcoMoon.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'Open Sans';
    src: local('☺'), local('Open Sans'), local('OpenSans');
    src: url('./themes/metro/fonts/opensans-regular-webfont.eot');
    src: url('./themes/metro/fonts/opensans-regular-webfont.eot?#iefix') format('embedded-opentype'),
        url('./themes/metro/fonts/opensans-regular-webfont.woff') format('woff'),
        url('./themes/metro/fonts/opensans-regular-webfont.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'Open Sans Light';
    src: local('☺'), local('Open Sans Light'), local('OpenSans-Light');
    src: url('./themes/metro/fonts/opensans-light-webfont.eot');
    src: url('./themes/metro/fonts/opensans-light-webfont.eot?#iefix') format('embedded-opentype'),
        url('./themes/metro/fonts/opensans-light-webfont.woff') format('woff'),
        url('./themes/metro/fonts/opensans-light-webfont.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'Open Sans Bold';
    src: local('☺'), local('Open Sans Bold'), local('OpenSans-Bold');
    src: url('./themes/metro/fonts/opensans-bold-webfont.eot');
    src: url('./themes/metro/fonts/opensans-bold-webfont.eot?#iefix') format('embedded-opentype'),
        url('./themes/metro/fonts/opensans-bold-webfont.woff') format('woff'),
        url('./themes/metro/fonts/opensans-bold-webfont.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'Open Sans Extrabold';
    src: local('☺'), local('Open Sans Extrabold'), local('OpenSans-Extrabold');
    src: url('./themes/metro/fonts/opensans-extrabold-webfont.eot');
    src: url('./themes/metro/fonts/opensans-extrabold-webfont.eot?#iefix') format('embedded-opentype'),
        url('./themes/metro/fonts/opensans-extrabold-webfont.woff') format('woff'),
        url('./themes/metro/fonts/opensans-extrabold-webfont.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
}

*:focus /* disable Chrome's and Safari's idiot input outline effect */
{
    outline: none;
}

#li_select_fontsize
{
    display: none;
}

/* general tags */
html {
    font-size: 100%;
}

input,
select,
textarea {
    font-size: 1em;
}

body {
<?php if (! empty($GLOBALS['cfg']['FontFamily'])) { ?>
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
<?php } ?>
    padding: 0;
    margin-<?php echo $left; ?>: 250px;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    line-height: 1;
    font-size: 11px;
}

/* Override style formats by html tags */

font[color=red], span[style="color: #FF0000"]
{
    color: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?> !important;
}

strong
{
    font-weight: normal;
}

/* login */

body#loginform {
    margin: 0;
    background-color: #666;
}

body#loginform #page_content {
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    margin: 0 !important;
    padding: 20px;
    margin-top: 10% !important;
    height: 220px;
}

body#loginform div.container
{
    text-align: <?php echo $left; ?>;
    width: 48em;
    margin-left: auto;
    margin-right: auto;
}

body#loginform div.container:before
{
    font-family: 'IcoMoon';
    font-size: 220px;
    color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    content: "a";
    float: left;
    margin-right: 20px;
    margin-top: -10px;
    background-color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    overflow: hidden;
    height: 220px;
    width: 230px;
    line-height: 1;
}

body#loginform h1
{
    display: inline-block;
    text-align: left;
    color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    font-size: 2.5em;
    padding-top: 0;
    margin-right: -50%;
    line-height: 2;
}

body#loginform a.logo
{
    display: none;
}

body#loginform fieldset legend
{
    display: none;
}

body#loginform .item
{
    margin-bottom: 10px;
}

body#loginform input.textfield
{
    width: 100%;
    border: 1px solid #ffffff;
    background: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    margin: 0;
}

body#loginform input.textfield:hover,
body#loginform input.textfield:focus
{
    background-color: #fff;
    color: #333;
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    margin: 0;
}

body#loginform input[type=submit]
{
    background-color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    border: none;
    padding: 7px;
    margin: 0;
}

body#loginform select
{
    margin: 0 !important;
    border: 1px solid <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding-left: 0 !important;
    border: 1px solid <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    min-width: 100%;
}

body#loginform select:hover
{
    border: 1px solid <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

body#loginform br
{
    display: none;
}

body#loginform fieldset
{
    border: none;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    padding: 0;
    margin-top: 0;
    margin-bottom: 10px;
    background: none;
}

body#loginform fieldset:first-child
{
    margin-bottom: 0;
    border-bottom: none;
    margin: 0;
}

body#loginform fieldset.tblFooters
{
    border: none;
    margin: 0;
    clear: none;
}

body#loginform .error
{
    float: left;
    width: 48em;
    margin-top: -280px;
}

form.login label
{
    display: none
}

.turnOffSelect {
  -moz-user-select: none;
  -khtml-user-select: none;
  -webkit-user-select: none;
  user-select: none;
}

#page_content  {
    margin: 20px !important;
}

<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea,
tt,
pre,
code {
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?> !important;
}
<?php } ?>


h1
{
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
    font-weight: normal;
    font-size: 3em;
    color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    margin: 0;
    letter-spacing: -1px;
    line-height: 1;
}

h2
{
    font-size: 3.6em;
    font-weight: normal;
    color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
    margin-top: 10px;
    margin-bottom: 0;
    line-height: 1;
    letter-spacing: -1px;
}

/* Hiding icons in the page titles */
h2 img {
    display: none;
}

h2 a img {
    display: inline;
}

.data,
.data_full_width {
    margin: 10px 0;
}

.data_full_width {
    width: 100%;
}

h3 {
    font-family: "open sans extrabold", "segoe black";
    text-transform: uppercase;
    font-weight: normal;
}

a,
a:link,
a:visited,
a:active,
button.mult_submit,
.checkall_box+label {
    text-decoration: none;
    color: #235a81;
    cursor: pointer;
    outline: none;

}

a:hover,
button.mult_submit:hover,
button.mult_submit:focus,
.checkall_box+label:hover {
    text-decoration: underline;
    color: #235a81;
}

#initials_table
{
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    margin-bottom: 10px;
}

#initials_table td
{
    padding:8px !important;
}

#initials_table a
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    padding: 4px 8px;
}

dfn:hover {
    cursor: help;
}

.data th
{
    border-bottom: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
}

th, th a
{
    font-family: "open sans bold";
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?> !important;
    font-weight: normal;
}

.data th a:hover
{
    color: #999 !important;
}

.column_heading,
.column_action
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    background-color: #f6f6f6;
}

a img {
    border: 0;
}

hr
{
    color: <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    border: 0;
    height: 1px;
}

form {
    padding: 0;
    margin: 0;
    display: inline;
}

input[type=text], input[type=password], input[type=number]
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseGrayColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    padding: 5px;
    margin: 6px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

input[type=text]:focus, input[type=password]:focus, input[type=number]:focus
{
    border: 1px solid <?php echo $GLOBALS['cfg']['NaviHoverBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

input[type=submit], input[type=reset], input[type=button]
{
    margin-left: 14px;
    border: 1px solid <?php echo $GLOBALS['cfg']['ButtonBackground']; ?>;
    padding: 4px;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    text-decoration: none;
    background-color: <?php echo $GLOBALS['cfg']['ButtonBackground']; ?>;
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
}

input[type=submit]:hover, input[type=reset]:hover, input[type=button]:hover
{
    position: relative;
    cursor:pointer;
    background-color: <?php echo $GLOBALS['cfg']['ButtonHover']; ?>;
    border: 1px solid <?php echo $GLOBALS['cfg']['ButtonHover']; ?>;

}

input[type=submit]:active, input[type=reset]:active, input[type=button]:active
{
    position: relative;
    background-color: #333;
    border: 1px solid #333;
}

.sqlbutton, #tablefieldinsertbuttoncontainer input[type=button]
{
    margin-top: 1em;
    margin-left: 0 !important;
    margin-right: 14px !important;
}

button
{
    margin-left: 14px;
    padding: 4px;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    text-decoration: none;
    background-color: <?php echo $GLOBALS['cfg']['ButtonBackground']; ?>;
}

textarea
{
    overflow: visible;
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseGrayColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

fieldset
{
    margin-top: 20px;
    padding: 5px;
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    padding: 20px;
    background-color: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
}

fieldset fieldset
{
    margin: 20px;
    margin-bottom: 0;
    background-color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border: none;
}

legend
{
  padding: 0 5px;
}

.some-margin {
    margin: 20px;
}

/* buttons in some browsers (eg. Konqueror) are block elements,
   this breaks design */
button {
    display: inline;
}

table caption,
table th,
table td {
    padding: 0.6em;
    vertical-align: top;
}

table {
    border-collapse: collapse;
}

th {
    text-align: left;
}


img,
button {
    vertical-align: middle;
}

select
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseGrayColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    padding: 4px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    margin: 5px;
    background-color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    max-width: 17em;
}

select:focus
{
    border: 1px solid <?php echo $GLOBALS['cfg']['NaviHoverBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

select[multiple] {

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

.floatright {
    float: <?php echo $right; ?>;
}

.center {
    text-align: center;
}

.displayblock {
    display: block;
}

table.nospacing {
    border-spacing: 0;
}

table.nopadding tr th, table.nopadding tr td {
    padding: 0;
}

th.left, td.left {
    text-align: left;
}

th.center, td.center {
    text-align: center;
}

th.right, td.right {
    text-align: right;
    padding-right: 1em;
}

tr.vtop th, tr.vtop td, th.vtop, td.vtop {
    vertical-align: top;
}

tr.vmiddle th, tr.vmiddle td, th.vmiddle, td.vmiddle {
    vertical-align: middle;
}

tr.vbottom th, tr.vbottom td, th.vbottom, td.vbottom {
    vertical-align: bottom;
}

.paddingtop {
    padding-top: 1em;
}

.separator {
    color: #fff;
}

.result_query
{
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    margin-bottom: 20px;
}

div.tools
{
    padding: 10px;
    text-align: <?php echo $right; ?>;
}

div.tools span {
    float: <?php echo $right; ?>;
    margin: 6px 2px;
}

div.tools a
{
    color: <?php echo $GLOBALS['cfg']['BlueHeader']; ?> !important;
}

.chrome input[type="checkbox"] {
    left: -9999px;
    position: relative;
}

.chrome input[type="checkbox"]:before {
    font-family: 'IcoMoon';
    content: "";
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    cursor: default;
    position: absolute;
    padding: 4px;
    top: 0;
    left: 9995px;
}

.chrome input[type="checkbox"]:indeterminate:before {
    content: "";
}

.chrome input[type="checkbox"]:checked:before {
    content: "";
}

.chrome .navigation input[type="checkbox"]:before {
    color: #fff;
}

.chrome input[type="radio"] {
    left: -9999px;
    position: relative;
}

.chrome input[type="radio"]:before {
    font-family: 'IcoMoon';
    content: "";
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    cursor: default;
    position: absolute;
    padding: 4px;
    top: 0;
    left: 9995px;
}

.chrome input[type="radio"]:checked:before {
    content: "";
}

tr.noclick td:first-child:before
{
    content: "";
}

fieldset.tblFooters
{
    margin-top: -1px;
    border-top: 0;
    text-align: <?php echo $right; ?>;
    float: none;
    clear: both;
}

div.null_div {
    height: 20px;
    text-align: center;
    font-style: normal;
    min-width: 50px;
}

fieldset .formelement
{
    float: <?php echo $left; ?>;
    margin-<?php echo $right; ?>: 0.5em;
}

/* revert for Gecko */
fieldset div[class=formelement] {
    white-space: normal;
}

button.mult_submit
{
    border: none;
    background-color: transparent;
    color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
    margin: 0;
}

/* odd items 1,3,5,7,... */
table tr:nth-child(odd)
{
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom: none;
}

/* even items 2,4,6,8,... */
/* (tested on CRTs and ACLs) */
table tr:nth-child(even)
{
    background: <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    border-bottom: none;
}

table tr th,
table tr
{
    text-align: <?php echo $left; ?>;
    border-bottom: 1px solid #eee;
}

table tr.odd, table tr.even
{
    border-left: 3px solid transparent;
}

/* marked table rows */
td.marked:not(.nomarker),
table tr.marked:not(.nomarker) td,
table tr.marked:not(.nomarker) th,
table tr.marked:not(.nomarker)
{
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

td.marked:not(.nomarker)
{
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
}

table tr.marked:not(.nomarker)
{
    border-left: 3px solid #24A0DA;
}

/* hovered items */
table tr:not(.nopointer):hover,
.hover:not(.nopointer),
.structure_actions_dropdown
{
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

.structure_actions_dropdown .icon
{
    vertical-align: middle !important;
}

/* hovered table rows */
table tr.hover:not(.nopointer) th
{
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['ThPointerColor']; ?>;
}

/* marks table rows/cells if the db field is in a where condition */

.condition
{
    border-color: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?> !important;
}

th.condition, th.condition a
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['MainBackground']; ?> !important;
}

td.condition
{
    border: 1px solid;
}

<?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
/* for first th which must have right border set (ltr only) */

<?php } ?>

/**
 * cells with the value NULL
 */
td.null {
    font-style: italic;
    color: #7d7d7d;
}

table .valueHeader {
    text-align: <?php echo $right; ?>;
    white-space: normal;
}
table .value {
    text-align: <?php echo $right; ?>;
    white-space: normal;
}
/* IE doesnt handles 'pre' right */
table [class=value] {
    white-space: normal;
}


<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
.value {
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
.attention {
    color: red;
    font-weight: bold;
}

.allfine {
    color: green;
}


img.lightbulb {
    cursor: pointer;
}

.pdflayout {
    overflow: hidden;
    clip: inherit;
    background-color: #fff;
    display: none;
    border: 1px solid #000;
    position: relative;
}

.pdflayout_table {
    background: #D3DCE3;
    color: #000;
    overflow: hidden;
    clip: inherit;
    z-index: 2;
    display: inline;
    visibility: inherit;
    cursor: move;
    position: absolute;
    font-size: 80%;
    border: 1px dashed #000;
}

/* Doc links in SQL */
.cm-sql-doc {
    text-decoration: none;
    border-bottom: 1px dotted #999999;
    color: inherit !important;
}

/* no extra space in table cells */
td .icon {
    margin: 0;
}

.selectallarrow {
    margin-<?php echo $right; ?>: .3em;
    margin-<?php echo $left; ?>: .6em;
}

/* message boxes: error, confirmation */
#pma_errors, #pma_demo, #pma_footer {
    padding: 20px;
}

#pma_errors #pma_errors
{
    padding: 0;
}

.success h1,
.notice h1,
div.error h1 {
    text-align: <?php echo $left; ?>;
    margin: 0 0 0.2em 0;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

div.success,
div.notice,
div.error,
div.footnotes {
    box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    margin: 20px 0 20px;
    border: 1px solid;
    background-repeat: no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding: 10px 10px 10px 10px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding: 10px 35px 10px 10px;
        <?php } ?>

}

div.success
{
    margin: 0 0 1em 0;
    border: none;
}

.success a,
.notice a,
.error a {
    text-decoration: underline;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
}

.success {
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['BrowseSuccessColor']; ?>;
}

h1.success,
div.success {
    border-color: <?php echo $GLOBALS['cfg']['BrowseSuccessColor']; ?>;
}

.notice, .footnotes
{
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['BlueHeader']; ?>;
}

h1.notice,
div.notice,
div.footnotes
{
    border-color: <?php echo $GLOBALS['cfg']['BlueHeader']; ?>;
}

.notice a
{
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
}

.error
{
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?> !important;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
}

h1.error,
div.error
{
    border-color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

.confirmation
{
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
}

fieldset.confirmation legend
{
    background-color: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
}

/* end messageboxes */

.new_central_col{
    width:              100%;
}

.tblcomment {
    font-size: 70%;
    font-weight: normal;
    color: #000099;
}

.tblHeaders {
    font-family: "open sans bold";
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    font-weight: normal;
}

div.tools,
.tblFooters {
    font-weight: normal;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
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
    color: #0000FF;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
    color: #FF0000;
}

/* forbidden, no privileges */
.noPrivileges {
    color: #FF0000;
    font-weight: bold;
}

/* disabled text */
.disabled,
.disabled a:link,
.disabled a:active,
.disabled a:visited {
    color: #666;
}

.disabled a:hover {
    color: #666;
    text-decoration: none;
}

tr.disabled td,
td.disabled {
    background-color: #f3f3f3;
    color: #aaa;
}

.nowrap {
    white-space: nowrap;
}

/******************************************************************************/
/* specific elements */

/* topmenu */

#topmenu .error {
    background: #eee;
    border: 0 !important;
    color: #aaa;
}

ul#topmenu,
ul#topmenu2
{
    list-style-type: none;
    margin: 0;
    height: 48px;
}

ul.tabs
{
    list-style-type: none;
    margin: 0;
}

ul#topmenu2 {
    margin: -20px -10px 20px;
    padding: 10px;
    clear: both;
}

ul#topmenu li,
ul#topmenu2 li {
    float: <?php echo $left; ?>;
    margin: 0;
    vertical-align: middle;
    padding-top: 10px;
    height: 38px;
}

#topmenu img,
#topmenu2 img {
    margin-right: .5em;
    vertical-align: -3px;
}

.menucontainer {
    background-color: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    height: 48px;
}

.scrollindicator {
    display: none;
}

/* default tab styles */
#topmenu .tabactive {
    background: #fff !important;
}

#topmenu2 .tabactive
{
    background: #ccc;
}

ul#topmenu2 a {
    display: block;
    margin: 7px 0;
    margin-<?php echo $left; ?>: 0;
    padding: 5px 15px;
    white-space: nowrap;
    font-family: "open sans extrabold", "segoe black";
    font-weight: normal;
    color: <?php echo $GLOBALS['cfg']['NaviPointerColor']; ?>;
    text-transform: uppercase;
    background-color: #f6f6f6;
}

fieldset.caution
{
    background: <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
    border: 1px solid <?php echo $GLOBALS['cfg']['BrowseWarningColor']; ?>;
}

fieldset.caution legend
{
    background-color: #fff;
}

fieldset.caution a
{
    font-family: 'Open Sans Bold';
    text-transform: uppercase;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    font-weight: normal;
}

fieldset.caution ul, #tbl_maintenance
{
    padding: 0;
}

fieldset.caution li, #tbl_maintenance li
{
    display: block;
}

fieldset.caution li:before
{
    font-family: 'IcoMoon';
    content: "";
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    margin-right: 10px;
}

fieldset.caution li a:nth-child(2) img
{
    background: url('./themes/metro/img/s_info.png') !important;
}

#tbl_maintenance li a
{
    font-family: 'Open Sans Bold';
    text-transform: uppercase;
    font-weight: normal;
}

#tbl_maintenance li:before
{
    font-family: 'IcoMoon';
    content: "%";
    margin-right: 10px;
}

#topmenu {
    padding-left: 20px;
    padding-right: 20px;
}

ul#topmenu ul {

}

ul#topmenu ul.only {
    <?php echo $left; ?>: 0;
}

ul#topmenu > li {

}

/* default tab styles */
ul#topmenu a,
ul#topmenu span {
    padding: 5px 10px;
    height: 28px;
    line-height: 28px;
    font-family: "open sans extrabold", "segoe black";
    text-transform: uppercase;
    color: #666;
    font-weight: normal;
}

ul#topmenu ul a {
    border-width: 1pt 0 0 0;
}

ul#topmenu ul li:first-child a {
    border-width: 0;
}

/* enabled hover/active tabs */
ul#topmenu > li > a:hover,
ul#topmenu > li > .tabactive {
    text-decoration: none;
    color: #333;
}

ul#topmenu ul a:hover,
ul#topmenu ul .tabactive {
    text-decoration: none;
}

ul#topmenu a.tab:hover,
ul#topmenu .tabactive {

}

ul#topmenu2 a,
ul#topmenu2 a:hover {

    text-decoration: none;
}

/* to be able to cancel the bottom border, use <li class="active"> */
ul#topmenu > li.active {

    border-right: 0;
}
/* end topmenu */

/* zoom search */
div#dataDisplay input,
div#dataDisplay select {
    margin: 0;
    margin-<?php echo $right; ?>: .5em;
}
div#dataDisplay th {
    line-height: 2em;
}

table#tableFieldsId {
    width: 100%;
}

/* Calendar */
table.calendar {
    width: 100%;
}
table.calendar td {
    text-align: center;
}
table.calendar td a {
    display: block;
}

table.calendar td a:hover {
    background-color: #CCFFCC;
}

table.calendar th {
    background-color: #D3DCE3;
}

table.calendar td.selected {
    background-color: #FFCC99;
}

img.calendar {
    border: none;
}
form.clock {
    text-align: center;
}
/* end Calendar */


/* table stats */
div#tablestatistics table {
    float: <?php echo $left; ?>;
    margin-bottom: .5em;
    margin-<?php echo $right; ?>: 1.5em;
    margin-top: .5em;
    min-width: 16em;
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
    width: 100%;
}

#serverinfo
{
    padding: 12px 30px;
    overflow: hidden;
    margin: 0;
    margin-left: -1em;
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    font-height: 1.1em;
    height: 15px;
}

#serverinfo .item
{
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    white-space: nowrap;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
}

#serverinfo .item:before
{
    padding-left: 5px;
    padding-right: 5px;
    font-family: "IcoMoon";
    font-size: 10px;
    color: #eee;
    content: "";
}

#serverinfo a:hover
{
    text-decoration: none;
}

#serverinfo a:first-child
{
    display: none !important;
}

#serverinfo .separator,
#serverinfo .icon
{
    display: none;
}

#page_nav_icons {
    position: fixed;
    top: 0;
    <?php echo $right; ?>: 0;
    z-index: 99;
    padding: .25em 0;
}

#goto_pagetop, #lock_page_icon, #page_settings_icon {
    padding: .25em;
}

#page_settings_icon {
    cursor: pointer;
    display: none;
}

#page_settings_modal {
    display: none;
}

#pma_navigation_settings {
    display: none;
}

#span_table_comment {
    font-weight: normal;
    font-style: italic;
    white-space: nowrap;
    margin-left: 10px;
}

#serverinfo img {
    margin: 0 .1em 0;
    margin-<?php echo $left; ?>: .2em;
}

#textSQLDUMP {
    width: 95%;
    height: 95%;
    font-family: Consolas, "Courier New", Courier, mono;
    font-size: 110%;
}

#TooltipContainer {
    position: absolute;
    z-index: 99;
    width: 20em;
    height: auto;
    overflow: visible;
    visibility: hidden;
    background-color: #ffffcc;
    color: #006600;
    border: .1em solid #000;
    padding: .5em;
}

/* user privileges */
#fieldset_add_user_login div.item {
    border-bottom: 1px solid #ddd;
    padding-bottom: .3em;
    margin-bottom: .3em;
}

#fieldset_add_user_login label {
    float: <?php echo $left; ?>;
    display: block;
    width: 10em;
    max-width: 100%;
    text-align: <?php echo $right; ?>;
    padding-<?php echo $right; ?>: .5em;
    line-height: 35px;
}

#fieldset_add_user_login span.options #select_pred_username,
#fieldset_add_user_login span.options #select_pred_hostname,
#fieldset_add_user_login span.options #select_pred_password {
    width: 100%;
    max-width: 100%;
}

#fieldset_add_user_login span.options {
    float: <?php echo $left; ?>;
    display: block;
    width: 12em;
    max-width: 100%;
    padding-<?php echo $right; ?>: .5em;
}

#fieldset_add_user_login input {
    width: 12em;
    clear: <?php echo $right; ?>;
    max-width: 100%;
}

#fieldset_add_user_login span.options input {
    width: auto;
}

#fieldset_add_user_login span.options input[type=button]
{
    margin: 4px;
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

#fieldset_user_group_rights fieldset {
    float: <?php echo $left; ?>;
}
/* END user privileges */


/* serverstatus */

.linkElem:hover {
    text-decoration: underline;
    color: #235a81;
    cursor: pointer;
}

h3#serverstatusqueries span {
    font-size: 60%;
    display: inline;
}

.buttonlinks {
    float: <?php echo $right; ?>;
    white-space: nowrap;
}

/* Also used for the variables page */
fieldset#tableFilter {
    padding: 0.1em 1em;
}

fieldset#tableFilter input[type=submit] {
    margin-top: 9px;
}

div#serverStatusTabs {
    margin-top: 1em;
}

caption a.top {
    float: <?php echo $right; ?>;
}

div#serverstatusquerieschart {
    float: <?php echo $left; ?>;
    width: 500px;
    height: 350px;
    padding-<?php echo $left; ?>: 30px;
}

table#serverstatusqueriesdetails,
table#serverstatustraffic {
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
    white-space: nowrap;
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
    clear: both;
    min-width: 500px;
    height: 400px;
    padding-bottom: 80px;
}

#addChartDialog input[type="text"] {
    margin: 0;
    padding: 3px;
}

div#chartVariableSettings {
    margin-left: 10px;
}

table#chartGrid td {
    padding: 3px;
    margin: 0;
}

table#chartGrid div.monitorChart {
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    overflow: hidden;
}

div.tabLinks {
    margin-left: 0.3em;
    float: <?php echo $left; ?>;
    padding: 5px 0;
}

div.tabLinks a, div.tabLinks label {
    margin-right: 7px;
}

div.tabLinks .icon {
    margin: -0.2em 0.3em 0 0;
}

.popupContent {
    display: none;
    position: absolute;
    border: 1px solid #CCC;
    margin: 0;
    padding: 3px;
    background-color: #fff;
    z-index: 2;
}

div#logTable {
    padding-top: 10px;
    clear: both;
}

div#logTable table {
    width: 100%;
}

div#queryAnalyzerDialog {
    min-width: 700px;
}

div#queryAnalyzerDialog div.CodeMirror-scroll {
    height: auto;
}

div#queryAnalyzerDialog div#queryProfiling {
    height: 300px;
}

div#queryAnalyzerDialog td.explain {
    width: 250px;
}

div#queryAnalyzerDialog table.queryNums {
    display: none;
    border: 0;
    text-align: left;
}

.smallIndent {
    padding-<?php echo $left; ?>: 7px;
}

/* end serverstatus */

/* server variables */
#serverVariables {
    table-layout: fixed;
    width: 100%;
}
#serverVariables .var-row > td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 2em;
}
#serverVariables .var-header {
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    background: #f3f3f3;
    font-weight: bold;
}
#serverVariables .var-header {
    text-align: <?php echo $left; ?>;
}
#serverVariables .var-row {
    padding: 0.5em;
    min-height: 18px;
}
#serverVariables .var-action {
    width: 120px;
}
#serverVariables .var-name {
    float: <?php echo $left; ?>;
}
#serverVariables .var-name.session {
    font-weight: normal;
    font-style: italic;
}
#serverVariables .var-value {
    width: 50%;
    float: <?php echo $right; ?>;
    text-align: <?php echo $right; ?>;
}
#serverVariables .var-doc {
    overflow:visible;
    float: <?php echo $right; ?>;
}

/* server variables editor */
#serverVariables .editLink {
    padding-<?php echo $right; ?>: 1em;
    float: <?php echo $left; ?>;
    font-family: sans-serif;
}
#serverVariables .serverVariableEditor {
    width: 100%;
    overflow: hidden;
}
#serverVariables .serverVariableEditor input {
    width: 100%;
    margin: 0 0.5em;
    box-sizing: border-box;
    -ms-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    height: 2.2em;
}
#serverVariables .serverVariableEditor div {
    display: block;
    overflow: hidden;
    padding-<?php echo $right; ?>: 1em;
}
#serverVariables .serverVariableEditor a {
    float: <?php echo $right; ?>;
    margin: 0 0.5em;
    line-height: 2em;
}
/* end server variables */


p.notice {
    margin: 1.5em 0;
    border: 1px solid #000;
    background-repeat: no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding: 10px 10px 10px 25px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding: 25px 10px 10px 10px
        <?php } ?>
    background: #555;
    color: #d4fb6a;
}

p.notice a {
    color: #fff;
    text-decoration: underline;
}

/* profiling */

div#profilingchart {
    width: 850px;
    height: 370px;
    float: <?php echo $left; ?>;
}

/* END profiling */

/* table charting */

#resizer {
    border: 1px solid silver;
}
#inner-resizer { /* make room for the resize handle */
    padding: 10px;
}
.chartOption {
    float: <?php echo $left; ?>;
    margin-<?php echo $right;?>: 40px;
}

/* END table charting */

/* querybox */

#togglequerybox {
    margin: 0 10px;
}

#serverstatus h3
{
    margin: 15px 0;
    font-weight: normal;
    color: #999;
    font-size: 1.7em;
}

#sectionlinks {
    padding: 16px;
    background: #f3f3f3;
    border: 1px solid #aaa;
}
#sectionlinks a,
.buttonlinks a,
a.button {
    font-size: .88em;
    font-weight: bold;
    line-height: 35px;
    margin-<?php echo $left; ?>: 7px;
    border: 1px solid #aaa;
    padding: 5px 10px;
    color: #111;
    text-decoration: none;
    background: #ddd;
    white-space: nowrap;
}

#sectionlinks a:hover,
.buttonlinks a:hover,
a.button:hover {

}

div#sqlquerycontainer {
    float: <?php echo $left; ?>;
    width: 69%;
    /* height: 15em; */
}

div#tablefieldscontainer {
    float: <?php echo $right; ?>;
    width: 29%;
    margin-top: -20px;
    /* height: 15em; */
}

div#tablefieldscontainer select {
    width: 100%;
    background: #fff;
    max-width: initial;
    /* height: 12em; */
}

textarea#sqlquery {
    width: 100%;
    /* height: 100%; */
    border: 1px solid #aaa;
    padding: 5px;
    font-family: inherit;
}
textarea#sql_query_edit {
    height: 7em;
    width: 95%;
    display: block;
}
div#queryboxcontainer div#bookmarkoptions {
    margin-top: .5em;
}
/* end querybox */

/* main page */

#mysqlmaininformation,
#pmamaininformation {
    float: <?php echo $left; ?>;
    width: 49%;
}

#maincontainer ul {
    list-style-type: square;
    vertical-align: middle;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    margin-left: 20px;
}

#maincontainer ul li
{
    line-height: 1.5;
}

#full_name_layer {
    position: absolute;
    padding: 2px;
    margin-top: -3px;
    z-index: 801;

    border: solid 1px #888;
    background: #fff;
}

/* END main page */


/* iconic view for ul items */

li br
{
    display: none;
}

li.no_bullets {
    list-style-type:none !important;
}

li#li_mysql_client_version
{
    overflow: hidden;
    text-overflow: ellipsis;
}

li#li_create_database
{
    background-color: #f6f6f6;
    padding: 10px;
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    display: block;
    margin-bottom: 20px;
}

li#li_select_lang select
{
    margin: 0 !important;
    height: 26px;
}

li#li_select_lang
{
    display: block;
    padding: 10px;
    padding-left: 20px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
}

li#li_select_lang:hover
{
    background: #f6f6f6;
}

li#li_select_mysql_collation select
{
    margin: 0 !important;
}

li#li_select_mysql_collation
{
    display: block;
    padding: 10px;
    padding-left: 20px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
}

li#li_select_mysql_collation:hover
{
    background: #f6f6f6;
}

li#li_select_theme select
{
    margin: 0 !important;
}

li#li_select_theme
{
    display: block;
    padding: 10px;
    padding-left: 20px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
}

li#li_select_theme:after
{
    content: "Scheme: <?php echo $GLOBALS['cfg']['Scheme']; ?>";
    margin-left: 10px;
}

li#li_select_theme:hover
{
    background: #f6f6f6;
}

li#li_change_password
{
    /* list-style-image: url(<?php echo $theme->getImgPath(); ?>s_passwd.png); */
    display: block;
    padding: 10px;
    padding-left: 20px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
}

li#li_change_password:hover
{
    background: #f6f6f6;
}

li#li_user_preferences
{
    /* list-style-image: url(<?php echo $theme->getImgPath(); ?>b_tblops.png); */
    display: block;
    padding: 10px;
    padding-left: 20px;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
}

li#li_user_preferences:hover
{
    background: #f6f6f6;
}

li#li_switch_dbstats
{
    background-color: #f6f6f6;
    padding: 10px;
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    display: block;
}

/* END iconic view for ul items */

#body_browse_foreigners {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    margin: .5em .5em 0 .5em;
}

#bodyquerywindow {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
}

#bodythemes {
    width: 500px;
    margin: auto;
    text-align: center;
}

#bodythemes img {
    border: .1em solid #000;
}

#bodythemes a:hover img {
    border: .1em solid red;
}

#fieldset_select_fields {
    float: <?php echo $left; ?>;
}

#selflink {
    clear: both;
    display: block;
    margin-top: 20px;
    margin-bottom: 20px;
    margin-left: 20px;
    margin-right: 20px;
    border-top: 1px solid silver;
    text-align: <?php echo $right; ?>;
}

#table_innodb_bufferpool_usage,
#table_innodb_bufferpool_activity {
    float: <?php echo $left; ?>;
}

#div_mysql_charset_collations table {
    float: <?php echo $left; ?>;
}

#div_mysql_charset_collations table th,
#div_mysql_charset_collations table td {
    padding: 0.4em;
}

#div_mysql_charset_collations table th#collationHeader {
    width: 35%;
}

.operations_half_width {
    width: 100%;
    float: <?php echo $left; ?>;
    margin-bottom: 10px;
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

code.php {
    display: block;
    padding-left: 0.3em;
    margin-top: 0;
    margin-bottom: 0;
    max-height: 10em;
    overflow: auto;
    direction: ltr;
}

.sqlOuter code.sql, div.sqlvalidate, #inline_editor_outer
{
    display: block;
    padding: 1em;
    margin: 1em;
    overflow: auto;
    background-color: <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    border: 1px solid <?php echo $GLOBALS['cfg']['BorderColor']; ?>;
    direction: ltr;
}

#main_pane_left
{
    width: 60%;
    min-width: 260px;
    float: <?php echo $left; ?>;
    padding-top: 1em;
}

#main_pane_right
{
    overflow: hidden;
    min-width: 160px;
    padding-top: 1em;
    padding-<?php echo $left; ?>: 3em;
}

.group
{
    margin-bottom: 1em;
    padding-bottom: 1em;
}

.group input[type=submit]
{
    margin-left: 0;
}

.group h2
{
    color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    font-size: 2.8em;
    font-weight: normal;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
    margin-top: 0;
    margin-bottom: 0.6em;
}

.group-cnt {
    padding: 0 0 0 0.5em;
    display: inline-block;
    width: 98%;
}

textarea#partitiondefinition {
    height: 3em;
}


/* for elements that should be revealed only via js */
.hide {
    display: none;
}

#list_server {
    list-style-image: none;
    padding: 0;
}

/**
  *  Progress bar styles
  */
div.upload_progress
{
    width: 400px;
    margin: 3em auto;
    text-align: center;
}

div.upload_progress_bar_outer
{
    border: 1px solid #000;
    width: 202px;
    position: relative;
    margin: 0 auto 1em;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
}

div.upload_progress_bar_inner
{
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    width: 0;
    height: 12px;
    margin: 1px;
    overflow: hidden;
    <?php if ($GLOBALS['cfg']['BrowseMarkerEnable']) { ?>
    color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
    <?php } ?>
    position: relative;
}

div.upload_progress_bar_outer div.percentage
{
    position: absolute;
    top: 0;
    <?php echo $left; ?>: 0;
    width: 202px;
}

div.upload_progress_bar_inner div.percentage
{
    top: -1px;
    <?php echo $left; ?>: -1px;
}

div#statustext {
    margin-top: .5em;
}

table#serverconnection_src_remote,
table#serverconnection_trg_remote,
table#serverconnection_src_local,
table#serverconnection_trg_local  {
  float: <?php echo $left; ?>;
}
/**
  *  Validation error message styles
  */
input[type=text].invalid_value,
input[type=password].invalid_value,
input[type=number].invalid_value,
input[type=date].invalid_value
.invalid_value {
    background: #FFCCCC;
}

/**
  *  Ajax notification styling
  */
/* additional styles */
.ajax_notification {
    top: 0;
    position: fixed;
    width: 100%;
    z-index: 1100;
    text-align: center;
    display: inline;
    left: 0;
    right: 0;
    background-image: url(<?php echo $theme->getImgPath(); ?>ajax_clock_small.gif);
    background-repeat: no-repeat;
    background-position: 46%;
    margin: 0;
    background-color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
    color: <?php echo $GLOBALS['cfg']['MainColor']; ?>;
    padding: 10px !important;
    border: none;
    height: 19px;
}

.dismissable
{
    margin-left: -10px;
    margin-top: -10px;
}

#loading_parent {
    /** Need this parent to properly center the notification division */
    position: relative;
    width: 100%;
 }
/**
  * Export and Import styles
  */

.export_table_list_container {
    display: inline-block;
    max-height: 20em;
    overflow-y: scroll;
}

.export_table_select th {
    text-align: center;
    vertical-align: middle;
}

.export_table_select .all {
    font-weight: bold;
    border-bottom: 1px solid black;
}

.export_structure, .export_data {
    text-align: center;
}

.export_table_name {
    vertical-align: middle;
}

.exportoptions h3,
.importoptions h3 {
    border-bottom: 1px #ccc solid;
    font-size: 110%;
}

.exportoptions ul,
.importoptions ul,
.format_specific_options ul {
    list-style-type: none;
    margin-bottom: 15px;
}

.exportoptions li,
.importoptions li {
    margin: 7px;
}
.exportoptions label,
.importoptions label,
.exportoptions p,
.importoptions p {
    margin: 5px;
    float: none;
}

#csv_options label.desc,
#ldi_options label.desc,
#latex_options label.desc,
#output label.desc {
    float: <?php echo $left; ?>;
    width: 15em;
}

.exportoptions,
.importoptions {
    margin: 20px 30px 30px;
    margin-<?php echo $left; ?>: 10px;
}

.exportoptions #buttonGo,
.importoptions #buttonGo {
    padding: 5px 12px;
    text-decoration: none;
    cursor: pointer;
    margin: 0;
}
#buttonGo:hover {

}

.format_specific_options h3 {
    margin: 10px 0 0;
    margin-<?php echo $left; ?>: 10px;
    border: 0;
}

.format_specific_options {
    border: 1px solid #999;
    margin: 7px 0;
    padding: 3px;
}

p.desc {
    margin: 5px;
}

/**
  * Export styles only
  */
select#db_select,
select#table_select {
    width: 400px;
}

.export_sub_options {
    margin: 20px 0 0;
    margin-<?php echo $left; ?>: 30px;
}

.export_sub_options h4 {
    border-bottom: 1px #999 solid;
}

.export_sub_options li.subgroup {
    display: inline-block;
    margin-top: 0;
}

.export_sub_options li {
    margin-bottom: 0;
}

#output_quick_export {
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

#filterText {
    vertical-align: baseline;
}

#popup_background {
    display: none;
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    <?php echo $left; ?>: 0;
    background: #000;
    z-index: 1000;
    overflow: hidden;
}

/**
 * Table structure styles
 */
#fieldsForm ul.table-structure-actions {
    margin: 0;
    padding: 0;
    list-style: none;
}
#fieldsForm ul.table-structure-actions li {
    float: <?php echo $left; ?>;
    margin-<?php echo $right; ?>: 0.3em; /* same as padding of "table td" */
}
#fieldsForm ul.table-structure-actions .submenu li {
    padding: 0;
    margin: 0;
}
#fieldsForm ul.table-structure-actions .submenu li span {
    padding: 0.3em;
    margin: 0.1em;
}
#structure-action-links a {
    margin-<?php echo $right; ?>: 1em;
}
/**
 * Indexes
 */
#index_frm .index_info input,
#index_frm .index_info select {
    width: 14em;
    box-sizing: border-box;
    -ms-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
}

#index_frm .index_info div {
    padding: .2em 0;
}

#index_frm .index_info .label {
    float: <?php echo $left; ?>;
    min-width: 12em;
}

#index_frm .slider {
    width: 10em;
    margin: .6em;
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
    width: 85%;
    float: right;
}

#move_columns_dialog div {
    padding: 1em;
}

#move_columns_dialog ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

#move_columns_dialog li {
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border: 1px solid #aaa;
    color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
    font-weight: bold;
    margin: .4em;
    padding: .2em;
}

/* config forms */
.config-form ul.tabs {
    margin: 1.1em .2em 0;
    padding: 0 0 .3em 0;
    list-style: none;
    font-weight: bold;
}

.config-form ul.tabs li {
    float: <?php echo $left; ?>;
    margin-bottom: -1px;
}

.config-form ul.tabs li a {
    display: block;
    margin: .1em .2em 0;
    white-space: nowrap;
    text-decoration: none;
    border: 1px solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-bottom: 1px solid #ddd;
    font-family: "open sans bold";
}

.config-form ul.tabs li a {
    padding: 7px 10px;
    background: #f2f2f2;
    color: #555;
}

.config-form ul.tabs li a:hover,
.config-form ul.tabs li a:active {
    background: #e5e5e5;
}

.config-form ul.tabs li.active a {
    background-color: #fff;
    margin-top: 1px;
    color: #000;
    border-color: #ddd;
    border-bottom: 1px solid #fff;
}

.config-form fieldset {
    margin-top: 0;
    padding: 0;
    clear: both;
    background: none;
}

.config-form legend {
    display: none;
}

.config-form fieldset p {
    margin: 0;
    padding: 10px;
    background: #fff;
    font-family: <?php echo $GLOBALS['cfg']['FontFamilyLight']; ?>;
    font-size: 16px;
}

.config-form fieldset .errors { /* form error list */
    margin: 0 -2px 1em;
    padding: .5em 1.5em;
    background: #FBEAD9;
    border: 0 #C83838 solid;
    border-width: 1px 0;
    list-style: none;
    font-family: sans-serif;
    font-size: small;
}

.config-form fieldset .inline_errors { /* field error list */
    margin: .3em .3em .3em;
    margin-<?php echo $left; ?>: 0;
    padding: 0;
    list-style: none;
    color: #9A0000;
    font-size: small;
}

.config-form fieldset table
{
    background-color: #fff;
}

.config-form fieldset label
{
    font-weight: normal;
}

.config-form fieldset textarea,
.insertRowTable textarea
{
    margin: 5px;
    padding: 5px;
}

.config-form fieldset th {
    padding: 10px;
    padding-<?php echo $left; ?>: .5em;
    text-align: <?php echo $left; ?>;
    vertical-align: top;
    width: 40%;
}

.config-form fieldset .doc,
.config-form fieldset .disabled-notice {
    margin-<?php echo $left; ?>: 1em;
}

.config-form fieldset .disabled-notice {
    font-size: 80%;
    text-transform: uppercase;
    color: #E00;
    cursor: help;
}

.config-form fieldset td {
    padding-top: .3em;
    padding-bottom: .3em;
    vertical-align: top;
}

.config-form fieldset th small {
    display: block;
    font-weight: normal;
    font-family: sans-serif;
    font-size: x-small;
    color: #444;
}

.config-form fieldset th,
.config-form fieldset td {
    border-bottom: 1px <?php echo $GLOBALS['cfg']['NaviColor']; ?> solid;
    border-<?php echo $right; ?>: none;
}

fieldset .group-header th {
    background: <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
}

fieldset .group-header + tr th {
    padding-top: .6em;
}

fieldset .group-field-1 th,
fieldset .group-header-2 th {
    padding-<?php echo $left; ?>: 1.5em;
}

fieldset .group-field-2 th,
fieldset .group-header-3 th {
    padding-<?php echo $left; ?>: 3em;
}

fieldset .group-field-3 th {
    padding-<?php echo $left; ?>: 4.5em;
}

fieldset .disabled-field th,
fieldset .disabled-field th small,
fieldset .disabled-field td {
    color: #666;
    background-color: #ddd;
}

form.create_table_form fieldset.tblFooters,
form#multi_edit_central_columns fieldset.tblFooters {
    background: none;
    border: none;
}

form#tableOptionsForm input[name="comment"], form#tableOptionsForm select[name="tbl_collation"] {
    width: 130px;
}

form#formDatabaseComment .tblFooters,
form#create_table_form_minimal .tblFooters,
form#rename_db_form .tblFooters,
form#copy_db_form .tblFooters,
form#change_db_charset_form .tblFooters,
form#alterTableOrderby .tblFooters,
form#moveTableForm .tblFooters,
form#copyTable .tblFooters,
form#tableOptionsForm .tblFooters
{
    margin-top: -40px;
}

#create_table_form_minimal
{
    display: block;
}

#fieldset_zoom_search table th,
#fieldset_zoom_search table td
{
    line-height: 35px;
}

#fieldset_table_qbe table th,
#fieldset_table_qbe table td
{
    line-height: 35px;
}

#fieldset_delete_user_footer
{
    margin-top: -59px;
}

#db_or_table_specific_priv .tblFooters
{
    margin-top: -68px;
}

#edit_user_dialog,
#add_user_dialog
{
    margin: 20px !important;
}

.config-form .lastrow {
    padding: .5em;
    text-align: center;
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
    padding: 1px;
    border: 1px #EDEC90 solid;
    background: #FFC;
}

.config-form img.ic_s_reload {
    -webkit-filter: invert(70%);
    filter: invert(70%);
}

.config-form .field-error {
    border-color: #A11 !important;
}

.config-form input[type="text"],
.config-form input[type="password"],
.config-form input[type="number"],
.config-form select,
.config-form textarea {
    border: 1px #A7A6AA solid;
    height: auto;
}

.config-form input[type="text"]:focus,
.config-form input[type="password"]:focus,
.config-form input[type="number"]:focus,
.config-form select:focus,
.config-form textarea:focus {
    border: 1px #6676FF solid;
    background: #F7FBFF;
}

.config-form .field-comment-mark {
    font-family: serif;
    color: #007;
    cursor: help;
    padding: 0 .2em;
    font-weight: bold;
    font-style: italic;
}

.config-form .field-comment-warning {
    color: #A00;
}

/* error list */
.config-form dd {
    margin-<?php echo $left; ?>: .5em;
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
    margin-bottom: .5em;
    margin-left: .5em;
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
    font-size: .8em;
    text-align: center;
    line-height: 1.4em;
    height: 1.55em;
    overflow: hidden;
    border-right: .1em solid #888;
    border-left: .1em solid #888;
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
.toggleButton .container td {
    background-image: none;
    background: none;
}
.toggleButton .toggleOn {
    color: #fff;
    padding: 0 1em;
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

form.append_fields_form .tblFooters
{
    background: none;
    border: none;
}

#table_columns input[type="text"],
#table_columns input[type="password"],
#table_columns input[type="number"],
#table_columns input[type="date"],
#table_columns select {
    width: 10em;
    box-sizing: border-box;
    -ms-box-sizing: border-box;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
}

#table_columns select {
    margin: 6px;
}

#placeholder {
    position: relative;
    border: 1px solid #aaa;
    float: <?php echo $right; ?>;
    overflow: hidden;
}

.placeholderDrag {
    cursor: move;
}

#placeholder .button {
    position: absolute;
}

#left_arrow {
    left: 8px;
    top: 26px;
}

#right_arrow {
    left: 26px;
    top: 26px;
}

#up_arrow {
    left: 17px;
    top: 8px;
}

#down_arrow {
    left: 17px;
    top: 44px;
}

#zoom_in {
    left: 17px;
    top: 67px;
}

#zoom_world {
    left: 17px;
    top: 85px;
}

#zoom_out {
    left: 17px;
    top: 103px;
}

.colborder {
    cursor: col-resize;
    height: 100%;
    margin-<?php echo $left; ?>: -6px;
    position: absolute;
    width: 5px;
}

.colborder_active {
    border-<?php echo $right; ?>: 2px solid #a44;
}

.pma_table td {
    position: static;
}

.pma_table th.draggable span,
.pma_table tbody td span {
    display: block;
    overflow: hidden;
}

.pma_table tbody td span code span {
    display: inline;
}

.modal-copy input {
    display: block;
    width: 100%;
    margin-top: 1.5em;
    padding: .3em 0;
}

.cRsz {
    position: absolute;
}

.cCpy {
    background: #333;
    color: #FFF;
    font-weight: bold;
    margin: .1em;
    padding: .3em;
    position: absolute;
}

.cPointer {
    background: url(<?php echo $theme->getImgPath('col_pointer.png');?>);
    height: 20px;
    margin-<?php echo $left; ?>: -5px;  /* must be minus half of its width */
    margin-top: -10px;
    position: absolute;
    width: 10px;
}

.tooltip {
    background: #333 !important;
    opacity: .8 !important;
    border: 1px solid #000 !important;
    font-size: 10px !important;
    font-weight: normal !important;
    padding: 5px !important;
    width: 260px;
    line-height: 1.5;
}

.tooltip * {
    background: none !important;
    color: #FFF !important;
}

.cDrop {
    left: 0;
    position: absolute;
    top: 0;
}

.coldrop {
    background: url(<?php echo $theme->getImgPath('col_drop.png');?>);
    cursor: pointer;
    height: 16px;
    margin-<?php echo $left; ?>: .3em;
    margin-top: .3em;
    position: absolute;
    width: 16px;
}

.coldrop:hover,
.coldrop-hover {
    background-color: #999;
}

.cList {
    background: #fff;
    border: solid 1px #ccc;
    position: absolute;
}

.cList .lDiv div {
    padding: .2em .5em .2em;
    padding-<?php echo $left; ?>: .2em;
}

.cList .lDiv div:hover {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    cursor: pointer;
    color: #fff;
}

.cList .lDiv div input {
    cursor: pointer;
}

.showAllColBtn {
    border-bottom: solid 1px #ccc;
    border-top: solid 1px #ccc;
    cursor: pointer;
    font-size: .9em;
    font-family: open sans bold;
    padding: .35em 1em;
    text-align: center;
    font-weight: normal;
}

.showAllColBtn:hover {
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    cursor: pointer;
    color: #fff;
}

#page_content {
    line-height: 1.5;
}

.navigation
{
    width: 100%;
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    color: <?php echo $GLOBALS['cfg']['NaviColor']; ?>;
}

.navigation td
{
    margin: 0;
    padding: 0;
    vertical-align: middle;
    white-space: nowrap;
}

.navigation_separator
{
    color: #eee;
    display: inline-block;
    font-size: 1.5em;
    text-align: center;
    height: 1.4em;
}

.navigation input[type=submit]
{
    background: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    border: none;
    filter: none;
    margin: 5px;
    padding: 0.4em;
}

.navigation input[type=submit]:hover, .navigation input.edit_mode_active
{
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    cursor: pointer;
    background-color: <?php echo $GLOBALS['cfg']['ThColor']; ?>;
}

.navigation select
{
    margin: 0 0.8em;
    border: none;
}

.navigation input[type=text]
{
    border: none;
}

.navigation_goto
{
    width: 100%;
}

.insertRowTable td, .insertRowTable th
{
    vertical-align: middle;
}

.cEdit {
    margin: 0;
    padding: 0;
    position: absolute;
}

.cEdit input[type=text],
.cEdit input[type=password],
.cEdit input[type=number] {
    background: #FFF;
    height: 100%;
    margin: 0;
    padding: 0;
}

.cEdit .edit_area {
    background: #FFF;
    border: 1px solid #999;
    min-width: 10em;
    padding: .3em .5em;
}

.cEdit .edit_area select,
.cEdit .edit_area textarea {
    width: 97%;
}

.cEdit .cell_edit_hint {
    color: #555;
    font-size: .8em;
    margin: .3em .2em;
}

.cEdit .edit_box {
    overflow-x: hidden;
    overflow-y: scroll;
    padding: 0;
    margin-top: 10px;
}

.cEdit .edit_box_posting {
    background: #FFF url(<?php echo $theme->getImgPath('ajax_clock_small.gif');?>) no-repeat right center;
    padding-<?php echo $right; ?>: 1.5em;
}

.cEdit .edit_area_loading {
    background: #FFF url(<?php echo $theme->getImgPath('ajax_clock_small.gif');?>) no-repeat center;
    height: 10em;
}

.cEdit .goto_link {
    background: #EEE;
    color: #555;
    padding: .2em .3em;
}

.saving_edited_data {
    background: url(<?php echo $theme->getImgPath('ajax_clock_small.gif');?>) no-repeat left;
    padding-<?php echo $left; ?>: 20px;
}

.relationalTable select {
    width: 125px;
    margin-right: 5px;
}

/* css for timepicker */
.ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }
.ui-timepicker-div dl { text-align: <?php echo $left; ?>; }
.ui-timepicker-div dl dt { height: 25px; margin-bottom: -25px; }
.ui-timepicker-div dl dd { margin: 0 10px 10px 85px; }
.ui-timepicker-div td { font-size: 90%; }
.ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }
.ui-timepicker-rtl { direction: rtl; }
.ui-timepicker-rtl dl { text-align: right; }
.ui-timepicker-rtl dl dd { margin: 0 65px 10px 10px; }

input.btn {
    color: #333;
    background-color: #D0DCE0;
}

body .ui-widget {
    font-size: 1em;
}

.ui-dialog fieldset legend a {
    color: #235A81;
}

.report-data {
    height:13em;
    overflow:scroll;
    width:570px;
    border: solid 1px;
    background: white;
    padding: 2px;
}

.report-description {
    height:10em;
    width:570px;
}

div#page_content div#tableslistcontainer table.data {
    border-top: 0.1px solid #EEEEEE;
}

div#page_content div#tableslistcontainer, div#page_content div.notice, div#page_content div.result_query {
    margin-top: 1em;
}

table.show_create {
    margin-top: 1em;
}

table.show_create td {
    border-right: 1px solid #bbb;
}

#alias_modal table th {
    vertical-align: middle;
    padding-left: 1em;
}

#alias_modal label.col-2 {
    min-width: 20%;
    display: inline-block;
}

#alias_modal select {
    width: 25%;
    margin-right: 2em;
}

#alias_modal label {
    font-weight: bold;
}

.small_font {
    font-size: smaller;
}

/* Console styles */
#pma_console_container {
    width: 100%;
    position: fixed;
    bottom: 0;
    <?php echo $left; ?>: 0;
    z-index: 100;
}
#pma_console {
    position: relative;
    margin-<?php echo $left; ?>: 250px;
}
#pma_console .templates {
    display: none;
}
#pma_console .mid_text,
#pma_console .toolbar span {
    vertical-align: middle;
}
#pma_console .toolbar {
    position: relative;
    background: <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    border-top: solid 1px #ccc;
    cursor: n-resize;
}
#pma_console .toolbar.collapsed:not(:hover) {
    display: inline-block;
    border-<?php echo $right; ?>: solid 1px #ccc;
}
#pma_console .toolbar.collapsed {
    cursor: default;
}
#pma_console .toolbar.collapsed>.button {
    display: none;
}
#pma_console .message span.text,
#pma_console .message span.action,
#pma_console .toolbar .button,
#pma_console .toolbar .text,
#pma_console .switch_button {
    padding: 0 3px;
    display: inline-block;
}
#pma_console .message span.action,
#pma_console .toolbar .button,
#pma_console .switch_button {
    cursor: pointer;
}
#pma_console .toolbar .text {
    font-weight: bold;
}
#pma_console .toolbar .button,
#pma_console .toolbar .text {
    margin-<?php echo $right; ?>: .4em;
}
#pma_console .toolbar .button,
#pma_console .toolbar .text {
    float: <?php echo $right; ?>;
}
#pma_console .content {
    overflow-x: hidden;
    overflow-y: auto;
    margin-bottom: -65px;
    border-top: solid 1px #ccc;
    background: #fff;
    padding-top: .4em;
}
#pma_console .content.console_dark_theme {
    background: #000;
    color: #fff;
}
#pma_console .content.console_dark_theme .CodeMirror-wrap {
    background: #000;
    color: #fff;
}
#pma_console .content.console_dark_theme .action_content {
    color: #000;
}
#pma_console .content.console_dark_theme .message {
    border-color: #373B41;
}
#pma_console .content.console_dark_theme .CodeMirror-cursor {
    border-color: #fff;
}
#pma_console .content.console_dark_theme .cm-keyword {
    color: #de935f;
}
#pma_console .message,
#pma_console .query_input {
    position: relative;
    font-family: Monaco, Consolas, monospace;
    cursor: text;
    margin: 0 10px .2em 1.4em;
}
#pma_console .message {
    border-bottom: solid 1px #ccc;
    padding-bottom: .2em;
}
#pma_console .message.expanded>.action_content {
    position: relative;
}
#pma_console .message:before,
#pma_console .query_input:before {
    left: -0.7em;
    position: absolute;
    content: ">";
}
#pma_console .query_input:before {
    top: -2px;
}
#pma_console .query_input textarea {
    width: 100%;
    height: 4em;
    resize: vertical;
}
#pma_console .message:hover:before {
    color: #7cf;
    font-weight: bold;
}
#pma_console .message.expanded:before {
    content: "]";
}
#pma_console .message.welcome:before {
    display: none;
}
#pma_console .message.failed:before,
#pma_console .message.failed.expanded:before,
#pma_console .message.failed:hover:before {
    content: "=";
    color: #944;
}
#pma_console .message.pending:before {
    opacity: .3;
}
#pma_console .message.collapsed>.query {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}
#pma_console .message.expanded>.query {
    display: block;
    white-space: pre;
    word-wrap: break-word;
}
#pma_console .message .text.targetdb,
#pma_console .message.collapsed .action.collapse,
#pma_console .message.expanded .action.expand,
#pma_console .message .action.requery,
#pma_console .message .action.profiling,
#pma_console .message .action.explain,
#pma_console .message .action.bookmark {
    display: none;
}
#pma_console .message.select .action.profiling,
#pma_console .message.select .action.explain,
#pma_console .message.history .text.targetdb,
#pma_console .message.successed .text.targetdb,
#pma_console .message.history .action.requery,
#pma_console .message.history .action.bookmark,
#pma_console .message.bookmark .action.requery,
#pma_console .message.bookmark .action.bookmark,
#pma_console .message.successed .action.requery,
#pma_console .message.successed .action.bookmark {
    display: inline-block;
}
#pma_console .message .action_content {
    position: absolute;
    bottom: 100%;
    background: #ccc;
    border: solid 1px #aaa;
}
html.ie8 #pma_console .message .action_content {
    position: relative!important;
}
#pma_console .message.bookmark .text.targetdb,
#pma_console .message .text.query_time {
    margin: 0;
    display: inline-block;
}
#pma_console .message.failed .text.query_time,
#pma_console .message .text.failed {
    display: none;
}
#pma_console .message.failed .text.failed {
    display: inline-block;
}
#pma_console .message .text {
    background: #fff;
}
#pma_console .message.collapsed>.action_content {
    display: none;
}
#pma_console .message.collapsed:hover>.action_content {
    display: block;
}
#pma_console .message .bookmark_label {
    padding: 0 4px;
    top: 0;
    background: #369;
    color: #fff;
}
#pma_console .message .bookmark_label.shared {
    background: #396;
}
#pma_console .query_input {
    position: relative;
}
#pma_console .mid_layer {
    height: 100%;
    width: 100%;
    position: absolute;
    top: 0;
    /* For support IE8, this layer doesn't use filter:opacity or opacity,
    js code will fade this layer opacity to 0.18(using animation) */
    background: #ccc;
    display: none;
    cursor: pointer;
    z-index: 200;
}
#pma_console .card {
    position: absolute;
    width: 94%;
    height: 100%;
    min-height: 48px;
    <?php echo $left; ?>: 100%;
    top: 0;
    border-<?php echo $left; ?>: solid 1px #999;
    z-index: 300;
    transition: <?php echo $left; ?> 0.2s;
    -ms-transition: <?php echo $left; ?> 0.2s;
    -webkit-transition: <?php echo $left; ?> 0.2s;
    -moz-transition: <?php echo $left; ?> 0.2s;
}
#pma_console .card.show {
    <?php echo $left; ?>: 6%;
}

html.ie7 #pma_console .query_input {
    display: none;
}

#pma_bookmarks .content.add_bookmark,
#pma_console_options .content {
    padding: 4px 6px;
}
#pma_bookmarks .content.add_bookmark .options {
    margin-<?php echo $left; ?>: 1.4em;
    padding-bottom: .4em;
    margin-bottom: .4em;
    border-bottom: solid 1px #ccc;
}
#pma_bookmarks .content.add_bookmark .options button {
    margin: 0 7px;
    vertical-align: bottom;
}
#pma_bookmarks .content.add_bookmark input[type=text] {
    margin: 0;
    padding: 2px 4px;
}
#pma_console .button.hide,
#pma_console .message span.text.hide {
    display: none;
}
#debug_console.grouped .ungroup_queries,
#debug_console.ungrouped .group_queries {
    display: inline-block;
}
#debug_console.ungrouped .ungroup_queries,
#debug_console.ungrouped .sort_count,
#debug_console.grouped .group_queries {
    display: none;
}
#debug_console .count {
    margin-right: 8px;
}
#debug_console .show_trace .trace,
#debug_console .show_args .args {
    display: block;
}
#debug_console .hide_trace .trace,
#debug_console .hide_args .args,
#debug_console .show_trace .action.dbg_show_trace,
#debug_console .hide_trace .action.dbg_hide_trace,
#debug_console .traceStep.hide_args .action.dbg_hide_args,
#debug_console .traceStep.show_args .action.dbg_show_args {
    display: none;
}

#debug_console .traceStep:after,
#debug_console .trace.welcome:after,
#debug_console .debug>.welcome:after {
    content: "";
    display: table;
    clear: both;
}
#debug_console .debug_summary {
    float: left;
}
#debug_console .trace.welcome .time {
    float: right;
}
#debug_console .traceStep .file,
#debug_console .script_name {
    float: right;
}
#debug_console .traceStep .args pre {
    margin: 0;
}

/* Code mirror console style*/

.cm-s-pma .CodeMirror-code pre,
.cm-s-pma .CodeMirror-code {
    font-family: Monaco, Consolas, monospace;
}
.cm-s-pma .CodeMirror-measure>pre,
.cm-s-pma .CodeMirror-code>pre,
.cm-s-pma .CodeMirror-lines {
    padding: 0;
}
.cm-s-pma.CodeMirror {
    resize: none;
    height: auto;
    width: 100%;
    min-height: initial;
    max-height: initial;
}
.cm-s-pma .CodeMirror-scroll {
    cursor: text;
}

/* PMA drop-improt style */

.pma_drop_handler {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: rgba(0, 0, 0, 0.6);
    height: 100%;
    z-index: 999;
    color: white;
    font-size: 30pt;
    text-align: center;
    padding-top: 20%;
}

.pma_sql_import_status {
    display: none;
    position: fixed;
    bottom: 0;
    right: 25px;
    width: 400px;
    border: 1px solid #999;
    background: #f3f3f3;
}

.pma_sql_import_status h2,
.pma_drop_result h2 {
    background-color: #bbb;
    padding: .1em .3em;
    margin-top: 0;
    margin-bottom: 0;
    color: #fff;
    font-size: 1.6em;
    font-weight: normal;
}

.pma_sql_import_status div {
    height: 270px;
    overflow-y:auto;
    overflow-x:hidden;
    list-style-type: none;
}

.pma_sql_import_status div li {
    padding: 8px 10px;
    border-bottom: 1px solid #bbb;
    color: rgb(148, 14, 14);
    background: white;
}

.pma_sql_import_status div li .filesize {
    float: right;
}

.pma_sql_import_status h2 .minimize {
    float: right;
    margin-right: 5px;
    padding: 0 10px;
}

.pma_sql_import_status h2 .close {
    float: right;
    margin-right: 5px;
    padding: 0 10px;
    display: none;
}

.pma_sql_import_status h2 .minimize:hover,
.pma_sql_import_status h2 .close:hover,
.pma_drop_result h2 .close:hover {
    background: rgba(155, 149, 149, 0.78);
    cursor: pointer;
}

.pma_drop_file_status {
    color: #235a81;
}

.pma_drop_file_status span.underline:hover {
    cursor: pointer;
    text-decoration: underline;
}

.pma_drop_result {
    position: fixed;
    top: 10%;
    left: 20%;
    width: 60%;
    background: white;
    min-height: 300px;
    z-index: 800;
    cursor: move;
}

.pma_drop_result h2 .close {
    float: right;
    margin-right: 5px;
    padding: 0 10px;
}

.dependencies_box {
    background-color: white;
    border: 3px ridge black;
}

#composite_index_list {
    list-style-type: none;
    list-style-position: inside;
}

span.drag_icon {
    display: inline-block;
    background-image: url('<?php echo $theme->getImgPath('s_sortable.png');?>');
    background-position: center center;
    background-repeat: no-repeat;
    width: 1em;
    height: 3em;
    cursor: move;
}

.topmargin {
    margin-top: 1em;
}

meter[value="1"]::-webkit-meter-optimum-value {
    background: linear-gradient(white 3%, #E32929 5%, transparent 10%, #E32929);
}
meter[value="2"]::-webkit-meter-optimum-value {
    background: linear-gradient(white 3%, #FF6600 5%, transparent 10%, #FF6600);
}
meter[value="3"]::-webkit-meter-optimum-value {
    background: linear-gradient(white 3%, #FFD700 5%, transparent 10%, #FFD700);
}

/* styles for sortable tables created with tablesorter jquery plugin */
th.header {
    cursor: pointer;
    color: #235a81;
}

th.header:hover {
    text-decoration: underline;
}

th.header .sorticon {
    width: 16px;
    height: 16px;
    background-repeat: no-repeat;
    background-position: right center;
    display: inline-table;
    vertical-align: middle;
    float: right;
}

th.headerSortUp .sorticon, th.headerSortDown:hover .sorticon {
    background-image: url(<?php echo $theme->getImgPath('s_desc.png');?>);
}

th.headerSortDown .sorticon, th.headerSortUp:hover .sorticon {
    background-image: url(<?php echo $theme->getImgPath('s_asc.png');?>);
}
/* end of styles of sortable tables */

/* styles for jQuery-ui to support rtl languages */
body .ui-dialog .ui-dialog-titlebar-close {
    <?php echo $right; ?>: .3em;
    <?php echo $left; ?>: initial;
}

body .ui-dialog .ui-dialog-title {
    float: <?php echo $left; ?>;
}

body .ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset {
    float: <?php echo $right; ?>;
}
/* end of styles for jQuery-ui to support rtl languages */

/* Override some jQuery-ui styling to have square corners */
body .ui-corner-all,
body .ui-corner-top,
body .ui-corner-left,
body .ui-corner-tl {
    border-top-left-radius: 0;
}
body .ui-corner-all,
body .ui-corner-top,
body .ui-corner-right,
body .ui-corner-tr {
    border-top-right-radius: 0;
}
body .ui-corner-all,
body .ui-corner-bottom,
body .ui-corner-left,
body .ui-corner-bl {
    border-bottom-left-radius: 0;
}
body .ui-corner-all,
body .ui-corner-bottom,
body .ui-corner-right,
body .ui-corner-br {
    border-bottom-right-radius: 0;
}
/* Override  jQuery-ui styling for ui-dialog */
body .ui-dialog {
    padding: 0;
}
body .ui-dialog .ui-widget-header {
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    border: none;
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    background-image: none;
}
body .ui-dialog .ui-dialog-title {
    padding: 5px;
    font-weight: normal;
}
body .ui-dialog .ui-dialog-buttonpane button {
    font-family: <?php echo $GLOBALS['cfg']['FontFamily']; ?>;
    color: <?php echo $GLOBALS['cfg']['ButtonColor']; ?>;
    background-color: <?php echo $GLOBALS['cfg']['NaviBackground']; ?>;
    background-image: none;
    border: 1px solid <?php echo $GLOBALS['cfg']['ButtonBackground']; ?>;
}
body .ui-dialog .ui-dialog-buttonpane button.ui-state-hover {
    background-color: <?php echo $GLOBALS['cfg']['ButtonHover']; ?>;
    border: 1px solid <?php echo $GLOBALS['cfg']['ButtonHover']; ?>;
}
body .ui-dialog .ui-dialog-buttonpane button.ui-state-active {
    background-color: #333;
    border: 1px solid #333;
}
