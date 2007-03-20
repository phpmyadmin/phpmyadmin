<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage OpenPHPNuke
 */
?>

body {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; background:#FFFFFF url(themes/openphpnuke/img/vertical_line.png) repeat-y;}

pre, tt					{font-size:<?php echo $font_size; ?>;}
table						{border-collapse:collapse;}
th							{font:bold <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; border:solid 1px #A5A5A5; height:22px; background:#FCFCFC url(themes/openphpnuke/img/titellinks.png) repeat-x;}
td							{font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif;}
form            {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; padding:0px; margin:0px;}
input           {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif;}
input.textfield {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; background:#FFFFFF;}
select          {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; background:#FFFFFF;}
textarea        {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; background:#FFFFFF;}
h1              {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif;}
h2              {font:bold <?php echo $font_bigger; ?> Arial,Verdana,Helvetica,sans-serif;}
h3              {font:bold <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif;}
a:link          {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; text-decoration:none; color:#0000FF; background:none;}
a:visited       {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; text-decoration:none; color:#0000FF; background:none;}
a:hover         {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; text-decoration:underline; color:#FF0000; background:none;}
a.nav:link      {font-family:Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.nav:visited   {font-family:Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.nav:hover     {font-family:Arial,Verdana,Helvetica,sans-serif; color:#FF0000}
a.h1:link       {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h1:active     {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h1:visited    {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h1:hover      {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h2:link       {font:bold <?php echo $font_biggest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h2:active     {font:bold <?php echo $font_bigger; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h2:visited    {font:bold <?php echo $font_bigger; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.h2:hover      {font:bold <?php echo $font_bigger; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000}
a.drop:link     {font-family:Arial,Verdana,Helvetica,sans-serif; color:#ff0000}
a.drop:visited  {font-family:Arial,Verdana,Helvetica,sans-serif; color:#ff0000}
a.drop:hover    {font-family:Arial,Verdana,Helvetica,sans-serif; color:#ffffff; background:#ff0000; text-decoration:none}
dfn             {font-style:normal}
dfn:hover       {font-style:normal; cursor:help}
.nav            {font-family:Arial,Verdana,Helvetica,sans-serif; color:#000000}
.warning        {font:bold <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#FF0000}
.tblcomment     {font:normal <?php echo $font_smallest; ?> Arial,Verdana,Helvetica,sans-serif; color:#000099;}
td.topline      {font-size:1px}
td.tab {border-top:1px solid #999; border-right:1px solid #666; border-left:1px solid #999; border-bottom:none;}
div.tabs {clear:both;}
table.tabs {border-top:none; border-right:none; border-left:none; border-bottom:1px solid #666;}

fieldset {border:#686868 solid 1px; padding:0.5em;}
fieldset fieldset {margin:0.8em;}

button.mult_submit {border:none; background:transparent;}
.pdflayout {overflow:hidden; clip:inherit; background:#FFFFFF; display:none; border:1px solid #000000; position:relative;}
.pdflayout_table {background:<?php echo $GLOBALS['cfg']['ThBgcolor']; ?>; color:#000000; overflow:hidden; clip:inherit; z-index:2; display:inline; visibility:inherit; cursor:move; position:absolute; font-size:<?php echo $font_smaller; ?>; border:1px dashed #000000;}
.print {font-size:8pt arial;}

/* MySQL Parser */
.syntax {font: <?php echo $font_smaller; ?>; sans-serif;}
.syntax_comment            { padding-left: 4pt; padding-right: 4pt;}
.syntax_digit              {}
.syntax_digit_hex          {}
.syntax_digit_integer      {}
.syntax_digit_float        {}
.syntax_punct              {}
.syntax_alpha              {}
.syntax_alpha_columnType   {text-transform: uppercase;}
.syntax_alpha_columnAttrib {text-transform: uppercase;}
.syntax_alpha_reservedWord {text-transform: uppercase; font-weight:bold;}
.syntax_alpha_functionName {text-transform: uppercase;}
.syntax_alpha_identifier   {}
.syntax_alpha_charset      {}
.syntax_alpha_variable     {}
.syntax_quote              {white-space: pre;}
.syntax_quote_backtick     {}

hr{ color:#666666; background:#666666; border:0; height:1px;}

/* new styles for navigation */
#topmenu table {border-collapse:collapse; border:solid 1px #A5A5A5; background:#FCFCFC url(themes/openphpnuke/img/titellinks.png) repeat-x; height:22px;}
.nav {font-family:Arial,Verdana,Helvetica,sans-serif; color:#000000; border-top:none; border-right:none; border-left:none; border-bottom: 1px solid #666;}
.navSpacer {width:5px; height:16px;}
.navNormal, .navDrop, .navActive {font:bold <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; border-top:none; border-right:1px solid #666; border-left:1px solid #999; border-bottom:none; padding: 2px 5px 2px 5px;}
.navNormal {color:#000000; background:none;}
.navActive {font:bold <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#000000; background:#FCFCFC url(themes/openphpnuke/img/titellinks1.png) repeat-x;}
.navDrop {color:#000000; background:#FFFFFF;}
.navNormal a:link, .navNormal a:active, .navNormal a:visited, .navActive a:link, .navActive a:active, .navActive a:visited {color:#0000FF;}

.navDrop a:link, .navDrop a:active, .navDrop a:visited {color:#FF0000;}
.navDrop a:hover {color:#FF0000; background:none; font-weight:bold; }
.navNormal a:hover, .navActive a:hover {color:#FF0000;}

/* Warning showing div with right border and optional icon */
div.errorhead {font-weight:bold; color:#ffffff; text-align:left; margin:0px;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background:url(themes/openphpnuke/img/s_error.png) no-repeat 5px 50%; padding:0px 0px 0px 25px;
    <?php } ?>
}

/* disabled text */
.disabled, .disabled a:link, disabled a:active, .disabled a:visited {font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; color:#666666;}
.disabled a:hover {text-decoration: none;}
tr.disabled td, td.disabled {background:#cccccc;}

#TooltipContainer {position:absolute; z-index:99; width:250px; height:50px; overflow:auto; visibility:hidden; background:#ffffcc; color:#006600; border:1px solid #000000; padding:5px; font:<?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif;}

/* tables */
.tblError {border:1px solid #FF0000; background:#FFFFCC;}
.tblWarn, div.tblWarn {border:1px solid #FF0000; background: #FFFFFF;}
div.tblWarn {padding:5px 5px 5px 5px; margin:0px 0px 5px 0px; width:100%;}
.tblHeaders {border:1px solid #A5A5A5; background:#FCFCFC url(themes/openphpnuke/img/titellinks.png) repeat-x; height:22px; font-weight:bold; color:#000000;}
.tblFooters {background: <?php echo $cfg['LeftBgColor']; ?>; font-weight:normal; color:#000000;}
.tblHeaders a:link, .tblHeaders a:active, .tblHeaders a:visited, .tblFooters a:link, .tblFooters a:active, .tblFooters a:visited {color:#0000FF;}
.tblHeaders a:hover, .tblFooters a:hover {color:#FF0000;}
.tblHeadError {background:#FF0000; font-weight:bold; color:#FFFFFF;}
.tblHeadWarn {background:#FFCC00; font-weight:bold; color:#000000;}
/* forbidden, no privilegs */
.noPrivileges {color:#FF0000; font-weight:bold;}
/* Heading */
.serverinfo {font:normal <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif; white-space:nowrap; vertical-align:middle; padding:0px 0px 10px 0px;}
img, input, select, button {vertical-align:middle;}
<?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
.serverinfo a:link, .serverinfo a:active, .serverinfo a:visited {font:bolder <?php echo $font_size; ?> Arial,Verdana,Helvetica,sans-serif;}
.serverinfo a img {vertical-align:middle; margin: 0px 1px 0px 2px;}
.serverinfo div {background:url(themes/openphpnuke/img/item_ltr.png) no-repeat 50% 50%; width:20px; height:16px;}
#textSQLDUMP {width:95%; height:95%; font:12px "Courier New", Courier, mono;}
<?php } // end of isDom ?>
