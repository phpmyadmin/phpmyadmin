<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

chdir('..');
$is_minimum_common = TRUE;
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/sqlparser.lib.php');

// Gets the default font sizes
// garvin: TODO: Should be optimized to not include the whole common.lib.php bunch
// but only functions used to determine browser heritage.
PMA_setFontSizes();

$ctype = 'css';
require_once('./libraries/header_http.inc.php');

if (!isset($js_frame)) {
    $js_frame = 'left';
}

if ($js_frame == 'left') {
/************************************************************************************
 * LEFT FRAME
 ************************************************************************************/

    /**
     * Add styles for positioned layers
     */
    if (isset($num_dbs) && $num_dbs == '0') {
    ?>
/* No layer effects neccessary */
div     {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.heada  {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
.tblItem:hover {color: #FF0000; text-decoration: underline}
    <?php
    } else {
        if (isset($js_capable) && $js_capable != '0') {
            // Brian Birtles : This is not the ideal method of doing this
            // but under the 7th June '00 Mozilla build (and many before
            // it) Mozilla did not treat text between <style> tags as
            // style information unless it was written with the one call
            // to write().
            if (isset($js_isDOM) && $js_isDOM != '0') {
            ?>
/* Layer effects neccessary: capable && is_DOM is set. We found a recent CSS-Browser */
div {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.headaCnt {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #000000}
.parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none; display: block}
.child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none; display: none}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
.tblItem:hover {color: #FF0000; text-decoration: underline}
            <?php
            } else {
            ?>
/* Layer effeccts neccessary: capable, but no is_DOM. We found an older CSS-Browser */
div {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.headaCnt {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #000000}
            <?php
                if (isset($js_isIE4) && $js_isIE4 != '0') {
                ?>
/* Additional effects for IE4 */
.parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none; display: block}
.child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none; display: none}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
.tblItem:hover {color: #FF0000; text-decoration: underline}
                <?php
                }
                 else {
                ?>
/* Additional effects for NON-IE4 */
.parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none; position: absolute; visibility: hidden}
.child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; position: absolute; visibility: hidden}
.item, .tblItem {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
                <?php
                }
            }
        } else {
        ?>
/* Additional effects for left frame not required or not possible because of lacking CSS-capability. */
div {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.heada {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000}
.headaCnt {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #000000}
.parent {font-family: <?php echo $left_font_family; ?>; color: #000000; text-decoration: none}
.child {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
.item, .item:active, .item:hover, .tblItem, .tblItem:active {font-size: <?php echo $font_smaller; ?>; color: #333399; text-decoration: none}
.tblItem:hover {color: #FF0000; text-decoration: underline}
        <?php
        }
    }
    ?>
/* Always enabled stylesheets (left frame) */
body {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
input   {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>}
select  {font-family: <?php echo $left_font_family; ?>; font-size: <?php echo $font_size; ?>; background-color: #ffffff; color: #000000}
hr{ color: #ffffff; background-color: #ffffff; border: 0; height: 1px; }
    <?php
} elseif ($js_frame == 'print') {
/************************************************************************************
 * PRINT VIEW
 ************************************************************************************/

    ?>
/* For printview */
body  {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #ffffff}
h1    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold}
table {border-width:1px; border-color:#000000; border-style:solid; border-collapse:collapse; border-spacing:0}
th    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #000000; background-color: #ffffff; border-width:1px; border-color:#000000; border-style:solid; padding:2px}
td    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #ffffff; border-width:1px; border-color:#000000; border-style:solid; padding:2px}
    <?php
} else {
/************************************************************************************
 * RIGHT FRAME
 ************************************************************************************/

?>
/* Always enabled stylesheets (right frame) */
body {
    font-family: <?php echo $right_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    color: #000000;
    <?php
    if ($GLOBALS['cfg']['RightBgImage'] == '') {
        // calls from a css file are relative to itself, so use ../images
        echo '    background-image: url(../images/vertical_line.png);' . "\n"
             . '    background-repeat: repeat-y;' . "\n";
    } else {
        echo '    background-image: url(' . $GLOBALS['cfg']['RightBgImage'] . ');' . "\n";
    } // end if... else...
    ?>
    background-color: <?php echo $GLOBALS['cfg']['RightBgColor'] . "\n"; ?>
}

pre, tt         {font-size: <?php echo $font_size; ?>}
th              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #000000; background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>}
td              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
form            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
input           {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
input.textfield {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #FFFFFF}
select          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #FFFFFF}
textarea        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; color: #000000; background-color: #FFFFFF}
h1              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold}
h2              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold}
h3              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold}
a:link          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #0000FF}
a:visited       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #0000FF}
a:hover         {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: underline; color: #FF0000}
a.nav:link      {font-family: <?php echo $right_font_family; ?>; color: #000000}
a.nav:visited   {font-family: <?php echo $right_font_family; ?>; color: #000000}
a.nav:hover     {font-family: <?php echo $right_font_family; ?>; color: #FF0000}
a.h1:link       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold; color: #000000}
a.h1:active     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold; color: #000000}
a.h1:visited    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold; color: #000000}
a.h1:hover      {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_biggest; ?>; font-weight: bold; color: #FF0000}
a.h2:link       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold; color: #000000}
a.h2:active     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold; color: #000000}
a.h2:visited    {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold; color: #000000}
a.h2:hover      {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_bigger; ?>; font-weight: bold; color: #FF0000}
a.drop:link     {font-family: <?php echo $right_font_family; ?>; color: #ff0000}
a.drop:visited  {font-family: <?php echo $right_font_family; ?>; color: #ff0000}
a.drop:hover    {font-family: <?php echo $right_font_family; ?>; color: #ffffff; background-color:#ff0000; text-decoration: none}
dfn             {font-style: normal}
dfn:hover       {font-style: normal; cursor: help}
.nav            {font-family: <?php echo $right_font_family; ?>; color: #000000}
.warning        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #FF0000}
td.topline      {font-size: 1px}
td.tab          {
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
}
table.tabs      {
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #666;
}

fieldset {
    border:     #686868 solid 1px;
    padding:    0.5em;
}
fieldset fieldset {
    margin:     0.8em;
}

button.mult_submit {
    border: none;
    background-color: transparent;
}

.pdflayout {
    overflow:         hidden;
    clip:             inherit;
    background-color: #FFFFFF;
    display:          none;
    border:           1px solid #000000;
    position:         relative;
}

.pdflayout_table {
    background:       <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>;
    color:            #000000;
    overflow:         hidden;
    clip:             inherit;
    z-index:          2;
    display:          inline;
    visibility:       inherit;
    cursor:           move;
    position:         absolute;
    font-size:        <?php echo $font_smaller; ?>;
    border:           1px dashed #000000;
}

/* some new styles added 20047-05-05 by Michael Keck (mkkeck) */

/* tables */
.tblWarning {
  border: 1px solid #cc0000;
  background-color: #ffffff;
		width: 100%;
}
.tblError {
  border:           1px solid #cc0000;
  background-color: #ffffcc;
		width: 100%;
}
.tblHeaders {
  background-color: #9999CC;
  font-weight:      bold;
  color:            #000000;
}
.tblFooters {
  background-color: #9999CC;
  font-weight:      normal;
  color:            #000000;
}
.tblHeadError {
  background-color: #cc0000;
  font-weight:      bold;
  color:            #ffffff;
}
.tblHeadWarn {
  background-color: #ffcc00;
  font-weight:      bold;
  color:            #000000;
}
/* forbidden, no privilegs */
.noPrivileges{
  color:            #cc0000;
  font-weight:      bold;
}

/* list elements */
ul.mainRight {
    margin: 0px;
    padding-left:2em;
    padding-right:2em;
}

ul.mainRight li {
    white-space: nowrap;
    padding-left:   0.5em;
    padding-top: 0.1em;
    padding-bottom: 0.1em;
}
ul.mainLeft {
    margin: 0px;
    padding-left:2em;
    padding-right:2em;
}

ul.mainLeft li {
    white-space: nowrap;
    padding-left:   0.5em;
    padding-top: 0.1em;
    padding-bottom: 0.1em;
}
.lstPhpInfo {
  list-style: url(../images/php_sym.png);
}
.lstPmaDocs {
  list-style: url(../images/b_docs.png);
}
.lstPmaPage {
  list-style: url(../images/b_home.png);
}
.lstPmaLang {
  list-style: url(../images/s_lang.png);
}
.lstPmaChar {
  list-style: url(../images/s_rights.png);
}
.lstNewdb {
  list-style: url(../images/b_newdb.png);
}
.lstStatus {
  list-style: url(../images/s_status.png);
}
.lstVars {
  list-style: url(../images/s_vars.png);
}
.lstProcess {
  list-style: url(../images/s_process.png);
}
.lstPrivileges {
  list-style: url(../images/s_rights.png);
}
.lstCollations {
  list-style: url(../images/s_asci.png);
}.lstReload {
  list-style: url(../images/s_reload.png);
}
.lstDatabases {
  list-style: url(../images/s_db.png);
}
.lstExport {
  list-style: url(../images/b_export.png);
}
.lstLogoff {
  list-style: url(../images/s_loggoff.png);
}
.lstPasswd {
  list-style: url(../images/s_passwd.png);
}

hr{ color: #666699; background-color: #6666cc; border: 0; height: 1px; }

/* end of some new styles added 20047-05-05 by Michael Keck (mkkeck) */

.print{font-family:arial;font-size:8pt;}

/* MySQL Parser */
.syntax {font-family: sans-serif; font-size: <?php echo $font_smaller; ?>;}
.syntax_comment            { padding-left: 4pt; padding-right: 4pt;}
.syntax_digit              {}
.syntax_digit_hex          {}
.syntax_digit_integer      {}
.syntax_digit_float        {}
.syntax_punct              {}
.syntax_alpha              {}
.syntax_alpha_columnType   {text-transform: uppercase;}
.syntax_alpha_columnAttrib {text-transform: uppercase;}
.syntax_alpha_reservedWord {text-transform: uppercase; font-weight: bold;}
.syntax_alpha_functionName {text-transform: uppercase;}
.syntax_alpha_identifier   {}
.syntax_alpha_charset      {}
.syntax_alpha_variable     {}
.syntax_quote              {white-space: pre;}
.syntax_quote_backtick     {}
    <?php
    echo PMA_SQP_buildCssData();
}

?>

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
    background-color: <?php echo $GLOBALS['cfg']['BrowsePointerColor']; ?>;
}

table.calendar th {
    background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>;
}

table.calendar td.selected {
    background-color: <?php echo $GLOBALS['cfg']['BrowseMarkerColor']; ?>;
}

img.calendar {
    border: none;
}

form.clock {
    text-align: center;
}

.nowrap {
    white-space: nowrap;
}

div.nowrap {
    margin: 0px;
    padding: 0px;
}

li {
    padding-bottom: 1em;
}

li form {
    display: inline;
}

ul.main {
    margin: 0px;
    padding-left:2em;
    padding-right:2em;
}

ul.main li {
    list-style-image: url(../images/dot_violet.png);
    padding-bottom: 0.1em;
}

button {
    /* buttons in some browsers (eg. Konqueror) are block elements, this breaks design */
    display: inline;
}

/* Tabs */

/* For both light and non light */
.tab {
    white-space: nowrap;
    font-weight: bolder;
}

/* For non light */
td.tab {
    width: 64px;
    text-align: center;
    background-color: #dfdfdf;
}

td.tab a {
    display: block;
}

/* For light */
div.tab {
}

/* Highlight active tab */
td.activetab {
    background-color: silver;
}


/* Textarea */

textarea {
    overflow: auto;
}
