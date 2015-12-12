<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Silkline
 */

// unplanned execution path
if (!defined('PMA_MINIMUM_COMMON')) {
    exit();
}
?>
/* Always enabled stylesheets (right frame) */
html {
    height:100%;
    border-left:1px solid #AAA;
}
html, body {
margin:0;
padding:0;
}

body {
    font-family:    Tahoma, Arial, Helvetica, Verdana, sans-serif;
    font-size:         11px;
    color: #000000;
         background: <?php echo $GLOBALS['cfg']['RightBgColor']; ?> url(themes/silkline/img/silkline_light.png) top left repeat-x;

}

pre, tt         {font-size: <?php echo $font_size; ?>}
th              {
font-family: <?php echo $right_font_family; ?>;
font-size: <?php echo $font_size; ?>;
font-weight: normal;
color: #000000;
background-color: <?php echo $GLOBALS['cfg']['ThBgcolor']; ?>
}
td              {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>}
form            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; padding: 0px; margin: 10px;}
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
.tblcomment     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_smallest; ?>; font-weight: normal; color: #000099; }
td.topline      {font-size: 1px}
td.tab          {
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
}
h1{
text-transform:capitalize;
letter-spacing:-2px;
font-family:"trebuchet MS";
font-weight:normal;
}
#mysqlmaininformation h1, #pmamaininformation h1{
margin-top:40px;
padding-left:10px;
}
fieldset        {
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

hr{ color: #666666; background-color: #666666; border: 0; height: 1px; }

/* new styles for navigation */

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
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
    padding: 2px 5px 2px 5px;
}
.navNormal {
    color:            #000000;
    background-color: #FEFEFE;
}
.navActive{
    font-family:      <?php echo $right_font_family; ?>;
    font-size:        <?php echo $font_size; ?>;
    font-weight:      bold;
    color:            #000000;
    background-color: #EAE6D0;
}
.navDrop{
    color: #000000;
    background-color: #E5E5E5;
}
.navNormal a:link, .navNormal a:active, .navNormal a:visited, .navActive a:link, .navActive a:active, .navActive a:visited{
    color: #0000FF;
}

.navDrop a:link, .navDrop a:active, .navDrop a:visited{
    color: #FF0000;
}
.navDrop a:hover{
    color: #FFFFFF;
    background-color: #FF0000;
}
.navNormal a:hover, .navActive a:hover{
    color: #FF0000;
}

/* Warning showing div with right border and optional icon */

div.errorhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/silkline/img/s_error.png);
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
    color: #FFFFFF;
}
.tblFooters {
    background-color: <?php echo $cfg['LeftBgColor']; ?>;
    font-weight: normal;
    color: #000000;
}
.tblHeaders a:link, .tblHeaders a:active, .tblHeaders a:visited, .tblFooters a:link, .tblFooters a:active, .tblFooters a:visited {
    color: #0000FF;
}
.tblHeaders a:hover, .tblFooters a:hover { color: #FF0000; }
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

#serverinfo {
    font-family: <?php echo $right_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    font-weight: normal;
    white-space: nowrap;
    vertical-align: middle;
    padding:23px 10px 10px 10px;
    border:1px solid #aaa;
    border:0;
    background-color:#FFF;
    color:#000;
    margin:0;
}

img, input, select, button {
    vertical-align: middle;
}

<?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
html body div#serverinfo a.item, #serverinfo a.item:active, #serverinfo a.item:visited {
    font-family: <?php echo $right_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    font-weight: normal;
    color:#111111;
}
#serverinfo a.item img{
    vertical-align: middle;
    margin: 0px 1px 0px 2px;
}
#serverinfo div{
    background-image:    url(themes/silkline/img/item_ltr.png);
    background-repeat:   no-repeat;
    background-position: 50% 50%;
    width: 20px;
    height: 16px;
}
#textSQLDUMP {
    width: 95%;
    height: 95%;
    font-family: "Courier New", Courier, mono;
    font-size:   12px;
}
<?php } // end of isDom ?>
/* disabled text */
.disabled, .disabled a:link, disabled a:active, .disabled a:visited {
    font-family: <?php echo $right_font_family; ?>;
    font-size:   <?php echo $font_size; ?>;
    color:       #666666;
}
.disabled a:hover {
    text-decoration: none;
}
td.disabled {
    background-color: #cccccc;
}

#TooltipContainer {
    position:   absolute;
    z-index:    99;
    width:      250px;
    height:     50px;
    overflow:   auto;
    visibility: hidden;
    background-color: #ffffcc;
    color:            #000000;
    border:           1px solid #000000;
    padding:          5px;
    font-family:      <?php echo $right_font_family; ?>;
    font-size:        <?php echo $font_size; ?>;
}

 /* disabled text */
 .disabled, .disabled a:link, disabled a:active, .disabled a:visited {
     font-family: <?php echo $right_font_family; ?>;
     font-size:   <?php echo $font_size; ?>;
     color:       #666666;
 }
 .disabled a:hover {
     text-decoration: none;
 }
 tr.disabled td, td.disabled {
     background-color: #cccccc;
 }

#TooltipContainer {
     position:   absolute;
     z-index:    99;
     width:      250px;
     height:     50px;
     overflow:   auto;
     visibility: hidden;
     background-color: #ffffcc;
     color:            #000000;
     border:           1px solid #000000;
     padding:          5px;
     font-family:      <?php echo $right_font_family; ?>;
     font-size:        <?php echo $font_size; ?>;
 }

 #topmenucontainer {

 border-top-width:0;
 border-bottom:1px solid #AAA;

 background-color:#FFF;
 }
#topmenu{
margin:0;
padding:0;
}
#topmenucontainer ul#topmenu li{
padding-right:3px;
border-bottom:0px;
}
#topmenu li .tab, #topmenu li .tabcaution, #topmenu li a.tabactive{
margin:0;
font: 9px Arial;
padding:3px 6px 1px;
background-color:#ffc5b6;
background-color:#ffffd7;
color:#000;
border:1px solid #AAA;
border-bottom:0px;
font-weight:normal;
text-transform:uppercase;

}
#topmenu li a.tabactive, #topmenu li a.tab:hover{
background-color:#FFEB8F;
color:#0000FF;
border:1px solid #AAA;
border-bottom:0px;
}

#topmenu li a.tabcaution{
background-color:#FFFF00;
color:#000;
}
html frameset#mainframeset{
border:5px solid red;
background-color:#FF0000;
}
#topmenu li a.tabcaution:hover{
background-color:#FFDD00;
color:#000;
}
table.calendar td.selected,table tr.marked th,
table tr.marked,table tr.odd:hover,
table tr.even:hover,
table tr.odd:hover th,
table tr.even:hover th,
table tr.hover th,
table tr.hover,.marked a,
.marked,.odd:hover,
.even:hover,
.hover  {
color:#000;
}
#calendar_data{
background-color:#CECECE;
}
#calendar_data table.calendar{
border-collapse:collapse;
border:0px solid #CECECE;
}
#calendar_data table.calendar td{
background-color:#EEE;
border:1px solid #CCC;
}
#calendar_data table.calendar th{
padding:5px 0 3px;
background-color:#FFF;
font-size:120%;
color:#333;
}
#clock_data {
margin:auto;
text-align:center;
}
#clock_data button{
font-size:120%;
color:#000;
font-weight:bold;
}
