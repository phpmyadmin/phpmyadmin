<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage WinXP_basic
 */
?>

body {
    font-family: <?php echo $right_font_family; ?>;
    font-size: <?php echo $font_size; ?>;
    color: #000000;
    <?php
    if ($GLOBALS['cfg']['RightBgImage'] == '') {
        echo '    background-image: url(themes/original/img/vertical_line.png);' . "\n"
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
form            {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; padding: 0px; margin: 0px;}
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
.warning        {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_size; ?>; font-weight: bold; color: #FF0000}
.tblcomment     {font-family: <?php echo $right_font_family; ?>; font-size: <?php echo $font_smallest; ?>; font-weight: normal; color: #000099; }
td.topline      {font-size: 1px}


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

/* topmenu */
#topmenu {
    font-weight: bold;
}

/* default tab styles */
.tab, .tabcaution, .tabactive {
    margin-right: 0.1em;
    margin-left: 0.1em;
}

/* disabled tabs */
span.tab {
    color: #666666;
}

/* disabled drop/empty tabs */
span.tabcaution {
    color: #ff6666;
}

/* enabled drop/empty tabs */
a.tabcaution {
    color: #FF0000;
}
a.tabcaution:hover {
    color: #FFFFFF;
    background-color: #FF0000;
}

<?php if ( $GLOBALS['cfg']['LightTabs'] ) { ?>
/* active tab */
a.tabactive {
    border-bottom: 0.1em solid black;
    color: black;
}
<?php } else { ?>
#topmenu {
    margin-top: 0.5em;
    border-bottom: 0.1em solid black;
    padding: 0.1em 0.3em 0.1em 0.3em;
}

/* default tab styles */
.tab, .tabcaution {
    font-weight:      bold;
    border-top: 1px solid #999;
    border-right: 1px solid #666;
    border-left: 1px solid #999;
    border-bottom: none;
    border-radius: 2px;
    -moz-border-radius: 2px;
    background-color: #FEFEFE;
}
.tabactive {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           10px;
    font-weight:         bold;
    height:              16px;
    padding: 2px 5px 2px 5px;
    color:            #000000;
    background-color: #EAE6D0;
}

/* enabled hover/active tabs */
a.tab:hover, .tabactive {
    font-weight:      bold;
    margin-right: 0;
    margin-left: 0;
    padding: 0.3em 0.3em 0.1em 0.3em;
    height:              16px;
    padding: 2px 5px 2px 5px;
    color: #FF0000;
    background-color: #EAE6D0;
}

a.tabcaution:hover {
    background-color: #ff0000;
    background-image: none;
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
    cursor: url(themes/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */
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

#serverinfo {
    font-weight: bold;
    margin-bottom: 0.5em;
}

#serverinfo .item {
    white-space: nowrap;
}

#span_table_comment {
    font-weight: normal;
    font-style: italic;
    white-space: nowrap;
}

#serverinfo img {
    margin: 0 0.1em 0 0.1em;
}

img, input, select, button {
    vertical-align: middle;
}

#textSQLDUMP {
    width: 95%;
    height: 95%;
    font-family: "Courier New", Courier, mono;
    font-size:   12px;
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

