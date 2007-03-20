<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * main css file from theme
 *
 * @version $Id$
 * @package phpMyAdmin-theme
 * @subpackage Graphivore
 */
?>

body{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #000000;
<?php
    if ($GLOBALS['cfg']['RightBgImage'] != '') {
        echo '    background-image: url(' . $GLOBALS['cfg']['RightBgImage'] . ');' . "\n";
    }
    ?>
    background-color: #FFE7B3;
    margin: 5px;
    scrollbar-face-color: #AB9C72;
    scrollbar-shadow-color: #FFE7B3;
    scrollbar-highlight-color: #FFE7B3;
    scrollbar-3dlight-color: #FFE7B3;
    scrollbar-darkshadow-color: #FFE7B3;
    scrollbar-track-color: #FFE7B3;
    scrollbar-arrow-color: #FFE7B3;
}

pre, tt, code{
    font-size:        11px;
}
a:link, a:visited, a:active{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    text-decoration:  none;
    color:            #AB9C72;

}
a:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    text-decoration:  none;
    color:            #b36448;
}
th{
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           10px;
    font-weight:         bold;
    color:               #FFF3DB;
    background-color:    #ff9900;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/graphivore/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
   <?php } ?>
    height:              18px;
}
th a:link, th a:active, th a:visited{
    color:            #FFFAEF;
    text-decoration:  none;
}

th a:hover{
    color:            #B36448;
    text-decoration:  none;
}
.tblcomment{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    font-weight:      normal;
    color:            #64583c;
}
th.td{
    font-weight: normal;
    color: transparent;
    background-color: transparent;
    background-image: none;
   
}
td{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}
form{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    padding:          0px 0px 0px 0px;
    margin:           0px 0px 0px 0px;
}
select, textarea, input {
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}
select, textarea{
    color:            #8A7A52;
    background-color: #FFFAEF;
}
input.textfield{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
    color:            #8A7A52;
    background-color: #FFFAEF;
    border-color:   #AB9C72;
    border-width: 1px;
    border-style: solid;
}

h1{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
}
h2{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
}
h3{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        12px;
    font-weight:      bold;
}
a.h1:link, a.h1:active, a.h1:visited{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #000000;
}
a.h1:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        18px;
    font-weight:      bold;
    color:            #cc0000;
}
a.h2:link, a.h2:active, a.h2:visited{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #000000;
}
a.h2:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        13px;
    font-weight:      bold;
    color:            #cc0000;
}
a.drop:link, a.drop:visited, a.drop:active{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #cc0000;
}
a.drop:hover{
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    color:            #fffaef;
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
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
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
    border:     #286860 solid 1px;
    padding:    0.5em;
}
fieldset fieldset {
    margin:     0.8em;
}
legend {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    size:        10px;
    color:       #286860;
    font-weight: bold;
    background-color: #fffaef;
    padding: 2px 2px 2px 2px;
}
button.mult_submit {
    border: none;
    background-color: transparent;
}

.pdflayout {
    overflow:         hidden;
    clip:             inherit;
    background-color: #fffaef;
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
    background-image: url(themes/graphivore/img/s_warn.png);
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
    background-color: #fff3db;
    padding: 0px;
}

div.error  div.text {
    padding: 5px;
}

div.error div.head {
    background-color: #cc0000;
    font-weight: bold;
    color: #fffaef;
/*
<?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
*/
    background-image: url(themes/graphivore/img/s_error.png);
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
.syntax {font-family: sans-serif; font-size: 10px;}
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

/* some new styles added 20047-05-05 by Michael Keck (mkkeck) */

/* tables */
.tblError {
    border:           1px solid #cc0000;
    background-color: #fff3db;
}
.tblWarn, div.tblWarn {
    border: 1px solid #cc0000;
    background-color: #fffaef;
}
div.tblWarn {
    padding: 5px 5px 5px 5px;
    margin:  2px 0px 2px 0px;
    width:   100%;
}
.tblHeaders{
    font-weight:         bold;
    color:               #fff3db;
    background-color:    #286860;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/graphivore/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              18px;
}
.tblHeaders a:link, .tblHeaders a:visited, .tblHeaders a:active, .tblFooters a:link, tblFooters a:visited, tblFooters a:active{
    color:            #fff3db;
    text-decoration:  underline;
}
.tblFooters{
    font-weight:         normal;
    color:               #fff3db;
    background-color:    #286860;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/graphivore/img/tbl_header.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
}
.tblHeaders a:hover, tblFooters a:hover{
    text-decoration: none;
    color:           #fff3db;
}
.tblHeadError {
    font-weight:         bold;
    color:               #fff3db;
    background-color:    #cc0000;
    <?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image:    url(themes/graphivore/img/tbl_error.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              18px;
}
div.errorhead {
    font-weight: bold;
    color: #fff3db;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/graphivore/img/s_error.png);
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
    background-image:    url(themes/graphivore/img/tbl_th.png);
    background-repeat:   repeat-x;
    background-position: top;
    <?php } ?>
    height:              18px;
}
div.warnhead {
    font-weight: bold;
    color: #fff3db;
    text-align: left;
    <?php if ($cfg['ErrorIconic'] && isset($js_isDOM) && $js_isDOM != '0') { ?>
    background-image: url(themes/graphivore/img/s_warn.png);
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


hr{
    color: #AB9C72; background-color: #FFE7B3; border: 0; height: 1px;
}


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
    color: #F3E3D6;
}
a.tabcaution:hover {
    color: #fff3db;
    background-color: #FF0000;
}
a.tab {
    color: #fff3db;
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
    background-image: url(themes/graphivore/img/tbl_header.png); background-repeat: repeat-x;
    background-position: top;
    background-color:    #676767;
    border: 0.1em solid silver;
    border-bottom: 0.1em solid black;
    border-radius-topleft: 0.5em;
    border-radius-topright: 0.5em;
    -moz-border-radius-topleft: 0.5em;
    -moz-border-radius-topright: 0.5em;
    height:              16px;
    padding: 2px 5px 2px 5px;
}
.tabactive {
    font-family:         Verdana, Arial, Helvetica, sans-serif;
    font-size:           10px;
    font-weight:         bold;
    color:               #000000;
    background-image: url(themes/graphivore/img/tbl_th2.png); background-repeat: repeat-x;
    background-position: top;
    background-color:    #ffaa44;
    height:              16px;
    padding: 2px 5px 2px 5px;
}

/* enabled hover/active tabs */
a.tab:hover, .tabactive {
    font-weight:      bold;
    margin-right: 0;
    margin-left: 0;
    padding: 0.3em 0.3em 0.1em 0.3em;
    background-image: url(themes/graphivore/img/tbl_th2.png); background-repeat: repeat-x;
    background-position: top;
    background-color:    #ffaa44;
    height:              16px;
    padding: 2px 5px 2px 5px;
    color:            #fff3db;
}

a.tabcaution:hover {
    background-color: #ff0000;
    background-image: url(themes/graphivore/img/tbl_error2.png); background-repeat: repeat-x;
}

/* disabled drop/empty tabs */
span.tab, span.tabcaution {
    cursor: url(themes/original/img/error.ico), default;
}
<?php } ?>
/* end topmenu */


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

<?php if (isset($js_isDOM) && $js_isDOM != '0') { ?>
/* some styles for IDs: */
#buttonNo{
    color:            #cc0000;
    font-size:        10px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonYes{
    color:            #AB9C72;
    font-size:        10px;
    font-weight:      bold;
    padding:          0px 10px 0px 10px;
}
#buttonGo{
    color:            #AB9C72;
    font-size:        10px;
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
    background-color: #fff3db;
    color:            #006600;
    border:           1px solid #006600;
    padding:          5px;
    font-family:      Verdana, Arial, Helvetica, sans-serif;
    font-size:        10px;
}
