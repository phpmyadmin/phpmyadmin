<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage garvBlue
 */
?>

#leftFrameset, #mainFrameset,
framset, frame {
    margin: 0px;
    padding: 0px;
}

body, pre, tt, th, td, form, input, select, textarea, h3 {
    font-family: Tahoma, Arial, sans-serif;
    font-size: 10pt;
    color: #2D3867;
}

form {
    padding: 0px;
    margin: 0px;
}

th {
    font-weight: bold;
    color: #000000;
    background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>
}

textarea, input.textfield, select {
    color: #000000;
    background-color: #FFFFFF;
    padding-left: 2px;
}

h1 {
    font-size: 12pt;
    font-weight: bold;
}

h2 {
    font-size: 11pt;
    font-weight: bold;
}

h3 {
    font-weight: bold;

}

/* BEGIN GARVIN */
#serverinfo {
    margin-left: auto;
    margin-right: 15px;
    margin-bottom: 15px;
    padding: 0px;
    border-bottom: 1px solid #2D3867;
}

.serverinfo {
    font-family: <?php echo $right_font_family; ?>;
    font-size: 9pt;
    font-weight: normal;
    white-space: nowrap;
    vertical-align: middle;
}

.serverinfo a:link, .serverinfo a:active, .serverinfo a:visited {
    font-family: <?php echo $right_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    font-weight: bolder;
    color: #2D3867;
}

.serverinfo a:hover {
    color: #F4A227;
    text-decoration: underline;
}

.serverinfo a img{
    vertical-align: middle;
    margin: 0px 1px 0px 2px;
}

.serverinfo div{
    background-image:    url(themes/garvblue/img/item_ltr.png);
    background-repeat:   no-repeat;
    background-position: 50% 50%;
    width: 20px;
    height: 16px;
}

a:link          {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #2D3867}
a:visited       {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; text-decoration: none; color: #2D3867}

/*
a.nav:link      {font-family: <?php echo $right_font_family; ?>; color: #000000}
a.nav:visited   {font-family: <?php echo $right_font_family; ?>; color: #000000}
a.nav:hover     {font-family: <?php echo $right_font_family; ?>; color: #FF0000}
a.drop:link     {font-family: <?php echo $right_font_family; ?>; color: #ff0000}
a.drop:visited  {font-family: <?php echo $right_font_family; ?>; color: #ff0000}
a.drop:hover    {font-family: <?php echo $right_font_family; ?>; color: #ffffff; background-color:#ff0000; text-decoration: none}
*/

table.tabs {
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #666;
}

hr {
    color: #666666;
    background-color: #666666;
    border: 0;
    height: 1px;
}

fieldset {
    border:     #686868 solid 1px;
    padding:    0.5em;
}

td.tab {
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
}

/* new styles for navigation */

#topmenu {
    border-bottom: 2px solid #2D3867;
}

.nav {
    font-family: <?php echo $right_font_family; ?>;
    color: #000000;
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #666;
}

.navSpacer {
    width:            5px;
    height:           16px;
}

.navNormal, .navDrop, .navActive {
    font-family:      <?php echo $right_font_family; ?>;
    font-size:        <?php echo $font_size; ?>;
    font-weight:      bold;
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: #2D3867;
    border-radius: 2px;
    -moz-border-radius: 2px;
    padding: 2px 5px 2px 5px;
}

.navNormal, .navDrop {
    color:            #000000;
    background-color: #E8EAF1;
}

.navActive{
    font-family:      <?php echo $right_font_family; ?>;
    font-size:        <?php echo $font_size; ?>;
    font-weight:      bold;
    color:            #F4A227;
    background-color: #2D3867;
}

.navActive a:link, .navActive a:active, .navActive a:visited {
    color: #F4A227;
}

.navNormal a:link, .navNormal a:active, .navNormal a:visited {
    color: #2D3867;
}

.navDrop a:link, .navDrop a:active, .navDrop a:visited{
    color: #FF0000;
}

.navDrop a:hover{
    color: #FFFFFF;
    background-color: #FF0000;
}

.navNormal a:hover, .navActive a:hover{
    color: #F4A227;
    text-decoration: underline;
}
/* END GARVIN */

dfn {
    font-style: normal
}

dfn:hover {
    font-style: normal;
    cursor: help
}

.warning {
    font-weight: bold;
    color: #FF0000
}

.tblcomment {
    font-size: 8pt;
    font-weight: normal;
    color: #000099;
}

td.topline {
    font-size: 1px;
}

div.tabs {
    clear: both;
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

.print {
    font-family:arial;
    font-size:8pt;
}

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

/* Warning showing div with right border and optional icon */

div.errorhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/original/img/s_error.png);
    background-repeat: no-repeat;
    background-position: 5px 50%;
    padding: 0px 0px 0px 25px;
    <?php } ?>
    margin: 0px;
}

/* tables */
.tblError {
    border: 1px solid #FF0000;
    background-color: #FFFFCC;
}

.tblWarn, div.tblWarn {
    border: 1px solid #FF0000;
    background-color: #FFFFFF;
}

div.tblWarn {
    padding: 5px 5px 5px 5px;
    margin:  0px 0px 5px 0px;
    width:   100%;
}

.tblHeaders {
    background-color: <?php echo $cfg['LeftBgColor']; ?>;
    font-weight: bold;
    color: #000000;
}

.tblFooters {
    background-color: <?php echo $cfg['LeftBgColor']; ?>;
    font-weight: normal;
    color: #000000;
}

.tblHeadError {
    background-color: #FF0000;
    font-weight: bold;
    color: #FFFFFF;
}

.tblHeadWarn {
    background-color: #FFCC00;
    font-weight: bold;
    color: #000000;
}

/* forbidden, no privilegs */
.noPrivileges{
    color: #FF0000;
    font-weight: bold;
}

/* Heading */

img, input, select, button {
    vertical-align: middle;
}

/* disabled text */
.disabled, .disabled a:link, disabled a:active, .disabled a:visited {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #666666;
}
.disabled a:hover {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #666666;
    text-decoration:  none;
}
td.disabled {
    background-color: #cccccc;
}

#textSQLDUMP {
    width: 95%;
    height: 95%;
    font-family: "Courier New", Courier, mono;
    font-size:   12px;
}

#TooltipContainer {
    position:   absolute;
    z-index:    99;
    width:      250px;
    height:     50px;
    overflow:   auto;
    visibility: hidden;
    background-color: #ffffcc;
    color:            #006600;
    border:           1px solid #000000;
    padding:          5px;
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}

a:hover {
    color: #F4A227;
    text-decoration: underline;
}
