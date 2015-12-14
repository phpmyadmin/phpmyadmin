<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage WinXP_blue
 */
?>

body{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #000000;
<?php
    if ($GLOBALS['cfg']['RightBgImage'] != '') {
        echo '    background-image: url(' . $GLOBALS['cfg']['RightBgImage'] . ');' . "\n";
    }
    ?>
    background-color: #ffffff;
    margin: 5px;
}
	
pre, tt, code{
    font-size:        11px;
}
a:link, a:visited, a:active{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    text-decoration:  none;
    color:            #0043cf;

}
a:hover{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    text-decoration:  underline;
    color:            #ff0000;
}
th{
    font-family:         Arial, Helvetica, Verdana, sans-serif;
    font-size:           12px;
    font-weight:         bold;
    color:               #ffffff;
    background-color:    #99bbf5;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
   <?php } ?>
    height:              18px;
}
th a:link, th a:active, th a:visited{
    color:            #ffffcc;
    text-decoration:  underline;
}

th a:hover{
    color:            #ffffcc;
    text-decoration:  none;
}
.tblcomment{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    font-weight:      normal;
    color:            #000099;
}
th.td{
    font-weight: normal;
    color: transparent;
    background-color: transparent;
    background-image: none;
   
}
td{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
}
form{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    padding:          0px 0px 0px 0px;
    margin:           0px 0px 0px 0px;
}
select, textarea, input {
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
}
select, textarea{
    color:            #000000;
    background-color: #FFFFFF;
}
input.textfield{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #000000;
    /*background-color: #FFFFFF;*/
}

h1{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        18px;
    font-weight:      bold;
}
h2{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        13px;
    font-weight:      bold;
}
h3{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    font-weight:      bold;
}
a.nav:link, a.nav:visited, a.nav:active{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #000000;
}
a.nav:hover{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #cc0000;
}
a.h1:link, a.h1:active, a.h1:visited{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #000000;
}
a.h1:hover{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #cc0000;
}
a.h2:link, a.h2:active, a.h2:visited{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #000000;
}
a.h2:hover{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #cc0000;
}
a.drop:link, a.drop:visited, a.drop:active{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #cc0000;
}
a.drop:hover{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    color:            #ffffff;
    background-color: #cc0000;
    text-decoration:  none;
}
dfn{
    font-style:       normal;
}
dfn:hover{
    font-style:       normal;
    cursor:           help;
}
.warning{
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    font-weight:      bold;
    color:            #cc0000;
}
td.topline{
    font-size:        1px;
}
td.tab{
    border-top:       1px solid #999;
    border-right:     1px solid #666;
    border-left:      1px solid #999;
    border-bottom:    none;
    border-radius:    2px;
    -moz-border-radius: 2px;
}
table.tabs      {
    border-top: none;
    border-right: none;
    border-left: none;
    border-bottom: 1px solid #666;
}

fieldset {
    border:     #c2c6d3 solid 1px;
    padding:    0.5em;
}
fieldset fieldset {
    margin:     0.8em;
}
legend {
    font-family: Arial, Helvetica, Verdana, sans-serif;
    size:        10px;
    color:       #c2c6d3;
    font-weight: bold;
    background-color: #ffffff;
    padding: 2px 2px 2px 2px;
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
    background:       #ff9900;
    color:            #000000;
    overflow:         hidden;
    clip:             inherit;
    z-index:          2;
    display:          inline;
    visibility:       inherit;
    cursor:           move;
    position:         absolute;
    font-size:        11px;
    border:           1px dashed #000000;
}

/* Warning showing div with right border and optional icon */

div.warning {
    border: 1px solid #cc0000;
/*
<?php if($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
*/
    background-image: url(themes/xp_blue/img/s_warn.png);
    background-repeat: no-repeat;
    background-position: 10px 10px;
    padding: 10px 10px 10px 36px;
    margin: 0px;
/*
<?php } ?>
*/
    width: 90%;
}

div.error {
    width: 100%;
    border: 1px solid #cc0000;
    background-color: #ffffcc;
    padding: 0px;
}

div.error  div.text {
    padding: 5px;
}

div.error div.head {
    background-color: #cc0000;
    font-weight: bold;
    color: #ffffff;
/*
<?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
*/
    background-image: url(themes/xp_blue/img/s_error.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 30px;
/*
<?php } ?>
*/
    margin: 0px;
}
.print{font-family:arial;font-size:8pt;}

/* MySQL Parser */
.syntax {font-family: sans-serif; font-size:        12px;}
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


/* tables */
.tblError {
    border:           1px solid #cc0000;
    background-color: #ffffcc;
}
.tblWarn, div.tblWarn {
    border: 1px solid #cc0000;
    background-color: #ffffff;
}
div.tblWarn {
    padding: 5px 5px 5px 5px;
    margin:  2px 0px 2px 0px;
    width:   100%;
}
.tblHeaders{
    font-weight:         bold;
    color:               #ffffff;
    background-color:    #0043cf;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
}
.tblHeaders a:link, .tblHeaders a:visited, .tblHeaders a:active, .tblFooters a:link, tblFooters a:visited, tblFooters a:active{
    color:            #ffffcc;
    text-decoration:  underline;
}
.tblFooters{
    font-weight:         normal;
    color:               #ffffff;
    background-color:    #0043CF;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_footer.png);
    background-repeat:   repeat-x;
    background-position: bottom;
    <?php } ?>
    height:              26px;
}
.tblHeaders a:hover, tblFooters a:hover{
    text-decoration: none;
    color:           #ffffcc;
}
.tblHeadError {
    font-weight:         bold;
    color:               #ffffff;
    background-color:    #cc0000;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
}
div.errorhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/xp_blue/img/s_error.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 20px;
    <?php } ?>
    margin: 0px;
}

.tblHeadWarn {
    background-color:    #ffcc00;
    font-weight:         bold;
    color:               #000000;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
}
div.warnhead {
    font-weight: bold;
    color: #ffffff;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/xp_blue/img/s_warn.png);
    background-repeat: no-repeat;
    background-position: 2px 50%;
    padding: 2px 2px 2px 20px;
    <?php } ?>
    margin: 0px;
}

/* forbidden, no privilegs */
.noPrivileges{
    color:            #cc0000;
    font-weight:      bold;
}

/* Heading for server links*/

.serverinfo {
    font-family:    Arial, Helvetica, Verdana, sans-serif;
    font-size:      12px;
    font-weight:    normal;
    padding: 0px 0px 10px 0px;
    margin: 0px;
    white-space:    nowrap;
    vertical-align: middle;
}
<?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
.serverinfo a:link, .serverinfo a:active, .serverinfo a:visited {
    font-family:    Arial, Helvetica, Verdana, sans-serif;
    font-size:      12px;
    font-weight:    bolder;
}
.serverinfo a img{
    vertical-align: middle;
    margin: 0px 1px 0px 1px;
}

.serverinfo div{
    background-image:    url(themes/xp_blue/img/item_ltr.png);
    background-repeat:   no-repeat;
    background-position: 50% 50%;
    width: 20px;
    height: 16px;
}
<?php } ?>
hr{
    color: #0043cf; background-color: #0043cf; border: 0; height: 1px;
}

/* navigation */
.nav{
    font-family:         Arial, Helvetica, Verdana, sans-serif;
    color:               #000000;
    background-color:    #0043cf;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
}

.navSpacer{
    width:            1px;
    height:           16px;
    background-color: #d6dff7;
}
.navNormal {
    font-family:         Arial, Helvetica, Verdana, sans-serif;
    font-size:           12px;
    font-weight:         bold;
    color:               #000000;
    background-color:    #0043cf;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
    padding: 2px 5px 2px 5px;
}
.navDrop {
    font-family:         Arial, Helvetica, Verdana, sans-serif;
    font-size:           12px;
    font-weight:         bold;
    color:               #000000;
    background-color:    #cc0000;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
    padding: 2px 5px 2px 5px;
}
.navActive {
    font-family:         Arial, Helvetica, Verdana, sans-serif;
    font-size:           12px;
    font-weight:         bold;
    color:               #ccccff;
    background-color:    #0043cf;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/xp_blue/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              26px;
    padding: 2px 5px 2px 5px;
}
.navNormal a:link,.navNormal a:active,.navNormal a:hover,.navNormal a:visited,.navDrop a:link,.navDrop a:active,.navDrop a:visited,.navDrop a:hover {
    color:               #ffffff;
}
.navActive a:link,.navActive a:active,.navActive a:visited,.navActive a:hover {
    color:               #ccccff;
}
img, input, select, button {
    vertical-align: middle;
}

/* disabled text */
.disabled, .disabled a:link, disabled a:active, .disabled a:visited {
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #333333;
}
.disabled a:hover {
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        12px;
    color:            #333333;
    text-decoration:  none;
}
td.disabled {
    background-color: #cccccc;
}

<?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
/* some styles for IDs: */
#buttonNo{
    color:            #CC0000;
    font-size:        12px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonYes{
    color:            #006600;
    font-size:        12px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonGo{
    color:            #006600;
    font-size:        12px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}

#listTable{
    width:            260px;
}

#textSqlquery{
    width:            450px;
}
#textSQLDUMP {
   width: 95%;
   height: 95%;
   font-family: "Courier New", Courier, mono;
   font-size:   11px;
}
<?php } ?>

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
    font-family:      Arial, Helvetica, Verdana, sans-serif;
    font-size:        11px;
}

