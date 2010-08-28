<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme Darkblue Orange
 *
 * @package phpMyAdmin-theme
 * @subpackage Darkblue_orange
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/******************************************************************************/
/* general tags */
html {
    font-size: <?php echo (null !== $GLOBALS['PMA_Config']->get('fontsize') ? $GLOBALS['PMA_Config']->get('fontsize') : $_COOKIE['pma_fontsize']); ?>;
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
    background:         <?php echo (isset($_SESSION['tmp_user_values']['custom_color']) ? $_SESSION['tmp_user_values']['custom_color'] : $GLOBALS['cfg']['MainBackground']); ?>;
}

<?php if (! empty($GLOBALS['cfg']['FontFamilyFixed'])) { ?>
textarea, tt, pre, code {
    font-family:        <?php echo $GLOBALS['cfg']['FontFamilyFixed']; ?>;
}
<?php } ?>
h1 {
    font-size:          180%;
    font-weight:        bold;
}

h2 {
    font-size:          130%;
    font-weight:        bold;
}

h3 {
    font-size:          120%;
    font-weight:        bold;
}

pre, tt, code {
    font-size:          110%;
}

a, a:link,
a:visited,
a:active {
    text-decoration:    none;
    color:              #333399;
    cursor: pointer;
}

a:hover {
    text-decoration:    underline;
    color:              #cc0000;
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

th a:link,
th a:active,
th a:visited {
    color:              <?php echo $GLOBALS['cfg']['ThColor']; ?>;
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
    color:              #666699;
    background-color:   #6666cc;
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
    margin-top:         1em;
    border:             #666699 solid 1px;
    padding:            0.5em;
}

fieldset fieldset {
    margin:             0.8em;
}

fieldset legend {
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
    font-weight:        bold;
    color:              #444444;
    padding:            2px 2px 2px 2px;
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
    border: 1px solid #000000;
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
table tr.odd {
    background-image:   none;
    background:         <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    text-align:         <?php echo $left; ?>;
}

/* even table rows 2,4,6,8,... */
table tr.even th,
table tr.even {
    background-image:   none;
    background:         <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
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
    background:         #ff9900;
    color:              #000000;
    overflow:           hidden;
    clip:               inherit;
    z-index:            2;
    display:            inline;
    visibility:         inherit;
    cursor:             move;
    position:           absolute;
    font-size:          110%;
    border:             1px dashed #000000;
}

/* MySQL Parser */
.syntax {
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

div.success,
div.notice,
div.warning,
div.error,
div.footnotes {
    margin:             0.3em 0 0 0;
    border:             2px solid;
    width:              90%;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-repeat:  no-repeat;
        <?php if ($GLOBALS['text_dir'] === 'ltr') { ?>
    background-position: 10px 50%;
    padding:            0.1em 0.1em 0.1em 36px;
        <?php } else { ?>
    background-position: 99% 50%;
    padding:            10px 5% 10px 10px;
        <?php } ?>
    <?php } else { ?>
    padding:            0.3em;
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
    border-color:       #FFD700;
}

.warning {
    color:              #CC0000;
    background-color:   #FFFFCC;
}
p.warning,
h1.warning,
div.warning {
    border-color:       #CC0000;
    <?php if ($GLOBALS['cfg']['ErrorIconic']) { ?>
    background-image:   url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>s_warn.png);
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
.warning h1 {
    border-color:       #cc0000;
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
    color:              #ffffcc;
    text-decoration:    underline;
}

.tblHeaders a:hover,
div.tools a:hover,
.tblFooters a:hover {
    text-decoration:    none;
    color:              #ffffff;
}

/* forbidden, no privilegs */
.noPrivileges {
    color:              #cc0000;
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
ul#topmenu, ul#topmenu2, ul.tabs {
    font-weight:        bold;
    list-style-type:    none;
    margin:             0;
    padding:            0;
}

ul#topmenu2 {
    margin:             0.25em 0.5em 0;
    height:             2em;
    clear:              both;
}

ul#topmenu li, ul#topmenu2 li {
    float:              <?php echo $left; ?>;
    margin:             0;
    padding:            0;
    vertical-align:     middle;
}

#topmenu img, #topmenu2 img {
    vertical-align:     middle;
    margin-<?php echo $right; ?>:       0.1em;
}

/* default tab styles */
ul#topmenu a, ul#topmenu span {
    display:            block;
    margin:             0.2em 0.2em 0 0.2em;
    padding:            0.2em 0.2em 0 0.2em;
    white-space:        nowrap;
}

ul#topmenu ul a {
    margin:             0;
    padding-bottom:     0.2em;
}

ul#topmenu .submenu {
    position:           relative;
}

ul#topmenu ul {
    padding:            0;
    margin:             0;
    position:           absolute;
    right:              0;
    list-style-type:    none;
    display:            none;
    border:             1px #666 solid;
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
ul#topmenu span.tab {
    color:              #666666;
}

/* disabled drop/empty tabs */
ul#topmenu span.tabcaution {
    color:              #ff6666;
}

/* enabled drop/empty tabs */
ul#topmenu a.tabcaution {
    color:              #FF0000;
}
ul#topmenu a.tabcaution:hover {
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

ul#topmenu ul {
    background:         <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}
<?php } else { ?>
#topmenu {
    margin-top:         0.5em;
    padding:            0.1em 0.3em 0.1em 0.3em;
}

ul#topmenu ul {
    -moz-box-shadow:    2px 2px 3px #666;
    -webkit-box-shadow: 2px 2px 3px #666;
    box-shadow:         2px 2px 3px #666;
}

ul#topmenu > li {
    border-bottom:      1pt solid black;
}

/* default tab styles */
ul#topmenu a, ul#topmenu span {
    background-color:   <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    border:             0 solid <?php echo $GLOBALS['cfg']['BgTwo']; ?>;
    border-width:       1pt 1pt 0 1pt;
    -moz-border-radius: 0.4em 0.4em 0 0;
    border-radius:      0.4em 0.4em 0 0;
}

ul#topmenu ul a {
    border-width:       1pt 0 0 0;
    -moz-border-radius: 0;
    border-radius:      0;
}

ul#topmenu ul li:first-child a {
    border-width:       0;
}

/* enabled hover/active tabs */
ul#topmenu > li > a:hover,
ul#topmenu > li > .tabactive {
    margin:             0;
    padding:            0.2em 0.4em 0.2em 0.4em;
    text-decoration:    none;
}

ul#topmenu ul a:hover,
ul#topmenu ul .tabactive {
    text-decoration:    none;
}

ul#topmenu a.tab:hover,
ul#topmenu .tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
}

ul#topmenu2 a.tab:hover,
ul#topmenu2 a.tabactive {
    background-color:   <?php echo $GLOBALS['cfg']['BgOne']; ?>;
    -moz-border-radius: 0.3em;
    border-radius:      0.3em;
    text-decoration:    none;
}

/* to be able to cancel the bottom border, use <li class="active"> */
ul#topmenu > li.active {
     border-bottom:      1pt solid <?php echo $GLOBALS['cfg']['MainBackground']; ?>;
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
    margin:             0 0.1em 0 0.1em;
}

/* some styles for IDs: */
#buttonNo {
    color:              #CC0000;
    font-weight:        bold;
    padding:            0 10px 0 10px;
}

#buttonYes {
    color:              #006600;
    font-weight:        bold;
    padding:            0 10px 0 10px;
}

#buttonGo {
    color:              #006600;
    font-weight:        bold;
    padding:            0 10px 0 10px;
}

#listTable {
    width:              260px;
}

#textSqlquery {
    width:              450px;
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
div#serverstatus table#serverstatustraffic_src,
div#serverstatus table#serverstatustraffic_trg,
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
#maincontainer {
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

#div_table_copy, #div_partition_maintenance, #div_referential_integrity, #div_table_removal, #div_table_maintenance {
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
    border:             <?php echo $GLOBALS['cfg']['MainColor']; ?> solid 1px;
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
    border-<?php echo $left; ?>: 0.3em solid <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    margin-bottom:      1em;
}

.group h2 {
    background:   <?php echo $GLOBALS['cfg']['ThBackground']; ?>;
    padding:            0.1em 0.3em;
    margin-top:         0;
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

#NavFilter {
    display: none;
}

#clear_fast_filter {
    background: white;
    color: black;
    cursor: pointer;
    padding: 0;
    margin: 0;
    position: relative;
    right: 3ex;
}

#fast_filter {
    width: 85%;
    padding: 0.1em;
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
    top: 0px;
    position: fixed;
    margin-top: 0;
    margin-right: auto;
    margin-bottom: 0;
    margin-left: auto;
    padding: 3px 5px;
    min-width: 70px;
    max-width: 350px;
    background-color: #FF9900;
    z-index: 1100;
    text-align: center;
    display: block;
    left: 0;
    right: 0;
    background-image: url(<?php echo $_SESSION['PMA_Theme']->getImgPath(); ?>ajax_clock_small.gif);
    background-repeat: no-repeat;
    background-position: 2%;
 }

 #loading_parent {
    position: relative;
    width: 100%;
 }

 /**
  * Export and Import styles
  */
.exportoptions h3, .importoptions h3 {
    border-bottom: 1px #999999 solid;
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
    overflow: auto;
}

#enum_editor_no_js {
   margin: auto auto;
}

#enum_editor, #enum_editor_no_js {
    width: 50%;
    height: 80%;
    background: #D0DCE0;
    padding: 15px;
}

#popup_background {
    display: none;
    position: fixed;
    _position: absolute; /* hack for IE */
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: #000;
    z-index: 100;
}

a.close_enum_editor {
    float: right;
}

#enum_editor #values, #enum_editor_no_js #values {
    margin: 15px 0px;
}

#enum_editor #values input, #enum_editor_no_js #values input {
    margin: 5px 0px;
    float: top;
    width: 100%;
}

#enum_editor input, #enum_editor_no_js input {
    float: bottom;
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
}

td.more_opts {
    display: none;
    white-space: nowrap;
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
    margin:  0;
    padding: 0.5em;
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

fieldset .group-field th {
    padding-left: 1.5em;
}

fieldset .disabled-field th,
fieldset .disabled-field th small,
fieldset .disabled-field td {
    color: #777;
    background-color: #eee;
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
    color: #00A;
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
